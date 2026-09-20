<?php
/**
 * invoice.php — Public & Customer Tax Invoice Viewer
 * Generates and prints official sequential Tax Invoices with GST breakdown
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

$orderId = (int)($_GET['order_id'] ?? $_GET['id'] ?? 0);
$orderNum = trim($_GET['order'] ?? $_GET['order_number'] ?? '');

$order = null;
if ($orderId > 0) {
    $order = Order::getById($orderId);
} elseif ($orderNum !== '') {
    $order = Order::getByNumber($orderNum);
}

if (!$order) {
    http_response_code(404);
    die('Order not found.');
}

// Access Control: Admins, Order owner, or valid order reference
$isAdmin = Auth::check() && in_array(Auth::user()['role'] ?? '', ['superadmin', 'admin', 'employee']);
$isOwner = Auth::check() && ((int)$order['user_id'] === (int)Auth::id());

// If guest or public access, allow viewing with order number
if (!$isAdmin && !$isOwner) {
    // If phone or email verification requested in query, verify
    if (!empty($_GET['phone']) && trim($_GET['phone']) !== trim($order['phone'] ?? $order['ship_phone'] ?? '')) {
        http_response_code(403);
        die('Access denied.');
    }
}

$invoice = Invoice::getOrCreate((int)$order['id']);
if (!$invoice) {
    http_response_code(500);
    die('Could not generate invoice. Please try again.');
}

// Render the invoice HTML
echo Invoice::renderHTML($order, $invoice);
