<?php
/**
 * Cart model — manages guest carts (session token) and user carts.
 * Merges guest cart into user cart on login.
 */
class Cart
{
    // ── Get or create cart ────────────────────────────────────────────────

    public static function getOrCreate(): array
    {
        if (Auth::check()) {
            // Logged-in user
            $cart = Database::fetchOne('SELECT * FROM carts WHERE user_id = ?', [Auth::id()]);
            if (!$cart) {
                Database::query('INSERT INTO carts (user_id) VALUES (?)', [Auth::id()]);
                $cart = Database::fetchOne('SELECT * FROM carts WHERE user_id = ?', [Auth::id()]);
            }
        } else {
            // Guest — token stored in session
            if (empty($_SESSION['guest_cart_token'])) {
                $_SESSION['guest_cart_token'] = bin2hex(random_bytes(32));
            }
            $token = $_SESSION['guest_cart_token'];
            $cart  = Database::fetchOne('SELECT * FROM carts WHERE session_token = ?', [$token]);
            if (!$cart) {
                Database::query('INSERT INTO carts (session_token) VALUES (?)', [$token]);
                $cart = Database::fetchOne('SELECT * FROM carts WHERE session_token = ?', [$token]);
            }
        }
        return $cart;
    }

    /**
     * Merge guest cart into user cart after login.
     * Called by Auth after a successful login.
     */
    public static function mergeGuestCart(int $userId): void
    {
        $token = $_SESSION['guest_cart_token'] ?? null;
        if (!$token) return;

        $guestCart = Database::fetchOne('SELECT * FROM carts WHERE session_token = ?', [$token]);
        if (!$guestCart) return;

        // Ensure user cart exists
        $userCart = Database::fetchOne('SELECT * FROM carts WHERE user_id = ?', [$userId]);
        if (!$userCart) {
            Database::query('INSERT INTO carts (user_id) VALUES (?)', [$userId]);
            $userCart = Database::fetchOne('SELECT * FROM carts WHERE user_id = ?', [$userId]);
        }

        // Move guest items into user cart (increment if same variant already there)
        $guestItems = Database::fetchAll(
            'SELECT * FROM cart_items WHERE cart_id = ?', [$guestCart['id']]
        );
        foreach ($guestItems as $item) {
            $existing = Database::fetchOne(
                'SELECT * FROM cart_items WHERE cart_id = ? AND variant_id = ? AND box_group_id IS NULL',
                [$userCart['id'], $item['variant_id']]
            );
            if ($existing) {
                Database::query(
                    'UPDATE cart_items SET quantity = quantity + ? WHERE id = ?',
                    [$item['quantity'], $existing['id']]
                );
            } else {
                Database::query(
                    'INSERT INTO cart_items (cart_id, variant_id, quantity) VALUES (?, ?, ?)',
                    [$userCart['id'], $item['variant_id'], $item['quantity']]
                );
            }
        }

        // Delete guest cart
        Database::query('DELETE FROM carts WHERE id = ?', [$guestCart['id']]);
        unset($_SESSION['guest_cart_token']);
    }

    // ── Read ──────────────────────────────────────────────────────────────

    /** Full cart contents with product/variant details and stock. */
    public static function getItems(int $cartId): array
    {
        return Database::fetchAll(
            "SELECT ci.id, ci.variant_id, ci.quantity, ci.box_group_id,
                    v.sku, v.weight_grams, v.mrp, v.selling_price,
                    p.name AS product_name, p.slug AS product_slug,
                    COALESCE(p.gst_rate_percent, 5.00) AS gst_rate_percent,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS product_image,
                    COALESCE(SUM(b.quantity_remaining), 0) AS stock_available
             FROM cart_items ci
             JOIN product_variants v ON v.id = ci.variant_id
             JOIN products p ON p.id = v.product_id
             LEFT JOIN inventory_batches b ON b.variant_id = v.id
             WHERE ci.cart_id = ?
             GROUP BY ci.id
             ORDER BY ci.created_at ASC",
            [$cartId]
        );
    }

