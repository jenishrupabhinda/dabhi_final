<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_settings');

if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'save') {
        $id = (int)post('id');
        $fields = [
            'code'               => strtoupper(post('code')),
            'description'        => post('description'),
            'discount_type'      => post('discount_type'),
            'discount_value'     => (float)post('discount_value'),
            'min_order_value'    => (float)post('min_order_value'),
            'max_discount_amount'=> post('max_discount_amount') !== '' ? (float)post('max_discount_amount') : null,
            'usage_limit_total'  => post('usage_limit_total') !== '' ? (int)post('usage_limit_total') : null,
            'usage_limit_per_user'=> post('usage_limit_per_user') !== '' ? (int)post('usage_limit_per_user') : null,
            'valid_from'         => post('valid_from'),
            'valid_to'           => post('valid_to'),
            'is_active'          => post('is_active') ? 1 : 0,
        ];
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
            Database::query("UPDATE coupons SET $set WHERE id=?", array_merge(array_values($fields), [$id]));
            flashSet('success', 'Coupon updated.');
        } else {
            $cols = implode(', ', array_keys($fields));
            $vals = implode(', ', array_fill(0, count($fields), '?'));
            Database::query("INSERT INTO coupons ($cols) VALUES ($vals)", array_values($fields));
            flashSet('success', 'Coupon created.');
        }
    }

    if ($action === 'toggle') {
        $id  = (int)post('id');
        $cur = Database::fetchOne('SELECT is_active FROM coupons WHERE id=?', [$id])['is_active'] ?? 0;
        Database::query('UPDATE coupons SET is_active=? WHERE id=?', [$cur ? 0 : 1, $id]);
    }

    if ($action === 'delete') {
        Database::query('DELETE FROM coupons WHERE id=?', [(int)post('id')]);
        flashSet('success', 'Coupon deleted.');
    }

    redirect('/admin/coupons.php');
}

$coupons = Database::fetchAll(
    "SELECT c.*, (SELECT COUNT(*) FROM coupon_usage cu WHERE cu.coupon_id=c.id) AS use_count
     FROM coupons c ORDER BY c.created_at DESC"
);

$editId   = (int)get('edit');
$editItem = $editId ? Database::fetchOne('SELECT * FROM coupons WHERE id=?', [$editId]) : null;

$pageTitle = 'Coupons';
$pageHeading = 'Coupons';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Create / Edit Form -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 class="card-title"><?= $editItem ? 'Edit Coupon' : 'Create Coupon' ?></h3></div>
    <form method="POST" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
      <?= csrfField() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
      <div class="form-group">
        <label class="form-label">Code *</label>
        <input type="text" name="code" class="form-control" value="<?= e($editItem['code'] ?? '') ?>" style="text-transform:uppercase;" required>
      </div>
      <div class="form-group">
        <label class="form-label">Type *</label>
        <select name="discount_type" class="form-control" required>
          <option value="flat" <?= ($editItem['discount_type'] ?? '') === 'flat' ? 'selected' : '' ?>>Flat ₹</option>
          <option value="percent" <?= ($editItem['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>Percent %</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Discount Value *</label>
        <input type="number" name="discount_value" class="form-control" step="0.01" min="0" value="<?= $editItem['discount_value'] ?? '' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Min Order (₹)</label>
        <input type="number" name="min_order_value" class="form-control" step="0.01" min="0" value="<?= $editItem['min_order_value'] ?? 0 ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Max Discount (₹, blank = unlimited)</label>
        <input type="number" name="max_discount_amount" class="form-control" step="0.01" value="<?= $editItem['max_discount_amount'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Total Usage Limit (blank = unlimited)</label>
        <input type="number" name="usage_limit_total" class="form-control" value="<?= $editItem['usage_limit_total'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Per User Limit</label>
        <input type="number" name="usage_limit_per_user" class="form-control" value="<?= $editItem['usage_limit_per_user'] ?? 1 ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Valid From *</label>
        <input type="datetime-local" name="valid_from" class="form-control" value="<?= $editItem ? date('Y-m-d\TH:i', strtotime($editItem['valid_from'])) : '' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Valid To *</label>
        <input type="datetime-local" name="valid_to" class="form-control" value="<?= $editItem ? date('Y-m-d\TH:i', strtotime($editItem['valid_to'])) : '' ?>" required>
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" value="<?= e($editItem['description'] ?? '') ?>">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label><input type="checkbox" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
      </div>
      <div style="grid-column:1/-1;display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary"><?= $editItem ? 'Update' : 'Create Coupon' ?></button>
        <?php if ($editItem): ?><a href="/admin/coupons.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- List -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">All Coupons (<?= count($coupons) ?>)</h3></div>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Validity</th><th>Used</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($coupons as $c): ?>
      <tr>
        <td><strong><?= e($c['code']) ?></strong></td>
        <td><?= $c['discount_type'] === 'flat' ? 'Flat ₹' : 'Percent %' ?></td>
        <td><?= $c['discount_type'] === 'flat' ? '₹' . number_format($c['discount_value'],2) : $c['discount_value'] . '%' ?></td>
        <td><?= $c['min_order_value'] > 0 ? '₹' . number_format($c['min_order_value']) : '—' ?></td>
        <td style="font-size:0.78rem;"><?= date('d M Y', strtotime($c['valid_from'])) ?> – <?= date('d M Y', strtotime($c['valid_to'])) ?></td>
        <td><?= $c['use_count'] ?><?= $c['usage_limit_total'] ? '/' . $c['usage_limit_total'] : '' ?></td>
        <td><span class="badge badge-<?= $c['is_active'] ? 'success' : 'secondary' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td>
          <a href="/admin/coupons.php?edit=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
          <form method="POST" style="display:inline;">
            <?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm"><?= $c['is_active'] ? 'Disable' : 'Enable' ?></button>
          </form>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete coupon <?= e($c['code']) ?>?')">
            <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--dc-danger);">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
