<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_payment_settings');

$activePage      = 'payment-settings';
$pageTitle       = 'Payment Settings';
$pageHeading     = 'Payment Settings';
$pageBreadcrumbs = [
    ['label' => 'Settings', 'url' => url('admin/settings.php')],
    ['label' => 'Payment Settings']
];

// Handle AJAX credential test
if (isPost() && post('action') === 'test_cashfree') {
    header('Content-Type: application/json; charset=utf-8');
    $appId     = post('app_id');
    $secretKey = post('secret_key');
    $mode      = post('mode');

    if (!$appId || !$secretKey) {
        echo json_encode(['ok' => false, 'error' => 'App ID and Secret Key are required.']);
        exit;
    }

    $baseUrl = ($mode === 'production')
        ? 'https://api.cashfree.com/pg'
        : 'https://sandbox.cashfree.com/pg';

    $ch = curl_init("{$baseUrl}/orders?limit=1");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-client-id: ' . $appId,
            'x-client-secret: ' . $secretKey,
            'x-api-version: 2023-08-01',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
        exit;
    }

    $body = json_decode($response, true);
    if ($httpCode === 200) {
        echo json_encode(['ok' => true, 'message' => 'Credentials verified successfully! Connected to Cashfree.']);
    } elseif ($httpCode === 401) {
        echo json_encode(['ok' => false, 'error' => 'Authentication failed (401). Check your App ID and Secret Key.']);
    } else {
        $msg = $body['message'] ?? "Cashfree responded with HTTP {$httpCode}";
        echo json_encode(['ok' => false, 'error' => $msg]);
    }
    exit;
}

if (isPost()) {
    csrfVerify();

    $codEnabled           = post('cod_enabled') ? '1' : '0';
    $cashfreeEnabled      = post('cashfree_enabled') ? '1' : '0';
    $paymentBypassEnabled = post('payment_bypass_enabled') ? '1' : '0';
    $cashfreeMode         = post('cashfree_mode') === 'production' ? 'production' : 'sandbox';
    $cashfreeAppId        = post('cashfree_app_id');
    $cashfreeSecretKey    = post('cashfree_secret_key');
    $upiQrEnabled         = post('upi_qr_enabled') ? '1' : '0';
    $upiId                = post('upi_id');
    $instructions         = post('payment_instructions');

    setSetting('cod_enabled',            $codEnabled);
    setSetting('cashfree_enabled',       $cashfreeEnabled);
    setSetting('payment_bypass_enabled', $paymentBypassEnabled);
    setSetting('cashfree_mode',          $cashfreeMode);
    setSetting('cashfree_app_id',        $cashfreeAppId);
    setSetting('cashfree_secret_key',    $cashfreeSecretKey);
    setSetting('upi_qr_enabled',         $upiQrEnabled);
    setSetting('upi_id',                 $upiId);
    setSetting('payment_instructions',   $instructions);

    if ($cashfreeSecretKey) {
        Database::query(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES ('cashfree_secret_key', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$cashfreeSecretKey]
        );
    }

    flashSet('success', 'Payment gateway settings updated successfully.');
    redirect('/admin/payment-settings.php');
}

