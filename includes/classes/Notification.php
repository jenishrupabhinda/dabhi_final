<?php
/**
 * Notification — log and dispatch order event notifications.
 * Channels: email (mail()), whatsapp (stub), sms (stub).
 * Per-channel toggles read from the settings table.
 */
class Notification
{
    /** Main entry point: trigger all enabled channels for an order event. */
    public static function trigger(int $orderId, string $eventType): void
    {
        $order = Order::getById($orderId);
        if (!$order) return;

        $user = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$order['user_id']]);
        if (!$user) return;

        $templateMap = [
            'order_placed'    => ['subject' => 'Order Placed — ' . $order['order_number'], 'body' => self::bodyPlaced($order)],
            'order_confirmed' => ['subject' => 'Order Confirmed — ' . $order['order_number'], 'body' => self::bodyConfirmed($order)],
            'order_shipped'   => ['subject' => 'Your Order Is On the Way! — ' . $order['order_number'], 'body' => self::bodyShipped($order)],
            'order_delivered' => ['subject' => 'Delivered! — ' . $order['order_number'], 'body' => self::bodyDelivered($order)],
            'order_cancelled' => ['subject' => 'Order Cancelled — ' . $order['order_number'], 'body' => self::bodyCancelled($order)],
        ];

        if (!isset($templateMap[$eventType])) return;
        $tpl = $templateMap[$eventType];

        // Email
        if (getSetting('email_notifications_enabled', '1') === '1') {
            self::sendEmail($user['email'], $tpl['subject'], $tpl['body'], $orderId, $user['id'], $eventType);
        }

        // WhatsApp (stub — integrate BSP API here)
        if (getSetting('whatsapp_enabled', '0') === '1') {
            self::logChannel($orderId, $user['id'], 'whatsapp', $eventType, $user['phone'], 'skipped_disabled', 'WhatsApp integration pending.');
        }
    }

    private static function sendEmail(string $to, string $subject, string $htmlBody, int $orderId, int $userId, string $eventType): void
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Dabhi Chikki <noreply@" . parse_url(APP_URL, PHP_URL_HOST) . ">\r\n";

        $sent = @mail($to, $subject, $htmlBody, $headers);
        self::logChannel($orderId, $userId, 'email', $eventType, $to, $sent ? 'sent' : 'failed', $sent ? '' : 'mail() returned false');
    }

    private static function logChannel(int $orderId, int $userId, string $channel, string $eventType, string $recipient, string $status, string $message = ''): void
    {
        try {
            Database::query(
                "INSERT INTO notifications_log (order_id, user_id, channel, event_type, recipient, status, response_message)
                 VALUES (?,?,?,?,?,?,?)",
                [$orderId, $userId, $channel, $eventType, $recipient, $status, $message]
            );
        } catch (\Throwable $e) {
            error_log('Notification log error: ' . $e->getMessage());
        }
    }

    // ── Email body templates ──────────────────────────────────────────
    private static function wrap(string $content, string $title): string
    {
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
          body{font-family:Arial,sans-serif;color:#222;margin:0;padding:20px;background:#f9f5ef;}
          .box{max-width:540px;margin:auto;background:#fff;border-radius:12px;padding:32px;}
          .brand{font-size:22px;font-weight:bold;color:#C44D32;margin-bottom:20px;}
          .footer{font-size:11px;color:#888;margin-top:24px;}
          </style></head><body><div class='box'>
          <div class='brand'>🍬 Dabhi Chikki</div>
          <h2 style='margin-top:0;'>{$title}</h2>
          {$content}
          <div class='footer'>Thank you for shopping with Dabhi Chikki. This is an automated message, please do not reply.</div>
          </div></body></html>";
    }

    private static function bodyPlaced(array $o): string
    {
        return self::wrap("<p>Hi <strong>{$o['full_name']}</strong>, your order <strong>{$o['order_number']}</strong> has been placed successfully!</p>
          <p>Total: <strong>₹" . number_format($o['total_amount'], 2) . "</strong></p>
          <p><a href='" . APP_URL . "/account/order-detail.php?id={$o['id']}' style='background:#C44D32;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>View Order</a></p>",
          'Order Placed!'
        );
    }

    private static function bodyConfirmed(array $o): string
    {
        return self::wrap("<p>Great news, <strong>{$o['full_name']}</strong>! Your order <strong>{$o['order_number']}</strong> has been confirmed and is being prepared.</p>
          <p><a href='" . APP_URL . "/track-order.php?order={$o['order_number']}' style='background:#C44D32;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>Track Order</a></p>",
          'Order Confirmed ✅'
        );
    }

    private static function bodyShipped(array $o): string
    {
        $labelRow = Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$o['id']]);
        $tracking = $labelRow ? "<p>Tracking: <strong>{$labelRow['tracking_number']}</strong> via {$labelRow['courier_name']}</p>" : '';
        return self::wrap("<p>Hi <strong>{$o['full_name']}</strong>, your order <strong>{$o['order_number']}</strong> is on its way!</p>
          {$tracking}
          <p><a href='" . APP_URL . "/track-order.php?order={$o['order_number']}' style='background:#C44D32;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>Track Order</a></p>",
          'Your Order Is Shipped 🚚'
        );
    }

    private static function bodyDelivered(array $o): string
    {
        return self::wrap("<p>Hi <strong>{$o['full_name']}</strong>, your order <strong>{$o['order_number']}</strong> has been delivered! We hope you love it.</p>
          <p>Leave a review to help other customers.</p>
          <p><a href='" . APP_URL . "/account/orders.php' style='background:#C44D32;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>My Orders</a></p>",
          'Delivered! 🎉'
        );
    }

    private static function bodyCancelled(array $o): string
    {
        return self::wrap("<p>Hi <strong>{$o['full_name']}</strong>, your order <strong>{$o['order_number']}</strong> has been cancelled.</p>
          <p>If you paid online, a refund will be initiated within 5–7 business days.</p>",
          'Order Cancelled'
        );
    }
}
