<?php
/**
 * order-success.php — Order Confirmation Page
 */
$pageTitle = 'Order Confirmed! — Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';

$orderId           = (int)($_GET['id'] ?? 0);
$orderNum          = trim($_GET['order_id'] ?? $_GET['order'] ?? '');
$order             = null;
$paymentFailed     = false;
$paymentFailureMsg = '';

try {
    if ($orderId > 0) {
        $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if ($order) $orderNum = $order['order_number'];
    } elseif ($orderNum !== '') {
        $order = Database::fetchOne('SELECT * FROM orders WHERE order_number = ?', [$orderNum]);
        if ($order) $orderId = (int)$order['id'];
    }
} catch (\Throwable $e) {
    $order = null;
}

// If customer is returning from Cashfree online payment and order is still pending, verify with Cashfree
if ($order && $order['payment_method'] === 'online' && $order['payment_status'] !== 'paid') {
    try {
        $cfCheck = CashfreeGateway::verifyOrderPayment($order['order_number']);
        if ($cfCheck['paid']) {
            $payId   = $cfCheck['payment_id'] ?: ('CF_' . bin2hex(random_bytes(6)));
            $rawJson = json_encode($cfCheck['details'] ?? ['verified_on_return' => true], JSON_UNESCAPED_UNICODE);

            Database::query(
                "UPDATE payments SET status = 'success', gateway_payment_id = ?, raw_response = ?, updated_at = NOW()
                 WHERE order_id = ? AND gateway = 'cashfree'",
                [$payId, $rawJson, $orderId]
            );

            Database::query(
                "UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?",
                [$orderId]
            );

            Database::query(
                "INSERT INTO order_status_history (order_id, status, remarks, changed_by) VALUES (?, 'confirmed', ?, ?)",
                [$orderId, 'Payment verified successfully via Cashfree (' . $payId . ')', $order['user_id']]
            );

            try {
                Invoice::getOrCreate($orderId);
            } catch (\Throwable $invE) {}

            try {
                Notification::trigger($orderId, 'order_confirmed');
            } catch (\Throwable $notifE) {}

            // Reload updated order
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        } else {
            // Payment failed, cancelled, or abandoned
            $paymentFailed     = true;
            $paymentFailureMsg = $cfCheck['error'] ?? 'Your payment attempt was cancelled or could not be completed.';

            // Automatically cancel order, restore FIFO inventory batches, release coupon, and restore items to active bag
            Order::cancelAndRecart($orderId, $paymentFailureMsg);

            // Reload order to reflect cancelled state
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        }
    } catch (\Throwable $cfErr) {
        error_log('Error verifying Cashfree order payment on return: ' . $cfErr->getMessage());
    }
}

// If viewing an already cancelled order that failed payment
if ($order && $order['status'] === 'cancelled' && ($order['payment_status'] === 'failed' || $order['payment_status'] === 'pending')) {
    $paymentFailed = true;
    if (empty($paymentFailureMsg)) {
        $lastHistory = Database::fetchOne(
            'SELECT remarks FROM order_status_history WHERE order_id = ? AND status = "cancelled" ORDER BY id DESC LIMIT 1',
            [$orderId]
        );
        $rawRemark = $lastHistory['remarks'] ?? 'Transaction was cancelled or declined.';
        $paymentFailureMsg = str_replace('Order cancelled due to Cashfree payment failure: ', '', $rawRemark);
    }
}
?>

