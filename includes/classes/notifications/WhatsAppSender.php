<?php
/**
 * WhatsAppSender — Meta WhatsApp Cloud API integration.
 * Supports:
 *  - Test Mode (Sandbox) with temporary developer credentials & verified recipient rerouting
 *  - Live Production Mode with permanent WABA credentials
 *  - Template messages (e.g. 'hello_world' or pre-approved order templates)
 *  - Direct text messages
 *  - Automated Order Event Notifications (placed, confirmed, shipped, delivered, cancelled)
 *  - Full audit logging into `notifications_log`
 */
class WhatsAppSender
{
    /**
     * Resolve configuration based on Test Mode vs Live Production Mode.
     */
    public static function getConfig(?array $overrides = null): array
    {
        $enabled = (getSetting('whatsapp_enabled', '0') === '1' || getSetting('whatsapp_notifications_enabled', '0') === '1');
        $mode    = getSetting('whatsapp_mode', 'test'); // 'test' | 'live'

        $testPhoneId    = getSetting('whatsapp_test_phone_number_id', defined('WHATSAPP_PHONE_NUMBER_ID') ? WHATSAPP_PHONE_NUMBER_ID : '');
        $testToken      = getSetting('whatsapp_test_api_token', defined('WHATSAPP_API_TOKEN') ? WHATSAPP_API_TOKEN : '');
        $testRecipient  = getSetting('whatsapp_test_recipient_number', '');
        $forceRecipient = getSetting('whatsapp_test_force_recipient', '1') === '1';

        $livePhoneId    = getSetting('whatsapp_live_phone_number_id', getSetting('whatsapp_phone_number_id', ''));
        $liveToken      = getSetting('whatsapp_live_api_token', getSetting('whatsapp_api_token', ''));

        $apiVersion     = getSetting('whatsapp_api_version', 'v21.0');

        $activePhoneId  = ($mode === 'test') ? ($testPhoneId ?: $livePhoneId) : ($livePhoneId ?: $testPhoneId);
        $activeToken    = ($mode === 'test') ? ($testToken ?: $liveToken) : ($liveToken ?: $testToken);

        $cfg = [
            'enabled'               => $enabled,
            'mode'                  => $mode, // 'test' or 'live'
            'phone_number_id'       => trim((string)$activePhoneId),
            'api_token'             => trim((string)$activeToken),
            'test_phone_number_id'  => trim((string)$testPhoneId),
            'test_api_token'        => trim((string)$testToken),
            'test_recipient_number' => trim((string)$testRecipient),
            'test_force_recipient'  => $forceRecipient,
            'live_phone_number_id'  => trim((string)$livePhoneId),
            'live_api_token'        => trim((string)$liveToken),
            'api_version'           => $apiVersion,
        ];

        if ($overrides) {
            $cfg = array_merge($cfg, $overrides);
        }

        return $cfg;
    }

    /**
     * Sanitize phone number to E.164 digits without '+' (Meta Cloud API requirement).
     * Automatically adds India country code (91) for 10-digit mobile numbers.
     */
    public static function sanitizePhone(string $phone): string
    {
        $clean = preg_replace('/\D+/', '', $phone);
        if ($clean === '') {
            return '';
        }

        // If prefixed with 00 (e.g. 0091...), strip 00
        if (str_starts_with($clean, '00')) {
            $clean = substr($clean, 2);
        }

        // 10-digit Indian mobile starting with 6, 7, 8, 9 -> prepend 91
        if (strlen($clean) === 10 && preg_match('/^[6-9]/', $clean)) {
            $clean = '91' . $clean;
        }

        // 11 digits starting with 0 (e.g. 09876543210) -> strip 0 and prepend 91
        if (strlen($clean) === 11 && str_starts_with($clean, '0') && preg_match('/^[6-9]/', substr($clean, 1))) {
            $clean = '91' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Execute a cURL request against Meta WhatsApp Cloud API.
     */
    public static function callGraphApi(array $payload, ?array $config = null): array
    {
        $cfg = $config ?? self::getConfig();

        if (empty($cfg['phone_number_id'])) {
            return [
                'ok'         => false,
                'http_code'  => 0,
                'error'      => 'WhatsApp Phone Number ID is missing. Please configure it in Notification Settings.',
                'error_code' => 0,
                'help'       => 'Go to Meta Developer Portal > WhatsApp > API Setup and copy the Phone number ID.',
            ];
        }

        if (empty($cfg['api_token'])) {
            return [
                'ok'         => false,
                'http_code'  => 0,
                'error'      => 'WhatsApp Access Token is missing. Please configure it in Notification Settings.',
                'error_code' => 0,
                'help'       => 'Go to Meta Developer Portal > WhatsApp > API Setup and copy the Temporary access token or System User token.',
            ];
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $cfg['api_version'] ?? 'v21.0',
            $cfg['phone_number_id']
        );

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $curlOpts = [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $cfg['api_token'],
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
        ];

        // SSL Certificate Verification Handling
        $knownCaBundles = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:\\wamp64\\apps\\phpmyadmin5.2.1\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
        ];
        $caFound = false;
        foreach ($knownCaBundles as $bundle) {
            if (!empty($bundle) && file_exists($bundle)) {
                $curlOpts[CURLOPT_CAINFO] = $bundle;
                $curlOpts[CURLOPT_SSL_VERIFYPEER] = true;
                $curlOpts[CURLOPT_SSL_VERIFYHOST] = 2;
                $caFound = true;
                break;
            }
        }
        if (!$caFound) {
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
                $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $curlOpts);

        $responseBody = curl_exec($ch);
        $httpCode     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'ok'         => false,
                'http_code'  => 0,
                'error'      => 'cURL connection error: ' . $curlError,
                'error_code' => 0,
                'help'       => 'Verify server internet connection and outgoing HTTPS requests to graph.facebook.com.',
            ];
        }

