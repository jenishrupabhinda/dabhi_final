<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('view_orders');

$filters = [
    'status'         => get('status'),
    'payment_status' => get('payment_status'),
    'search'         => get('search'),
    'date_from'      => get('date_from'),
    'date_to'        => get('date_to'),
];
$page    = max(1, (int)get('page', '1'));
$result  = Order::adminList($filters, $page, 25);
$orders  = $result['data'];
$total   = $result['total'];
$pages   = $result['pages'];

$statuses = ['placed','confirmed','packed','shipped','out_for_delivery','delivered','cancelled','return_requested','returned'];

$pageTitle   = 'Orders';
$pageHeading = 'Orders';
$activePage  = 'orders';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">

  <!-- Stats row -->
  <?php $stats = Order::dashboardStats(); ?>
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending</div>
      <div class="stat-value" style="color:var(--adm-terracotta);"><?= number_format($stats['pending_orders']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Today's Orders</div>
      <div class="stat-value"><?= number_format($stats['today_orders']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Monthly Revenue</div>
      <div class="stat-value">₹<?= number_format($stats['monthly_revenue']) ?></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="adm-filter-bar">
        <div class="form-group" style="flex:2;min-width:180px;margin-bottom:0;">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" placeholder="Order # / Name / Email" value="<?= e($filters['search']) ?>">
        </div>
        <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0;">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0;">
          <label class="form-label">Payment</label>
          <select name="payment_status" class="form-control">
            <option value="">All Payments</option>
            <?php foreach (['pending','paid','failed','refunded'] as $ps): ?>
            <option value="<?= $ps ?>" <?= $filters['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0;">
          <label class="form-label">From</label>
          <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0;">
          <label class="form-label">To</label>
          <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="adm-filter-actions">
          <button type="submit" class="btn btn-primary" style="height:42px;">Filter</button>
          <a href="<?= url('admin/orders.php') ?>" class="btn btn-secondary" style="height:42px;">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Orders (<?= number_format($total) ?>)</h3>
    </div>
    <?php if (empty($orders)): ?>
      <p class="text-muted text-center" style="padding:32px;">No orders found.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th><th>Customer</th><th>Date</th>
            <th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
            $statusColors = ['placed'=>'warning','confirmed'=>'success','packed'=>'info',
              'shipped'=>'info','out_for_delivery'=>'purple','delivered'=>'success',
              'cancelled'=>'danger','return_requested'=>'warning','returned'=>'secondary'];
            $badgeClass = $statusColors[$o['status']] ?? 'secondary';
          ?>
          <tr>
            <td><strong><?= e($o['order_number']) ?></strong></td>
            <td>
              <div><?= e($o['full_name']) ?></div>
              <div style="font-size:0.75rem;color:var(--dc-muted);"><?= e($o['email']) ?></div>
            </td>
            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($o['placed_at'])) ?></td>
            <td>—</td>
            <td><strong><?= formatINR((float)$o['total_amount']) ?></strong></td>
            <td>
              <span class="badge badge-<?= $o['payment_status'] === 'paid' ? 'success' : ($o['payment_status'] === 'failed' ? 'danger' : 'warning') ?>">
                <?= ucfirst($o['payment_status']) ?>
              </span>
              <div style="font-size:0.72rem;color:var(--dc-muted);"><?= strtoupper($o['payment_method']) ?></div>
            </td>
            <td><span class="badge badge-<?= $badgeClass ?>"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span></td>
            <td><a href="<?= url('admin/order-detail.php?id=' . (int)$o['id']) ?>" class="btn btn-ghost btn-sm">View →</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="pagination" style="margin-top:16px;">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
        class="<?= $p === $page ? 'active' : '' ?>">
        <?= $p ?>
      </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
