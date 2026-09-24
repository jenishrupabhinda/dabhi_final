<?php
/**
 * EmailSender — Native SMTP transport for PHP without external dependencies.
 * Supports:
 *  - Authenticated SMTP (AUTH LOGIN)
 *  - STARTTLS (e.g. Port 587) and Direct SSL (e.g. Port 465)
 *  - Plain text and rich HTML emails with UTF-8 MIME encoding
 *  - Full protocol logging (transcript) for real-time diagnostic testing
 *  - Actionable troubleshooting tips for common SMTP errors (Gmail, Mailtrap, Outlook)
 *  - Audit logging to `notifications_log`
 */
class EmailSender
{
    /**
     * Resolve SMTP configuration from database settings, falling back to config.php constants.
     */
    public static function getConfig(?array $overrides = null): array
    {
        $enabled = (getSetting('email_notifications_enabled', '1') === '1');

        $host       = getSetting('smtp_host', defined('SMTP_HOST') ? SMTP_HOST : 'localhost');
        $port       = (int)getSetting('smtp_port', defined('SMTP_PORT') ? (string)SMTP_PORT : '587');
        $user       = getSetting('smtp_user', defined('SMTP_USER') ? SMTP_USER : '');
        $pass       = getSetting('smtp_pass', defined('SMTP_PASS') ? SMTP_PASS : '');
        $fromEmail  = getSetting('smtp_from_email', defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'orders@dabhichikki.com');
        $fromName   = getSetting('smtp_from_name', defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Dabhi Chikki');
        $encryption = getSetting('smtp_encryption', defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls');

        // Auto-detect encryption if set to auto
        if ($encryption === 'auto' || empty($encryption)) {
            $encryption = ($port === 465) ? 'ssl' : (($port === 587) ? 'tls' : 'none');
        }

        $cfg = [
            'enabled'    => $enabled,
            'host'       => trim((string)$host),
            'port'       => $port,
            'user'       => trim((string)$user),
            'pass'       => (string)$pass,
            'from_email' => trim((string)$fromEmail),
            'from_name'  => trim((string)$fromName),
            'encryption' => strtolower(trim((string)$encryption)),
            'timeout'    => 15,
        ];

        if ($overrides) {
            $cfg = array_merge($cfg, $overrides);
            $cfg['port'] = (int)$cfg['port'];
        }

        return $cfg;
    }

    /**
     * Send an email via SMTP.
     * Returns ['ok' => bool, 'message' => string, 'error' => string, 'transcript' => array, 'help' => string]
     */
    public static function send(string $toEmail, string $subject, string $htmlBody, ?array $config = null): array
    {
        $cfg        = $config ? self::getConfig($config) : self::getConfig();
        $transcript = [];
        $toEmail    = trim($toEmail);

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok'         => false,
                'error'      => 'Invalid recipient email address: ' . htmlspecialchars($toEmail),
                'transcript' => ['CLIENT: Invalid recipient format'],
                'help'       => 'Please provide a valid email address (e.g. user@example.com).',
            ];
        }

        if (empty($cfg['host'])) {
            return [
                'ok'         => false,
                'error'      => 'SMTP Host is missing.',
                'transcript' => ['CLIENT: SMTP Host not specified'],
                'help'       => 'Enter your SMTP server hostname (e.g. smtp.gmail.com, smtp.mailtrap.io).',
            ];
        }

        // Determine socket protocol
        $scheme = ($cfg['encryption'] === 'ssl' || $cfg['port'] === 465) ? 'ssl://' : 'tcp://';
        $remote = $scheme . $cfg['host'] . ':' . $cfg['port'];

        $contextOptions = [
            'ssl' => [
                'verify_peer'       => defined('APP_ENV') && APP_ENV === 'development' ? false : true,
                'verify_peer_name'  => defined('APP_ENV') && APP_ENV === 'development' ? false : true,
                'allow_self_signed' => true,
            ],
        ];

        // Discover local CA bundles on Windows/WAMP
        $knownCaBundles = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:\\wamp64\\apps\\phpmyadmin5.2.1\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
        ];
        foreach ($knownCaBundles as $ca) {
            if (!empty($ca) && file_exists($ca)) {
                $contextOptions['ssl']['cafile'] = $ca;
                break;
            }
        }

        $context = stream_context_create($contextOptions);

        $transcript[] = "CLIENT: Connecting to {$remote} (timeout {$cfg['timeout']}s)...";
        $socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            (float)$cfg['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $err = "Failed to connect to {$remote}: [{$errno}] {$errstr}";
            $transcript[] = "ERROR: {$err}";
            return [
                'ok'         => false,
                'error'      => $err,
                'transcript' => $transcript,
                'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
            ];
        }