        $decoded = json_decode((string)$responseBody, true) ?? [];

        // Check for Meta Success: HTTP 200 or 201 with 'messages' array
        if (($httpCode === 200 || $httpCode === 201) && !empty($decoded['messages'][0]['id'])) {
            return [
                'ok'         => true,
                'http_code'  => $httpCode,
                'message_id' => $decoded['messages'][0]['id'],
                'raw'        => $decoded,
            ];
        }

        // Meta Error structure: { "error": { "message": "...", "type": "...", "code": 190, ... } }
        $errData    = $decoded['error'] ?? [];
        $errMsg     = $errData['message'] ?? ('HTTP ' . $httpCode . ' unexpected response');
        $errCode    = (int)($errData['code'] ?? 0);
        $errSubcode = (int)($errData['error_subcode'] ?? 0);

        return [
            'ok'            => false,
            'http_code'     => $httpCode,
            'error'         => $errMsg,
            'error_code'    => $errCode,
            'error_subcode' => $errSubcode,
            'details'       => $errData,
            'raw'           => $decoded,
            'help'          => self::getTroubleshootingTip($errCode, $errSubcode, $errMsg),
        ];
    }

    /**
     * Provide actionable troubleshooting instructions for common Meta error codes.
     */
    public static function getTroubleshootingTip(int $code, int $subcode, string $message): string
    {
        if ($code === 190) {
            return '⚠️ Access Token Expired or Invalid: Your 24-hour temporary token has expired. In Meta Developer Portal (WhatsApp > API Setup), copy a fresh temporary token, or create a permanent System User Token in Meta Business Settings.';
        }
        if ($code === 131030) {
            return '⚠️ Sandbox Recipient Not Verified: In Test Mode, Meta only sends messages to phone numbers added in "API Setup > Step 2: Select phone numbers to send messages to". Add and verify this number in Meta Developer App first.';
        }
        if ($code === 100 && (stripos($message, 'template') !== false || $subcode === 132000)) {
            return '⚠️ Template Not Found: Ensure the template name (e.g., "hello_world") matches exactly and language code is set correctly (e.g., "en_US").';
        }
        if ($code === 100 && stripos($message, 'phone_number_id') !== false) {
            return '⚠️ Invalid Phone Number ID: Double-check the 15-digit Phone Number ID in your Meta Developer App (WhatsApp > API Setup).';
        }
        if ($code === 131026 || $code === 131009) {
            return '⚠️ Undeliverable / Number not on WhatsApp: The recipient number does not appear to be registered on WhatsApp, or messaging permissions were restricted by Meta.';
        }
        if ($code === 131047) {
            return '⚠️ 24-Hour Messaging Window Expired: Outside a 24-hour customer-initiated conversation, you must use a pre-approved template message rather than free-form text.';
        }

        return 'Check Meta Cloud API dashboard or verify your credentials and recipient phone number.';
    }

    /**
     * Send a pre-approved WhatsApp template message (e.g., 'hello_world' or custom template).
     */
    public static function sendTemplate(
        string $to,
        string $templateName = 'hello_world',
        string $langCode = 'en_US',
        array $components = [],
        ?array $config = null
    ): array {
        $sanitizedTo = self::sanitizePhone($to);
        if (empty($sanitizedTo)) {
            return ['ok' => false, 'error' => 'Invalid or empty recipient phone number.'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $sanitizedTo,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $langCode],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return self::callGraphApi($payload, $config);
    }

    /**
     * Send a direct text message via Meta Cloud API.
     */
    public static function sendText(string $to, string $messageBody, ?array $config = null): array
    {
        $sanitizedTo = self::sanitizePhone($to);
        if (empty($sanitizedTo)) {
            return ['ok' => false, 'error' => 'Invalid or empty recipient phone number.'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $sanitizedTo,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $messageBody,
            ],
        ];

        return self::callGraphApi($payload, $config);
    }

    /**
     * Test connection runner used by Admin UI.
     * $testType: 'hello_world' | 'text' | 'order_simulation'
     */
    public static function testConnection(
        string $toPhone,
        string $testType = 'hello_world',
        ?string $customText = null,
        ?array $overrideConfig = null
    ): array {
        $cfg = self::getConfig($overrideConfig);
        $to  = self::sanitizePhone($toPhone ?: $cfg['test_recipient_number']);

        if (empty($to)) {
            return [
                'ok'    => false,
                'error' => 'Please provide a valid recipient phone number (e.g. +91 98765 43210).',
            ];
        }

        if ($testType === 'hello_world') {
            $res = self::sendTemplate($to, 'hello_world', 'en_US', [], $cfg);
        } elseif ($testType === 'text') {
            $msg = $customText ?: "👋 Test message from Dabhi Chikki!\nYour WhatsApp Cloud API integration is connected successfully.";
            if ($cfg['mode'] === 'test') {
                $msg = "[TEST MODE SANDBOX]\n" . $msg;
            }
            $res = self::sendText($to, $msg, $cfg);
        } elseif ($testType === 'order_simulation') {
            $mockOrder = [
                'id'           => 0,
                'order_number' => 'ORD-TEST-' . rand(1000, 9999),
                'full_name'    => 'Tester Customer',
                'total_amount' => 649.00,
                'status'       => 'confirmed',
            ];
            $msg = self::buildEventMessage('order_confirmed', $mockOrder);
            if ($cfg['mode'] === 'test') {
                $msg = "🧪 [TEST SIMULATION]\n" . $msg;
            }
            $res = self::sendText($to, $msg, $cfg);
        } else {
            return ['ok' => false, 'error' => 'Unknown test type requested.'];
        }

        // Log test to notifications_log
        self::logNotification(
            null,
            Auth::id() ?: null,
            'whatsapp',
            'test_' . $testType,
            $to,
            $res['ok'] ? 'sent' : 'failed',
            $res['ok']
                ? ('Test message sent. Meta Message ID: ' . ($res['message_id'] ?? 'N/A') . ' [Mode: ' . $cfg['mode'] . ']')
                : ('Test failed: ' . ($res['error'] ?? 'Unknown error') . ' (Code: ' . ($res['error_code'] ?? 0) . ')')
        );

        $res['mode']      = $cfg['mode'];
        $res['recipient'] = $to;
        return $res;
    }

    /**
     * Dispatch an automated order event notification (placed, confirmed, shipped, delivered, cancelled).
     */
    public static function sendOrderEvent(int $orderId, string $eventType, ?array $user = null): bool
    {
        $cfg = self::getConfig();
        if (!$cfg['enabled']) {
            self::logNotification($orderId, $user['id'] ?? null, 'whatsapp', $eventType, $user['phone'] ?? 'unknown', 'skipped_disabled', 'WhatsApp notifications disabled in settings.');
            return false;
        }

        $order = Order::getById($orderId);
        if (!$order) {
            return false;
        }

        if (!$user && !empty($order['user_id'])) {
            $user = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$order['user_id']]);
        }

        $originalPhone = $user['phone'] ?? $order['ship_phone'] ?? '';
        $customerName  = $order['full_name'] ?? ($user['full_name'] ?? 'Customer');

        // Determine destination phone
        $recipientPhone = $originalPhone;
        $isSimulated    = false;

        if ($cfg['mode'] === 'test') {
            if ($cfg['test_force_recipient'] && !empty($cfg['test_recipient_number'])) {
                $recipientPhone = $cfg['test_recipient_number'];
                $isSimulated    = true;
            }
        }

        $cleanPhone = self::sanitizePhone($recipientPhone);
        if (empty($cleanPhone)) {
            self::logNotification(
                $orderId,
                $user['id'] ?? null,
                'whatsapp',
                $eventType,
                $originalPhone ?: 'none',
                'failed',
                'Missing or invalid recipient phone number.'
            );
            return false;
        }

        // Build message body
        $body = self::buildEventMessage($eventType, $order);
        if ($isSimulated) {
            $header = "🧪 *[WHATSAPP TEST MODE]*\n";
            $header .= "_(Simulated notification for customer: {$customerName}, Phone: {$originalPhone})_\n\n";
            $body = $header . $body;
        }

        // Send via Meta Cloud API
        $res = self::sendText($cleanPhone, $body, $cfg);

        $status = $res['ok'] ? 'sent' : 'failed';
        $logMsg = $res['ok']
            ? ('Delivered. Meta Message ID: ' . ($res['message_id'] ?? ''))
            : ('Failed (' . ($res['error_code'] ?? 0) . '): ' . ($res['error'] ?? ''));

        if ($isSimulated) {
            $logMsg .= ' [Redirected to test phone: ' . $cleanPhone . ']';
        }

        self::logNotification(
            $orderId,
            $user['id'] ?? null,
            'whatsapp',
            $eventType,
            $cleanPhone,
            $status,
            $logMsg
        );

        return $res['ok'];
    }

    /**
     * Build rich, formatted WhatsApp message copy for store events.
     */
    public static function buildEventMessage(string $eventType, array $order): string
    {
        $orderNumber = $order['order_number'] ?? ('#' . ($order['id'] ?? ''));
        $name        = $order['full_name'] ?? 'Valued Customer';
        $total       = number_format((float)($order['total_amount'] ?? 0), 2);
        $appUrl      = rtrim(defined('APP_URL') ? APP_URL : 'http://localhost', '/');

        switch ($eventType) {
            case 'order_placed':
                return "🍬 *Dabhi Chikki — Order Placed!*\n\n"
                    . "Hi *{$name}*, thank you for your order!\n\n"
                    . "📦 *Order Number:* `{$orderNumber}`\n"
                    . "💰 *Total Amount:* ₹{$total}\n\n"
                    . "We are preparing your handcrafted pure jaggery chikki with care.\n\n"
                    . "Track your order here:\n"
                    . "👉 {$appUrl}/track-order.php?order={$orderNumber}\n\n"
                    . "_Pure Taste Since 2009 • Dabhi Chikki, Rajkot_";

            case 'order_confirmed':
                return "✅ *Order Confirmed — Dabhi Chikki*\n\n"
                    . "Great news, *{$name}*! Your order *{$orderNumber}* has been confirmed and is scheduled for packing.\n\n"
                    . "💰 *Amount:* ₹{$total}\n\n"
                    . "Track live status:\n"
                    . "👉 {$appUrl}/track-order.php?order={$orderNumber}\n\n"
                    . "_Thank you for choosing pure taste!_";

            case 'order_shipped':
                $labelRow = !empty($order['id'])
                    ? Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$order['id']])
                    : null;
                $courier  = $labelRow['courier_name'] ?? 'Express Courier';
                $tracking = $labelRow['tracking_number'] ?? 'Assigned soon';

                return "🚚 *Your Order Is On The Way! — Dabhi Chikki*\n\n"
                    . "Hi *{$name}*, your chikki treats have been dispatched!\n\n"
                    . "📦 *Order Number:* `{$orderNumber}`\n"
                    . "🚛 *Courier:* {$courier}\n"
                    . "📍 *Tracking Number:* `{$tracking}`\n\n"
                    . "Track shipment live:\n"
                    . "👉 {$appUrl}/track-order.php?order={$orderNumber}\n\n"
                    . "_Freshly packed from Rajkot to your doorstep._";

            case 'order_delivered':
                return "🎉 *Delivered! Enjoy Your Chikki — Dabhi Chikki*\n\n"
                    . "Hi *{$name}*, order *{$orderNumber}* has been successfully delivered!\n\n"
                    . "We hope you enjoy the authentic taste of 100% pure jaggery chikki.\n\n"
                    . "Let us know what you think:\n"
                    . "👉 {$appUrl}/account/orders.php\n\n"
                    . "_Dabhi Chikki — Crafted with Pure Ingredients._";

            case 'order_cancelled':
                return "⚠️ *Order Cancelled — Dabhi Chikki*\n\n"
                    . "Hi *{$name}*, your order *{$orderNumber}* has been cancelled.\n\n"
                    . "If you made an online payment, a refund will be processed back to your original payment method in 5–7 business days.\n\n"
                    . "Questions? Contact support at orders@dabhichikki.com\n"
                    . "_Dabhi Chikki_";

            default:
                return "🔔 *Dabhi Chikki Update*\n\n"
                    . "Hi *{$name}*, there is an update on your order *{$orderNumber}*.\n"
                    . "View details: {$appUrl}/track-order.php?order={$orderNumber}";
        }
    }

    /**
     * Helper to write to `notifications_log` table.
     */
    private static function logNotification(
        ?int $orderId,
        ?int $userId,
        string $channel,
        string $eventType,
        string $recipient,
        string $status,
        string $message = ''
    ): void {
        try {
            Database::query(
                "INSERT INTO notifications_log (order_id, user_id, channel, event_type, recipient, status, response_message)
                 VALUES (?,?,?,?,?,?,?)",
                [$orderId, $userId, $channel, $eventType, $recipient, $status, $message]
            );
        } catch (\Throwable $e) {
            error_log('WhatsApp logNotification error: ' . $e->getMessage());
        }
    }
}
