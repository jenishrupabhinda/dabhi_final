<?php
/**
 * cart-items.php — Returns current cart items and dynamic store policies as JSON for the cart drawer
 */
$bootstrap = null;
foreach ([
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/../../includes/bootstrap.php',
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

try {
    $cart    = Cart::getOrCreate();
    $cartId  = (int)($cart['id'] ?? 0);
    $items   = Cart::getItems($cartId);
    $summary = Cart::getSummary($items);

    $freeShippingThreshold = (float)getSetting('free_shipping_threshold', '999');
    if ($freeShippingThreshold <= 0) {
        $freeShippingThreshold = (float)getSetting('free_shipping_above', '999');
    }
    $defaultShippingCharge = (float)getSetting('default_shipping_charge', '60');
    $codEnabled            = getSetting('cod_enabled', '1') === '1';

    echo json_encode([
        'ok'                      => true,
        'items'                   => $items,
        'subtotal'                => $summary['subtotal'],
        'item_count'              => $summary['item_count'],
        'total_weight'            => $summary['total_weight'],
        'free_shipping_threshold' => $freeShippingThreshold,
        'default_shipping_charge' => $defaultShippingCharge,
        'cod_enabled'             => $codEnabled,
        'gst_enabled'             => GST::isEnabled(),
    ]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Cart unavailable.', 'items' => []]);
}
