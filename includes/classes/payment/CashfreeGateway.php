<?php
/**
 * CashfreeGateway — Cashfree Payments PG API (v2023-08-01) integration.
 * Supports credentials configured via Admin Payment Settings or config.php.
 */
class CashfreeGateway
{
    /**
     * Get Cashfree App / Client ID (prefers database settings, falls back to config.php).
     */
    public static function getAppId(): string
    {
        $id = getSetting('cashfree_app_id', '');
        if (!empty($id)) {
            return trim($id);
        }
        if (defined('CASHFREE_APP_ID') && CASHFREE_APP_ID !== 'your-test-app-id') {
            return trim(CASHFREE_APP_ID);
        }
        return '';
    }

    /**
     * Get Cashfree Secret Key (prefers database settings, falls back to config.php).
     */
    public static function getSecretKey(): string
    {
        $key = getSetting('cashfree_secret_key', '');
        if (!empty($key)) {
            return trim($key);
        }
        if (defined('CASHFREE_SECRET_KEY') && CASHFREE_SECRET_KEY !== 'your-test-secret-key') {
            return trim(CASHFREE_SECRET_KEY);
        }
        if (defined('CASHFREE_SECRET') && CASHFREE_SECRET !== 'your-test-secret-key') {
            return trim(CASHFREE_SECRET);
        }
        return '';
    }

    /**
     * Get active mode: 'sandbox' or 'production'.
     */
    public static function getMode(): string
    {
        $mode = strtolower(trim((string)getSetting('cashfree_mode', '')));
        if ($mode === 'production' || $mode === 'live') {
            return 'production';
        }
        if ($mode === 'sandbox' || $mode === 'test') {
            return 'sandbox';
        }
        return (defined('CASHFREE_SANDBOX') && CASHFREE_SANDBOX) ? 'sandbox' : 'production';
    }

    /**
     * Returns true if valid App ID & Secret Key are configured.
     */
    public static function isConfigured(): bool
    {
        return self::getAppId() !== '' && self::getSecretKey() !== '';
    }

    private static function baseUrl(): string
    {
        return self::getMode() === 'sandbox'
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    private static function headers(): array
    {
        return [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . self::getAppId(),
            'x-client-secret: ' . self::getSecretKey(),
        ];
    }

    private static function request(string $method, string $endpoint, array $body = []): array
    {
        $url = self::baseUrl() . $endpoint;
        $ch  = curl_init($url);

        $isProd = defined('APP_ENV') && APP_ENV === 'production';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => self::headers(),
            CURLOPT_TIMEOUT        => 25,
            // Allow localhost development on Windows WAMP without self-signed cert chain errors
            CURLOPT_SSL_VERIFYPEER => $isProd,
            CURLOPT_SSL_VERIFYHOST => $isProd ? 2 : 0,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            return [
                'http_status' => $status,
                'data'        => ['message' => 'Cashfree gateway connection error: ' . ($err ?: 'Empty response')],
                'raw'         => (string)$err,
            ];
        }

        $data = json_decode($raw, true) ?? ['raw' => $raw];
        return ['http_status' => $status, 'data' => $data, 'raw' => $raw];
    }

