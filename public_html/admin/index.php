<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['superadmin', 'admin', 'employee']);

$activePage   = 'dashboard';
$pageHeading  = 'Dashboard';
$user = Auth::user();
$role = Auth::role();

// Quick stats
$totalOrders   = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0);
$ordersToday   = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE DATE(placed_at)=CURDATE()")['c'] ?? 0);
$pendingOrders = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status='placed'")['c'] ?? 0);
$totalRevenue  = (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount),0) as s FROM orders WHERE payment_status='paid'")['s'] ?? 0);
$lowStockCount = (int)(Database::fetchOne(
    "SELECT COUNT(*) as c FROM (
       SELECT v.id, v.reorder_level, COALESCE(SUM(b.quantity_remaining),0) as stock
       FROM product_variants v LEFT JOIN inventory_batches b ON b.variant_id=v.id
       GROUP BY v.id, v.reorder_level
       HAVING stock <= v.reorder_level
     ) t"
)['c'] ?? 0);
$expiryAlertDays = (int)(getSetting('expiry_alert_days', '30'));
$expiringBatches = (int)(Database::fetchOne(
    "SELECT COUNT(*) as c FROM inventory_batches WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) AND quantity_remaining > 0",
    [$expiryAlertDays]
)['c'] ?? 0);

// Recent orders
$recentOrders = Database::fetchAll(
    "SELECT o.order_number, o.status, o.total_amount, o.placed_at, u.full_name
     FROM orders o JOIN users u ON u.id=o.user_id
     ORDER BY o.placed_at DESC LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | <?= e(APP_NAME) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
  <!-- Chart.js for analytics widgets -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php require_once __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">

      <!-- Welcome banner -->
      <div style="background:linear-gradient(135deg,var(--dc-yellow) 0%,var(--dc-yellow-dark) 100%);border-radius:var(--dc-radius-lg);padding:var(--dc-space-lg);margin-bottom:var(--dc-space-lg);display:flex;align-items:center;justify-content:space-between;">
        <div>
          <h2 style="margin:0;font-size:1.4rem;">👋 Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?>!</h2>
          <p style="margin:4px 0 0;color:var(--dc-black-soft);font-size:0.9rem;"><?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= e(ucfirst($role)) ?></p>
        </div>
        <a href="<?= url('admin/orders.php') ?>" class="btn btn-primary">View Orders</a>
      </div>

      <!-- Stats row -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Total Orders</div>
          <div class="stat-value"><?= number_format($totalOrders) ?></div>
          <div class="stat-change">📦 <?= $ordersToday ?> today</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending Orders</div>
          <div class="stat-value" style="color:var(--dc-warning);"><?= number_format($pendingOrders) ?></div>
          <div class="stat-change" style="color:var(--dc-warning);">⏳ Awaiting action</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Paid Revenue</div>
          <div class="stat-value"><?= formatINR($totalRevenue) ?></div>
          <div class="stat-change" style="color:var(--dc-success);">💰 All time</div>
        </div>
        <div class="stat-card" <?= $lowStockCount > 0 || $expiringBatches > 0 ? 'style="border-color:var(--dc-warning);"' : '' ?>>
          <div class="stat-label">Inventory Alerts</div>
          <div class="stat-value" style="color:<?= ($lowStockCount > 0 || $expiringBatches > 0) ? 'var(--dc-warning)' : 'var(--dc-success)' ?>;">
            <?= $lowStockCount + $expiringBatches ?>
          </div>
          <div class="stat-change" style="color:var(--dc-warning);">
            <?= $lowStockCount ?> low stock · <?= $expiringBatches ?> expiring
          </div>
        </div>
      </div>

      <!-- Alerts row -->
      <?php if ($lowStockCount > 0): ?>
        <div class="alert alert-warning">
          ⚠️ <strong><?= $lowStockCount ?> variant<?= $lowStockCount > 1 ? 's are' : ' is' ?> below reorder level.</strong>
          <a href="<?= url('admin/inventory.php') ?>" style="margin-left:8px;font-weight:700;color:var(--dc-warning);">View Inventory →</a>
        </div>
      <?php endif; ?>
      <?php if ($expiringBatches > 0): ?>
        <div class="alert alert-danger">
          🚨 <strong><?= $expiringBatches ?> batch<?= $expiringBatches > 1 ? 'es expire' : ' expires' ?> within <?= $expiryAlertDays ?> days.</strong>
          <a href="<?= url('admin/inventory-batch.php') ?>" style="margin-left:8px;font-weight:700;color:var(--dc-danger);">View Batches →</a>
        </div>
      <?php endif; ?>

      <!-- Recent Orders -->
      <div class="card" style="margin-top:var(--dc-space-md);">
        <div class="card-header">
          <h3 class="card-title">Recent Orders</h3>
          <a href="<?= url('admin/orders.php') ?>" class="btn btn-ghost btn-sm">View all</a>
        </div>

        <?php if (empty($recentOrders)): ?>
          <p class="text-muted text-center" style="padding:var(--dc-space-lg) 0;">No orders yet. They will appear here once buyers complete checkout.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="dc-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Placed</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $o):
                  $statusColors = [
                    'placed'     => 'warning', 'confirmed' => 'info',   'packed'    => 'info',
                    'shipped'    => 'info',    'delivered' => 'success', 'cancelled' => 'danger',
                  ];
                  $sc = $statusColors[$o['status']] ?? 'neutral';
                ?>
                <tr>
                  <td><strong><?= e($o['order_number']) ?></strong></td>
                  <td><?= e($o['full_name']) ?></td>
                  <td><span class="badge badge-<?= $sc ?>"><?= e(ucfirst(str_replace('_', ' ', $o['status']))) ?></span></td>
                  <td><?= formatINR((float)$o['total_amount']) ?></td>
                  <td style="font-size:0.82rem;color:var(--dc-muted);"><?= date('d M Y, g:i a', strtotime($o['placed_at'])) ?></td>
                  <td><a href="<?= url('admin/order-detail.php?num=' . urlencode($o['order_number'])) ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick actions -->
      <div class="grid-4" style="margin-top:var(--dc-space-lg);">
        <a href="<?= url('admin/product-edit.php') ?>" class="card" style="text-align:center;text-decoration:none;display:block;">
          <div style="font-size:2rem;margin-bottom:8px;">➕</div>
          <div class="fw-semibold" style="font-size:0.9rem;">Add Product</div>
        </a>
        <a href="<?= url('admin/inventory-batch.php?action=receive') ?>" class="card" style="text-align:center;text-decoration:none;display:block;">
          <div style="font-size:2rem;margin-bottom:8px;">📥</div>
          <div class="fw-semibold" style="font-size:0.9rem;">Receive Stock</div>
        </a>
        <a href="<?= url('admin/reports.php') ?>" class="card" style="text-align:center;text-decoration:none;display:block;">
          <div style="font-size:2rem;margin-bottom:8px;">📋</div>
          <div class="fw-semibold" style="font-size:0.9rem;">Generate Report</div>
        </a>
        <a href="<?= url('admin/users.php?action=new') ?>" class="card" style="text-align:center;text-decoration:none;display:block;">
          <div style="font-size:2rem;margin-bottom:8px;">👤</div>
          <div class="fw-semibold" style="font-size:0.9rem;">Add User</div>
        </a>
      </div>

    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div><!-- /admin-layout -->

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
