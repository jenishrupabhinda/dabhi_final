<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');

$userId = Auth::id();
$page   = max(1, (int)get('page', '1'));
$result = Order::buyerList($userId, $page, 10);
$orders = $result['data'];
$pages  = $result['pages'];

$pageTitle = 'My Orders';
$pageDesc  = 'View all your past orders from Dabhi Chikki.';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--dc-space-xl);padding-bottom:var(--dc-space-xl);max-width:860px;">

  <h1 style="margin-bottom:20px;">My Orders</h1>

  <?php if (empty($orders)): ?>
  <div class="card text-center" style="padding:60px 24px;">
    <div style="font-size:4rem;">🛍️</div>
    <h2>No orders yet</h2>
    <p class="text-muted">When you place an order, it will appear here.</p>
    <a href="<?= url('shop.php') ?>" class="btn btn-primary btn-lg" style="margin-top:12px;">Start Shopping</a>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($orders as $o):
      $statusColors = ['placed'=>'#D98E20','confirmed'=>'#16a34a','packed'=>'#2563EB',
        'shipped'=>'#2563EB','out_for_delivery'=>'#7C3AED','delivered'=>'#16a34a',
        'cancelled'=>'#DC2626','return_requested'=>'#D98E20','returned'=>'#6B7280'];
      $statusColor = $statusColors[$o['status']] ?? '#6B7280';
    ?>
    <div class="card" style="border-left:4px solid <?= $statusColor ?>;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <strong><?= e($o['order_number']) ?></strong>
            <span class="badge" style="background:<?= $statusColor ?>;color:#fff;">
              <?= ucwords(str_replace('_', ' ', $o['status'])) ?>
            </span>
          </div>
          <div style="font-size:0.8rem;color:var(--dc-muted);margin-top:4px;">
            Placed <?= date('d M Y', strtotime($o['placed_at'])) ?> ·
            <?= strtoupper($o['payment_method']) ?> ·
            <span style="color:<?= $o['payment_status'] === 'paid' ? 'var(--dc-success)' : 'var(--dc-warning)' ?>">
              <?= ucfirst($o['payment_status']) ?>
            </span>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:1.2rem;font-weight:800;"><?= formatINR((float)$o['total_amount']) ?></div>
          <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;justify-content:flex-end;">
            <a href="<?= url('account/order-detail.php?id=' . (int)$o['id']) ?>" class="btn btn-ghost btn-sm">Details</a>
            <a href="<?= url('track-order.php?order=' . urlencode($o['order_number'])) ?>" class="btn btn-secondary btn-sm">Track</a>
            <?php if (in_array($o['status'], ['placed','confirmed'])): ?>
            <form method="POST" action="<?= url('account/order-detail.php?id=' . (int)$o['id']) ?>" style="display:inline;">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="cancel">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--dc-danger);"
                onclick="return confirm('Cancel this order?')">Cancel</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination" style="margin-top:20px;">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
