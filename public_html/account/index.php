<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');

$userId = Auth::id();
$user   = Database::fetchOne('SELECT * FROM users WHERE id=?', [$userId]);

// Quick stats
$orderCount  = (int)(Database::fetchOne('SELECT COUNT(*) AS n FROM orders WHERE user_id=?', [$userId])['n'] ?? 0);
$recentOrders = Database::fetchAll('SELECT * FROM orders WHERE user_id=? ORDER BY placed_at DESC LIMIT 3', [$userId]);
$addrCount   = (int)(Database::fetchOne('SELECT COUNT(*) AS n FROM addresses WHERE user_id=?', [$userId])['n'] ?? 0);

$pageTitle = 'My Account';
$pageDesc  = 'Manage your Dabhi Chikki account.';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--dc-space-xl);padding-bottom:var(--dc-space-xl);max-width:900px;">

  <h1 style="margin-bottom:24px;">Welcome, <?= e($user['full_name']) ?> 👋</h1>

  <!-- Quick stats -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
    <div class="card text-center" style="padding:24px;">
      <div style="font-size:2rem;font-weight:800;color:var(--dc-terracotta);"><?= $orderCount ?></div>
      <div style="font-size:0.85rem;color:var(--dc-muted);">Total Orders</div>
    </div>
    <div class="card text-center" style="padding:24px;">
      <div style="font-size:2rem;font-weight:800;color:var(--dc-terracotta);"><?= $addrCount ?></div>
      <div style="font-size:0.85rem;color:var(--dc-muted);">Saved Addresses</div>
    </div>
    <div class="card text-center" style="padding:24px;">
      <div style="font-size:2rem;font-weight:800;color:var(--dc-terracotta);">
        <?= (int)(Database::fetchOne('SELECT COUNT(*) AS n FROM build_boxes WHERE user_id=?', [$userId])['n'] ?? 0) ?>
      </div>
      <div style="font-size:0.85rem;color:var(--dc-muted);">Saved Boxes</div>
    </div>
  </div>

  <!-- Quick links -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;margin-bottom:28px;">
    <?php $links = [
      ['icon'=>'📦','label'=>'My Orders','url'=>url('account/orders.php')],
      ['icon'=>'📍','label'=>'Addresses','url'=>url('account/addresses.php')],
      ['icon'=>'🎁','label'=>'Saved Boxes','url'=>url('build-your-box.php')],
      ['icon'=>'👤','label'=>'Profile','url'=>url('account/profile.php')],
      ['icon'=>'🛍️','label'=>'Shop','url'=>url('shop.php')],
      ['icon'=>'🔍','label'=>'Track Order','url'=>url('track-order.php')],
    ];
    foreach ($links as $l): ?>
    <a href="<?= $l['url'] ?>" style="display:flex;align-items:center;gap:10px;padding:14px 16px;border:2px solid var(--dc-border);border-radius:var(--dc-radius);text-decoration:none;color:var(--dc-black);transition:border-color var(--dc-transition),box-shadow var(--dc-transition);"
      style="hover:border-color:var(--dc-terracotta);">
      <span style="font-size:1.4rem;"><?= $l['icon'] ?></span>
      <span style="font-weight:600;"><?= $l['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Recent orders -->
  <?php if ($recentOrders): ?>
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3 class="card-title" style="margin:0;">Recent Orders</h3>
      <a href="<?= url('account/orders.php') ?>" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <?php foreach ($recentOrders as $o):
      $statusColors = ['placed'=>'#D98E20','confirmed'=>'#16a34a','packed'=>'#2563EB','shipped'=>'#2563EB',
        'out_for_delivery'=>'#7C3AED','delivered'=>'#16a34a','cancelled'=>'#DC2626'];
      $sc = $statusColors[$o['status']] ?? '#6B7280';
    ?>
    <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--dc-border);flex-wrap:wrap;gap:8px;">
      <div>
        <div style="font-weight:700;"><?= e($o['order_number']) ?></div>
        <div style="font-size:0.78rem;color:var(--dc-muted);"><?= date('d M Y', strtotime($o['placed_at'])) ?></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <span class="badge" style="background:<?= $sc ?>;color:#fff;"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span>
        <strong><?= formatINR((float)$o['total_amount']) ?></strong>
        <a href="<?= url('account/order-detail.php?id=' . (int)$o['id']) ?>" class="btn btn-ghost btn-sm">Details</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
