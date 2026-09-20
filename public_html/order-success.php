<?php
/**
 * order-success.php — Order Confirmation Page
 */
$pageTitle = 'Order Confirmed! — Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';

$orderId  = (int)($_GET['id'] ?? 0);
$order    = null;
$orderNum = '';

try {
    if ($orderId) {
        $order    = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        $orderNum = $order['order_number'] ?? '';
    }
} catch (\Throwable $e) {
    $order = null;
}
?>

<section class="success-page">
  <div class="success-card">
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
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
