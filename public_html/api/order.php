<?php
/**
 * order.php — Place order API endpoint for Dabhi_final
 * Bridges the modern storefront checkout with Dabhi's order, tax, notifications & FIFO inventory backend.
 * Features automated Payment Gateway Bypass simulation and real-time SMTP order email notifications.
 */
ob_start();

$bootstrap = null;
foreach ([
    __DIR__ . '/../../includes/bootstrap.php',
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/includes/bootstrap.php',
] as $b) {
    if (file_exists($b)) {
        $bootstrap = $b;
        break;
    }
}
if ($bootstrap) {
    require_once $bootstrap;
}

header('Content-Type: application/json; charset=UTF-8');

/**
 * Clean output buffer to ensure stray warnings or notices never corrupt JSON response.
 */
function sendJsonResponse(array $data, int $httpCode = 200): void
{
    if (ob_get_length()) {
        $buffered = ob_get_clean();
        if (!empty(trim($buffered))) {
            error_log('Notice/Warning in api/order.php: ' . $buffered);
        }
    }
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
    sendJsonResponse(['ok' => false, 'error' => 'Invalid request.']);
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if ($action !== 'place_order') {
    sendJsonResponse(['ok' => false, 'error' => 'Unknown action.']);
}

// ── 1. Validate cart ─────────────────────────────────────
$cart   = Cart::getOrCreate();
$cartId = (int)($cart['id'] ?? 0);
$items  = Cart::getItems($cartId);

if (empty($items)) {
    sendJsonResponse(['ok' => false, 'error' => 'Your cart is empty.']);
}

// ── 2. Validate required delivery fields ─────────────────
$required = ['ship_name', 'ship_phone', 'ship_line1', 'ship_city', 'ship_state', 'ship_pincode'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        sendJsonResponse(['ok' => false, 'error' => "Please fill in all required delivery details ({$f})."]);
    }
}

$shipName    = trim($input['ship_name']);
$shipPhone   = trim($input['ship_phone']);
$shipLine1   = trim($input['ship_line1']);
$shipLine2   = trim($input['ship_line2'] ?? '');
$shipCity    = trim($input['ship_city']);
$shipState   = trim($input['ship_state']);
$shipPincode = trim($input['ship_pincode']);
$guestEmail  = trim($input['guest_email'] ?? '');
$notes       = trim($input['notes'] ?? '');

// ── 3. Determine User Account ────────────────────────────
$userId = null;
if (Auth::check()) {
    $userId = Auth::id();
} else {
    // Check if user exists by email or phone
    $lookupEmail = $guestEmail !== '' ? $guestEmail : $shipPhone . '@guest.dabhichikki.com';
    $existing = Database::fetchOne(
        'SELECT id FROM users WHERE email = ? OR (phone = ? AND phone != "") LIMIT 1',
        [$lookupEmail, $shipPhone]
    );
    if ($existing) {
        $userId = (int)$existing['id'];
    } else {
        $uuid = generateUuid();
        $randomPass = bin2hex(random_bytes(8));
        try {
            Database::query(
                'INSERT INTO users (uuid, role, full_name, email, phone, password_hash, is_active)
                 VALUES (?, "buyer", ?, ?, ?, ?, 1)',
                [$uuid, $shipName, $lookupEmail, $shipPhone, password_hash($randomPass, PASSWORD_BCRYPT)]
            );
            $userId = (int)Database::lastInsertId();
        } catch (\Throwable $ue) {
            $userFallback = Database::fetchOne('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1', [$lookupEmail, $shipPhone]);
            $userId = $userFallback ? (int)$userFallback['id'] : 1;
        }
    }
}

// ── 4. Save delivery address to addresses & user_addresses ─
Database::query(
    'INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode, is_default)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
    [$userId, $shipName, $shipPhone, $shipLine1, $shipLine2, $shipCity, $shipState, $shipPincode]
);
$addressId = (int)Database::lastInsertId();

try {
    Database::query(
        'INSERT INTO user_addresses (user_id, full_name, phone, line1, line2, city, state, pincode, is_default)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
        [$userId, $shipName, $shipPhone, $shipLine1, $shipLine2, $shipCity, $shipState, $shipPincode]
    );
} catch (\Throwable $e) {}

