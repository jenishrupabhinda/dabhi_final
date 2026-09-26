<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('manage_inventory');

// Handle batch add / stock adjustment via POST
if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'add_batch') {
        $variantId = (int)post('variant_id');
        $data = [
            'batch_number'    => post('batch_number'),
            'manufacture_date'=> post('manufacture_date') ?: null,
            'expiry_date'     => post('expiry_date'),
            'quantity_received'  => (int)post('quantity_received'),
            'quantity_remaining' => (int)post('quantity_received'),
            'cost_price'      => post('cost_price') ? (float)post('cost_price') : null,
            'supplier_name'   => post('supplier_name'),
            'received_by'     => Auth::id(),
            'notes'           => post('notes'),
        ];
        Database::query(
            "INSERT INTO inventory_batches
             (variant_id, batch_number, manufacture_date, expiry_date, quantity_received,
              quantity_remaining, cost_price, supplier_name, received_by, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            array_merge([$variantId], array_values($data))
        );
        // Stock movement
        Database::query(
            "INSERT INTO stock_movements (variant_id, movement_type, quantity, notes, performed_by)
             VALUES (?,'purchase_in',?,?,?)",
            [$variantId, $data['quantity_received'], 'Batch added: ' . $data['batch_number'], Auth::id()]
        );
        flashSet('success', 'Batch added successfully.');
    }

    if ($action === 'adjust') {
        $variantId = (int)post('variant_id');
        $batchId   = (int)post('batch_id');
        $type      = post('movement_type'); // adjustment_in | adjustment_out | damage_out
        $qty       = (int)post('quantity');
        $notes     = post('notes');

        $direction = ($type === 'adjustment_out' || $type === 'damage_out') ? -1 : 1;
        Database::query(
            'UPDATE inventory_batches SET quantity_remaining = quantity_remaining + ? WHERE id = ?',
            [$direction * $qty, $batchId]
        );
        Database::query(
            "INSERT INTO stock_movements (variant_id, batch_id, movement_type, quantity, reference_type, notes, performed_by)
             VALUES (?,?,?,?,'manual',?,?)",
            [$variantId, $batchId, $type, $qty, $notes, Auth::id()]
        );
        flashSet('success', 'Stock adjusted.');
    }

    redirect('/admin/inventory.php?' . http_build_query(['product_id' => get('product_id')]));
}

// Product filter
$productId = (int)get('product_id');
$products  = Database::fetchAll(
    'SELECT p.id, p.name, c.name AS category FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.name'
);

$variants  = [];
$batches   = [];
$lowStock  = [];

if ($productId) {
    $variants = Database::fetchAll(
        'SELECT pv.*,
           (SELECT SUM(ib.quantity_remaining) FROM inventory_batches ib
            WHERE ib.variant_id = pv.id AND ib.quantity_remaining > 0) AS total_stock,
           (SELECT MIN(ib.expiry_date) FROM inventory_batches ib
            WHERE ib.variant_id = pv.id AND ib.quantity_remaining > 0) AS nearest_expiry
         FROM product_variants pv WHERE pv.product_id = ?',
        [$productId]
    );

    foreach ($variants as $v) {
        $batches[(int)$v['id']] = Database::fetchAll(
            'SELECT * FROM inventory_batches WHERE variant_id = ? ORDER BY expiry_date ASC',
            [$v['id']]
        );
    }
}

// Global low-stock/expiry alerts
$lowStockItems = Database::fetchAll(
    "SELECT p.name AS product, pv.sku, pv.reorder_level,
       COALESCE(SUM(ib.quantity_remaining),0) AS total_stock
     FROM product_variants pv
     JOIN products p ON p.id = pv.product_id
     LEFT JOIN inventory_batches ib ON ib.variant_id = pv.id
     GROUP BY pv.id, pv.reorder_level, pv.sku, p.name
     HAVING total_stock <= pv.reorder_level
     ORDER BY total_stock ASC LIMIT 20"
);

$expiringBatches = Database::fetchAll(
    "SELECT ib.*, p.name AS product, pv.sku
     FROM inventory_batches ib
     JOIN product_variants pv ON pv.id = ib.variant_id
     JOIN products p ON p.id = pv.product_id
     WHERE ib.quantity_remaining > 0
       AND ib.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY ib.expiry_date ASC LIMIT 20"
);