    /**
     * Create a Cashfree order → returns payment_session_id for Cashfree JS SDK checkout.
     */
    public static function createOrder(array $order, array $buyer = []): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'Cashfree credentials (App ID / Secret Key) are not configured.'];
        }

        // Format and clean phone number (Cashfree requires 10 digits in India)
        $rawPhone = preg_replace('/\D/', '', $buyer['phone'] ?? $order['ship_phone'] ?? '');
        if (strlen($rawPhone) > 10 && str_starts_with($rawPhone, '91')) {
            $rawPhone = substr($rawPhone, 2);
        }
        if (strlen($rawPhone) !== 10) {
            $rawPhone = '9876543210';
        }

        $customerId = !empty($order['user_id'])
            ? 'user_' . $order['user_id']
            : 'guest_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $order['order_number']);

        $customerName  = trim($buyer['full_name'] ?? $order['ship_name'] ?? '') ?: 'Customer';
        $customerEmail = trim($buyer['email'] ?? $order['guest_email'] ?? '') ?: 'orders@dabhichikki.com';

        // Cashfree replaces {order_id} with the actual order id string
        $returnUrl = rtrim(APP_URL, '/') . '/order-success.php?order_id={order_id}';
        $notifyUrl = rtrim(APP_URL, '/') . '/api/cashfree-webhook.php';

        $body = [
            'order_id'       => $order['order_number'],
            'order_amount'   => (float)$order['total_amount'],
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => $customerId,
                'customer_email' => $customerEmail,
                'customer_phone' => $rawPhone,
                'customer_name'  => $customerName,
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ],
            'order_note' => 'Dabhi Chikki Order ' . $order['order_number'],
        ];

        $resp = self::request('POST', '/orders', $body);

        // Success response
        if (($resp['http_status'] === 200 || $resp['http_status'] === 201) && !empty($resp['data']['payment_session_id'])) {
            $cfOrderId = $resp['data']['cf_order_id'] ?? $order['order_number'];

            Database::query(
                "INSERT INTO payments (order_id, gateway, gateway_order_id, amount, currency, status, raw_response, created_at, updated_at)
                 VALUES (?, 'cashfree', ?, ?, 'INR', 'created', ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE gateway_order_id = VALUES(gateway_order_id), raw_response = VALUES(raw_response), updated_at = NOW()",
                [$order['id'], $cfOrderId, $order['total_amount'], $resp['raw']]
            );

            return [
                'ok'                 => true,
                'payment_session_id' => $resp['data']['payment_session_id'],
                'cf_order_id'        => $cfOrderId,
                'cashfree_mode'      => self::getMode(),
            ];
        }

        // If order already exists with Cashfree (e.g. customer refreshed or retried), fetch existing active session
        if (isset($resp['data']['code']) && in_array($resp['data']['code'], ['order_already_exists', 'order_already_paid'])) {
            $existing = self::getOrder($order['order_number']);
            if ($existing['ok'] && !empty($existing['data']['payment_session_id'])) {
                return [
                    'ok'                 => true,
                    'payment_session_id' => $existing['data']['payment_session_id'],
                    'cf_order_id'        => $existing['data']['cf_order_id'] ?? $order['order_number'],
                    'cashfree_mode'      => self::getMode(),
                ];
            }
        }

        error_log('Cashfree createOrder failed [' . $resp['http_status'] . ']: ' . ($resp['raw'] ?? ''));
        $errMsg = $resp['data']['message'] ?? 'Payment gateway error. Please verify your Cashfree credentials.';
        return ['ok' => false, 'error' => $errMsg, 'raw' => $resp['raw'] ?? ''];
    }

    /**
     * Retrieve order details from Cashfree: GET /orders/{order_id}
     */
    public static function getOrder(string $orderNumber): array
    {
        $resp = self::request('GET', "/orders/{$orderNumber}");
        return [
            'ok'          => ($resp['http_status'] === 200),
            'http_status' => $resp['http_status'],
            'data'        => $resp['data'],
        ];
    }

    /**
     * Retrieve payment attempts for an order: GET /orders/{order_id}/payments
     */
    public static function getPaymentStatus(string $orderNumber): array
    {
        $resp = self::request('GET', "/orders/{$orderNumber}/payments");
        return [
            'ok'          => ($resp['http_status'] === 200),
            'http_status' => $resp['http_status'],
            'data'        => $resp['data'],
        ];
    }

    /**
     * Verify payment status across both order entity and payment attempts.
     * Returns: ['ok' => bool, 'paid' => bool, 'failed' => bool, 'error' => string, 'payment_id' => string, 'details' => array]
     */
    public static function verifyOrderPayment(string $orderNumber): array
    {
        // 1. Check order status
        $orderRes = self::getOrder($orderNumber);
        $orderStatus = strtoupper($orderRes['data']['order_status'] ?? '');
        if ($orderRes['ok'] && $orderStatus === 'PAID') {
            return [
                'ok'         => true,
                'paid'       => true,
                'failed'     => false,
                'error'      => '',
                'payment_id' => $orderRes['data']['cf_order_id'] ?? $orderNumber,
                'details'    => $orderRes['data'],
            ];
        }

        // 2. Check payment attempts
        $paymentsRes = self::getPaymentStatus($orderNumber);
        if ($paymentsRes['ok'] && is_array($paymentsRes['data']) && !empty($paymentsRes['data'])) {
            foreach ($paymentsRes['data'] as $p) {
                $payStatus = strtoupper($p['payment_status'] ?? '');
                if ($payStatus === 'SUCCESS') {
                    return [
                        'ok'         => true,
                        'paid'       => true,
                        'failed'     => false,
                        'error'      => '',
                        'payment_id' => (string)($p['cf_payment_id'] ?? $p['payment_id'] ?? ''),
                        'details'    => $p,
                    ];
                }
            }

            // Extract failure message from the latest payment attempt
            $last       = end($paymentsRes['data']);
            $lastStatus = strtoupper($last['payment_status'] ?? '');

            $failMsg = trim((string)($last['payment_message'] ?? ''));
            if ($failMsg === '' && !empty($last['error_details']['error_description'])) {
                $failMsg = trim((string)$last['error_details']['error_description']);
            }
            if ($failMsg === '' && !empty($last['error_details']['error_reason'])) {
                $failMsg = trim((string)$last['error_details']['error_reason']);
            }
            if ($failMsg === '') {
                if ($lastStatus === 'USER_DROPPED') {
                    $failMsg = 'Payment window was closed or transaction abandoned before completion.';
                } elseif ($lastStatus === 'CANCELLED') {
                    $failMsg = 'Payment was cancelled by the customer.';
                } else {
                    $failMsg = 'Transaction declined or failed at payment gateway.';
                }
            }

            return [
                'ok'         => true,
                'paid'       => false,
                'failed'     => true,
                'error'      => $failMsg,
                'payment_id' => (string)($last['cf_payment_id'] ?? ''),
                'details'    => $last,
            ];
        }

        // If no payment attempts recorded or session expired/abandoned
        $failMsg = 'Payment was not completed. Transaction was cancelled or abandoned.';
        if (!empty($orderRes['data']['message'])) {
            $failMsg = $orderRes['data']['message'];
        } elseif ($orderStatus === 'EXPIRED') {
            $failMsg = 'Payment session expired.';
        }

        return [
            'ok'         => true,
            'paid'       => false,
            'failed'     => true,
            'error'      => $failMsg,
            'payment_id' => '',
            'details'    => $orderRes['data'] ?? [],
        ];
    }

    /**
     * Verify webhook signature.
     */
    public static function verifyWebhook(string $rawBody, string $signature, string $timestamp): array
    {
        $secret = self::getSecretKey();
        if ($secret === '') {
            return ['ok' => false, 'error' => 'Cashfree secret key not configured.'];
        }

        $computed = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $secret, true));

        if (!hash_equals($computed, $signature)) {
            return ['ok' => false, 'error' => 'Signature mismatch.'];
        }

        $event = json_decode($rawBody, true);
        return ['ok' => true, 'event' => $event];
    }
}