// ── 5. Calculate totals, taxes & coupons ─────────────────
$summary        = Cart::getSummary($items);
$subtotal       = (float)$summary['subtotal'];
$totalWeight    = (int)($summary['total_weight'] ?? 0);
$discountAmt    = 0.0;
$couponId       = null;
$couponCode     = strtoupper(trim($input['applied_coupon'] ?? ''));

if ($couponCode !== '') {
    $couponResult = Order::validateCouponCode($couponCode, $userId, $subtotal);
    if ($couponResult['ok']) {
        $discountAmt = (float)$couponResult['discount'];
        $couponId    = (int)($couponResult['coupon']['id'] ?? 0);
    }
}

$rawPaymentMethod = strtolower(trim($input['payment_method'] ?? 'cod'));
$isCod            = ($rawPaymentMethod === 'cod');
$paymentMethod    = $isCod ? 'cod' : 'online';

// Real shipping & COD calculation from admin configured rules
$shipCalc = Shipping::calculate($shipPincode, $totalWeight, $isCod, $subtotal);
if (!$shipCalc['ok']) {
    sendJsonResponse(['ok' => false, 'error' => $shipCalc['error']]);
}

$shippingCharge = (float)$shipCalc['rate'];
$codCharge      = (float)($shipCalc['cod_extra'] ?? 0.0);

// GST Calculation
$taxableAmount  = max(0, $subtotal - $discountAmt);
$gstData        = GST::calculate($items, $shipState);
$cgst           = (float)($gstData['cgst'] ?? 0);
$sgst           = (float)($gstData['sgst'] ?? 0);
$igst           = (float)($gstData['igst'] ?? 0);
$totalGst       = round($cgst + $sgst + $igst, 2);

$grandTotal     = round($taxableAmount + $shippingCharge + $codCharge, 2);

// ── 6. Payment Bypass & Status Resolution ────────────────
$paymentBypassEnabled = (getSetting('payment_bypass_enabled', '0') === '1');
$isBypassedOnline     = (!$isCod && $paymentBypassEnabled);

$orderStatus   = $isBypassedOnline ? 'confirmed' : 'placed';
$paymentStatus = $isBypassedOnline ? 'paid' : 'pending';

// ── 7. Generate order number ─────────────────────────────
$orderNumber = Order::generateNumber();

