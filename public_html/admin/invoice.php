<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('view_orders');

$orderId = (int)get('order_id');
$order   = Order::getById($orderId);
if (!$order) { http_response_code(404); die('Order not found.'); }

$invoice = Invoice::getOrCreate($orderId);
if (!$invoice) { die('Invoice generation failed.'); }

// Now render inline HTML
echo Invoice::renderHTML($order, $invoice);
