<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$itemId   = (int)($input['item_id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 0);

$cart = Cart::getOrCreate();
$result = Cart::updateItem($itemId, $quantity);
$items  = Cart::getItems((int)$cart['id']);
$summary = Cart::getSummary($items);
$result  = array_merge($result, $summary);
$result['cart_count'] = Cart::itemCount((int)$cart['id']);
echo json_encode($result);