try {
    Database::beginTransaction();

    // ── 8. Insert Order ──────────────────────────────────
    Database::query(
        'INSERT INTO orders
         (order_number, user_id, guest_email, status, payment_method, payment_status,
          subtotal, discount_amount, coupon_id, shipping_charge, cod_charge,
          cgst_amount, sgst_amount, igst_amount, total_amount, total, gst_amount, total_weight_grams,
          shipping_address_id, billing_address_id,
          ship_name, ship_phone, ship_line1, ship_line2, ship_city, ship_state, ship_pincode,
          customer_notes, notes, placed_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
        [
            $orderNumber,
            $userId,
            $guestEmail,
            $orderStatus,
            $paymentMethod,
            $paymentStatus,
            $subtotal,
            $discountAmt,
            $couponId,
            $shippingCharge,
            $codCharge,
            $cgst,
            $sgst,
            $igst,
            $grandTotal,
            $grandTotal,
            $totalGst,
            $totalWeight,
            $addressId,
            $addressId,
            $shipName,
            $shipPhone,
            $shipLine1,
            $shipLine2,
            $shipCity,
            $shipState,
            $shipPincode,
            $notes,
            $notes,
        ]
    );
    $orderId = (int)Database::lastInsertId();

    // ── 9. Insert Order Items + FIFO batch deduction ─────
    foreach ($items as $item) {
        $variantId = (int)$item['variant_id'];
        $qty       = (int)$item['quantity'];
        $unitPrice = (float)$item['selling_price'];
        $mrp       = (float)($item['mrp'] ?? $unitPrice);
        $gstRate   = (float)($item['gst_rate_percent'] ?? 5.0);
        $lineTotal = round($unitPrice * $qty, 2);

        // Get product_id from variant
        $variantRow = Database::fetchOne('SELECT product_id, sku, weight_grams FROM product_variants WHERE id = ?', [$variantId]);
        $productId  = (int)($variantRow['product_id'] ?? 0);
        $sku        = $variantRow['sku'] ?? ($item['sku'] ?? 'CHIKKI');
        $weight     = (int)($variantRow['weight_grams'] ?? ($item['weight_grams'] ?? 250));

        // FIFO batch allocation
        $batches = Database::fetchAll(
            'SELECT id, quantity_remaining FROM inventory_batches WHERE variant_id = ? AND quantity_remaining > 0 ORDER BY expiry_date ASC, id ASC',
            [$variantId]
        );
        $primaryBatchId = !empty($batches) ? (int)$batches[0]['id'] : null;

        $gstAmt   = round($unitPrice * $qty * $gstRate / 100, 2);
        $prodName = $item['product_name'] ?? $item['name'] ?? 'Chikki';
        $varLabel = $weight >= 1000 ? ($weight / 1000) . ' kg' : $weight . ' g';

        Database::query(
            'INSERT INTO order_items
             (order_id, product_id, variant_id, batch_id,
              product_name_snapshot, product_name,
              variant_label_snapshot, variant_label,
              weight_grams, sku, quantity, mrp,
              unit_price, selling_price,
              gst_rate_percent, gst_rate, gst_amount, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $orderId,
                $productId,
                $variantId,
                $primaryBatchId,
                $prodName,
                $prodName,
                $varLabel,
                $varLabel,
                $weight,
                $sku,
                $qty,
                $mrp,
                $unitPrice,
                $unitPrice,
                $gstRate,
                $gstRate,
                $gstAmt,
                $lineTotal,
            ]
        );

        // Deduct FIFO inventory
        $toDeduct = $qty;
        foreach ($batches as $batch) {
            if ($toDeduct <= 0) break;
            $bId = (int)$batch['id'];
            $rem = (int)$batch['quantity_remaining'];
            $take = min($toDeduct, $rem);
            Database::query('UPDATE inventory_batches SET quantity_remaining = quantity_remaining - ? WHERE id = ?', [$take, $bId]);
            $toDeduct -= $take;

            Database::query(
                'INSERT INTO stock_movements (variant_id, batch_id, movement_type, quantity, reference_type, reference_id, performed_by, notes)
                 VALUES (?, ?, "sale_out", ?, "order", ?, ?, ?)',
                [$variantId, $bId, $take, $orderId, $userId, 'Sold in order ' . $orderNumber]
            );
        }
    }

    // ── 10. Record Payment Details ───────────────────────
    $cfPaymentSessionId = null;
    $cfMode             = null;

    if ($isBypassedOnline) {
        $simPaymentId = 'SIM_PAY_' . strtoupper(bin2hex(random_bytes(6)));
        Database::query(
            'INSERT INTO payments (order_id, gateway, gateway_order_id, gateway_payment_id, amount, currency, status, raw_response, created_at, updated_at)
             VALUES (?, "cashfree", ?, ?, ?, "INR", "success", ?, NOW(), NOW())',
            [
                $orderId,
                'BYPASS_' . $orderNumber,
                $simPaymentId,
                $grandTotal,
                json_encode([
                    'simulated'      => true,
                    'gateway'        => 'cashfree_bypass',
                    'note'           => 'Online payment gateway bypassed via simulation mode',
                    'transaction_id' => $simPaymentId,
                    'amount'         => $grandTotal,
                    'timestamp'      => date('c'),
                ], JSON_UNESCAPED_UNICODE)
            ]
        );
    } elseif ($isCod) {
        Database::query(
            'INSERT INTO payments (order_id, gateway, gateway_order_id, amount, currency, status, raw_response, created_at, updated_at)
             VALUES (?, "cod", ?, ?, "INR", "pending", ?, NOW(), NOW())',
            [
                $orderId,
                'COD_' . $orderNumber,
                $grandTotal,
                json_encode(['method' => 'cod', 'due_on_delivery' => $grandTotal])
            ]
        );
    } else {
        // Real Cashfree Payment Gateway Call
        if (!CashfreeGateway::isConfigured()) {
            throw new \Exception('Cashfree payment gateway is enabled, but App ID and Secret Key are not configured. Please check Admin > Payment Settings.');
        }

        $cfBuyer = [
            'full_name' => $shipName,
            'email'     => $guestEmail ?: ($user['email'] ?? 'orders@dabhichikki.com'),
            'phone'     => $shipPhone,
        ];

        $cfOrderData = [
            'id'           => $orderId,
            'order_number' => $orderNumber,
            'user_id'      => $userId,
            'total_amount' => $grandTotal,
            'ship_phone'   => $shipPhone,
            'ship_name'    => $shipName,
            'guest_email'  => $guestEmail,
        ];

        $cfResult = CashfreeGateway::createOrder($cfOrderData, $cfBuyer);

        if (!$cfResult['ok']) {
            throw new \Exception('Cashfree error: ' . ($cfResult['error'] ?? 'Could not initiate payment session.'));
        }

        $cfPaymentSessionId = $cfResult['payment_session_id'];
        $cfMode             = $cfResult['cashfree_mode'] ?? CashfreeGateway::getMode();
    }

    // ── 11. Order status history ─────────────────────────
    $historyRemarks = $isBypassedOnline
        ? 'Order confirmed. Online payment verified via simulated Cashfree gateway bypass.'
        : ($isCod ? 'Order placed by customer via Cash on Delivery.' : 'Order placed. Awaiting Cashfree online payment.');

    Database::query(
        'INSERT INTO order_status_history (order_id, status, remarks, changed_by) VALUES (?, ?, ?, ?)',
        [$orderId, $orderStatus, $historyRemarks, $userId]
    );

    // ── 12. Record coupon usage ──────────────────────────
    if ($couponId) {
        Database::query(
            'INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?, ?, ?)',
            [$couponId, $userId, $orderId]
        );
        try {
            Database::query(
                'INSERT INTO coupon_uses (coupon_id, user_id, order_id) VALUES (?, ?, ?)',
                [$couponId, $userId, $orderId]
            );
        } catch (\Throwable $e) {}
    }

    // ── 13. Clear Cart & Commit Order Transaction ───────
    Cart::clear($cartId);

    Database::commit();

    // ── Auto-Login Buyer Session ────────────────────────
    if (!Auth::check() && $userId) {
        $buyerUser = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($buyerUser && (int)($buyerUser['is_active'] ?? 1) === 1) {
            $_SESSION['user_id']   = (int)$buyerUser['id'];
            $_SESSION['user_role'] = $buyerUser['role'] ?? 'buyer';
            try {
                Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$buyerUser['id']]);
            } catch (\Throwable $ule) {}
        }
    }

    // For COD and Bypassed orders, generate invoice and notify immediately
    // For real Cashfree orders, invoice and confirmation notification happen upon payment verification
    if ($isCod || $isBypassedOnline) {
        // ── 14. Sequential Tax Invoice Generation ────────────
        try {
            Invoice::getOrCreate($orderId);
        } catch (\Throwable $invErr) {
            error_log('Tax invoice generation error: ' . $invErr->getMessage());
        }

        // ── 15. Real-Time Order Email Notification Dispatch ──
        try {
            $notifyEvent = $isBypassedOnline ? 'order_confirmed' : 'order_placed';
            Notification::trigger($orderId, $notifyEvent);
        } catch (\Throwable $ne) {
            error_log('Order notification error: ' . $ne->getMessage());
        }
    }

    if (!$isCod && !$isBypassedOnline) {
        sendJsonResponse([
            'ok'                 => true,
            'payment_mode'       => 'cashfree',
            'payment_session_id' => $cfPaymentSessionId,
            'cashfree_mode'      => $cfMode,
            'order_id'           => $orderId,
            'order_number'       => $orderNumber,
            'message'            => 'Redirecting to Cashfree for payment...'
        ]);
    }

    sendJsonResponse([
        'ok'           => true,
        'order_id'     => $orderId,
        'order_number' => $orderNumber,
        'status'       => $orderStatus,
        'payment_mode' => $isBypassedOnline ? 'online_simulated' : ($isCod ? 'cod' : 'online'),
        'bypassed'     => $isBypassedOnline,
        'message'      => $isBypassedOnline
            ? 'Online payment bypassed in simulation mode! Order confirmed and receipt emailed.'
            : 'Order placed successfully! Confirmation email sent.'
    ]);

} catch (\Throwable $e) {
    Database::rollBack();
    error_log('Order placement error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    sendJsonResponse(['ok' => false, 'error' => 'Could not place your order: ' . $e->getMessage()]);
}