        stream_set_timeout($socket, $cfg['timeout']);

        // Helper closures for SMTP communication
        $readResponse = function () use ($socket, &$transcript): string {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            $clean = trim($response);
            $transcript[] = "SERVER: {$clean}";
            return $clean;
        };

        $sendCommand = function (string $cmd, bool $mask = false) use ($socket, &$transcript, $readResponse): string {
            $transcript[] = "CLIENT: " . ($mask ? '••••••••' : $cmd);
            fputs($socket, $cmd . "\r\n");
            return $readResponse();
        };

        // 1. Initial greeting
        $greeting = $readResponse();
        if (!str_starts_with($greeting, '220')) {
            fclose($socket);
            $err = "Unexpected server greeting: {$greeting}";
            return [
                'ok'         => false,
                'error'      => $err,
                'transcript' => $transcript,
                'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
            ];
        }

        // 2. EHLO
        $clientHost = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $ehloRes    = $sendCommand("EHLO {$clientHost}");
        if (!str_starts_with($ehloRes, '250')) {
            $ehloRes = $sendCommand("HELO {$clientHost}");
        }

        // 3. STARTTLS if required and supported
        if ($cfg['encryption'] === 'tls' && $cfg['port'] !== 465) {
            $starttlsRes = $sendCommand('STARTTLS');
            if (str_starts_with($starttlsRes, '220')) {
                $crypto = @stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                );
                if (!$crypto) {
                    fclose($socket);
                    $err = 'STARTTLS cryptographic handshake negotiation failed.';
                    $transcript[] = "ERROR: {$err}";
                    return [
                        'ok'         => false,
                        'error'      => $err,
                        'transcript' => $transcript,
                        'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
                    ];
                }
                $transcript[] = 'CLIENT: TLS encryption established successfully.';
                // Re-send EHLO over encrypted channel
                $sendCommand("EHLO {$clientHost}");
            }
        }

        // 4. Authenticate if username is provided
        if (!empty($cfg['user'])) {
            $authRes = $sendCommand('AUTH LOGIN');
            if (str_starts_with($authRes, '334')) {
                // Send Base64 Username
                $userRes = $sendCommand(base64_encode($cfg['user']));
                if (!str_starts_with($userRes, '334')) {
                    fclose($socket);
                    $err = "SMTP rejected username: {$userRes}";
                    return [
                        'ok'         => false,
                        'error'      => $err,
                        'transcript' => $transcript,
                        'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
                    ];
                }

                // Send Base64 Password
                $passRes = $sendCommand(base64_encode($cfg['pass']), true);
                if (!str_starts_with($passRes, '235')) {
                    fclose($socket);
                    $err = "SMTP authentication failed: {$passRes}";
                    return [
                        'ok'         => false,
                        'error'      => $err,
                        'transcript' => $transcript,
                        'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
                    ];
                }
                $transcript[] = 'CLIENT: Authentication succeeded (235).';
            }
        }

        // 5. MAIL FROM
        $fromEmail = !empty($cfg['from_email']) ? $cfg['from_email'] : $cfg['user'];
        $fromRes   = $sendCommand("MAIL FROM:<{$fromEmail}>");
        if (!str_starts_with($fromRes, '250')) {
            fclose($socket);
            $err = "MAIL FROM rejected: {$fromRes}";
            return [
                'ok'         => false,
                'error'      => $err,
                'transcript' => $transcript,
                'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
            ];
        }

        // 6. RCPT TO
        $rcptRes = $sendCommand("RCPT TO:<{$toEmail}>");
        if (!str_starts_with($rcptRes, '250') && !str_starts_with($rcptRes, '251')) {
            fclose($socket);
            $err = "Recipient rejected by server: {$rcptRes}";
            return [
                'ok'         => false,
                'error'      => $err,
                'transcript' => $transcript,
                'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
            ];
        }

        // 7. DATA
        $dataRes = $sendCommand('DATA');
        if (!str_starts_with($dataRes, '354')) {
            fclose($socket);
            $err = "DATA initiation rejected: {$dataRes}";
            return [
                'ok'         => false,
                'error'      => $err,
                'transcript' => $transcript,
                'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
            ];
        }

        // 8. Build message payload
        $fromName  = !empty($cfg['from_name']) ? $cfg['from_name'] : 'Dabhi Chikki';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $messageId = sprintf('<%s.%s@%s>', time(), bin2hex(random_bytes(6)), parse_url(defined('APP_URL') ? APP_URL : 'http://localhost', PHP_URL_HOST) ?: 'localhost');

        $headers = [
            "Date: " . date('r'),
            "To: <{$toEmail}>",
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
            "Subject: {$encodedSubject}",
            "Message-ID: {$messageId}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "X-Mailer: DabhiChikki SMTP Mailer/1.0",
        ];

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";

        $transcript[] = "CLIENT: Sending message data (" . strlen($payload) . " bytes)...";
        fputs($socket, $payload . "\r\n");
        $sendRes = $readResponse();

        // 9. QUIT
        $sendCommand('QUIT');
        fclose($socket);

        if (str_starts_with($sendRes, '250')) {
            return [
                'ok'         => true,
                'message'    => 'Email delivered successfully to SMTP server. (' . $sendRes . ')',
                'transcript' => $transcript,
                'message_id' => $messageId,
            ];
        }

        $err = "Server rejected message body: {$sendRes}";
        return [
            'ok'         => false,
            'error'      => $err,
            'transcript' => $transcript,
            'help'       => self::getTroubleshootingTip($err, $transcript, $cfg),
        ];
    }

    /**
     * Run a test email transmission from the Admin Testing Console.
     */
    public static function testConnection(
        string $toEmail,
        string $testType = 'ping',
        ?string $customBody = null,
        ?array $overrideConfig = null
    ): array {
        $cfg = self::getConfig($overrideConfig);
        $to  = trim($toEmail);

        if (empty($to)) {
            return [
                'ok'         => false,
                'error'      => 'Please provide a valid destination email address.',
                'transcript' => ['CLIENT: Missing destination email'],
            ];
        }

        if ($testType === 'order_simulation') {
            $subject  = 'Order Confirmed — ORD-TEST-7789 | Dabhi Chikki';
            $htmlBody = self::buildSampleOrderEmail();
        } else {
            $subject  = '⚡ Dabhi Chikki SMTP Connection Test — ' . date('d M Y, g:i A');
            $hostInfo = htmlspecialchars($cfg['host'] . ':' . $cfg['port']);
            $userMask = htmlspecialchars(!empty($cfg['user']) ? substr($cfg['user'], 0, 3) . '***@' . substr(strstr($cfg['user'], '@') ?: 'host', 1) : 'None');
            $htmlBody = "
              <div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px;border:1px solid #E5E7EB;border-radius:12px;background:#fff;'>
                <div style='font-size:22px;font-weight:bold;color:#C44D32;margin-bottom:12px;'>🍬 Dabhi Chikki</div>
                <h3 style='color:#111827;margin-top:0;'>SMTP Email Service Connected! ✅</h3>
                <p style='color:#374151;font-size:15px;line-height:1.5;'>
                  This test message confirms that your SMTP server configuration is working correctly and capable of delivering automated transactional emails.
                </p>
                <div style='background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:14px;margin:18px 0;font-size:13px;color:#4B5563;'>
                  <div><strong>SMTP Host:</strong> {$hostInfo}</div>
                  <div><strong>Encryption:</strong> " . strtoupper($cfg['encryption']) . "</div>
                  <div><strong>Authenticated User:</strong> {$userMask}</div>
                  <div><strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "</div>
                </div>
                <p style='color:#6B7280;font-size:12px;margin-top:20px;'>
                  Automated verification email sent from Dabhi Chikki Admin Console.
                </p>
              </div>
            ";
        }

        $res = self::send($to, $subject, $htmlBody, $cfg);

        // Record in notifications_log
        self::logNotification(
            null,
            Auth::id() ?: null,
            'email',
            'test_' . $testType,
            $to,
            $res['ok'] ? 'sent' : 'failed',
            $res['ok']
                ? ('SMTP test successful. Message-ID: ' . ($res['message_id'] ?? 'N/A'))
                : ('SMTP test failed: ' . ($res['error'] ?? 'Unknown error'))
        );

        $res['recipient'] = $to;
        return $res;
    }

    /**
     * Provide actionable troubleshooting tips for common SMTP errors.
     */
    public static function getTroubleshootingTip(string $error, array $transcript, array $cfg): string
    {
        $errLower = strtolower($error . ' ' . implode(' ', $transcript));

        if (str_contains($errLower, '535') || str_contains($errLower, 'authentication failed') || str_contains($errLower, 'bad credentials')) {
            if (str_contains(strtolower($cfg['host']), 'gmail')) {
                return '⚠️ Gmail Authentication Failed: Google requires an App Password instead of your regular password. Enable 2-Step Verification on your Google Account, then go to Security > App Passwords, generate a 16-character password, and paste it in SMTP Password.';
            }
            if (str_contains(strtolower($cfg['host']), 'mailtrap')) {
                return '⚠️ Mailtrap Authentication Failed: Verify your sandbox inbox username and password from your Mailtrap.io dashboard.';
            }
            return '⚠️ Authentication Failed: Check your SMTP Username and Password. Ensure your email provider allows third-party SMTP access.';
        }

        if (str_contains($errLower, 'connection refused') || str_contains($errLower, '10061')) {
            return '⚠️ Connection Refused: The SMTP server refused the connection on port ' . $cfg['port'] . '. Verify the hostname (' . htmlspecialchars($cfg['host']) . ') and ensure the port is open (typically 587 for TLS, 465 for SSL, or 2525).';
        }

        if (str_contains($errLower, 'timed out') || str_contains($errLower, 'timeout')) {
            return '⚠️ Connection Timed Out: The server took longer than ' . $cfg['timeout'] . ' seconds to respond. Check your server network/firewall settings or try a different port (e.g. 587 or 2525).';
        }

        if (str_contains($errLower, 'starttls') || str_contains($errLower, 'crypto')) {
            return '⚠️ TLS Negotiation Failed: The server could not establish a TLS handshake. If using port 465, switch Encryption to SSL. If using port 587, ensure STARTTLS is supported.';
        }

        if (str_contains($errLower, '550') || str_contains($errLower, 'relay access denied') || str_contains($errLower, 'mailbox unavailable')) {
            return '⚠️ Relaying Denied / Invalid Sender: Ensure the "From Email" address matches the authenticated SMTP user account, as many providers (like Gmail and Zoho) reject emails with mismatched sender headers.';
        }

        return 'Verify your SMTP credentials, port, and security settings with your mail hosting provider.';
    }

    /**
     * Sample branded HTML order email for realistic preview testing.
     */
    public static function buildSampleOrderEmail(): string
    {
        $appUrl = rtrim(defined('APP_URL') ? APP_URL : 'http://localhost', '/');
        return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#2D2D2D;margin:0;padding:24px;background:#F9F5EF;}
.box{max-width:540px;margin:auto;background:#fff;border-radius:14px;padding:32px;border:1px solid #EFE8DE;box-shadow:0 4px 14px rgba(0,0,0,0.03);}
.brand{font-size:24px;font-weight:800;color:#C44D32;margin-bottom:20px;letter-spacing:-0.5px;}
.badge{display:inline-block;background:#EBF7EE;color:#1B873F;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;margin-bottom:16px;}
.btn{display:inline-block;background:#C44D32;color:#ffffff !important;font-weight:600;padding:12px 24px;border-radius:8px;text-decoration:none;margin-top:20px;}
.table{width:100%;border-collapse:collapse;margin:18px 0;font-size:14px;}
.table th{text-align:left;color:#888;padding-bottom:8px;font-size:12px;text-transform:uppercase;}
.table td{padding:10px 0;border-top:1px solid #F3EDE3;}
.footer{font-size:12px;color:#888;margin-top:28px;border-top:1px solid #F3EDE3;padding-top:16px;line-height:1.5;}
</style></head>
<body>
  <div class='box'>
    <div class='brand'>🍬 Dabhi Chikki</div>
    <div class='badge'>ORDER CONFIRMED</div>
    <h2 style='margin-top:0;font-size:20px;'>Thank you for your order, Tester!</h2>
    <p style='color:#555;font-size:14px;line-height:1.6;'>
      Your handcrafted pure jaggery chikki order <strong>ORD-TEST-7789</strong> has been confirmed and is scheduled for small-batch preparation.
    </p>

    <table class='table'>
      <thead>
        <tr><th>Item</th><th>Qty</th><th style='text-align:right;'>Price</th></tr>
      </thead>
      <tbody>
        <tr><td>Pure Jaggery Peanut Chikki (400g)</td><td>2</td><td style='text-align:right;'>₹380.00</td></tr>
        <tr><td>Roasted Sesame Til Chikki (250g)</td><td>1</td><td style='text-align:right;'>₹169.00</td></tr>
        <tr><td><strong>Total Amount</strong></td><td></td><td style='text-align:right;'><strong>₹549.00</strong></td></tr>
      </tbody>
    </table>

    <div style='text-align:center;'>
      <a href='{$appUrl}/track-order.php?order=ORD-TEST-7789' class='btn'>Track Your Order</a>
    </div>

    <div class='footer'>
      Handcrafted with 100% pure jaggery in Rajkot since 2009.<br>
      Zero refined sugar. Zero preservatives.
    </div>
  </div>
</body>
</html>";
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
            error_log('Email logNotification error: ' . $e->getMessage());
        }
    }
}