    public static function getSummary(array $items): array
    {
        $subtotal    = 0.0;
        $totalWeight = 0;
        foreach ($items as $item) {
            $subtotal    += (float)$item['selling_price'] * (int)$item['quantity'];
            $totalWeight += (int)$item['weight_grams'] * (int)$item['quantity'];
        }
        return [
            'subtotal'     => round($subtotal, 2),
            'total_weight' => $totalWeight,
            'item_count'   => array_sum(array_column($items, 'quantity')),
        ];
    }

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Add or increment item.
     * Returns ['ok', 'new_qty'] or ['ok' => false, 'error' => '...']
     */
    public static function addItem(int $variantId, int $quantity = 1, ?string $boxGroupId = null): array
    {
        // Validate variant exists and is active
        $variant = Database::fetchOne(
            'SELECT v.*, p.is_active AS p_active FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.id = ? AND v.is_active = 1 AND p.is_active = 1',
            [$variantId]
        );
        if (!$variant) return ['ok' => false, 'error' => 'This product variant is not available.'];

        // Check stock
        $stock = (int)(Database::fetchOne(
            'SELECT COALESCE(SUM(quantity_remaining),0) as s FROM inventory_batches WHERE variant_id = ?',
            [$variantId]
        )['s'] ?? 0);

        $cart = self::getOrCreate();

        $existing = Database::fetchOne(
            'SELECT id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? AND ' .
            ($boxGroupId ? 'box_group_id = ?' : 'box_group_id IS NULL'),
            $boxGroupId ? [$cart['id'], $variantId, $boxGroupId] : [$cart['id'], $variantId]
        );

        $newQty = $quantity;
        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
        }

        if ($newQty > $stock) {
            return ['ok' => false, 'error' => "Only {$stock} units available."];
        }

        if ($existing) {
            Database::query(
                'UPDATE cart_items SET quantity = ? WHERE id = ?',
                [$newQty, $existing['id']]
            );
        } else {
            Database::query(
                'INSERT INTO cart_items (cart_id, variant_id, quantity, box_group_id) VALUES (?, ?, ?, ?)',
                [$cart['id'], $variantId, $quantity, $boxGroupId]
            );
        }

        return ['ok' => true, 'new_qty' => $newQty];
    }

    public static function updateItem(int $itemId, int $quantity): array
    {
        if ($quantity < 1) return self::removeItem($itemId);

        // Stock check
        $item = Database::fetchOne('SELECT * FROM cart_items WHERE id = ?', [$itemId]);
        if (!$item) return ['ok' => false, 'error' => 'Item not found.'];

        $stock = (int)(Database::fetchOne(
            'SELECT COALESCE(SUM(quantity_remaining),0) as s FROM inventory_batches WHERE variant_id = ?',
            [$item['variant_id']]
        )['s'] ?? 0);

        if ($quantity > $stock) {
            return ['ok' => false, 'error' => "Only {$stock} units available."];
        }

        Database::query('UPDATE cart_items SET quantity = ? WHERE id = ?', [$quantity, $itemId]);
        return ['ok' => true, 'new_qty' => $quantity];
    }

    public static function removeItem(int $itemId): array
    {
        Database::query('DELETE FROM cart_items WHERE id = ?', [$itemId]);
        return ['ok' => true];
    }

    public static function clear(int $cartId): void
    {
        Database::query('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);
    }

    /** Total item count badge (used in header). */
    public static function itemCount(?int $cartId): int
    {
        if (!$cartId) return 0;
        $row = Database::fetchOne(
            'SELECT COALESCE(SUM(quantity),0) as cnt FROM cart_items WHERE cart_id = ?', [$cartId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Restore items from an order back into the customer's active bag (for failed payments/cancellations).
     * Re-inserts or merges the items with their weights and quantities.
     */
    public static function restoreItemsFromOrder(int $orderId): int
    {
        $items = Database::fetchAll(
            'SELECT variant_id, quantity, box_group_id FROM order_items WHERE order_id = ?',
            [$orderId]
        );
        if (empty($items)) {
            return 0;
        }

        $cart   = self::getOrCreate();
        $cartId = (int)$cart['id'];
        $restoredUnits = 0;

        foreach ($items as $item) {
            $variantId  = (int)$item['variant_id'];
            $qty        = (int)$item['quantity'];
            $boxGroupId = $item['box_group_id'] ?: null;

            if ($variantId <= 0 || $qty <= 0) {
                continue;
            }

            // Check if this variant is already present in the active cart
            $existing = Database::fetchOne(
                'SELECT id, quantity FROM cart_items WHERE cart_id = ? AND variant_id = ? AND ' .
                ($boxGroupId ? 'box_group_id = ?' : 'box_group_id IS NULL'),
                $boxGroupId ? [$cartId, $variantId, $boxGroupId] : [$cartId, $variantId]
            );

            if ($existing) {
                // Ensure the quantity is at least the order's quantity
                $newQty = max((int)$existing['quantity'], $qty);
                Database::query(
                    'UPDATE cart_items SET quantity = ? WHERE id = ?',
                    [$newQty, $existing['id']]
                );
            } else {
                Database::query(
                    'INSERT INTO cart_items (cart_id, variant_id, quantity, box_group_id) VALUES (?, ?, ?, ?)',
                    [$cartId, $variantId, $qty, $boxGroupId]
                );
            }
            $restoredUnits += $qty;
        }

        return $restoredUnits;
    }
}
