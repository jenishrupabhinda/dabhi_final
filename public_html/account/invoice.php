<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');
// Let buyer view their own invoice
$userId  = Auth::id();
$orderId = (int)get('order_id');
$order   = Order::getById($orderId, $userId);
if (!$order) { redirect('/account/orders.php'); }

$invoice = Invoice::getOrCreate($orderId);
if (!$invoice) { die('Invoice generation failed.'); }

echo Invoice::renderHTML($order, $invoice);
