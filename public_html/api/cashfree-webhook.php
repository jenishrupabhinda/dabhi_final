<?php
/**
 * Cashfree webhook handler.
 * Cashfree POSTs payment events here. Verify signature, update order/payment.
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

$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';

// Verify
$result = CashfreeGateway::verifyWebhook($rawBody, $signature, $timestamp);
if (!$result['ok']) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid signature.']);
    exit;
}

$event     = $result['event'];
$eventType = $event['type'] ?? '';
$data      = $event['data'] ?? [];

// Map Cashfree order_id back to our order_number
$cfOrderId    = $data['order']['cf_order_id'] ?? '';
$orderNumber  = $data['order']['order_id']    ?? '';
if (!$orderNumber) {
    http_response_code(200); echo '{}'; exit;
}

$order = Order::getByNumber($orderNumber);
if (!$order) { http_response_code(200); echo '{}'; exit; }

$orderId  = (int)$order['id'];
$rawJson  = $rawBody;

switch ($eventType) {
    case 'PAYMENT_SUCCESS':
    case 'PAYMENT_SUCCESS_WEBHOOK':
        $gatewayPaymentId = $data['payment']['cf_payment_id'] ?? '';
        // Update payments table
        Database::query(
            "UPDATE payments SET status='success', gateway_payment_id=?, raw_response=?, updated_at=NOW()
             WHERE order_id = ? AND gateway='cashfree'",
            [$gatewayPaymentId, $rawJson, $orderId]
        );
        // Update order
        Database::query(
            "UPDATE orders SET payment_status='paid', status='confirmed' WHERE id = ?",
            [$orderId]
        );
        Order::updateStatus($orderId, 'confirmed', null, 'Payment received via Cashfree.');

        // Trigger notifications (Phase 6)
        // NotificationDispatcher::trigger($orderId, 'order_confirmed');
        break;

    case 'PAYMENT_FAILED':
    case 'PAYMENT_FAILED_WEBHOOK':
        Database::query(
            "UPDATE payments SET status='failed', raw_response=?, updated_at=NOW() WHERE order_id=? AND gateway='cashfree'",
            [$rawJson, $orderId]
        );
        Database::query("UPDATE orders SET payment_status='failed' WHERE id=?", [$orderId]);
        break;

    case 'REFUND_PROCESSED':
        Database::query(
            "UPDATE payments SET status='refunded', raw_response=?, updated_at=NOW() WHERE order_id=? AND gateway='cashfree'",
            [$rawJson, $orderId]
        );
        Database::query("UPDATE orders SET payment_status='refunded' WHERE id=?", [$orderId]);
        break;

    default:
        // Log unknown events silently
        error_log('Cashfree unhandled webhook event: ' . $eventType);
}

http_response_code(200);
echo json_encode(['ok' => true]);
