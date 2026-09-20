<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_payment_settings');

$activePage  = 'payment-settings';
$pageHeading = 'Payment Settings';
$pageTitle   = 'Payment Settings';

if (isPost()) {
    csrfVerify();

    $fields = [
        'cod_enabled',
        'cashfree_enabled',
        'cashfree_mode',
        'cashfree_app_id',
        'cashfree_secret_key',
        'upi_qr_enabled',
        'upi_id',
        'payment_instructions',
    ];

    foreach ($fields as $key) {
        $val = post($key);
        if ($key === 'cod_enabled' || $key === 'cashfree_enabled' || $key === 'upi_qr_enabled') {
            $val = $val ? '1' : '0';
        }
        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$key, (string)$val, Auth::id()]
        );
    }

    flashSet('success', 'Payment gateway settings updated successfully.');
    redirect('/admin/payment-settings.php');
}

$codEnabled        = getSetting('cod_enabled', '1') === '1';
$cashfreeEnabled   = getSetting('cashfree_enabled', '0') === '1';
$cashfreeMode      = getSetting('cashfree_mode', 'sandbox');
$cashfreeAppId     = getSetting('cashfree_app_id', '');
$cashfreeSecretKey = getSetting('cashfree_secret_key', '');
$upiQrEnabled      = getSetting('upi_qr_enabled', '0') === '1';
$upiId             = getSetting('upi_id', '');
$instructions      = getSetting('payment_instructions', '');

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="margin-bottom:20px;">
    <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
    <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Configure payment gateways, Cash on Delivery, and UPI payment methods.</p>
  </div>

  <form method="POST">
    <?= csrfField() ?>

    <!-- Cash on Delivery -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">💵 Cash on Delivery (COD)</h3></div>
      <div style="margin-top:12px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;font-size:1rem;">
          <input type="checkbox" name="cod_enabled" value="1" <?= $codEnabled ? 'checked' : '' ?> style="width:20px;height:20px;">
          Accept Cash on Delivery across serviceable pincodes
        </label>
        <p class="form-hint" style="margin-top:6px;">Extra COD handling fee per weight slab can be adjusted under <a href="<?= url('admin/shipping-settings.php') ?>">Shipping &amp; Zones</a>.</p>
      </div>
    </div>

    <!-- Cashfree Payment Gateway -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 class="card-title">💳 Cashfree Online Payment Gateway</h3>
        <span class="badge badge-<?= $cashfreeEnabled ? 'success' : 'neutral' ?>">
          <?= $cashfreeEnabled ? 'Enabled' : 'Disabled' ?>
        </span>
      </div>

      <div style="margin-top:12px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;margin-bottom:16px;">
          <input type="checkbox" name="cashfree_enabled" value="1" <?= $cashfreeEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          Enable Cashfree Payments (Cards, NetBanking, UPI, Wallets)
        </label>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
          <div class="form-group">
            <label class="form-label">Environment Mode</label>
            <select name="cashfree_mode" class="form-control">
              <option value="sandbox" <?= $cashfreeMode === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test Mode</option>
              <option value="production" <?= $cashfreeMode === 'production' ? 'selected' : '' ?>>Production / Live Mode</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">App ID / Client ID</label>
            <input type="text" name="cashfree_app_id" class="form-control" value="<?= e($cashfreeAppId) ?>" placeholder="CF_APP_ID...">
          </div>

          <div class="form-group">
            <label class="form-label">Secret Key</label>
            <input type="password" name="cashfree_secret_key" class="form-control" value="<?= e($cashfreeSecretKey) ?>" placeholder="CF_SECRET_KEY...">
          </div>
        </div>
      </div>
    </div>

    <!-- UPI Direct / QR -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">📱 Direct UPI Transfer</h3></div>
      <div style="margin-top:12px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;margin-bottom:14px;">
          <input type="checkbox" name="upi_qr_enabled" value="1" <?= $upiQrEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          Allow direct UPI VPA payment / QR scan
        </label>

        <div class="form-group" style="max-width:360px;">
          <label class="form-label">UPI ID / VPA</label>
          <input type="text" name="upi_id" class="form-control" value="<?= e($upiId) ?>" placeholder="e.g. dabhichikki@upi">
        </div>
      </div>
    </div>

    <!-- Payment Notice -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">📝 Checkout Payment Notice</h3></div>
      <div class="form-group" style="margin-top:12px;">
        <label class="form-label">Customer Notice / Instructions</label>
        <textarea name="payment_instructions" class="form-control" rows="3" placeholder="Displayed to customer on checkout payment selection screen..."><?= e($instructions) ?></textarea>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save Payment Settings</button>
  </form>
</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
