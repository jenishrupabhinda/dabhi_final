<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_gst');

$activePage  = 'gst-settings';
$pageHeading = 'GST Settings & Rates';
$pageTitle   = 'GST Configuration';

if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'save_gst') {
        $isEnabled    = post('is_gst_enabled') ? 1 : 0;
        $businessName = trim(post('business_name'));
        $gstin        = strtoupper(trim(post('gstin')));
        $state        = trim(post('business_state'));
        $address      = trim(post('address'));

        $existing = Database::fetchOne('SELECT id FROM gst_settings LIMIT 1');
        if ($existing) {
            Database::query(
                "UPDATE gst_settings 
                 SET is_gst_enabled = ?, business_name = ?, gstin = ?, business_state = ?, address = ?, updated_at = NOW() 
                 WHERE id = ?",
                [$isEnabled, $businessName, $gstin, $state, $address, $existing['id']]
            );
        } else {
            Database::query(
                "INSERT INTO gst_settings (is_gst_enabled, business_name, gstin, business_state, address) 
                 VALUES (?, ?, ?, ?, ?)",
                [$isEnabled, $businessName, $gstin, $state, $address]
            );
        }

        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES ('gst_enabled', ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$isEnabled ? '1' : '0', Auth::id()]
        );

        flashSet('success', 'GST profile saved successfully.');
        redirect('/admin/gst-settings.php');
    }

    if ($action === 'update_product_gst') {
        $catId   = (int)post('category_id');
        $gstRate = (float)post('gst_rate_percent');
        if ($catId > 0 && $gstRate >= 0) {
            Database::query('UPDATE products SET gst_rate_percent = ? WHERE category_id = ?', [$gstRate, $catId]);
            flashSet('success', 'Updated GST rate for all products in category.');
        }
        redirect('/admin/gst-settings.php');
    }
}

$gstConfig  = Database::fetchOne('SELECT * FROM gst_settings LIMIT 1') ?? [];
$categories = Database::fetchAll(
    'SELECT c.id, c.name, 
       (SELECT AVG(p.gst_rate_percent) FROM products p WHERE p.category_id = c.id) AS avg_rate,
       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.sort_order ASC'
);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="margin-bottom:20px;">
    <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
    <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Set tax registration details and category-wise GST slab percentages for automated tax invoices.</p>
  </div>

  <!-- GST Profile Form -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3 class="card-title">🧾 Business Tax Profile</h3></div>
    <form method="POST" style="margin-top:14px;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_gst">

      <div style="margin-bottom:16px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;font-size:1rem;">
          <input type="checkbox" name="is_gst_enabled" value="1" <?= ($gstConfig['is_gst_enabled'] ?? 1) ? 'checked' : '' ?> style="width:20px;height:20px;">
          Enable GST Invoicing on Store Orders
        </label>
        <p class="form-hint" style="margin-top:4px;">When enabled, customers within your business state are charged CGST + SGST (split equally), and other states are charged IGST.</p>
      </div>

      <div class="adm-form-grid-3">
        <div class="form-group">
          <label class="form-label">Legal Business Name <span style="color:var(--dc-danger);">*</span></label>
          <input type="text" name="business_name" class="form-control" value="<?= e($gstConfig['business_name'] ?? 'Dabhi Chikki') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">GSTIN (15-character Tax ID)</label>
          <input type="text" name="gstin" class="form-control" maxlength="15" placeholder="e.g. 24AAAAA0000A1Z5" value="<?= e($gstConfig['gstin'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Business State (Origin)</label>
          <input type="text" name="business_state" class="form-control" value="<?= e($gstConfig['business_state'] ?? 'Gujarat') ?>" required>
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Registered Tax Address</label>
          <textarea name="address" class="form-control" rows="2" placeholder="Full registered address printed on invoice header..."><?= e($gstConfig['address'] ?? '') ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:12px;">Save Tax Profile</button>
    </form>
  </div>

  <!-- Category GST Rates -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">🏷 Category-wise GST Slabs</h3></div>
    <div class="table-wrap" style="margin-top:10px;">
      <table class="dc-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>Products</th>
            <th>Current Rate</th>
            <th>Bulk Update Rate</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): 
            $rate = $cat['avg_rate'] !== null ? round((float)$cat['avg_rate'], 1) : 5.0;
          ?>
          <tr>
            <td><strong><?= e($cat['name']) ?></strong></td>
            <td><?= number_format($cat['product_count']) ?></td>
            <td><span class="badge badge-warning"><?= $rate ?>% GST</span></td>
            <td>
              <form method="POST" style="display:flex;gap:8px;align-items:center;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_product_gst">
                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                <select name="gst_rate_percent" class="form-control form-control-sm" style="width:90px;">
                  <option value="0" <?= $rate == 0 ? 'selected' : '' ?>>0%</option>
                  <option value="5" <?= $rate == 5 ? 'selected' : '' ?>>5%</option>
                  <option value="12" <?= $rate == 12 ? 'selected' : '' ?>>12%</option>
                  <option value="18" <?= $rate == 18 ? 'selected' : '' ?>>18%</option>
                </select>
                <button type="submit" class="btn btn-ghost btn-sm">Apply to all</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
