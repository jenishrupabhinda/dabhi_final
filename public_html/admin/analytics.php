<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('view_financials');

// Date range (default: current month)
$dateFrom = get('date_from') ?: date('Y-m-01');
$dateTo   = get('date_to')   ?: date('Y-m-d');

// Revenue by day
$revenueByDay = Database::fetchAll(
    "SELECT DATE(placed_at) AS day, COUNT(*) AS orders, SUM(total_amount) AS revenue
     FROM orders
     WHERE payment_status = 'paid'
       AND placed_at BETWEEN ? AND ?
     GROUP BY DATE(placed_at) ORDER BY day ASC",
    [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
);

// Top products (by revenue)
$topProducts = Database::fetchAll(
    "SELECT oi.product_name_snapshot AS name, SUM(oi.quantity) AS units_sold,
       SUM(oi.line_total) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.payment_status = 'paid'
       AND o.placed_at BETWEEN ? AND ?
     GROUP BY oi.product_name_snapshot ORDER BY revenue DESC LIMIT 10",
    [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
);

// Summary totals
$totals = Database::fetchOne(
    "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount),0) AS total_revenue,
       COALESCE(SUM(discount_amount),0) AS total_discounts,
       COALESCE(SUM(shipping_charge),0) AS total_shipping,
       COALESCE(SUM(igst_amount+cgst_amount+sgst_amount),0) AS total_gst
     FROM orders WHERE payment_status='paid' AND placed_at BETWEEN ? AND ?",
    [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
);

// Order status breakdown
$statusBreakdown = Database::fetchAll(
    "SELECT status, COUNT(*) AS n FROM orders
     WHERE placed_at BETWEEN ? AND ?
     GROUP BY status ORDER BY n DESC",
    [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
);

// Payment method split
$paymentSplit = Database::fetchAll(
    "SELECT payment_method, COUNT(*) AS n, SUM(total_amount) AS revenue
     FROM orders WHERE placed_at BETWEEN ? AND ? GROUP BY payment_method",
    [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
);

$pageTitle = 'Analytics';
$pageHeading = 'Analytics';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">

  <!-- Date filter -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="adm-filter-bar">
        <div class="form-group" style="margin:0;flex:1;min-width:140px;">
          <label class="form-label">From Date</label>
          <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:140px;">
          <label class="form-label">To Date</label>
          <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
        </div>
        <div class="adm-filter-actions">
          <button type="submit" class="btn btn-primary" style="height:42px;">Apply Filter</button>
          <a href="<?= url('admin/analytics.php') ?>" class="btn btn-secondary" style="height:42px;">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Paid Orders</div><div class="stat-value"><?= number_format($totals['total_orders']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">₹<?= number_format($totals['total_revenue']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Discounts Given</div><div class="stat-value">₹<?= number_format($totals['total_discounts']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Shipping Earned</div><div class="stat-value">₹<?= number_format($totals['total_shipping']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total GST</div><div class="stat-value">₹<?= number_format($totals['total_gst']) ?></div></div>
  </div>

  <div class="analytics-main-grid">

    <!-- Revenue by day -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">Daily Revenue</h3></div>
      <?php if (empty($revenueByDay)): ?>
        <p class="text-muted text-center" style="padding:28px;">No paid orders in this period.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
          <tbody>
          <?php foreach ($revenueByDay as $r): ?>
          <tr>
            <td><strong><?= date('d M Y', strtotime($r['day'])) ?></strong></td>
            <td><?= $r['orders'] ?></td>
            <td><strong><?= formatINR((float)$r['revenue']) ?></strong></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Status breakdown + Payment split -->
    <div>
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3 class="card-title">Order Status</h3></div>
        <div class="card-body">
          <?php foreach ($statusBreakdown as $s): ?>
          <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem;border-bottom:1px solid var(--adm-border-subtle);">
            <span><?= ucwords(str_replace('_',' ',e($s['status']))) ?></span>
            <strong><?= $s['n'] ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Payment Method</h3></div>
        <div class="card-body">
          <?php foreach ($paymentSplit as $p): ?>
          <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem;border-bottom:1px solid var(--adm-border-subtle);">
            <span><?= strtoupper(e($p['payment_method'])) ?> (<?= $p['n'] ?>)</span>
            <strong><?= formatINR((float)$p['revenue']) ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Top products -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Top Products by Revenue</h3></div>
    <?php if (empty($topProducts)): ?>
      <p class="text-muted text-center" style="padding:28px;">No data recorded.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($topProducts as $t): ?>
        <tr>
          <td><strong><?= e($t['name']) ?></strong></td>
          <td><?= number_format($t['units_sold']) ?></td>
          <td><strong><?= formatINR((float)$t['revenue']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
