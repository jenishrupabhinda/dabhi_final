<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('manage_inventory');

$activePage  = 'inventory-batch';
$pageHeading = 'Batch Manager';
$pageTitle   = 'Batch Manager';

$selectedVariantId = (int)get('variant_id');
$showReceiveForm   = get('action') === 'receive' || $selectedVariantId > 0;

// Handle POST: Add new batch or adjust batch stock
if (isPost()) {
    csrfVerify();
    $postAction = post('action');

    if ($postAction === 'receive_batch') {
        $variantId         = (int)post('variant_id');
        $batchNumber       = trim(post('batch_number'));
        $manufactureDate   = post('manufacture_date') ?: null;
        $expiryDate        = post('expiry_date');
        $quantityReceived  = max(1, (int)post('quantity_received'));
        $costPrice         = post('cost_price') !== '' ? (float)post('cost_price') : null;
        $supplierName      = trim(post('supplier_name'));
        $notes             = trim(post('notes'));

        if (!$variantId) {
            flashSet('error', 'Please select a valid product variant.');
        } elseif (!$batchNumber) {
            flashSet('error', 'Batch number is required.');
        } elseif (!$expiryDate) {
            flashSet('error', 'Expiry date is required.');
        } else {
            Database::query(
                "INSERT INTO inventory_batches 
                 (variant_id, batch_number, manufacture_date, expiry_date, quantity_received, quantity_remaining, cost_price, supplier_name, received_by, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$variantId, $batchNumber, $manufactureDate, $expiryDate, $quantityReceived, $quantityReceived, $costPrice, $supplierName, Auth::id(), $notes]
            );
            $batchId = Database::lastInsertId();

            Database::query(
                "INSERT INTO stock_movements (variant_id, batch_id, movement_type, quantity, reference_type, notes, performed_by)
                 VALUES (?, ?, 'purchase_in', ?, 'manual_receive', ?, ?)",
                [$variantId, $batchId, $quantityReceived, 'Batch received: ' . $batchNumber, Auth::id()]
            );

            flashSet('success', "Batch {$batchNumber} received successfully ({$quantityReceived} units).");
            redirect('/admin/inventory-batch.php');
        }
    }

    if ($postAction === 'adjust_batch') {
        $batchId = (int)post('batch_id');
        $type    = post('movement_type'); // adjustment_in, adjustment_out, damage_out
        $qty     = max(1, (int)post('quantity'));
        $notes   = trim(post('notes'));

        $batch = Database::fetchOne("SELECT * FROM inventory_batches WHERE id = ?", [$batchId]);
        if ($batch) {
            $isDecrease = in_array($type, ['adjustment_out', 'damage_out'], true);
            $newRemaining = $isDecrease 
                ? max(0, (int)$batch['quantity_remaining'] - $qty)
                : (int)$batch['quantity_remaining'] + $qty;

            Database::query("UPDATE inventory_batches SET quantity_remaining = ? WHERE id = ?", [$newRemaining, $batchId]);

            Database::query(
                "INSERT INTO stock_movements (variant_id, batch_id, movement_type, quantity, reference_type, notes, performed_by)
                 VALUES (?, ?, ?, ?, 'manual_adjustment', ?, ?)",
                [(int)$batch['variant_id'], $batchId, $type, $qty, $notes, Auth::id()]
            );

            flashSet('success', "Batch #{$batch['batch_number']} stock adjusted successfully.");
            redirect('/admin/inventory-batch.php');
        }
    }
}

// Fetch all variants with product name for selection dropdown
$variantsList = Database::fetchAll(
    "SELECT pv.id, pv.sku, pv.weight_grams, p.name AS product_name
     FROM product_variants pv
     JOIN products p ON p.id = pv.product_id
     ORDER BY p.name ASC, pv.weight_grams ASC"
);

