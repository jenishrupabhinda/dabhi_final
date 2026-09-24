<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_payment_settings');

$activePage  = 'payment-settings';
$pageHeading = 'Payment Settings';
$pageTitle   = 'Payment Settings';

if (isPost() && post('action') === 'test_cashfree') {
    $testMode   = post('mode', 'sandbox');
    $testAppId  = trim(post('app_id', ''));
    $testSecret = trim(post('secret_key', ''));

    if (empty($testAppId) || empty($testSecret)) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Please enter both App ID and Secret Key first to test.']);
        exit;
    }

    $baseUrl = ($testMode === 'production' || $testMode === 'live')
        ? 'https://api.cashfree.com/pg'
        : 'https://sandbox.cashfree.com/pg';

    $ch = curl_init($baseUrl . '/orders');
    $headers = [
        'Content-Type: application/json',
        'x-api-version: 2023-08-01',
        'x-client-id: ' . $testAppId,
        'x-client-secret: ' . $testSecret,
    ];

    $testBody = [
        'order_id'         => 'TEST_CONN_' . time(),
        'order_amount'     => 1.00,
        'order_currency'   => 'INR',
        'customer_details' => [
            'customer_id'    => 'test_admin_probe',
            'customer_email' => 'test@dabhichikki.com',
            'customer_phone' => '9876543210',
            'customer_name'  => 'Admin Probe',
        ],
        'order_meta' => [
            'return_url' => rtrim(APP_URL, '/') . '/order-success.php?order_id={order_id}',
        ]
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($testBody),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    header('Content-Type: application/json');
    if ($status === 200 || $status === 201) {
        echo json_encode(['ok' => true, 'message' => 'Credentials verified! Successfully connected to Cashfree (' . ucfirst($testMode) . ').']);
    } elseif ($status === 401) {
        echo json_encode(['ok' => false, 'error' => 'Authentication Failed (HTTP 401): Please verify that your App ID and Secret Key match the ' . ucfirst($testMode) . ' environment.']);
    } else {
        $json = json_decode($raw, true);
        $errMsg = $json['message'] ?? ($curlErr ?: "Cashfree returned HTTP {$status}: {$raw}");
        echo json_encode(['ok' => false, 'error' => $errMsg]);
    }
    exit;
}

if (isPost()) {
    csrfVerify();

    $fields = [
        'cod_enabled',
        'cashfree_enabled',
        'payment_bypass_enabled',
        'cashfree_mode',
        'cashfree_app_id',
        'cashfree_secret_key',
        'upi_qr_enabled',
        'upi_id',
        'payment_instructions',
    ];

    foreach ($fields as $key) {
        $val = post($key);
        if ($key === 'cod_enabled' || $key === 'cashfree_enabled' || $key === 'upi_qr_enabled' || $key === 'payment_bypass_enabled') {
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

  <div style="margin-bottom:20px;">
    <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
    <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Configure payment gateways, Cash on Delivery, and UPI payment methods.</p>
  </div>

  <form method="POST" id="payment-settings-form">
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
            <select name="cashfree_mode" id="cashfree_mode" class="form-control">
              <option value="sandbox" <?= $cashfreeMode === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test Mode</option>
              <option value="production" <?= $cashfreeMode === 'production' ? 'selected' : '' ?>>Production / Live Mode</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">App ID / Client ID</label>
            <input type="text" name="cashfree_app_id" id="cashfree_app_id" class="form-control" value="<?= e($cashfreeAppId) ?>" placeholder="CF_APP_ID or TEST...">
          </div>

          <div class="form-group">
            <label class="form-label">Secret Key</label>
            <input type="password" name="cashfree_secret_key" id="cashfree_secret_key" class="form-control" value="<?= e($cashfreeSecretKey) ?>" placeholder="CF_SECRET_KEY...">
          </div>
        </div>

        <div style="margin-top:16px;display:flex;align-items:center;gap:12px;">
          <button type="button" id="btn-test-cashfree" class="btn btn-outline btn-sm">
            ⚡ Test Cashfree Credentials
          </button>
          <span id="cashfree-test-status" style="font-size:0.85rem;font-weight:600;"></span>
        </div>
      </div>
    </div>

    <!-- Payment Gateway Bypass (Simulation Mode) -->
    <div class="card" style="margin-bottom:24px;border-left:4px solid <?= $paymentBypassEnabled ? '#eab308' : 'var(--dc-primary,#541f21)' ?>;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 class="card-title">🚀 Payment Gateway Bypass (Simulation Mode)</h3>
        <span class="badge badge-<?= $paymentBypassEnabled ? 'warning' : 'success' ?>">
          <?= $paymentBypassEnabled ? 'Bypass Active (Auto-Confirm)' : 'Live Gateway Active' ?>
        </span>
      </div>
      <div style="margin-top:12px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;font-size:1rem;">
          <input type="checkbox" name="payment_bypass_enabled" id="payment_bypass_enabled" value="1" <?= $paymentBypassEnabled ? 'checked' : '' ?> style="width:20px;height:20px;">
          Enable Instant Payment Gateway Bypass for Online Orders
        </label>

        <?php if ($paymentBypassEnabled): ?>
          <div style="margin-top:12px;padding:12px 16px;background:rgba(234,179,8,0.12);border-left:4px solid #eab308;border-radius:6px;font-size:0.9rem;color:#854d0e;">
            ⚠️ <strong>Simulation Bypass is currently ACTIVE.</strong> When checked, all online checkouts will auto-confirm immediately and will <strong>NOT</strong> redirect to Cashfree. <strong>Uncheck this checkbox</strong> and click Save if you want customers to be redirected to Cashfree for test/live payment.
          </div>
        <?php else: ?>
          <div style="margin-top:12px;padding:12px 16px;background:rgba(34,197,94,0.1);border-left:4px solid #22c55e;border-radius:6px;font-size:0.9rem;color:#15803d;">
            ✓ <strong>Live Gateway Redirection Active.</strong> Customers choosing Pay Online will be redirected directly to Cashfree for real/test payment.
          </div>
        <?php endif; ?>

        <p class="form-hint" style="margin-top:8px;line-height:1.5;">
          When bypass is enabled, orders are instantly marked as <strong>Paid &amp; Confirmed</strong> without external gateway redirection. Useful for rapid testing of invoice and email generation without interacting with Cashfree.
        </p>
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

<script>
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
