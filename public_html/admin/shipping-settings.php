<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_shipping_rules');

$activePage  = 'shipping-settings';
$pageHeading = 'Shipping & Zones';
$pageTitle   = 'Shipping & Zones';

// Handle POST
if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'save_general') {
        $freeThreshold = (float)post('free_shipping_threshold');
        $defaultCharge = (float)post('default_shipping_charge');
        $codEnabled    = post('cod_enabled') ? '1' : '0';

        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES ('free_shipping_threshold', ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$freeThreshold, Auth::id()]
        );
        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES ('default_shipping_charge', ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$defaultCharge, Auth::id()]
        );
        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES ('cod_enabled', ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$codEnabled, Auth::id()]
        );

        flashSet('success', 'General shipping rules saved.');
        redirect('/admin/shipping-settings.php');
    }

    if ($action === 'add_rate_slab') {
        $zoneId      = (int)post('zone_id');
        $fromGrams   = (int)post('weight_from_grams');
        $toGrams     = (int)post('weight_to_grams');
        $rate        = (float)post('rate');
        $codExtra    = (float)post('cod_extra_charge');

        if ($zoneId && $toGrams > $fromGrams) {
            Database::query(
                "INSERT INTO shipping_rates (zone_id, weight_from_grams, weight_to_grams, rate, cod_extra_charge)
                 VALUES (?, ?, ?, ?, ?)",
                [$zoneId, $fromGrams, $toGrams, $rate, $codExtra]
            );
            flashSet('success', 'Shipping rate slab added.');
        } else {
            flashSet('error', 'Invalid weight range or zone.');
        }
        redirect('/admin/shipping-settings.php');
    }

    if ($action === 'delete_rate_slab') {
        $rateId = (int)post('rate_id');
        Database::query("DELETE FROM shipping_rates WHERE id = ?", [$rateId]);
        flashSet('success', 'Rate slab deleted.');
        redirect('/admin/shipping-settings.php');
    }

    if ($action === 'add_pincode') {
        $pincode    = preg_replace('/\D/', '', post('pincode'));
        $zoneId     = (int)post('zone_id');
        $serviceable= post('is_serviceable') ? 1 : 0;
        $codAvail   = post('cod_available') ? 1 : 0;

        if (strlen($pincode) === 6 && $zoneId) {
            Database::query(
                "INSERT INTO pincode_zones (pincode, zone_id, is_serviceable, cod_available)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE zone_id=VALUES(zone_id), is_serviceable=VALUES(is_serviceable), cod_available=VALUES(cod_available)",
                [$pincode, $zoneId, $serviceable, $codAvail]
            );
            flashSet('success', "Pincode {$pincode} updated.");
        } else {
            flashSet('error', 'Please enter a valid 6-digit pincode and select a zone.');
        }
        redirect('/admin/shipping-settings.php');
    }
}

// Fetch current values
$freeShippingThreshold = getSetting('free_shipping_threshold', '499');
$defaultShippingCharge = getSetting('default_shipping_charge', '50');
$codEnabled            = getSetting('cod_enabled', '1') === '1';

$zones = Database::fetchAll("SELECT * FROM shipping_zones ORDER BY id ASC");
$rates = Database::fetchAll(
    "SELECT sr.*, sz.name AS zone_name
     FROM shipping_rates sr
     JOIN shipping_zones sz ON sz.id = sr.zone_id
     ORDER BY sz.id ASC, sr.weight_from_grams ASC"
);

