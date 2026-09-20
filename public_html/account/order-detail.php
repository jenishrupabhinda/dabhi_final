<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');

$userId  = Auth::id();
$orderId = (int)get('id');
$order   = Order::getById($orderId, $userId);
if (!$order) { redirect('/account/orders.php'); }

// Handle cancel
if (isPost() && post('action') === 'cancel') {
    csrfVerify();
    $result = Order::cancel($orderId, $userId);
    if ($result['ok']) {
        flashSet('success', 'Order cancelled successfully.');
    } else {
        flashSet('error', $result['error']);
    }
    redirect('/account/order-detail.php?id=' . $orderId);
}

$invoice = Database::fetchOne('SELECT * FROM invoices WHERE order_id = ?', [$orderId]);
$label   = Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$orderId]);

$pageTitle = 'Order ' . $order['order_number'];
$pageDesc  = 'View order details and tracking.';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--dc-space-xl);padding-bottom:var(--dc-space-xl);max-width:760px;">

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="<?= url('account/orders.php') ?>" class="btn btn-ghost btn-sm">← My Orders</a>
    <h1 style="margin:0;"><?= e($order['order_number']) ?></h1>
  </div>

  <?php flashRender(); ?>

  <!-- Status banner -->
  <div class="card text-center" style="padding:24px;margin-bottom:20px;">
    <div style="font-size:0.8rem;color:var(--dc-muted);">Current Status</div>
    <div style="font-size:1.4rem;font-weight:800;text-transform:capitalize;margin-top:4px;">
      <?= ucwords(str_replace('_',' ',$order['status'])) ?>
    </div>
    <?php if ($label && $label['tracking_number']): ?>
    <div style="margin-top:10px;font-size:0.85rem;color:var(--dc-muted);">
      Tracking: <strong><?= e($label['tracking_number']) ?></strong> via <?= e($label['courier_name'] ?? '') ?>
    </div>
    <?php endif; ?>
    <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;flex-wrap:wrap;">
      <a href="<?= url('track-order.php?order=' . urlencode($order['order_number'])) ?>" class="btn btn-secondary btn-sm">📦 Track Order</a>
      <?php if ($invoice): ?>
      <a href="<?= url('account/invoice.php?order_id=' . (int)$orderId) ?>" target="_blank" class="btn btn-ghost btn-sm">🧾 Download Invoice</a>
      <?php endif; ?>
      <?php if (in_array($order['status'], ['placed','confirmed'])): ?>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="cancel">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--dc-danger);"
          onclick="return confirm('Are you sure you want to cancel this order?')">✕ Cancel Order</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3 class="card-title">Items</h3></div>
    <?php foreach ($order['items'] as $item): ?>
    <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--dc-border);font-size:0.88rem;">
      <span><?= e($item['product_name_snapshot']) ?> — <?= e($item['variant_label_snapshot']) ?> ×<?= $item['quantity'] ?></span>
      <strong><?= formatINR((float)$item['line_total']) ?></strong>
    </div>
    <?php endforeach; ?>
    <!-- Totals -->
    <div style="margin-top:10px;display:flex;flex-direction:column;gap:4px;font-size:0.85rem;">
      <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span><?= formatINR($order['subtotal']) ?></span></div>
      <?php if ((float)$order['discount_amount'] > 0): ?>
      <div style="display:flex;justify-content:space-between;color:var(--dc-success);"><span>Discount</span><span>−<?= formatINR($order['discount_amount']) ?></span></div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;"><span>Shipping</span><span><?= formatINR($order['shipping_charge']) ?></span></div>
      <div style="display:flex;justify-content:space-between;font-weight:800;border-top:1px solid var(--dc-border);padding-top:6px;margin-top:4px;"><span>Total</span><span><?= formatINR($order['total_amount']) ?></span></div>
    </div>
  </div>

  <!-- Timeline -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Order Timeline</h3></div>
    <div class="tracking-timeline">
      <?php foreach (array_reverse($order['status_history']) as $h): ?>
      <div class="tracking-step">
        <div class="tracking-dot"></div>
        <div>
          <div style="font-weight:600;"><?= ucwords(str_replace('_',' ',e($h['status']))) ?></div>
          <div style="font-size:0.75rem;color:var(--dc-muted);"><?= date('d M Y, g:i A', strtotime($h['changed_at'])) ?> <?= $h['remarks'] ? '· ' . e($h['remarks']) : '' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
