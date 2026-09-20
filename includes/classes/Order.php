<?php
/**
 * Order — create, retrieve, cancel, and status-change orders.
 * FIFO stock allocation happens inside the same DB transaction as order creation.
 */
class Order
{
    // ── Generate next order number e.g. DC-2026-000123 ──────────────
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = Database::fetchOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) AS n
             FROM orders WHERE order_number LIKE ?",
            ["DC-{$year}-%"]
        );
        $next = (int)($last['n'] ?? 0) + 1;
        return sprintf('DC-%s-%06d', $year, $next);
    }

    // ── Place a new order (wraps entire flow in one transaction) ─────
    public static function create(array $data): array
    {
        /*
         * $data keys:
         *   user_id, shipping_address_id, billing_address_id,
         *   payment_method ('cod'|'online'),
         *   cart_id, coupon_id (optional),
         *   customer_notes (optional)
         */
        $userId     = (int)$data['user_id'];
        $cartId     = (int)$data['cart_id'];
        $addrShip   = (int)$data['shipping_address_id'];
        $addrBill   = (int)$data['billing_address_id'];
        $method     = $data['payment_method'] ?? 'cod';
        $couponId   = !empty($data['coupon_id']) ? (int)$data['coupon_id'] : null;
        $notes      = $data['customer_notes'] ?? null;

        // 1. Load cart items
        $items = Cart::getItems($cartId);
        if (empty($items)) {
            return ['ok' => false, 'error' => 'Cart is empty.'];
        }

        // 2. Calculate totals + GST
        $gstData = GST::calculate($items, self::getBuyerState($addrShip));
        $summary = Cart::getSummary($items);

        // 3. Shipping charge
        $addr     = Database::fetchOne('SELECT pincode FROM addresses WHERE id = ?', [$addrShip]);
        $shipCalc = Shipping::calculate($addr['pincode'] ?? '', $summary['total_weight'], $method === 'cod');
        $shipCharge  = $shipCalc['ok'] ? (float)$shipCalc['rate']     : 0.0;
        $codCharge   = $shipCalc['ok'] ? (float)($shipCalc['cod_extra'] ?? 0) : 0.0;

        // 4. Coupon discount
        $discountAmt = 0.0;
        if ($couponId) {
            $couponResult = self::applyCoupon($couponId, $userId, (float)$summary['subtotal']);
            if ($couponResult['ok']) {
                $discountAmt = $couponResult['discount'];
            } else {
                $couponId = null;
            }
        }

        $total = max(0, $summary['subtotal'] - $discountAmt + $shipCharge + $codCharge);

        $orderNumber = self::generateNumber();

        try {
            Database::beginTransaction();

            // 5. Insert order
            Database::query(
                "INSERT INTO orders
                 (order_number, user_id, status, payment_method, payment_status,
                  subtotal, discount_amount, coupon_id, shipping_charge, cod_charge,
                  cgst_amount, sgst_amount, igst_amount, total_amount, total_weight_grams,
                  shipping_address_id, billing_address_id, customer_notes, placed_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                [
                    $orderNumber, $userId, 'placed', $method,
                    $method === 'cod' ? 'pending' : 'pending',
                    $summary['subtotal'], $discountAmt, $couponId,
                    $shipCharge, $codCharge,
                    $gstData['cgst'], $gstData['sgst'], $gstData['igst'],
                    $total, $summary['total_weight'],
                    $addrShip, $addrBill, $notes,
                ]
            );
            $orderId = Database::lastInsertId();

            // 6. Insert order_items + FIFO batch allocation
            foreach ($items as $item) {
                $variantId   = (int)$item['variant_id'];
                $qty         = (int)$item['quantity'];
                $unitPrice   = (float)$item['selling_price'];
                $gstRate     = (float)$item['gst_rate_percent'];
                $gstAmt      = round($unitPrice * $qty * $gstRate / 100, 2);
                $lineTotal   = round($unitPrice * $qty, 2);
                $batchId     = self::allocateFIFO($variantId, $qty);

                Database::query(
                    "INSERT INTO order_items
                     (order_id, variant_id, batch_id, product_name_snapshot, variant_label_snapshot,
                      quantity, unit_price, gst_rate_percent, gst_amount, line_total, box_group_id)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $orderId, $variantId, $batchId,
                        $item['product_name'],
                        self::variantLabel($item),
                        $qty, $unitPrice, $gstRate, $gstAmt, $lineTotal,
                        $item['box_group_id'] ?? null,
                    ]
                );

                // Decrement batch stock
                if ($batchId) {
                    Database::query(
                        'UPDATE inventory_batches SET quantity_remaining = quantity_remaining - ? WHERE id = ?',
                        [$qty, $batchId]
                    );
                }

                // Stock movement record
                Database::query(
                    "INSERT INTO stock_movements
                     (variant_id, batch_id, movement_type, quantity, reference_type, reference_id)
                     VALUES (?,?,'sale_out',?,?,?)",
                    [$variantId, $batchId, $qty, 'order', $orderId]
                );
            }

            // 7. Status history entry
            Database::query(
                'INSERT INTO order_status_history (order_id, status, remarks) VALUES (?,?,?)',
                [$orderId, 'placed', 'Order placed successfully.']
            );

            // 8. Mark coupon used
            if ($couponId) {
                Database::query(
                    'INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?,?,?)',
                    [$couponId, $userId, $orderId]
                );
            }

            // 9. Clear cart
            Database::query('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);

            Database::commit();

            return ['ok' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];

        } catch (\Throwable $e) {
            Database::rollback();
            error_log('Order::create error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Order could not be placed. Please try again.'];
        }
    }

    // ── FIFO: pick the batch with earliest expiry that has stock ─────
    private static function allocateFIFO(int $variantId, int $qty): ?int
    {
        $batch = Database::fetchOne(
            'SELECT id FROM inventory_batches
             WHERE variant_id = ? AND quantity_remaining >= ?
             ORDER BY expiry_date ASC LIMIT 1',
            [$variantId, $qty]
        );
        return $batch ? (int)$batch['id'] : null;
    }

    private static function variantLabel(array $item): string
    {
        $g = (int)$item['weight_grams'];
        return $g >= 1000 ? ($g / 1000) . ' kg' : $g . ' g';
    }

    private static function getBuyerState(int $addressId): string
    {
        $row = Database::fetchOne('SELECT state FROM addresses WHERE id = ?', [$addressId]);
        return $row['state'] ?? '';
    }

    // ── Coupon validation + discount calc ────────────────────────────
    public static function applyCoupon(int $couponId, int $userId, float $subtotal): array
    {
        $c = Database::fetchOne(
            "SELECT * FROM coupons
             WHERE id = ? AND is_active = 1
               AND valid_from <= NOW() AND valid_to >= NOW()",
            [$couponId]
        );
        if (!$c) return ['ok' => false, 'error' => 'Invalid or expired coupon.'];
        if ($subtotal < (float)$c['min_order_value']) {
            return ['ok' => false, 'error' => 'Minimum order value not met.'];
        }

        // Usage limits
        if ($c['usage_limit_total']) {
            $used = Database::fetchOne('SELECT COUNT(*) AS n FROM coupon_usage WHERE coupon_id = ?', [$couponId]);
            if ((int)$used['n'] >= (int)$c['usage_limit_total'])
                return ['ok' => false, 'error' => 'Coupon usage limit reached.'];
        }
        if ($c['usage_limit_per_user']) {
            $used = Database::fetchOne(
                'SELECT COUNT(*) AS n FROM coupon_usage WHERE coupon_id = ? AND user_id = ?',
                [$couponId, $userId]
            );
            if ((int)$used['n'] >= (int)$c['usage_limit_per_user'])
                return ['ok' => false, 'error' => 'You have already used this coupon.'];
        }

        $discount = $c['discount_type'] === 'percent'
            ? round($subtotal * $c['discount_value'] / 100, 2)
            : (float)$c['discount_value'];

        if ($c['max_discount_amount']) {
            $discount = min($discount, (float)$c['max_discount_amount']);
        }

        return ['ok' => true, 'discount' => $discount, 'coupon' => $c];
    }

    // ── Validate coupon code by code string ──────────────────────────
    public static function validateCouponCode(string $code, int $userId, float $subtotal): array
    {
        $c = Database::fetchOne(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
            [strtoupper(trim($code))]
        );
        if (!$c) return ['ok' => false, 'error' => 'Coupon code not found.'];
        return self::applyCoupon((int)$c['id'], $userId, $subtotal);
    }

    // ── Get order by ID (with items + status history) ─────────────────
    public static function getById(int $orderId, ?int $userId = null): ?array
    {
        $sql    = 'SELECT o.*, COALESCE(u.full_name, o.ship_name) AS full_name, COALESCE(u.email, o.guest_email) AS email, COALESCE(u.phone, o.ship_phone) AS phone FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?';
        $params = [$orderId];
        if ($userId) { $sql .= ' AND o.user_id = ?'; $params[] = $userId; }
        $order = Database::fetchOne($sql, $params);
        if (!$order) return null;

        $order['items'] = Database::fetchAll(
            'SELECT oi.*, pv.sku FROM order_items oi
             LEFT JOIN product_variants pv ON pv.id = oi.variant_id
             WHERE oi.order_id = ?',
            [$orderId]
        );
        $order['status_history'] = Database::fetchAll(
            'SELECT osh.*, u.full_name AS changed_by_name
             FROM order_status_history osh
             LEFT JOIN users u ON u.id = osh.changed_by
             WHERE osh.order_id = ? ORDER BY osh.changed_at ASC',
            [$orderId]
        );
        $order['shipping_address'] = Database::fetchOne(
            'SELECT * FROM addresses WHERE id = ?', [$order['shipping_address_id'] ?? 0]
        );
        if (!$order['shipping_address'] && !empty($order['ship_name'])) {
            $order['shipping_address'] = [
                'full_name'     => $order['ship_name'],
                'phone'         => $order['ship_phone'] ?? '',
                'address_line1' => $order['ship_line1'] ?? '',
                'address_line2' => $order['ship_line2'] ?? '',
                'city'          => $order['ship_city'] ?? '',
                'state'         => $order['ship_state'] ?? '',
                'pincode'       => $order['ship_pincode'] ?? '',
            ];
        }
        $order['payment'] = Database::fetchOne(
            'SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]
        );
        $order['shipping_label'] = Database::fetchOne(
            'SELECT * FROM shipping_labels WHERE order_id = ?', [$orderId]
        );
        $order['invoice'] = Database::fetchOne(
            'SELECT * FROM invoices WHERE order_id = ?', [$orderId]
        );
        return $order;
    }

    // ── Get order by order_number ─────────────────────────────────────
    public static function getByNumber(string $number): ?array
    {
        $row = Database::fetchOne('SELECT id FROM orders WHERE order_number = ?', [$number]);
        return $row ? self::getById((int)$row['id']) : null;
    }

    // ── Order list (admin) ────────────────────────────────────────────
    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) { $where[] = 'o.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['payment_status'])) { $where[] = 'o.payment_status = ?'; $params[] = $filters['payment_status']; }
        if (!empty($filters['search'])) {
            $where[] = '(o.order_number LIKE ? OR u.full_name LIKE ? OR o.ship_name LIKE ? OR u.email LIKE ? OR o.guest_email LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($filters['date_from'])) { $where[] = 'o.placed_at >= ?'; $params[] = $filters['date_from'] . ' 00:00:00'; }
        if (!empty($filters['date_to'])) { $where[]   = 'o.placed_at <= ?'; $params[] = $filters['date_to']   . ' 23:59:59'; }

        $where = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $total = Database::fetchOne(
            "SELECT COUNT(*) AS n FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $where",
            $params
        )['n'];

        $rows = Database::fetchAll(
            "SELECT o.*, 
                    COALESCE(u.full_name, o.ship_name, 'Guest Customer') AS full_name,
                    COALESCE(u.email, o.guest_email, '') AS email,
                    COALESCE(u.phone, o.ship_phone, '') AS phone
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE $where ORDER BY o.placed_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['total' => (int)$total, 'pages' => (int)ceil($total / $perPage), 'data' => $rows];
    }

    // ── Buyer order list ──────────────────────────────────────────────
    public static function buyerList(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = Database::fetchOne(
            'SELECT COUNT(*) AS n FROM orders WHERE user_id = ?', [$userId]
        )['n'];
        $rows = Database::fetchAll(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT ? OFFSET ?',
            [$userId, $perPage, $offset]
        );
        return ['total' => (int)$total, 'pages' => (int)ceil($total / $perPage), 'data' => $rows];
    }

    // ── Update order status ───────────────────────────────────────────
    public static function updateStatus(int $orderId, string $status, ?int $changedBy = null, string $remarks = ''): bool
    {
        Database::query('UPDATE orders SET status = ? WHERE id = ?', [$status, $orderId]);
        Database::query(
            'INSERT INTO order_status_history (order_id, status, remarks, changed_by) VALUES (?,?,?,?)',
            [$orderId, $status, $remarks, $changedBy]
        );
        return true;
    }

    // ── Cancel order ──────────────────────────────────────────────────
    public static function cancel(int $orderId, ?int $userId = null): array
    {
        $order = self::getById($orderId, $userId);
        if (!$order) return ['ok' => false, 'error' => 'Order not found.'];
        if (!in_array($order['status'], ['placed', 'confirmed'])) {
            return ['ok' => false, 'error' => 'This order cannot be cancelled at this stage.'];
        }

        // Restore stock
        foreach ($order['items'] as $item) {
            if ($item['batch_id']) {
                Database::query(
                    'UPDATE inventory_batches SET quantity_remaining = quantity_remaining + ? WHERE id = ?',
                    [$item['quantity'], $item['batch_id']]
                );
                Database::query(
                    "INSERT INTO stock_movements
                     (variant_id, batch_id, movement_type, quantity, reference_type, reference_id)
                     VALUES (?,?,'return_in',?,?,?)",
                    [$item['variant_id'], $item['batch_id'], $item['quantity'], 'order_cancel', $orderId]
                );
            }
        }

        self::updateStatus($orderId, 'cancelled', $userId, 'Cancelled by customer.');
        return ['ok' => true];
    }

    // ── Dashboard stats (admin) ───────────────────────────────────────
    public static function dashboardStats(): array
    {
        $today = date('Y-m-d');
        return [
            'total_orders'    => (int)(Database::fetchOne("SELECT COUNT(*) AS n FROM orders")['n'] ?? 0),
            'pending_orders'  => (int)(Database::fetchOne("SELECT COUNT(*) AS n FROM orders WHERE status IN ('placed','confirmed')")['n'] ?? 0),
            'today_orders'    => (int)(Database::fetchOne("SELECT COUNT(*) AS n FROM orders WHERE DATE(placed_at) = ?", [$today])['n'] ?? 0),
            'today_revenue'   => (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount),0) AS n FROM orders WHERE DATE(placed_at) = ? AND payment_status='paid'", [$today])['n'] ?? 0),
            'monthly_revenue' => (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount),0) AS n FROM orders WHERE payment_status='paid' AND placed_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['n'] ?? 0),
        ];
    }
}
