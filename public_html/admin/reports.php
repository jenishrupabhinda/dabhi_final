<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('generate_reports');

$activePage  = 'reports';
$pageHeading = 'Reports & Exports';
$pageTitle   = 'Reports';

$reportType = get('type', 'sales');
$dateFrom   = get('from', date('Y-m-01'));
$dateTo     = get('to', date('Y-m-d'));
$exportCsv  = get('export') === 'csv';

// Handle CSV Export
if ($exportCsv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($reportType === 'sales') {
        fputcsv($output, ['Date', 'Orders', 'Gross Sales', 'Discounts', 'Net Revenue', 'Tax Collected']);
        $rows = Database::fetchAll(
            "SELECT DATE(placed_at) AS report_date, COUNT(*) AS orders,
                    SUM(subtotal) AS gross, SUM(discount_amount) AS discounts,
                    SUM(total_amount) AS net, SUM(cgst_amount+sgst_amount+igst_amount) AS tax
             FROM orders
             WHERE payment_status='paid' AND DATE(placed_at) BETWEEN ? AND ?
             GROUP BY DATE(placed_at) ORDER BY report_date DESC",
            [$dateFrom, $dateTo]
        );
        foreach ($rows as $r) {
            fputcsv($output, [$r['report_date'], $r['orders'], $r['gross'], $r['discounts'], $r['net'], $r['tax']]);
        }
    } elseif ($reportType === 'gst') {
        fputcsv($output, ['Order #', 'Date', 'Customer', 'State', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Invoice']);
        $rows = Database::fetchAll(
            "SELECT o.order_number, DATE(o.placed_at) AS order_date, u.full_name,
                    o.subtotal, o.cgst_amount, o.sgst_amount, o.igst_amount, o.total_amount
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.payment_status='paid' AND DATE(o.placed_at) BETWEEN ? AND ?
             ORDER BY o.placed_at DESC",
            [$dateFrom, $dateTo]
        );
        foreach ($rows as $r) {
            fputcsv($output, [$r['order_number'], $r['order_date'], $r['full_name'], 'Gujarat', $r['subtotal'], $r['cgst_amount'], $r['sgst_amount'], $r['igst_amount'], $r['total_amount']]);
        }
    } else {
        fputcsv($output, ['Product', 'Category', 'SKU', 'Weight (g)', 'Stock Units', 'Selling Price', 'Total Stock Value']);
        $rows = Database::fetchAll(
            "SELECT p.name AS product_name, c.name AS category_name, pv.sku, pv.weight_grams,
                    COALESCE(SUM(b.quantity_remaining),0) AS stock, pv.selling_price,
                    (COALESCE(SUM(b.quantity_remaining),0) * pv.selling_price) AS stock_val
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             JOIN categories c ON c.id = p.category_id
             LEFT JOIN inventory_batches b ON b.variant_id = pv.id
             GROUP BY pv.id, p.name, c.name, pv.sku, pv.weight_grams, pv.selling_price
             ORDER BY stock_val DESC"
        );
        foreach ($rows as $r) {
            fputcsv($output, [$r['product_name'], $r['category_name'], $r['sku'], $r['weight_grams'], $r['stock'], $r['selling_price'], $r['stock_val']]);
        }
    }
    fclose($output);
    exit;
}

// Fetch report data
$reportData = [];
$totals = ['orders' => 0, 'gross' => 0, 'tax' => 0, 'net' => 0];

if ($reportType === 'sales') {
    $reportData = Database::fetchAll(
        "SELECT DATE(placed_at) AS report_date, COUNT(*) AS orders,
                COALESCE(SUM(subtotal),0) AS gross,
                COALESCE(SUM(discount_amount),0) AS discounts,
                COALESCE(SUM(total_amount),0) AS net,
                COALESCE(SUM(cgst_amount+sgst_amount+igst_amount),0) AS tax
         FROM orders
         WHERE payment_status='paid' AND DATE(placed_at) BETWEEN ? AND ?
         GROUP BY DATE(placed_at) ORDER BY report_date DESC",
        [$dateFrom, $dateTo]
    );
    foreach ($reportData as $row) {
        $totals['orders'] += (int)$row['orders'];
        $totals['gross']  += (float)$row['gross'];
        $totals['tax']    += (float)$row['tax'];
        $totals['net']    += (float)$row['net'];
    }
} elseif ($reportType === 'gst') {
    $reportData = Database::fetchAll(
        "SELECT o.order_number, DATE(o.placed_at) AS order_date, u.full_name,
                o.subtotal, o.cgst_amount, o.sgst_amount, o.igst_amount, o.total_amount
         FROM orders o
         JOIN users u ON u.id = o.user_id
         WHERE o.payment_status='paid' AND DATE(o.placed_at) BETWEEN ? AND ?
         ORDER BY o.placed_at DESC",
        [$dateFrom, $dateTo]
    );
    foreach ($reportData as $row) {
        $totals['orders']++;
        $totals['gross'] += (float)$row['subtotal'];
        $totals['tax']   += (float)($row['cgst_amount'] + $row['sgst_amount'] + $row['igst_amount']);
        $totals['net']   += (float)$row['total_amount'];
    }
} else { // inventory
    $reportData = Database::fetchAll(
        "SELECT p.name AS product_name, c.name AS category_name, pv.sku, pv.weight_grams,
                COALESCE(SUM(b.quantity_remaining),0) AS stock, pv.selling_price,
                (COALESCE(SUM(b.quantity_remaining),0) * pv.selling_price) AS stock_val
         FROM product_variants pv
         JOIN products p ON p.id = pv.product_id
         JOIN categories c ON c.id = p.category_id
         LEFT JOIN inventory_batches b ON b.variant_id = pv.id
         GROUP BY pv.id, p.name, c.name, pv.sku, pv.weight_grams, pv.selling_price
         ORDER BY stock_val DESC"
    );
    foreach ($reportData as $row) {
        $totals['orders'] += (int)$row['stock'];
        $totals['net']    += (float)$row['stock_val'];
    }
}

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
      <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
      <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Generate consolidated financial, sales, tax, and inventory valuation statements.</p>
    </div>
    <div>
      <a href="?<?= http_build_query(['type' => $reportType, 'from' => $dateFrom, 'to' => $dateTo, 'export' => 'csv']) ?>" class="btn btn-secondary">
        📥 Export to CSV
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="margin:0;min-width:180px;">
        <label class="form-label">Report Type</label>
        <select name="type" class="form-control form-control-sm">
          <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales Summary</option>
          <option value="gst" <?= $reportType === 'gst' ? 'selected' : '' ?>>GST / Tax Filing</option>
          <option value="inventory" <?= $reportType === 'inventory' ? 'selected' : '' ?>>Stock Valuation</option>
        </select>
      </div>

      <?php if ($reportType !== 'inventory'): ?>
      <div class="form-group" style="margin:0;">
        <label class="form-label">From Date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">To Date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-sm">Generate Report</button>
      <a href="<?= url('admin/reports.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
    </form>
  </div>

  <!-- Summary Cards -->
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));margin-bottom:20px;">
    <?php if ($reportType === 'inventory'): ?>
      <div class="stat-card">
        <div class="stat-label">Total Units in Stock</div>
        <div class="stat-value"><?= number_format($totals['orders']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Inventory Valuation</div>
        <div class="stat-value"><?= formatINR($totals['net']) ?></div>
      </div>
    <?php else: ?>
      <div class="stat-card">
        <div class="stat-label">Paid Orders</div>
        <div class="stat-value"><?= number_format($totals['orders']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Taxable Value</div>
        <div class="stat-value"><?= formatINR($totals['gross']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Tax</div>
        <div class="stat-value"><?= formatINR($totals['tax']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Net Sales Revenue</div>
        <div class="stat-value" style="color:var(--dc-success);"><?= formatINR($totals['net']) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Data Table -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Statement Entries (<?= count($reportData) ?> rows)</h3>
    </div>

    <?php if (empty($reportData)): ?>
      <p class="text-muted text-center" style="padding:24px;">No records found for the selected period.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="dc-table">
          <?php if ($reportType === 'sales'): ?>
            <thead>
              <tr>
                <th>Date</th>
                <th>Orders</th>
                <th>Gross (₹)</th>
                <th>Discounts (₹)</th>
                <th>Net Revenue (₹)</th>
                <th>GST Collected (₹)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reportData as $r): ?>
              <tr>
                <td><strong><?= date('d M Y', strtotime($r['report_date'])) ?></strong></td>
                <td><?= $r['orders'] ?></td>
                <td><?= formatINR((float)$r['gross']) ?></td>
                <td><?= formatINR((float)$r['discounts']) ?></td>
                <td><strong><?= formatINR((float)$r['net']) ?></strong></td>
                <td><?= formatINR((float)$r['tax']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          <?php elseif ($reportType === 'gst'): ?>
            <thead>
              <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Taxable Subtotal</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>IGST</th>
                <th>Total Invoice</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reportData as $r): ?>
              <tr>
                <td><strong><?= e($r['order_number']) ?></strong></td>
                <td><?= date('d M Y', strtotime($r['order_date'])) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= formatINR((float)$r['subtotal']) ?></td>
                <td><?= formatINR((float)$r['cgst_amount']) ?></td>
                <td><?= formatINR((float)$r['sgst_amount']) ?></td>
                <td><?= formatINR((float)$r['igst_amount']) ?></td>
                <td><strong><?= formatINR((float)$r['total_amount']) ?></strong></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          <?php else: ?>
            <thead>
              <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th>SKU</th>
                <th>Weight</th>
                <th>Units Remaining</th>
                <th>Selling Price</th>
                <th>Total Value</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reportData as $r): ?>
              <tr>
                <td><strong><?= e($r['product_name']) ?></strong></td>
                <td><?= e($r['category_name']) ?></td>
                <td><?= e($r['sku']) ?></td>
                <td><?= $r['weight_grams'] ?>g</td>
                <td><?= number_format($r['stock']) ?></td>
                <td><?= formatINR((float)$r['selling_price']) ?></td>
                <td><strong><?= formatINR((float)$r['stock_val']) ?></strong></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endif; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
