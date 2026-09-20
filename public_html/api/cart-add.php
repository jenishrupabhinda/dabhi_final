<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$variantId = (int)($input['variant_id'] ?? 0);
$quantity  = max(1, (int)($input['quantity'] ?? 1));

if (!$variantId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid variant ID.']);
    exit;
}

$cart = Cart::getOrCreate();
$result = Cart::addItem($variantId, $quantity);
$result['cart_count'] = Cart::itemCount((int)$cart['id']);
echo json_encode($result);