$codEnabled           = getSetting('cod_enabled', '1') === '1';
$cashfreeEnabled      = getSetting('cashfree_enabled', '0') === '1';
$paymentBypassEnabled = getSetting('payment_bypass_enabled', '0') === '1';
$cashfreeMode         = (in_array(getSetting('cashfree_mode', 'sandbox'), ['production', 'live'], true)) ? 'production' : 'sandbox';
$cashfreeAppId        = CashfreeGateway::getAppId();
$cashfreeSecretKey    = CashfreeGateway::getSecretKey();
$upiQrEnabled         = getSetting('upi_qr_enabled', '0') === '1';
$upiId                = getSetting('upi_id', '');
$instructions         = getSetting('payment_instructions', '');

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Subtitle Bar (No duplicate heading) -->
  <div style="margin-bottom: 22px;">
    <p style="margin:0;color:var(--adm-text-muted);font-size:0.92rem;">
      Configure customer checkout payment options, Cashfree online payments, COD handling, simulation bypass, and direct UPI VPA.
    </p>
  </div>

  <form method="POST" id="payment-settings-form">
    <?= csrfField() ?>

    <!-- ── 1. Cash on Delivery (COD) ─────────────────────────────────── -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <h3 class="card-title">💵 Cash on Delivery (COD)</h3>
        <span class="badge badge-<?= $codEnabled ? 'success' : 'neutral' ?>">
          <?= $codEnabled ? 'Active' : 'Disabled' ?>
        </span>
      </div>
      <div class="card-body">
        <label style="cursor:pointer;display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.96rem;min-height:36px;">
          <input type="checkbox" name="cod_enabled" value="1" <?= $codEnabled ? 'checked' : '' ?> style="width:20px;height:20px;accent-color:var(--adm-terracotta);cursor:pointer;">
          Accept Cash on Delivery across serviceable pincodes
        </label>
        <p style="margin:8px 0 0 32px;font-size:0.85rem;color:var(--adm-text-muted);line-height:1.45;">
          Extra COD handling fee per weight slab can be adjusted under <a href="<?= url('admin/shipping-settings.php') ?>" style="color:var(--adm-terracotta);font-weight:600;">Shipping &amp; Zones</a>.
        </p>
      </div>
    </div>

    <!-- ── 2. Cashfree Online Payment Gateway ────────────────────────── -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <h3 class="card-title">💳 Cashfree Online Payment Gateway</h3>
        <span class="badge badge-<?= $cashfreeEnabled ? 'success' : 'neutral' ?>">
          <?= $cashfreeEnabled ? 'Enabled' : 'Disabled' ?>
        </span>
      </div>

      <div class="card-body">
        <label style="cursor:pointer;display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.96rem;margin-bottom:20px;min-height:36px;">
          <input type="checkbox" name="cashfree_enabled" value="1" <?= $cashfreeEnabled ? 'checked' : '' ?> style="width:20px;height:20px;accent-color:var(--adm-terracotta);cursor:pointer;">
          Enable Cashfree Payments (Cards, NetBanking, UPI, Wallets)
        </label>

        <!-- 3-Column Responsive Grid -->
        <div class="adm-form-grid-3">
          <div class="form-group">
            <label class="form-label" style="display:block;font-size:0.86rem;font-weight:600;margin-bottom:6px;">Environment Mode</label>
            <select name="cashfree_mode" id="cashfree_mode" class="form-control">
              <option value="sandbox" <?= $cashfreeMode === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test Mode</option>
              <option value="production" <?= $cashfreeMode === 'production' ? 'selected' : '' ?>>Production / Live Mode</option>
            </select>
            <p style="margin:4px 0 0;font-size:0.75rem;color:var(--adm-text-muted);">Switch to Production for live payments.</p>
          </div>

          <div class="form-group">
            <label class="form-label" style="display:block;font-size:0.86rem;font-weight:600;margin-bottom:6px;">App ID / Client ID</label>
            <input type="text" name="cashfree_app_id" id="cashfree_app_id" class="form-control" value="<?= e($cashfreeAppId) ?>" placeholder="CF_APP_ID or TEST..." autocomplete="off">
            <p style="margin:4px 0 0;font-size:0.75rem;color:var(--adm-text-muted);">From Cashfree Merchant Dashboard.</p>
          </div>

          <div class="form-group">
            <label class="form-label" style="display:block;font-size:0.86rem;font-weight:600;margin-bottom:6px;">Secret Key</label>
            <div style="position:relative;display:flex;align-items:center;">
              <input type="password" name="cashfree_secret_key" id="cashfree_secret_key" class="form-control" value="<?= e($cashfreeSecretKey) ?>" placeholder="CF_SECRET_KEY..." autocomplete="new-password" style="padding-right:42px;">
              <button type="button" id="toggleSecretKeyBtn" aria-label="Toggle secret key visibility" style="position:absolute;right:8px;background:none;border:none;cursor:pointer;color:#777;padding:6px;display:flex;align-items:center;font-size:15px;" title="Show/Hide secret key">
                👁️
              </button>
            </div>
            <p style="margin:4px 0 0;font-size:0.75rem;color:var(--adm-text-muted);">Never share your live secret key.</p>
          </div>
        </div>

        <div style="margin-top:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <button type="button" id="btn-test-cashfree" class="btn btn-outline btn-sm" style="min-height:40px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;touch-action:manipulation;">
            ⚡ Test Cashfree Credentials
          </button>
          <span id="cashfree-test-status" style="font-size:0.88rem;font-weight:600;"></span>
        </div>
      </div>
    </div>

    <!-- ── 3. Payment Gateway Bypass (Simulation Mode) ────────────────── -->
    <div class="card" style="margin-bottom:20px;border-left:4px solid <?= $paymentBypassEnabled ? '#eab308' : 'var(--adm-terracotta)' ?>;">
      <div class="card-header">
        <h3 class="card-title">🚀 Payment Gateway Bypass (Simulation Mode)</h3>
        <span class="badge badge-<?= $paymentBypassEnabled ? 'warning' : 'success' ?>">
          <?= $paymentBypassEnabled ? 'Simulation Bypass Active' : 'Live Gateway Active' ?>
        </span>
      </div>
      <div class="card-body">
        <label style="cursor:pointer;display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.96rem;min-height:36px;">
          <input type="checkbox" name="payment_bypass_enabled" id="payment_bypass_enabled" value="1" <?= $paymentBypassEnabled ? 'checked' : '' ?> style="width:20px;height:20px;accent-color:var(--adm-terracotta);cursor:pointer;">
          Enable Instant Payment Gateway Bypass for Online Orders
        </label>

        <?php if ($paymentBypassEnabled): ?>
          <div style="margin-top:14px;padding:14px 18px;background:rgba(234,179,8,0.12);border-left:4px solid #eab308;border-radius:8px;font-size:0.88rem;color:#854d0e;line-height:1.5;">
            ⚠️ <strong>Simulation Bypass is currently ACTIVE.</strong> When checked, all online checkouts will auto-confirm immediately and will <strong>NOT</strong> redirect to Cashfree. <strong>Uncheck this checkbox</strong> and click Save if you want customers to be redirected to Cashfree for real/test payment.
          </div>
        <?php else: ?>
          <div style="margin-top:14px;padding:14px 18px;background:rgba(27,138,90,0.1);border-left:4px solid #1b8a5a;border-radius:8px;font-size:0.88rem;color:#126842;line-height:1.5;">
            ✓ <strong>Live Gateway Redirection Active.</strong> Customers choosing Pay Online will be redirected directly to Cashfree for real/test payment.
          </div>
        <?php endif; ?>

        <p style="margin:10px 0 0 0;font-size:0.82rem;color:var(--adm-text-muted);line-height:1.5;">
          When bypass is enabled, orders are instantly marked as <strong>Paid &amp; Confirmed</strong> without external gateway redirection. Useful for rapid testing of invoice and email generation without interacting with Cashfree.
        </p>
      </div>
    </div>

    <!-- ── 4. UPI Direct / QR ─────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <h3 class="card-title">📱 Direct UPI Transfer</h3>
        <span class="badge badge-<?= $upiQrEnabled ? 'success' : 'neutral' ?>">
          <?= $upiQrEnabled ? 'Active' : 'Disabled' ?>
        </span>
      </div>
      <div class="card-body">
        <label style="cursor:pointer;display:flex;align-items:center;gap:12px;font-weight:600;font-size:0.96rem;margin-bottom:14px;min-height:36px;">
          <input type="checkbox" name="upi_qr_enabled" value="1" <?= $upiQrEnabled ? 'checked' : '' ?> style="width:20px;height:20px;accent-color:var(--adm-terracotta);cursor:pointer;">
          Allow direct UPI VPA payment / QR scan
        </label>

        <div class="form-group" style="max-width:380px;">
          <label class="form-label" style="display:block;font-size:0.86rem;font-weight:600;margin-bottom:6px;">UPI ID / VPA</label>
          <input type="text" name="upi_id" class="form-control" value="<?= e($upiId) ?>" placeholder="e.g. dabhichikki@upi">
          <p style="margin:4px 0 0;font-size:0.75rem;color:var(--adm-text-muted);">Displayed to customer during checkout for direct app transfers.</p>
        </div>
      </div>
    </div>

    <!-- ── 5. Payment Notice ──────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <h3 class="card-title">📝 Checkout Payment Notice</h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label" style="display:block;font-size:0.86rem;font-weight:600;margin-bottom:6px;">Customer Notice / Instructions</label>
          <textarea name="payment_instructions" class="form-control" rows="3" placeholder="Displayed to customer on checkout payment selection screen..."><?= e($instructions) ?></textarea>
          <p style="margin:4px 0 0;font-size:0.75rem;color:var(--adm-text-muted);">Optional custom notes shown above payment method options.</p>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div style="margin-bottom:40px;">
      <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-size:1rem;min-height:46px;border-radius:10px;">
        💾 Save Payment Settings
      </button>
    </div>
  </form>
