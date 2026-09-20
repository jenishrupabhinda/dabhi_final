<?php
/**
 * order.php — Place order API endpoint for Dabhi_final
 * Bridges the modern Yogurt Alley storefront checkout with Dabhi's order & FIFO inventory backend.
 */
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

header('Content-Type: application/json');

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request.']); exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if ($action !== 'place_order') {
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']); exit;
}

// ── 1. Validate cart ─────────────────────────────────────
$cart   = Cart::getOrCreate();
$cartId = (int)($cart['id'] ?? 0);
$items  = Cart::getItems($cartId);

if (empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'Your cart is empty.']); exit;
}

// ── 2. Validate required delivery fields ─────────────────
$required = ['ship_name', 'ship_phone', 'ship_line1', 'ship_city', 'ship_state', 'ship_pincode'];
foreach ($required as $f) {
    if (empty(trim($input[$f] ?? ''))) {
        echo json_encode(['ok' => false, 'error' => "Please fill in all required delivery details ({$f})."]); exit;
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
        Database::query(
            'INSERT INTO users (uuid, role, full_name, email, phone, password_hash, is_active)
             VALUES (?, "buyer", ?, ?, ?, ?, 1)',
            [$uuid, $shipName, $lookupEmail, $shipPhone, password_hash($randomPass, PASSWORD_BCRYPT)]
        );
        $userId = (int)Database::lastInsertId();
    }
}

// ── 4. Save delivery address to addresses & user_addresses ─
Database::query(
    'INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode, is_default)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
    [$userId, $shipName, $shipPhone, $shipLine1, $shipLine2, $shipCity, $shipState, $shipPincode]
);
$addressId = (int)Database::lastInsertId();

// Also save to user_addresses compatibility table
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

$paymentMethod  = in_array($input['payment_method'] ?? 'cod', ['cod', 'cashfree', 'upi'])
    ? $input['payment_method']
    : 'cod';
$isCod = ($paymentMethod === 'cod');

// Real shipping & COD calculation from admin configured rules
$shipCalc = Shipping::calculate($shipPincode, $totalWeight, $isCod, $subtotal);
if (!$shipCalc['ok']) {
    echo json_encode(['ok' => false, 'error' => $shipCalc['error']]);
    exit;
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

// ── 6. Generate order number ─────────────────────────────
$orderNumber = Order::generateNumber();

try {
    Database::beginTransaction();

    // ── 7. Insert Order ──────────────────────────────────
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
            'placed',
            $paymentMethod === 'cod' ? 'cod' : 'online',
            'pending',
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

    // ── 8. Insert Order Items + FIFO batch deduction ─────
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

            // Log stock movement
            Database::query(
                'INSERT INTO stock_movements (variant_id, batch_id, movement_type, quantity, reference_type, reference_id, performed_by, notes)
                 VALUES (?, ?, "sale_out", ?, "order", ?, ?, ?)',
                [$variantId, $bId, $take, $orderId, $userId, 'Sold in order ' . $orderNumber]
            );
        }
    }

    // ── 9. Order status history ──────────────────────────
    Database::query(
        'INSERT INTO order_status_history (order_id, status, remarks, changed_by) VALUES (?, "placed", "Order placed by customer.", ?)',
        [$orderId, $userId]
    );

    // ── 10. Record coupon usage ──────────────────────────
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

    // ── 11. Clear Cart ───────────────────────────────────
    Cart::clear($cartId);

    Database::commit();

    echo json_encode([
        'ok'           => true,
        'order_id'     => $orderId,
        'order_number' => $orderNumber
    ]);

} catch (\Throwable $e) {
    Database::rollBack();
    error_log('Order placement error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Could not place your order: ' . $e->getMessage()]);
}
