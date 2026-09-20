<?php
/**
 * CashfreeGateway — Cashfree Payments API v2 integration stub.
 * Uses the Cashfree Order API for seamless checkout (JS SDK).
 * Set CASHFREE_APP_ID and CASHFREE_SECRET in config.php.
 */
class CashfreeGateway
{
    private static function baseUrl(): string
    {
        return defined('CASHFREE_SANDBOX') && CASHFREE_SANDBOX
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    private static function headers(): array
    {
        return [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . (defined('CASHFREE_APP_ID') ? CASHFREE_APP_ID : ''),
            'x-client-secret: ' . (defined('CASHFREE_SECRET') ? CASHFREE_SECRET : ''),
        ];
    }

    private static function request(string $method, string $endpoint, array $body = []): array
    {
        $ch = curl_init(self::baseUrl() . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => self::headers(),
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true) ?? ['raw' => $raw];
        return ['http_status' => $status, 'data' => $data, 'raw' => $raw];
    }

    /**
     * Create a Cashfree order → returns payment_session_id for JS SDK.
     */
    public static function createOrder(array $order, array $buyer): array
    {
        $body = [
            'order_id'       => $order['order_number'],
            'order_amount'   => (float)$order['total_amount'],
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => 'user_' . $order['user_id'],
                'customer_email' => $buyer['email'],
                'customer_phone' => preg_replace('/\D/', '', $buyer['phone']),
                'customer_name'  => $buyer['full_name'],
            ],
            'order_meta' => [
                'return_url' => APP_URL . '/order-confirmation.php?order=' . urlencode($order['order_number']) . '&cf_id={order_id}',
            ],
            'order_note' => 'Dabhi Chikki order ' . $order['order_number'],
        ];

        $resp = self::request('POST', '/orders', $body);

        if ($resp['http_status'] === 200 && !empty($resp['data']['payment_session_id'])) {
            // Persist gateway details
            Database::query(
                "INSERT INTO payments (order_id, gateway, gateway_order_id, amount, status)
                 VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE gateway_order_id = VALUES(gateway_order_id)",
                [$order['id'], 'cashfree', $resp['data']['cf_order_id'] ?? $order['order_number'],
                 $order['total_amount'], 'created']
            );
            return [
                'ok'                 => true,
                'payment_session_id' => $resp['data']['payment_session_id'],
                'cf_order_id'        => $resp['data']['cf_order_id'] ?? '',
            ];
        }

        error_log('Cashfree createOrder failed: ' . $resp['raw']);
        return ['ok' => false, 'error' => $resp['data']['message'] ?? 'Payment gateway error.'];
    }

    /**
     * Verify webhook signature and return parsed event.
     */
    public static function verifyWebhook(string $rawBody, string $signature, string $timestamp): array
    {
        $secret = defined('CASHFREE_SECRET') ? CASHFREE_SECRET : '';
        $computed = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $secret, true));

        if (!hash_equals($computed, $signature)) {
            return ['ok' => false, 'error' => 'Signature mismatch.'];
        }

        $event = json_decode($rawBody, true);
        return ['ok' => true, 'event' => $event];
    }

    /**
     * Check order payment status directly with Cashfree.
     */
    public static function getPaymentStatus(string $cfOrderId): array
    {
        $resp = self::request('GET', "/orders/{$cfOrderId}/payments");
        return ['ok' => $resp['http_status'] === 200, 'data' => $resp['data']];
    }
}