</div>

<script>
// Show/Hide Secret Key toggle
document.getElementById('toggleSecretKeyBtn')?.addEventListener('click', function() {
  const input = document.getElementById('cashfree_secret_key');
  if (input) {
    if (input.type === 'password') {
      input.type = 'text';
      this.textContent = '🙈';
    } else {
      input.type = 'password';
      this.textContent = '👁️';
    }
  }
});

// Test Cashfree Credentials AJAX
document.getElementById('btn-test-cashfree')?.addEventListener('click', async function() {
  const btn = this;
  const statusEl = document.getElementById('cashfree-test-status');
  const appId = document.getElementById('cashfree_app_id').value.trim();
  const secretKey = document.getElementById('cashfree_secret_key').value.trim();
  const mode = document.getElementById('cashfree_mode').value;

  if (!appId || !secretKey) {
    statusEl.style.color = '#dc2626';
    statusEl.textContent = '❌ Please enter both App ID and Secret Key above before testing.';
    return;
  }

  btn.disabled = true;
  statusEl.style.color = '#4b5563';
  statusEl.textContent = 'Connecting to Cashfree (' + mode + ')…';

  try {
    const formData = new FormData();
    formData.append('action', 'test_cashfree');
    formData.append('app_id', appId);
    formData.append('secret_key', secretKey);
    formData.append('mode', mode);

    const res = await fetch('payment-settings.php', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();
    if (data.ok) {
      statusEl.style.color = '#16a34a';
      statusEl.textContent = '✓ ' + data.message;
    } else {
      statusEl.style.color = '#dc2626';
      statusEl.textContent = '❌ ' + (data.error || 'Connection test failed.');
    }
  } catch (err) {
    statusEl.style.color = '#dc2626';
    statusEl.textContent = '❌ Connection error: ' + (err.message || 'Could not contact server.');
  } finally {
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