<section class="success-page">
  <div class="success-card" <?= $paymentFailed ? 'style="border-top:4px solid #dc2626;"' : '' ?>>
    <?php if ($paymentFailed): ?>
      <div class="success-icon" style="background:rgba(239,68,68,0.12);color:#dc2626;border:2px solid rgba(239,68,68,0.25);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:36px;height:36px;">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </div>

      <div style="display:inline-block;padding:4px 14px;background:#fee2e2;color:#991b1b;border-radius:999px;font-size:0.75rem;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.75rem;">
        Payment Incomplete · Order Cancelled
      </div>

      <h1 style="font-size:1.875rem;margin-bottom:0.5rem;color:var(--text-color, #1e293b);">Payment Could Not Be Completed</h1>

      <!-- Cashfree Failure Reason Box -->
      <div style="background:#fff5f5;border:1.5px solid #fecaca;border-radius:12px;padding:1.1rem 1.35rem;margin:1.25rem auto 1.5rem;max-width:560px;text-align:left;">
        <div style="font-size:0.75rem;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.4rem;display:flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Reason Received from Cashfree
        </div>
        <div style="font-size:1rem;color:#7f1d1d;font-weight:600;line-height:1.5;">
          <?= htmlspecialchars($paymentFailureMsg) ?>
        </div>
      </div>

      <!-- Recart Reassurance Banner -->
      <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:1.1rem 1.35rem;margin:0 auto 1.5rem;max-width:560px;text-align:left;display:flex;gap:14px;align-items:flex-start;">
        <div style="font-size:1.75rem;line-height:1;">🛒</div>
        <div>
          <div style="font-size:1rem;font-weight:700;color:#166534;">
            Your bag has been automatically restored!
          </div>
          <p style="font-size:0.875rem;color:#15803d;margin:4px 0 0;line-height:1.45;">
            Order <strong>#<?= htmlspecialchars($order['order_number'] ?? '') ?></strong> was cancelled so you were not charged. All your products, selected weights, and quantities have been restored to your bag with stock preserved.
          </p>
        </div>
      </div>

      <?php if ($order): ?>
        <div style="background:var(--bg-alt, #f8fafc);border-radius:10px;padding:0.75rem 1.25rem;margin:0 auto 1.5rem;display:inline-flex;gap:20px;text-align:left;font-size:0.85rem;border:1px solid var(--border, #e2e8f0);">
          <div>
            <span style="color:var(--text-muted, #64748b);">Cancelled Order:</span>
            <strong style="margin-left:4px;">#<?= htmlspecialchars($order['order_number']) ?></strong>
          </div>
          <div>
            <span style="color:var(--text-muted, #64748b);">Amount:</span>
            <strong style="margin-left:4px;">₹<?= number_format((float)$order['total_amount'], 2) ?></strong>
          </div>
        </div>
      <?php endif; ?>

      <div style="display:flex;gap:0.875rem;justify-content:center;flex-wrap:wrap;margin-top:0.5rem;">
        <a href="checkout.php" class="btn btn-primary btn-lg" style="box-shadow:0 4px 14px rgba(84,31,33,0.3);">
          🛍️ Retry Checkout with Restored Bag
        </a>
        <a href="cart.php" class="btn btn-outline btn-lg">
          View Bag
        </a>
        <a href="index.php" class="btn btn-outline btn-lg">
          Back to Home
        </a>
      </div>
    <?php else: ?>
      <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>

    <?php if ($order): ?>
      <h1 style="font-size:1.875rem;margin-bottom:0.75rem">Order Confirmed! 🎉</h1>
      <p style="margin-bottom:0.5rem">Thank you for your order. We'll start preparing it right away!</p>
      <div style="background:var(--bg-alt);border-radius:var(--radius);padding:1rem 1.5rem;margin:1.25rem 0;display:inline-block">
        <div style="font-size:0.875rem;color:var(--text-muted)">Order Number</div>
        <div style="font-size:1.375rem;font-weight:800;color:var(--primary)">#<?= htmlspecialchars($order['order_number']) ?></div>
      </div>
      <p style="margin-bottom:0.25rem">
        <strong>Payment:</strong>
        <?= strtoupper(htmlspecialchars($order['payment_method'])) ?>
        (<?= ucfirst(htmlspecialchars($order['payment_status'])) ?>)
      </p>
      <p style="margin-bottom:0.25rem">
        <strong>Delivering to:</strong> <?= htmlspecialchars($order['ship_city']) ?>, <?= htmlspecialchars($order['ship_state']) ?>
      </p>
      <p style="margin-bottom:1.75rem">
        <strong>Estimated delivery:</strong> 4–7 business days
      </p>

      <!-- Order Timeline -->
      <div class="order-timeline" style="text-align:left;max-width:320px;margin:0 auto 2rem">
        <?php
        $steps = [
            ['label'=>'Order Placed','done'=>true,'active'=>false],
            ['label'=>'Confirmed','done'=>false,'active'=>true],
            ['label'=>'Processing','done'=>false,'active'=>false],
            ['label'=>'Dispatched','done'=>false,'active'=>false],
            ['label'=>'Delivered','done'=>false,'active'=>false],
        ];
        foreach ($steps as $i => $step):
        ?>
          <div class="timeline-step <?= $step['done'] ? 'done' : ($step['active'] ? 'active' : '') ?>">
            <div class="timeline-dot">
              <?php if ($step['done']): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
              <?php else: ?>
                <?= $i + 1 ?>
              <?php endif; ?>
            </div>
            <div class="timeline-content">
              <div class="timeline-label"><?= $step['label'] ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <h1 style="font-size:1.875rem;margin-bottom:0.75rem">Order Confirmed! 🎉</h1>
      <p style="margin-bottom:1.75rem">Thank you for shopping with Dabhi Chikki. Your order has been placed successfully!</p>
    <?php endif; ?>

    <div style="display:flex;gap:0.875rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem;">
      <?php if (!empty($order['order_number'])): ?>
        <a href="track.php?order=<?= urlencode($order['order_number']) ?>" class="btn btn-primary">📦 Track Order</a>
        <a href="invoice.php?order=<?= urlencode($order['order_number']) ?>" target="_blank" class="btn btn-outline">🧾 Tax Invoice</a>
      <?php endif; ?>
      <a href="account.php" class="btn btn-outline">My Orders</a>
      <a href="index.php" class="btn btn-outline">Continue Shopping</a>
    </div>
    <?php endif; // end !paymentFailed ?>
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
