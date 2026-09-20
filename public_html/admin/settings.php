<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_settings');

if (isPost()) {
    csrfVerify();

    $settingKeys = [
        // Notifications
        'email_notifications_enabled', 'whatsapp_enabled', 'sms_enabled',
        // Commerce
        'cod_enabled', 'free_shipping_threshold', 'default_shipping_charge',
        // GST
        'gst_enabled',
        // Lockout
        'login_max_attempts', 'login_lockout_minutes',
        // Box
        'box_weight_limit_grams',
        // App
        'app_name', 'support_email', 'support_phone', 'business_address',
    ];

    foreach ($settingKeys as $key) {
        $val = post($key);
        if ($val !== null) {
            Database::query(
                "INSERT INTO settings (setting_key, setting_value, updated_by)
                 VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
                [$key, $val, Auth::id()]
            );
        }
    }

    // GST settings row
    $gst = Database::fetchOne('SELECT id FROM gst_settings LIMIT 1');
    if ($gst) {
        Database::query(
            'UPDATE gst_settings SET business_name=?, gstin=?, business_state=?, address=?, is_gst_enabled=? WHERE id=?',
            [post('gst_business_name'), post('gstin'), post('business_state'), post('gst_address'), post('is_gst_enabled') ? 1 : 0, $gst['id']]
        );
    } else {
        Database::query(
            'INSERT INTO gst_settings (business_name, gstin, business_state, address, is_gst_enabled) VALUES (?,?,?,?,?)',
            [post('gst_business_name'), post('gstin'), post('business_state'), post('gst_address'), post('is_gst_enabled') ? 1 : 0]
        );
    }

    flashSet('success', 'Settings saved.');
    redirect('/admin/settings.php');
}

// Load all settings into a lookup map
$rawSettings = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
$cfg = [];
foreach ($rawSettings as $r) $cfg[$r['setting_key']] = $r['setting_value'];
$gstCfg = Database::fetchOne('SELECT * FROM gst_settings LIMIT 1') ?? [];

function s(string $key, $default = ''): string { global $cfg; return htmlspecialchars($cfg[$key] ?? $default); }
function sc(string $key, string $match = '1'): string { global $cfg; return ($cfg[$key] ?? '1') === $match ? 'checked' : ''; }

$pageTitle = 'Settings';
$pageHeading = 'Settings';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>
  <form method="POST">
    <?= csrfField() ?>

    <!-- App -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3 class="card-title">🏪 Store Info</h3></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group"><label class="form-label">App / Store Name</label>
          <input type="text" name="app_name" class="form-control" value="<?= s('app_name','Dabhi Chikki') ?>"></div>
        <div class="form-group"><label class="form-label">Support Email</label>
          <input type="email" name="support_email" class="form-control" value="<?= s('support_email') ?>"></div>
        <div class="form-group"><label class="form-label">Support Phone</label>
          <input type="text" name="support_phone" class="form-control" value="<?= s('support_phone') ?>"></div>
        <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Business Address (for labels/invoices)</label>
          <input type="text" name="business_address" class="form-control" value="<?= s('business_address') ?>"></div>
      </div>
    </div>

    <!-- Notifications -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3 class="card-title">🔔 Notification Channels</h3></div>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <label><input type="checkbox" name="email_notifications_enabled" value="1" <?= sc('email_notifications_enabled') ?>> Email Notifications</label>
        <label><input type="checkbox" name="whatsapp_enabled" value="1" <?= sc('whatsapp_enabled','0') ?>> WhatsApp Notifications</label>
        <label><input type="checkbox" name="sms_enabled" value="1" <?= sc('sms_enabled','0') ?>> SMS Notifications</label>
      </div>
    </div>

    <!-- Commerce -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3 class="card-title">🛒 Commerce Settings</h3></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label><input type="checkbox" name="cod_enabled" value="1" <?= sc('cod_enabled') ?>> Enable Cash on Delivery</label>
        </div>
        <div></div>
        <div class="form-group"><label class="form-label">Free Shipping Threshold (₹)</label>
          <input type="number" name="free_shipping_threshold" class="form-control" value="<?= s('free_shipping_threshold','0') ?>" step="1"></div>
        <div class="form-group"><label class="form-label">Default Shipping Charge (₹)</label>
          <input type="number" name="default_shipping_charge" class="form-control" value="<?= s('default_shipping_charge','50') ?>" step="1"></div>
        <div class="form-group"><label class="form-label">Box Weight Limit (grams)</label>
          <input type="number" name="box_weight_limit_grams" class="form-control" value="<?= s('box_weight_limit_grams','1000') ?>"></div>
      </div>
    </div>

    <!-- GST -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3 class="card-title">📋 GST Configuration</h3></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group" style="grid-column:1/-1;">
          <label><input type="checkbox" name="is_gst_enabled" value="1"
            <?= ($gstCfg['is_gst_enabled'] ?? 1) ? 'checked' : '' ?>> GST Enabled on all orders</label>
        </div>
        <div class="form-group"><label class="form-label">Business Name (on invoice)</label>
          <input type="text" name="gst_business_name" class="form-control" value="<?= htmlspecialchars($gstCfg['business_name'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">GSTIN</label>
          <input type="text" name="gstin" class="form-control" placeholder="22AAAAA0000A1Z5" value="<?= htmlspecialchars($gstCfg['gstin'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Business State (for CGST/IGST split)</label>
          <input type="text" name="business_state" class="form-control" placeholder="Gujarat" value="<?= htmlspecialchars($gstCfg['business_state'] ?? 'Gujarat') ?>"></div>
        <div class="form-group"><label class="form-label">Business Address (invoice footer)</label>
          <input type="text" name="gst_address" class="form-control" value="<?= htmlspecialchars($gstCfg['address'] ?? '') ?>"></div>
      </div>
    </div>

    <!-- Security -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3 class="card-title">🔐 Security</h3></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group"><label class="form-label">Max Login Attempts</label>
          <input type="number" name="login_max_attempts" class="form-control" value="<?= s('login_max_attempts','5') ?>" min="3" max="20"></div>
        <div class="form-group"><label class="form-label">Lockout Duration (minutes)</label>
          <input type="number" name="login_lockout_minutes" class="form-control" value="<?= s('login_lockout_minutes','15') ?>" min="5" max="180"></div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>
  </form>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