// Fetch all batches
$batches = Database::fetchAll(
    "SELECT ib.*, p.name AS product_name, pv.sku, pv.weight_grams
     FROM inventory_batches ib
     JOIN product_variants pv ON pv.id = ib.variant_id
     JOIN products p ON p.id = pv.product_id
     ORDER BY ib.expiry_date ASC, ib.id DESC"
);

// Recent stock movements
$recentMovements = Database::fetchAll(
    "SELECT sm.*, p.name AS product_name, pv.sku, u.full_name AS operator_name
     FROM stock_movements sm
     JOIN product_variants pv ON pv.id = sm.variant_id
     JOIN products p ON p.id = pv.product_id
     LEFT JOIN users u ON u.id = sm.performed_by
     ORDER BY sm.created_at DESC LIMIT 15"
);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
      <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Track production batches, expiry dates, and warehouse receipts.</p>
    </div>
    <div style="display:flex;gap:8px;">
      <button type="button" class="btn btn-primary" onclick="document.getElementById('receiveBatchModal').style.display='block';">
        + Receive New Batch
      </button>
      <a href="<?= url('admin/inventory.php') ?>" class="btn btn-ghost">📊 Stock Overview</a>
    </div>
  </div>

  <!-- Receive Batch Modal / Card -->
  <div id="receiveBatchModal" class="card" style="margin-bottom:24px;<?= $showReceiveForm ? 'display:block;' : 'display:none;' ?>;border:2px solid var(--dc-yellow);">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3 class="card-title">📥 Receive Stock Batch</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('receiveBatchModal').style.display='none';">✕ Close</button>
    </div>
    <form method="POST" style="margin-top:12px;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="receive_batch">

      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;">
        <div class="form-group">
          <label class="form-label">Product Variant <span style="color:var(--dc-danger);">*</span></label>
          <select name="variant_id" class="form-control" required>
            <option value="">Select Variant...</option>
            <?php foreach ($variantsList as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $selectedVariantId === (int)$v['id'] ? 'selected' : '' ?>>
                <?= e($v['product_name']) ?> (<?= $v['weight_grams'] ?>g - <?= e($v['sku']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Batch Number <span style="color:var(--dc-danger);">*</span></label>
          <input type="text" name="batch_number" class="form-control" placeholder="e.g. BATCH-<?= date('Ymd') ?>-01" required value="BATCH-<?= date('Ymd') ?>-01">
        </div>

        <div class="form-group">
          <label class="form-label">Quantity Received (Units) <span style="color:var(--dc-danger);">*</span></label>
          <input type="number" name="quantity_received" class="form-control" min="1" required value="50">
        </div>

        <div class="form-group">
          <label class="form-label">Cost Price / Unit (₹)</label>
          <input type="number" step="0.01" name="cost_price" class="form-control" placeholder="Optional">
        </div>

        <div class="form-group">
          <label class="form-label">Manufacture Date</label>
          <input type="date" name="manufacture_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Expiry Date <span style="color:var(--dc-danger);">*</span></label>
          <input type="date" name="expiry_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+90 days')) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Supplier / Kitchen</label>
          <input type="text" name="supplier_name" class="form-control" placeholder="In-house or supplier name" value="In-house Kitchen">
        </div>

        <div class="form-group">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" class="form-control" placeholder="Optional batch notes">
        </div>
      </div>

      <div style="margin-top:16px;display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">Save & Receive Stock</button>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('receiveBatchModal').style.display='none';">Cancel</button>
      </div>
    </form>
  </div>

  <!-- Batches Table -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h3 class="card-title">All Stock Batches (<?= count($batches) ?>)</h3>
    </div>
    <?php if (empty($batches)): ?>
      <p class="text-muted text-center" style="padding:24px;">No stock batches found. Receive your first batch above.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="dc-table">
          <thead>
            <tr>
              <th>Batch #</th>
              <th>Product / SKU</th>
              <th>Received</th>
              <th>Remaining</th>
              <th>Mfg Date</th>
              <th>Expiry Date</th>
              <th>Supplier</th>
              <th>Quick Adjust</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($batches as $b): 
              $now = new DateTime();
              $exp = new DateTime($b['expiry_date']);
              $diffDays = (int)$now->diff($exp)->format('%r%a');
              $isExpired = $diffDays <= 0;
              $isNearExpiry = $diffDays > 0 && $diffDays <= 30;
            ?>
            <tr>
              <td><strong><?= e($b['batch_number']) ?></strong></td>
              <td>
                <div style="font-weight:600;"><?= e($b['product_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--dc-muted);"><?= e($b['sku']) ?> &bull; <?= (int)$b['weight_grams'] ?>g</div>
              </td>
              <td><?= number_format($b['quantity_received']) ?></td>
              <td>
                <span style="font-weight:700;font-size:1.05rem;color:<?= (int)$b['quantity_remaining'] <= 5 ? 'var(--dc-danger)' : 'inherit' ?>;">
                  <?= number_format($b['quantity_remaining']) ?>
                </span>
              </td>
              <td><?= $b['manufacture_date'] ? date('d M Y', strtotime($b['manufacture_date'])) : '—' ?></td>
              <td>
                <?= date('d M Y', strtotime($b['expiry_date'])) ?>
                <?php if ($isExpired): ?>
                  <span class="badge badge-danger" style="margin-left:4px;">Expired</span>
                <?php elseif ($isNearExpiry): ?>
                  <span class="badge badge-warning" style="margin-left:4px;"><?= $diffDays ?>d left</span>
                <?php endif; ?>
              </td>
              <td><?= e($b['supplier_name'] ?: '—') ?></td>
              <td>
                <form method="POST" style="display:flex;gap:6px;align-items:center;" onsubmit="return confirm('Adjust batch stock?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="adjust_batch">
                  <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
                  <select name="movement_type" class="form-control form-control-sm" style="width:110px;padding:3px 6px;">
                    <option value="damage_out">Damaged (-)</option>
                    <option value="adjustment_out">Count Out (-)</option>
                    <option value="adjustment_in">Count In (+)</option>
                  </select>
                  <input type="number" name="quantity" min="1" value="1" class="form-control form-control-sm" style="width:55px;padding:3px 6px;">
                  <button type="submit" class="btn btn-ghost btn-sm">Adjust</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent Movements -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Stock Movements</h3>
    </div>
    <?php if (empty($recentMovements)): ?>
      <p class="text-muted text-center" style="padding:20px;">No stock movements recorded yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="dc-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Product / SKU</th>
              <th>Type</th>
              <th>Qty</th>
              <th>Notes</th>
              <th>Operator</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentMovements as $m): 
              $isPositive = in_array($m['movement_type'], ['purchase_in', 'adjustment_in', 'return_in'], true);
            ?>
            <tr>
              <td style="font-size:0.8rem;color:var(--dc-muted);"><?= date('d M Y, g:i a', strtotime($m['created_at'])) ?></td>
              <td><?= e($m['product_name']) ?> <span style="font-size:0.75rem;color:var(--dc-muted);">(<?= e($m['sku']) ?>)</span></td>
              <td>
                <span class="badge badge-<?= $isPositive ? 'success' : 'neutral' ?>">
                  <?= ucwords(str_replace('_', ' ', $m['movement_type'])) ?>
                </span>
              </td>
              <td>
                <strong style="color:<?= $isPositive ? 'var(--dc-success)' : 'var(--dc-danger)' ?>;">
                  <?= $isPositive ? '+' : '-' ?><?= abs((int)$m['quantity']) ?>
                </strong>
              </td>
              <td style="font-size:0.85rem;"><?= e($m['notes'] ?: '—') ?></td>
              <td style="font-size:0.85rem;"><?= e($m['operator_name'] ?: 'System') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