$pageTitle = 'Inventory Management';
$pageHeading = 'Inventory';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Alerts -->
  <?php if ($lowStockItems): ?>
  <div class="adm-alert adm-alert-warning">
    <span class="adm-alert-icon">⚠️</span>
    <div class="adm-alert-content">
      <strong><?= count($lowStockItems) ?> variant(s)</strong> are at or below reorder level.
      <?php foreach ($lowStockItems as $ls): ?><br>&bull; <?= e($ls['product']) ?> (<?= e($ls['sku']) ?>) — <?= $ls['total_stock'] ?> units remaining<?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($expiringBatches): ?>
  <div class="adm-alert adm-alert-danger">
    <span class="adm-alert-icon">🔴</span>
    <div class="adm-alert-content">
      <strong><?= count($expiringBatches) ?> batch(es)</strong> expire within 30 days.
      <?php foreach ($expiringBatches as $eb): ?><br>&bull; <?= e($eb['product']) ?> (<?= e($eb['sku']) ?>) — expires <?= $eb['expiry_date'] ?>, <?= $eb['quantity_remaining'] ?> units remaining<?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Product selector -->
  <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;">
    <select name="product_id" class="form-control" style="max-width:340px;" onchange="this.form.submit()">
      <option value="">— Select a product to manage inventory —</option>
      <?php foreach ($products as $p): ?>
      <option value="<?= $p['id'] ?>" <?= $productId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($productId && $variants): ?>

  <!-- Add batch form -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 class="card-title">Add Stock Batch</h3></div>
    <div class="card-body">
      <form method="POST" class="adm-form-grid-3">
        <?= csrfField() ?><input type="hidden" name="action" value="add_batch">
        <div class="form-group">
          <label class="form-label">Variant</label>
          <select name="variant_id" class="form-control" required>
            <?php foreach ($variants as $v): ?>
            <option value="<?= $v['id'] ?>"><?= e($v['sku']) ?> (<?= e($v['weight_grams']) ?>g)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Batch Number</label>
          <input type="text" name="batch_number" class="form-control" placeholder="e.g. BATCH-01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Qty Received</label>
          <input type="number" name="quantity_received" class="form-control" min="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">Manufacture Date</label>
          <input type="date" name="manufacture_date" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date *</label>
          <input type="date" name="expiry_date" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Cost Price (₹)</label>
          <input type="number" name="cost_price" step="0.01" class="form-control" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Supplier</label>
          <input type="text" name="supplier_name" class="form-control" placeholder="Supplier name">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" class="form-control" placeholder="Optional notes">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <button type="submit" class="btn btn-primary" style="height:42px;width:100%;">Add Batch</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Variants & batches -->
  <?php foreach ($variants as $v): ?>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
      <h3 class="card-title"><?= e($v['sku']) ?> — <?= e($v['weight_grams']) ?>g</h3>
      <div style="font-size:0.86rem;">
        Total Stock: <strong><?= (int)$v['total_stock'] ?></strong> units
        <?php if ((int)$v['total_stock'] <= (int)$v['reorder_level']): ?>
        <span class="badge badge-danger" style="margin-left:8px;">Low Stock</span>
        <?php endif; ?>
        <?php if ($v['nearest_expiry']): ?>
        · Nearest Expiry: <span style="font-weight:600;color:var(--adm-terracotta);"><?= $v['nearest_expiry'] ?></span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($batches[$v['id']])): ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Batch</th><th>Mfg Date</th><th>Expiry</th><th>Received</th><th>Remaining</th><th>Cost</th><th>Adjust</th></tr></thead>
        <tbody>
        <?php foreach ($batches[$v['id']] as $b): ?>
        <tr style="<?= $b['quantity_remaining'] == 0 ? 'opacity:0.5;' : '' ?>">
          <td><strong><?= e($b['batch_number']) ?></strong></td>
          <td><?= $b['manufacture_date'] ?? '—' ?></td>
          <td style="<?= $b['expiry_date'] <= date('Y-m-d', strtotime('+30 days')) ? 'color:var(--adm-terracotta);font-weight:700;' : '' ?>"><?= $b['expiry_date'] ?></td>
          <td><?= $b['quantity_received'] ?></td>
          <td><strong><?= $b['quantity_remaining'] ?></strong></td>
          <td><?= $b['cost_price'] ? '₹' . number_format($b['cost_price'], 2) : '—' ?></td>
          <td>
            <form method="POST" style="display:flex;gap:4px;align-items:center;">
              <?= csrfField() ?><input type="hidden" name="action" value="adjust">
              <input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
              <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
              <select name="movement_type" class="form-control" style="width:120px;font-size:0.78rem;height:34px;padding:4px 8px;">
                <option value="adjustment_in">+ Add</option>
                <option value="adjustment_out">− Remove</option>
                <option value="damage_out">⚠ Damage</option>
              </select>
              <input type="number" name="quantity" class="form-control" min="1" style="width:55px;height:34px;padding:4px 8px;" required>
              <button type="submit" class="btn btn-secondary btn-sm" style="height:34px;">OK</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="text-muted" style="padding:16px 20px;margin:0;">No batches yet for this variant.</p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