$pincodes = Database::fetchAll(
    "SELECT pz.*, sz.name AS zone_name
     FROM pincode_zones pz
     JOIN shipping_zones sz ON sz.id = pz.zone_id
     ORDER BY pz.pincode ASC LIMIT 50"
);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="margin-bottom:20px;">
    <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
    <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Configure delivery charges, weight slabs, and serviceable pincode regions.</p>
  </div>

  <!-- General rules -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3 class="card-title">📦 General Shipping Policy</h3></div>
    <form method="POST" style="margin-top:12px;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_general">
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;">
        <div class="form-group">
          <label class="form-label">Free Shipping Threshold (₹)</label>
          <input type="number" name="free_shipping_threshold" class="form-control" value="<?= e($freeShippingThreshold) ?>" min="0">
          <p class="form-hint">Orders above this amount get free standard delivery.</p>
        </div>
        <div class="form-group">
          <label class="form-label">Default Shipping Charge (₹)</label>
          <input type="number" name="default_shipping_charge" class="form-control" value="<?= e($defaultShippingCharge) ?>" min="0">
          <p class="form-hint">Fallback charge if weight slab does not match.</p>
        </div>
        <div class="form-group" style="display:flex;align-items:center;padding-top:20px;">
          <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
            <input type="checkbox" name="cod_enabled" value="1" <?= $codEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
            Enable Cash on Delivery (COD)
          </label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save General Rules</button>
    </form>
  </div>

  <!-- Weight Slab Rates -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3 class="card-title">⚖️ Weight Slabs & Shipping Rates</h3>
      <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addSlabForm').style.display='block';">+ Add Rate Slab</button>
    </div>

    <!-- Add Slab Form -->
    <div id="addSlabForm" style="display:none;background:var(--dc-bg-soft);padding:16px;border-radius:var(--dc-radius-md);margin:16px 0;">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_rate_slab">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;align-items:flex-end;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Zone</label>
            <select name="zone_id" class="form-control" required>
              <?php foreach ($zones as $z): ?>
                <option value="<?= $z['id'] ?>"><?= e($z['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">From (grams)</label>
            <input type="number" name="weight_from_grams" class="form-control" required value="0">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">To (grams)</label>
            <input type="number" name="weight_to_grams" class="form-control" required value="500">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Delivery Fee (₹)</label>
            <input type="number" step="0.5" name="rate" class="form-control" required value="40">
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">COD Extra (₹)</label>
            <input type="number" step="0.5" name="cod_extra_charge" class="form-control" value="20">
          </div>
          <div style="display:flex;gap:6px;">
            <button type="submit" class="btn btn-primary btn-sm">Add Slab</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('addSlabForm').style.display='none';">Cancel</button>
          </div>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="dc-table">
        <thead>
          <tr>
            <th>Zone</th>
            <th>Weight Range</th>
            <th>Standard Rate</th>
            <th>COD Extra Charge</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rates as $r): ?>
          <tr>
            <td><strong><?= e($r['zone_name']) ?></strong></td>
            <td><?= number_format($r['weight_from_grams']) ?>g &ndash; <?= number_format($r['weight_to_grams']) ?>g</td>
            <td><?= formatINR((float)$r['rate']) ?></td>
            <td><?= formatINR((float)$r['cod_extra_charge']) ?></td>
            <td>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this rate slab?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_rate_slab">
                <input type="hidden" name="rate_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--dc-danger);">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pincode Zone Mapping -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3 class="card-title">📍 Pincode Serviceability</h3>
    </div>
    
    <form method="POST" style="background:var(--dc-bg-soft);padding:14px;border-radius:var(--dc-radius-md);margin-bottom:16px;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_pincode">
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;min-width:140px;">
          <label class="form-label">Add / Edit Pincode</label>
          <input type="text" name="pincode" maxlength="6" class="form-control form-control-sm" placeholder="e.g. 360001" required>
        </div>
        <div class="form-group" style="margin:0;min-width:160px;">
          <label class="form-label">Assigned Zone</label>
          <select name="zone_id" class="form-control form-control-sm" required>
            <?php foreach ($zones as $z): ?>
              <option value="<?= $z['id'] ?>"><?= e($z['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;display:flex;align-items:center;gap:6px;height:34px;">
          <label style="cursor:pointer;display:flex;align-items:center;gap:4px;font-size:0.88rem;">
            <input type="checkbox" name="is_serviceable" value="1" checked> Serviceable
          </label>
        </div>
        <div class="form-group" style="margin:0;display:flex;align-items:center;gap:6px;height:34px;">
          <label style="cursor:pointer;display:flex;align-items:center;gap:4px;font-size:0.88rem;">
            <input type="checkbox" name="cod_available" value="1" checked> COD Available
          </label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save Pincode</button>
      </div>
    </form>

    <div class="table-wrap">
      <table class="dc-table">
        <thead>
          <tr>
            <th>Pincode</th>
            <th>Zone</th>
            <th>Serviceable</th>
            <th>COD Available</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pincodes)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:20px;">No pincodes registered yet. Add one above.</td></tr>
          <?php else: ?>
            <?php foreach ($pincodes as $pz): ?>
            <tr>
              <td><strong><?= e($pz['pincode']) ?></strong></td>
              <td><?= e($pz['zone_name']) ?></td>
              <td><span class="badge badge-<?= $pz['is_serviceable'] ? 'success' : 'danger' ?>"><?= $pz['is_serviceable'] ? 'Yes' : 'No' ?></span></td>
              <td><span class="badge badge-<?= $pz['cod_available'] ? 'success' : 'neutral' ?>"><?= $pz['cod_available'] ? 'Available' : 'Prepaid Only' ?></span></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
