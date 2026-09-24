<?php
/**
 * Notification — Log and dispatch rich order notifications.
 * Channels: Native authenticated SMTP email, WhatsApp (stub), SMS (stub).
 * Sends rich HTML receipts with full itemized breakdown, tax details, and tracking links.
 */
class Notification
{
    /** Main entry point: trigger all enabled channels for an order event. */
    public static function trigger(int $orderId, string $eventType): void
    {
        $order = Order::getById($orderId);
        if (!$order) return;

        $user = !empty($order['user_id'])
            ? Database::fetchOne('SELECT * FROM users WHERE id = ?', [$order['user_id']])
            : null;

        $customerEmail = !empty($order['guest_email'])
            ? trim($order['guest_email'])
            : (!empty($user['email']) ? trim($user['email']) : trim($order['email'] ?? ''));

        $customerName = !empty($order['ship_name'])
            ? trim($order['ship_name'])
            : (!empty($user['full_name']) ? trim($user['full_name']) : 'Valued Customer');

        $userId = !empty($order['user_id']) ? (int)$order['user_id'] : 0;

        // Subject resolution
        $subjectMap = [
            'order_placed'    => "Order Placed — #{$order['order_number']} | Dabhi Chikki",
            'order_confirmed' => "Order Confirmed! — #{$order['order_number']} | Dabhi Chikki",
            'order_shipped'   => "Your Order Is On the Way! — #{$order['order_number']} | Dabhi Chikki",
            'order_delivered' => "Delivered! Enjoy Your Chikki — #{$order['order_number']}",
            'order_cancelled' => "Order Cancelled — #{$order['order_number']}",
        ];

        $subject = $subjectMap[$eventType] ?? ("Order Update — #{$order['order_number']}");
        $htmlBody = self::renderEmailTemplate($order, $eventType, $customerName);

        // 1. Send to Customer if email is valid and not a synthetic guest domain
        $isSyntheticEmail = strpos($customerEmail, '@guest.dabhichikki.com') !== false;
        if (getSetting('email_notifications_enabled', '1') === '1' && !empty($customerEmail) && !$isSyntheticEmail) {
            self::sendEmail($customerEmail, $subject, $htmlBody, $orderId, $userId, $eventType);
        }

        // 2. Also send store admin copy for new order events
        if (in_array($eventType, ['order_placed', 'order_confirmed'])) {
            $adminEmail = getSetting('contact_email', '');
            if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $adminEmail = getSetting('smtp_from_email', 'orders@dabhichikki.com');
            }
            if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL) && strcasecmp($adminEmail, $customerEmail) !== 0) {
                $adminSubject = "[New Order Alert] #{$order['order_number']} — ₹" . number_format((float)$order['total_amount'], 2) . " (" . strtoupper($order['payment_method']) . ")";
                self::sendEmail($adminEmail, $adminSubject, $htmlBody, $orderId, $userId, 'admin_order_alert');
            }
        }

        // WhatsApp notification if enabled
        $waEnabled = (getSetting('whatsapp_enabled', '0') === '1' || getSetting('whatsapp_notifications_enabled', '0') === '1');
        if ($waEnabled && $user) {
            try {
                WhatsAppSender::sendOrderEvent($orderId, $eventType, $user);
            } catch (\Throwable $we) {
                error_log('WhatsApp dispatch error: ' . $we->getMessage());
            }
        }
    }

    private static function sendEmail(string $to, string $subject, string $htmlBody, int $orderId, int $userId, string $eventType): void
    {
        $res = EmailSender::send($to, $subject, $htmlBody);
        if (!$res['ok']) {
            // Fallback to basic mail() if SMTP connection fails
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $host     = parse_url(defined('APP_URL') ? APP_URL : 'http://localhost', PHP_URL_HOST) ?: 'localhost';
            $headers .= "From: Dabhi Chikki <noreply@{$host}>\r\n";
            $sent     = @mail($to, $subject, $htmlBody, $headers);
            $status   = $sent ? 'sent' : 'failed';
            $msg      = $sent ? 'Delivered via fallback mail()' : ('SMTP failed: ' . ($res['error'] ?? 'Unknown') . '; fallback mail() returned false');
            self::logChannel($orderId, $userId, 'email', $eventType, $to, $status, $msg);
            return;
        }

        self::logChannel($orderId, $userId, 'email', $eventType, $to, 'sent', $res['message'] ?? 'Delivered via SMTP');
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

    // ── Rich Branded Email Template Builder ───────────────────────────
    public static function renderEmailTemplate(array $order, string $eventType, string $customerName): string
    {
        $baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/dabhi_final/public_html';
        $trackUrl   = $baseUrl . '/track.php?order=' . urlencode($order['order_number']);
        $invoiceUrl = $baseUrl . '/invoice.php?order=' . urlencode($order['order_number']);

        $isPaid = ($order['payment_status'] === 'paid');
        $isCod  = ($order['payment_method'] === 'cod');

        $statusTitle = 'Order Placed Successfully!';
        $statusSub   = 'Thank you for choosing authentic Dabhi Chikki handcrafted with pure jaggery.';
        $badgeBg     = '#c7613d';
        $badgeText   = 'ORDER PLACED';

        if ($eventType === 'order_confirmed' || $isPaid) {
            $statusTitle = 'Order Confirmed & Preparing!';
            $statusSub   = 'Your payment was successfully received. Our master confectioners are preparing your fresh batch!';
            $badgeBg     = '#166534';
            $badgeText   = 'PAID & CONFIRMED';
        } elseif ($eventType === 'order_shipped') {
            $statusTitle = 'Your Fresh Chikki Is On The Way!';
            $statusSub   = 'Your package has been dispatched with our delivery partner.';
            $badgeBg     = '#1e40af';
            $badgeText   = 'DISPATCHED';
        } elseif ($eventType === 'order_delivered') {
            $statusTitle = 'Delivered With Love!';
            $statusSub   = 'Your order has been delivered. Enjoy the pure jaggery crunch!';
            $badgeBg     = '#047857';
            $badgeText   = 'DELIVERED';
        } elseif ($eventType === 'order_cancelled') {
            $statusTitle = 'Order Cancelled';
            $statusSub   = 'Your order has been cancelled. Any online payment will be refunded within 5-7 business days.';
            $badgeBg     = '#991b1b';
            $badgeText   = 'CANCELLED';
        }

        // Build item rows
        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $name    = htmlspecialchars($item['product_name_snapshot'] ?? $item['product_name'] ?? 'Chikki');
            $variant = htmlspecialchars($item['variant_label_snapshot'] ?? $item['variant_label'] ?? '');
            $qty     = (int)($item['quantity'] ?? 1);
            $price   = number_format((float)($item['unit_price'] ?? $item['selling_price'] ?? 0), 2);
            $total   = number_format((float)($item['line_total'] ?? ((float)($item['unit_price'] ?? 0) * $qty)), 2);

            $itemsHtml .= "
            <tr>
              <td style='padding:12px 8px;border-bottom:1px solid #f1ece4;vertical-align:middle;'>
                <div style='font-weight:600;color:#2b1311;font-size:14px;'>{$name}</div>
                " . (!empty($variant) ? "<div style='font-size:12px;color:#7a6b67;'>Pack: {$variant}</div>" : '') . "
              </td>
              <td style='padding:12px 8px;border-bottom:1px solid #f1ece4;text-align:center;color:#4a3f3d;font-size:14px;vertical-align:middle;'>
                {$qty}
              </td>
              <td style='padding:12px 8px;border-bottom:1px solid #f1ece4;text-align:right;color:#4a3f3d;font-size:14px;vertical-align:middle;'>
                ₹{$price}
              </td>
              <td style='padding:12px 8px;border-bottom:1px solid #f1ece4;text-align:right;font-weight:700;color:#2b1311;font-size:14px;vertical-align:middle;'>
                ₹{$total}
              </td>
            </tr>";
        }

        // Financial calculations
        $subtotal = number_format((float)($order['subtotal'] ?? 0), 2);
        $discount = (float)($order['discount_amount'] ?? 0);
        $shipping = (float)($order['shipping_charge'] ?? 0);
        $codFee   = (float)($order['cod_charge'] ?? 0);
        $gstAmt   = (float)($order['gst_amount'] ?? 0);
        if ($gstAmt <= 0) {
            $gstAmt = (float)($order['cgst_amount'] ?? 0) + (float)($order['sgst_amount'] ?? 0) + (float)($order['igst_amount'] ?? 0);
        }
        $grandTotal = number_format((float)($order['total_amount'] ?? $order['total'] ?? 0), 2);

        $discountRow = $discount > 0
            ? "<tr><td style='padding:6px 0;color:#166534;'>Coupon Discount:</td><td style='padding:6px 0;text-align:right;color:#166534;font-weight:600;'>−₹" . number_format($discount, 2) . "</td></tr>"
            : "";

        $shippingRow = $shipping > 0
            ? "<tr><td style='padding:6px 0;color:#7a6b67;'>Shipping Charges:</td><td style='padding:6px 0;text-align:right;color:#2b1311;font-weight:600;'>₹" . number_format($shipping, 2) . "</td></tr>"
            : "<tr><td style='padding:6px 0;color:#7a6b67;'>Shipping Charges:</td><td style='padding:6px 0;text-align:right;color:#166534;font-weight:600;'>FREE</td></tr>";

        $codRow = ($isCod && $codFee > 0)
            ? "<tr><td style='padding:6px 0;color:#7a6b67;'>COD Handling Fee:</td><td style='padding:6px 0;text-align:right;color:#2b1311;font-weight:600;'>₹" . number_format($codFee, 2) . "</td></tr>"
            : "";

        $gstRow = $gstAmt > 0
            ? "<tr><td style='padding:6px 0;color:#7a6b67;'>Included GST (5%):</td><td style='padding:6px 0;text-align:right;color:#2b1311;font-weight:600;'>₹" . number_format($gstAmt, 2) . "</td></tr>"
            : "";

        // Shipping Address
        $addrLine1 = htmlspecialchars($order['ship_line1'] ?? '');
        $addrLine2 = htmlspecialchars($order['ship_line2'] ?? '');
        $cityState = htmlspecialchars(($order['ship_city'] ?? '') . ', ' . ($order['ship_state'] ?? '') . ' — ' . ($order['ship_pincode'] ?? ''));
        $shipPhone = htmlspecialchars($order['ship_phone'] ?? '');

        // Payment info label
        $payLabel = $isCod ? 'Cash on Delivery (COD)' : 'Pay Online (Cashfree / Cards / UPI)';
        $payStatusText = $isPaid ? 'Paid & Verified' : 'Pending (Pay upon arrival)';

        return "<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>{$statusTitle}</title>
</head>
<body style='margin:0;padding:0;background-color:#faf7f2;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;color:#2b1311;line-height:1.5;'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#faf7f2;padding:24px 12px;'>
    <tr>
      <td align='center'>
        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(84,31,33,0.08);border:1px solid #f2e9dc;'>
          
          <!-- Header Banner -->
          <tr>
            <td style='background-color:#541f21;padding:28px 24px;text-align:center;'>
              <div style='color:#f6dc94;font-size:24px;font-weight:800;letter-spacing:1px;text-transform:uppercase;'>🍬 DABHI CHIKKI</div>
              <div style='color:rgba(246,220,148,0.85);font-size:12px;margin-top:4px;letter-spacing:0.5px;'>Authentic Handmade Chikki with Pure Jaggery Since 2009 • Rajkot</div>
            </td>
          </tr>

          <!-- Status Highlight -->
          <tr>
            <td style='padding:28px 28px 12px 28px;text-align:center;'>
              <div style='display:inline-block;background:{$badgeBg};color:#ffffff;font-size:11px;font-weight:700;letter-spacing:1px;padding:6px 14px;border-radius:20px;margin-bottom:12px;'>
                {$badgeText}
              </div>
              <h1 style='margin:0;font-size:22px;color:#2b1311;font-weight:800;'>{$statusTitle}</h1>
              <p style='margin:8px 0 0 0;font-size:14px;color:#7a6b67;'>{$statusSub}</p>
            </td>
          </tr>

          <!-- Order Summary Badge -->
          <tr>
            <td style='padding:12px 28px;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#fdfaf6;border:1px dashed #e5d8c8;border-radius:12px;padding:16px;'>
                <tr>
                  <td width='50%' style='padding:6px 8px;vertical-align:top;'>
                    <div style='font-size:11px;color:#7a6b67;text-transform:uppercase;font-weight:600;'>Order Number</div>
                    <div style='font-size:16px;font-weight:800;color:#541f21;margin-top:2px;'>#{$order['order_number']}</div>
                  </td>
                  <td width='50%' style='padding:6px 8px;vertical-align:top;'>
                    <div style='font-size:11px;color:#7a6b67;text-transform:uppercase;font-weight:600;'>Placed On</div>
                    <div style='font-size:14px;font-weight:600;color:#2b1311;margin-top:2px;'>" . date('d M Y, h:i A') . "</div>
                  </td>
                </tr>
                <tr>
                  <td width='50%' style='padding:6px 8px;vertical-align:top;'>
                    <div style='font-size:11px;color:#7a6b67;text-transform:uppercase;font-weight:600;'>Payment Method</div>
                    <div style='font-size:13px;font-weight:600;color:#2b1311;margin-top:2px;'>{$payLabel}</div>
                  </td>
                  <td width='50%' style='padding:6px 8px;vertical-align:top;'>
                    <div style='font-size:11px;color:#7a6b67;text-transform:uppercase;font-weight:600;'>Payment Status</div>
                    <div style='font-size:13px;font-weight:700;color:" . ($isPaid ? '#166534' : '#c7613d') . ";margin-top:2px;'>{$payStatusText}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Delivery Address & Recipient -->
          <tr>
            <td style='padding:12px 28px;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#f9f5ef;border-radius:12px;padding:14px 16px;'>
                <tr>
                  <td>
                    <div style='font-size:12px;font-weight:700;color:#541f21;text-transform:uppercase;'>📦 Delivery Address</div>
                    <div style='font-weight:700;color:#2b1311;font-size:14px;margin-top:4px;'>{$customerName} &nbsp;·&nbsp; <span style='font-weight:400;color:#555;'>{$shipPhone}</span></div>
                    <div style='font-size:13px;color:#555;margin-top:2px;'>{$addrLine1}</div>
                    " . (!empty($addrLine2) ? "<div style='font-size:13px;color:#555;'>{$addrLine2}</div>" : '') . "
                    <div style='font-size:13px;color:#555;'>{$cityState}</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Items Table -->
          <tr>
            <td style='padding:16px 28px 8px 28px;'>
              <div style='font-size:14px;font-weight:700;color:#2b1311;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;'>Items In Your Order</div>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;'>
                <thead>
                  <tr style='background:#f9f5ef;'>
                    <th align='left' style='padding:8px;font-size:12px;color:#7a6b67;text-transform:uppercase;'>Item</th>
                    <th align='center' style='padding:8px;font-size:12px;color:#7a6b67;text-transform:uppercase;'>Qty</th>
                    <th align='right' style='padding:8px;font-size:12px;color:#7a6b67;text-transform:uppercase;'>Price</th>
                    <th align='right' style='padding:8px;font-size:12px;color:#7a6b67;text-transform:uppercase;'>Total</th>
                  </tr>
                </thead>
                <tbody>
                  {$itemsHtml}
                </tbody>
              </table>
            </td>
          </tr>

          <!-- Financial Breakdown -->
          <tr>
            <td style='padding:8px 28px 20px 28px;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='font-size:13px;'>
                <tr>
                  <td style='padding:6px 0;color:#7a6b67;'>Subtotal:</td>
                  <td style='padding:6px 0;text-align:right;color:#2b1311;font-weight:600;'>₹{$subtotal}</td>
                </tr>
                {$discountRow}
                {$shippingRow}
                {$codRow}
                {$gstRow}
                <tr>
                  <td style='padding:12px 0 6px 0;font-size:16px;font-weight:800;color:#541f21;border-top:2px solid #541f21;'>Grand Total:</td>
                  <td style='padding:12px 0 6px 0;font-size:18px;font-weight:800;color:#541f21;text-align:right;border-top:2px solid #541f21;'>₹{$grandTotal}</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- CTA Buttons -->
          <tr>
            <td style='padding:12px 28px 28px 28px;text-align:center;'>
              <table role='presentation' cellpadding='0' cellspacing='0' style='margin:0 auto;'>
                <tr>
                  <td style='padding:0 6px;'>
                    <a href='{$trackUrl}' target='_blank' style='display:inline-block;background-color:#541f21;color:#ffffff;text-decoration:none;font-size:13px;font-weight:700;padding:12px 22px;border-radius:8px;'>
                      📦 Track Your Order
                    </a>
                  </td>
                  <td style='padding:0 6px;'>
                    <a href='{$invoiceUrl}' target='_blank' style='display:inline-block;background-color:#ffffff;color:#541f21;border:1.5px solid #541f21;text-decoration:none;font-size:13px;font-weight:700;padding:10.5px 20px;border-radius:8px;'>
                      🧾 View Tax Invoice
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer Information -->
          <tr>
            <td style='background-color:#f9f5ef;padding:24px 28px;text-align:center;border-top:1px solid #f1ece4;'>
              <div style='font-size:13px;font-weight:700;color:#2b1311;'>Dabhi Chikki — 100% Handcrafted with Pure Jaggery</div>
              <div style='font-size:12px;color:#7a6b67;margin-top:4px;'>Rajkot, Gujarat • Since 2009</div>
              <div style='font-size:11px;color:#998d89;margin-top:12px;'>
                Need help with your order? Reply directly to this email or contact us at <a href='mailto:orders@dabhichikki.com' style='color:#541f21;font-weight:600;'>orders@dabhichikki.com</a>.
              </div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>";
    }
}
