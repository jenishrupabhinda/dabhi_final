<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_notifications');

$activePage  = 'notification-settings';
$pageHeading = 'Notification Settings';
$pageTitle   = 'Notifications';

$testResult = null;

if (isPost()) {
    csrfVerify();
    $action = post('action', 'save_settings');

    if ($action === 'test_whatsapp') {
        $testTo    = post('test_whatsapp_to', '');
        $testType  = post('test_whatsapp_type', 'hello_world');
        $customMsg = post('test_whatsapp_message', '');

        // Use submitted credentials if provided, or fallback to saved config
        $testMode       = post('whatsapp_mode', getSetting('whatsapp_mode', 'test'));
        $testPhoneId    = post('whatsapp_test_phone_number_id', getSetting('whatsapp_test_phone_number_id', ''));
        $testToken      = post('whatsapp_test_api_token', getSetting('whatsapp_test_api_token', ''));
        $testRecipient  = post('whatsapp_test_recipient_number', getSetting('whatsapp_test_recipient_number', ''));
        $livePhoneId    = post('whatsapp_live_phone_number_id', getSetting('whatsapp_live_phone_number_id', ''));
        $liveToken      = post('whatsapp_live_api_token', getSetting('whatsapp_live_api_token', ''));

        $activePhoneId  = ($testMode === 'test') ? ($testPhoneId ?: $livePhoneId) : ($livePhoneId ?: $testPhoneId);
        $activeToken    = ($testMode === 'test') ? ($testToken ?: $liveToken) : ($liveToken ?: $testToken);

        $overrideConfig = [
            'mode'                  => $testMode,
            'phone_number_id'       => $activePhoneId,
            'api_token'             => $activeToken,
            'test_phone_number_id'  => $testPhoneId,
            'test_api_token'        => $testToken,
            'test_recipient_number' => $testRecipient,
            'live_phone_number_id'  => $livePhoneId,
            'live_api_token'        => $liveToken,
        ];

        $targetNumber = !empty($testTo) ? $testTo : $testRecipient;
        $testResult   = WhatsAppSender::testConnection($targetNumber, $testType, $customMsg, $overrideConfig);

        $isAjax = function_exists('isAjax') ? isAjax() : (!empty($_SERVER['HTTP_X_REQUESTED_WITH']));
        if ($isAjax) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode($testResult);
            exit;
        }

        if ($testResult['ok']) {
            flashSet('success', 'WhatsApp test message sent successfully! Meta Message ID: ' . ($testResult['message_id'] ?? 'N/A'));
        } else {
            flashSet('error', 'WhatsApp test failed: ' . ($testResult['error'] ?? 'Unknown error'));
        }
    } elseif ($action === 'test_email') {
        $testTo   = post('test_email_to', '');
        $testType = post('test_email_type', 'ping');

        $overrideConfig = [
            'host'       => post('smtp_host', getSetting('smtp_host', 'smtp.gmail.com')),
            'port'       => (int)post('smtp_port', getSetting('smtp_port', '587')),
            'user'       => post('smtp_user', getSetting('smtp_user', '')),
            'pass'       => post('smtp_pass', getSetting('smtp_pass', '')),
            'from_email' => post('smtp_from_email', getSetting('smtp_from_email', 'orders@dabhichikki.com')),
            'from_name'  => post('smtp_from_name', getSetting('smtp_from_name', 'Dabhi Chikki')),
            'encryption' => post('smtp_encryption', getSetting('smtp_encryption', 'tls')),
        ];

        $testEmailResult = EmailSender::testConnection($testTo, $testType, null, $overrideConfig);

        $isAjax = function_exists('isAjax') ? isAjax() : (!empty($_SERVER['HTTP_X_REQUESTED_WITH']));
        if ($isAjax) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode($testEmailResult);
            exit;
        }

        if ($testEmailResult['ok']) {
            flashSet('success', 'Email test sent successfully! (' . e($testEmailResult['message'] ?? 'OK') . ')');
        } else {
            flashSet('error', 'Email test failed: ' . ($testEmailResult['error'] ?? 'Unknown error'));
        }
    } elseif ($action === 'save_settings') {
        $fields = [
            'email_notifications_enabled',
            'whatsapp_enabled',
            'sms_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_user',
            'smtp_pass',
            'smtp_from_email',
            'smtp_from_name',
            'smtp_encryption',
            'whatsapp_mode',
            'whatsapp_test_phone_number_id',
            'whatsapp_test_api_token',
            'whatsapp_test_recipient_number',
            'whatsapp_test_force_recipient',
            'whatsapp_live_phone_number_id',
            'whatsapp_live_api_token',
            'sms_api_key',
            'sms_sender_id',
        ];

        foreach ($fields as $key) {
            $val = post($key);
            if (in_array($key, ['email_notifications_enabled', 'whatsapp_enabled', 'sms_enabled', 'whatsapp_test_force_recipient'], true)) {
                $val = $val ? '1' : '0';
            }
            setSetting($key, (string)$val, Auth::id());
        }

        // Keep legacy / backward-compatible settings in sync
        $waMode      = post('whatsapp_mode', 'test');
        $activeToken = ($waMode === 'test') ? post('whatsapp_test_api_token') : post('whatsapp_live_api_token');
        $activePhone = ($waMode === 'test') ? post('whatsapp_test_phone_number_id') : post('whatsapp_live_phone_number_id');
        setSetting('whatsapp_api_token', (string)$activeToken, Auth::id());
        setSetting('whatsapp_phone_number_id', (string)$activePhone, Auth::id());
        setSetting('whatsapp_notifications_enabled', post('whatsapp_enabled') ? '1' : '0', Auth::id());
        clearSettingCache();

        flashSet('success', 'Notification preferences and WhatsApp/SMTP settings saved.');
        redirect('/admin/notification-settings.php');
    }
}

$emailEnabled  = getSetting('email_notifications_enabled', '1') === '1';
$waEnabled     = (getSetting('whatsapp_enabled', '0') === '1' || getSetting('whatsapp_notifications_enabled', '0') === '1');
$smsEnabled    = getSetting('sms_enabled', '0') === '1';

$waMode        = getSetting('whatsapp_mode', 'test'); // 'test' | 'live'
$waTestPhoneId = getSetting('whatsapp_test_phone_number_id', getSetting('whatsapp_phone_number_id', ''));
$waTestToken   = getSetting('whatsapp_test_api_token', getSetting('whatsapp_api_token', ''));
$waTestRecip   = getSetting('whatsapp_test_recipient_number', '');
$waTestForce   = getSetting('whatsapp_test_force_recipient', '1') === '1';

$waLivePhoneId = getSetting('whatsapp_live_phone_number_id', '');
$waLiveToken   = getSetting('whatsapp_live_api_token', '');

$smtpHost       = getSetting('smtp_host', 'smtp.gmail.com');
$smtpPort       = getSetting('smtp_port', '587');
$smtpUser       = getSetting('smtp_user', '');
$smtpPass       = getSetting('smtp_pass', '');
$smtpFromEmail  = getSetting('smtp_from_email', 'orders@dabhichikki.com');
$smtpFromName   = getSetting('smtp_from_name', 'Dabhi Chikki');
$smtpEncryption = getSetting('smtp_encryption', 'tls');
$adminEmail     = Auth::user()['email'] ?? $smtpFromEmail;

$smsKey        = getSetting('sms_api_key', '');
$smsSenderId   = getSetting('sms_sender_id', 'DABHIC');

$recentLogs    = Database::fetchAll(
    "SELECT nl.*, o.order_number
     FROM notifications_log nl
     LEFT JOIN orders o ON o.id = nl.order_id
     ORDER BY nl.sent_at DESC LIMIT 25"
);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
    <div>
      <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
      <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Configure automated customer notifications across WhatsApp Cloud API, Email, and SMS.</p>
    </div>
    <div>
      <?php if ($waEnabled): ?>
        <?php if ($waMode === 'test'): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;font-weight:600;font-size:0.82rem;padding:6px 14px;border-radius:20px;">
            🧪 WhatsApp: Test Mode (Sandbox)
          </span>
        <?php else: ?>
          <span style="display:inline-flex;align-items:center;gap:6px;background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;font-weight:600;font-size:0.82rem;padding:6px 14px;border-radius:20px;">
            🚀 WhatsApp: Live Production Mode
          </span>
        <?php endif; ?>
      <?php else: ?>
        <span style="display:inline-flex;align-items:center;gap:6px;background:#F3F4F6;color:#4B5563;border:1px solid #E5E7EB;font-weight:600;font-size:0.82rem;padding:6px 14px;border-radius:20px;">
          ⚪ WhatsApp Disabled
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($waEnabled && $waMode === 'test'): ?>
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;gap:16px;align-items:flex-start;">
      <span style="font-size:1.6rem;line-height:1;">💡</span>
      <div style="font-size:0.88rem;color:#78350F;line-height:1.5;">
        <strong>WhatsApp Sandbox Testing Mode is Active:</strong> Messages use your Meta temporary test number and token.
        <?php if ($waTestForce): ?>
          All automated order alerts triggered on the site will be safely rerouted to your registered test recipient number <strong>(<?= e($waTestRecip ?: 'Not configured yet') ?>)</strong> so Meta sandbox permissions are not violated and actual customers are not messaged.
        <?php else: ?>
          Alerts will attempt to send to the customer's phone number (note: Meta will reject numbers not registered in your sandbox recipient list).
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" id="settingsForm">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_settings">

    <!-- Active Channel Toggles -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">📢 Active Channels</h3></div>
      <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:14px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="whatsapp_enabled" value="1" <?= $waEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          💬 WhatsApp Cloud API
        </label>
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="email_notifications_enabled" value="1" <?= $emailEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          ✉️ Email Alerts
        </label>
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="sms_enabled" value="1" <?= $smsEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          📱 SMS Gateway
        </label>
      </div>
    </div>

    <!-- WhatsApp Cloud API Configuration -->
    <div class="card" style="margin-bottom:24px;border-top:3px solid #25D366;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h3 class="card-title" style="display:flex;align-items:center;gap:8px;">
          <span style="color:#25D366;font-size:1.3rem;">💬</span> WhatsApp Cloud API Configuration
        </h3>
        <span style="font-size:0.8rem;color:var(--dc-muted);">Powered by Meta Graph API</span>
      </div>

      <!-- Environment Mode Selector -->
      <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:16px;margin-top:16px;">
        <label style="display:block;font-weight:700;margin-bottom:8px;font-size:0.92rem;color:var(--dc-dark);">Operating Environment</label>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:0.88rem;background:#fff;padding:10px 16px;border-radius:8px;border:1px solid #D1D5DB;">
            <input type="radio" name="whatsapp_mode" value="test" <?= $waMode === 'test' ? 'checked' : '' ?> onchange="toggleWaMode(this.value)">
            🧪 Sandbox / Test Mode (Temporary Test Number)
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:0.88rem;background:#fff;padding:10px 16px;border-radius:8px;border:1px solid #D1D5DB;">
            <input type="radio" name="whatsapp_mode" value="live" <?= $waMode === 'live' ? 'checked' : '' ?> onchange="toggleWaMode(this.value)">
            🚀 Live Production Mode (Permanent Business Number)
          </label>
        </div>
      </div>

      <!-- Test Mode Section -->
      <div id="waTestSection" style="margin-top:20px;background:#FEFCE8;border:1px solid #FEF08A;border-radius:10px;padding:20px;<?= $waMode === 'live' ? 'opacity:0.65;' : '' ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <h4 style="margin:0;color:#854D0E;display:flex;align-items:center;gap:6px;">
            🧪 Test Mode Credentials (Meta Developer Sandbox)
          </h4>
          <span style="font-size:0.75rem;background:#FEF3C7;color:#B45309;padding:3px 8px;border-radius:6px;font-weight:600;">Sandbox Credentials</span>
        </div>
        <p style="margin:0 0 16px;color:#713F12;font-size:0.82rem;">
          Obtain these from your <a href="https://developers.facebook.com/apps" target="_blank" rel="noreferrer" style="color:#B45309;text-decoration:underline;font-weight:600;">Meta Developer App</a> under <strong>WhatsApp &gt; API Setup</strong>.
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
          <div class="form-group">
            <label class="form-label" style="font-weight:600;">Test Phone Number ID <span style="color:#DC2626;">*</span></label>
            <input type="text" name="whatsapp_test_phone_number_id" id="wa_test_phone_id" class="form-control" value="<?= e($waTestPhoneId) ?>" placeholder="e.g. 102938475612345">
            <small style="color:#854D0E;font-size:0.75rem;">From Meta API Setup: "Phone number ID" under your Test phone number.</small>
          </div>

          <div class="form-group">
            <label class="form-label" style="font-weight:600;">Temporary Access Token <span style="color:#DC2626;">*</span></label>
            <input type="password" name="whatsapp_test_api_token" id="wa_test_token" class="form-control" value="<?= e($waTestToken) ?>" placeholder="EAAG... (Temporary or System User Token)">
            <small style="color:#854D0E;font-size:0.75rem;">⚠️ Temporary tokens expire in 24 hours. Paste fresh token as needed.</small>
          </div>

          <div class="form-group">
            <label class="form-label" style="font-weight:600;">Verified Test Recipient WhatsApp Number <span style="color:#DC2626;">*</span></label>
            <input type="text" name="whatsapp_test_recipient_number" id="wa_test_recip" class="form-control" value="<?= e($waTestRecip) ?>" placeholder="e.g. +91 98765 43210">
            <small style="color:#854D0E;font-size:0.75rem;">Your mobile number added &amp; verified in Meta API Setup (Step 2: "To" field).</small>
          </div>
        </div>

        <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #FDE68A;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:0.86rem;color:#78350F;">
            <input type="checkbox" name="whatsapp_test_force_recipient" value="1" <?= $waTestForce ? 'checked' : '' ?> style="width:16px;height:16px;">
            <span>Route all automated store notifications to Test Recipient while in Test Mode</span>
          </label>
          <p style="margin:4px 0 0 24px;font-size:0.78rem;color:#92400E;">
            (Recommended) When placing test orders with arbitrary customer phones, notifications will safely route to your verified test number with a <code>[SIMULATED FOR: Customer Name]</code> banner, preventing Meta API rejection errors.
          </p>
        </div>
      </div>

      <!-- Live Mode Section -->
      <div id="waLiveSection" style="margin-top:20px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:20px;<?= $waMode === 'test' ? 'opacity:0.65;' : '' ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <h4 style="margin:0;color:#166534;display:flex;align-items:center;gap:6px;">
            🚀 Production Credentials (Live WhatsApp Business Account)
          </h4>
          <span style="font-size:0.75rem;background:#DCFCE7;color:#15803D;padding:3px 8px;border-radius:6px;font-weight:600;">Production Ready</span>
        </div>
        <p style="margin:0 0 16px;color:#14532D;font-size:0.82rem;">
          Configure once your WhatsApp Business Account (WABA) and phone number are approved.
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
          <div class="form-group">
            <label class="form-label" style="font-weight:600;">Live Phone Number ID</label>
            <input type="text" name="whatsapp_live_phone_number_id" id="wa_live_phone_id" class="form-control" value="<?= e($waLivePhoneId) ?>" placeholder="e.g. 109876543210987">
          </div>

          <div class="form-group">
            <label class="form-label" style="font-weight:600;">Permanent System User Access Token</label>
            <input type="password" name="whatsapp_live_api_token" id="wa_live_token" class="form-control" value="<?= e($waLiveToken) ?>" placeholder="EAAG... (Never-expiring System User Token)">
          </div>
        </div>
      </div>
    </div>

    <!-- SMTP Settings -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">✉️ SMTP Email Server Settings</h3></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-top:12px;">
        <div class="form-group">
          <label class="form-label">SMTP Host</label>
          <input type="text" name="smtp_host" class="form-control" value="<?= e($smtpHost) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Port</label>
          <input type="text" name="smtp_port" class="form-control" value="<?= e($smtpPort) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Username</label>
          <input type="text" name="smtp_user" class="form-control" value="<?= e($smtpUser) ?>" placeholder="user@domain.com">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Password</label>
          <input type="password" name="smtp_pass" class="form-control" value="<?= e($smtpPass) ?>" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label class="form-label">From Name</label>
          <input type="text" name="smtp_from_name" class="form-control" value="<?= e($smtpFromName) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">From Email Address</label>
          <input type="email" name="smtp_from_email" class="form-control" value="<?= e($smtpFromEmail) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Encryption Protocol</label>
          <select name="smtp_encryption" class="form-control">
            <option value="tls" <?= $smtpEncryption === 'tls' ? 'selected' : '' ?>>TLS / STARTTLS (Port 587 - Recommended)</option>
            <option value="ssl" <?= $smtpEncryption === 'ssl' ? 'selected' : '' ?>>Direct SSL (Port 465)</option>
            <option value="none" <?= $smtpEncryption === 'none' ? 'selected' : '' ?>>None / Plain (Port 25 / 2525)</option>
          </select>
        </div>
      </div>
    </div>

    <!-- SMS Gateway -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">📱 SMS Gateway (DLT Approved)</h3></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin-top:12px;">
        <div class="form-group">
          <label class="form-label">SMS API Key</label>
          <input type="password" name="sms_api_key" class="form-control" value="<?= e($smsKey) ?>" placeholder="API key...">
        </div>
        <div class="form-group">
          <label class="form-label">6-Char Sender Header / ID</label>
          <input type="text" name="sms_sender_id" maxlength="6" class="form-control" value="<?= e($smsSenderId) ?>" placeholder="DABHIC">
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:28px;">
      <button type="submit" class="btn btn-primary btn-lg">💾 Save All Notification Settings</button>
    </div>
  </form>

  <!-- Interactive WhatsApp Testing Sandbox Console -->
  <div class="card" style="margin-bottom:28px;border:1px solid #93C5FD;box-shadow:0 4px 12px rgba(59,130,246,0.06);">
    <div class="card-header" style="background:#EFF6FF;border-bottom:1px solid #DBEAFE;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div>
        <h3 class="card-title" style="margin:0;color:#1E40AF;display:flex;align-items:center;gap:8px;">
          <span>🧪</span> WhatsApp Interactive Testing Console
        </h3>
        <p style="margin:2px 0 0;font-size:0.82rem;color:#3B82F6;">
          Test your temporary credentials and verify WhatsApp delivery in real-time before sending to customers.
        </p>
      </div>
      <span class="badge badge-primary" style="font-size:0.75rem;">Instant Dispatcher</span>
    </div>

    <div style="padding:20px;">
      <form id="waTestForm" onsubmit="handleWaTestSubmit(event)">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="test_whatsapp">

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;align-items:flex-end;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-weight:600;">Destination WhatsApp Number</label>
            <input type="text" name="test_whatsapp_to" id="test_whatsapp_to" class="form-control"
                   value="<?= e($waTestRecip) ?>"
                   placeholder="e.g. +91 98765 43210" required>
            <small style="color:var(--dc-muted);font-size:0.75rem;">Must be on your Meta sandbox verified recipient list.</small>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-weight:600;">Test Scenario</label>
            <select name="test_whatsapp_type" id="test_whatsapp_type" class="form-control" onchange="toggleTestMessageInput(this.value)">
              <option value="hello_world" selected>🌐 Built-in Meta Template ("hello_world") [Recommended 1st Check]</option>
              <option value="order_simulation">📦 Simulate Order Confirmation Notification</option>
              <option value="text">💬 Custom Direct Text Message</option>
            </select>
            <small style="color:var(--dc-muted);font-size:0.75rem;">The template "hello_world" works automatically with temporary numbers.</small>
          </div>

          <div style="display:flex;align-items:flex-end;">
            <button type="submit" id="btnSendTestWa" class="btn btn-success" style="height:42px;width:100%;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;background:#059669;border-color:#059669;">
              <span id="btnSendSpinner" style="display:none;">⏳</span>
              <span>🚀 Send Test WhatsApp</span>
            </button>
          </div>
        </div>

        <div id="customMessageContainer" style="display:none;margin-top:16px;">
          <label class="form-label" style="font-weight:600;">Custom Message Body</label>
          <textarea name="test_whatsapp_message" id="test_whatsapp_message" class="form-control" rows="3" placeholder="Enter custom message to send..."></textarea>
        </div>
      </form>

      <!-- Diagnostic Live Results Display -->
      <div id="waTestOutput" style="display:none;margin-top:20px;"></div>
    </div>
  </div>

  <!-- Interactive Email Testing Sandbox Console -->
  <div class="card" style="margin-bottom:28px;border:1px solid #C7D2FE;box-shadow:0 4px 12px rgba(99,102,241,0.06);">
    <div class="card-header" style="background:#EEF2FF;border-bottom:1px solid #E0E7FF;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div>
        <h3 class="card-title" style="margin:0;color:#3730A3;display:flex;align-items:center;gap:8px;">
          <span>✉️</span> Email (SMTP) Interactive Testing Console
        </h3>
        <p style="margin:2px 0 0;font-size:0.82rem;color:#4F46E5;">
          Test your SMTP server connectivity, authentication, TLS encryption, and deliverability in real-time before sending customer alerts.
        </p>
      </div>
      <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;font-size:0.75rem;font-weight:600;">SMTP Diagnostic Tool</span>
    </div>

    <div style="padding:20px;">
      <form id="emailTestForm" onsubmit="handleEmailTestSubmit(event)">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="test_email">

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;align-items:flex-end;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-weight:600;">Destination Test Email</label>
            <input type="email" name="test_email_to" id="test_email_to" class="form-control"
                   value="<?= e($adminEmail) ?>"
                   placeholder="e.g. your-email@gmail.com" required>
            <small style="color:var(--dc-muted);font-size:0.75rem;">Test email will be delivered to this mailbox.</small>
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" style="font-weight:600;">Test Email Scenario</label>
            <select name="test_email_type" id="test_email_type" class="form-control">
              <option value="ping" selected>⚡ SMTP Handshake &amp; Ping Test [Quick Verification]</option>
              <option value="order_simulation">📦 Simulate Order Confirmation HTML Email [Full Template]</option>
            </select>
            <small style="color:var(--dc-muted);font-size:0.75rem;">Preview the full handcrafted order confirmation email layout.</small>
          </div>

          <div style="display:flex;align-items:flex-end;">
            <button type="submit" id="btnSendTestEmail" class="btn btn-primary" style="height:42px;width:100%;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;background:#4F46E5;border-color:#4F46E5;">
              <span id="btnEmailSpinner" style="display:none;">⏳</span>
              <span>🚀 Send Test Email</span>
            </button>
          </div>
        </div>
      </form>

      <!-- Diagnostic Live Results Display -->
      <div id="emailTestOutput" style="display:none;margin-top:20px;"></div>
    </div>
  </div>

  <!-- Notification Logs -->
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <h3 class="card-title">📋 Recent Dispatch Log</h3>
      <span style="font-size:0.8rem;color:var(--dc-muted);">Last 25 notification attempts across channels</span>
    </div>
    <?php if (empty($recentLogs)): ?>
      <p class="text-muted text-center" style="padding:24px;">No notifications recorded yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="dc-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Channel</th>
              <th>Event</th>
              <th>Recipient</th>
              <th>Order #</th>
              <th>Status</th>
              <th>Response Details</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $log): ?>
            <tr>
              <td style="font-size:0.8rem;color:var(--dc-muted);white-space:nowrap;"><?= date('d M Y, g:i a', strtotime($log['sent_at'])) ?></td>
              <td>
                <?php if ($log['channel'] === 'whatsapp'): ?>
                  <span class="badge" style="background:#DCFCE7;color:#15803D;border:1px solid #86EFAC;font-weight:600;">💬 WHATSAPP</span>
                <?php elseif ($log['channel'] === 'email'): ?>
                  <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;font-weight:600;">✉️ EMAIL</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><?= strtoupper($log['channel']) ?></span>
                <?php endif; ?>
              </td>
              <td><code><?= e($log['event_type']) ?></code></td>
              <td style="font-size:0.85rem;font-family:monospace;"><?= e($log['recipient']) ?></td>
              <td>
                <?php if (!empty($log['order_number'])): ?>
                  <a href="/admin/order-detail.php?id=<?= (int)$log['order_id'] ?>" style="font-weight:600;color:var(--dc-primary);">
                    <?= e($log['order_number']) ?>
                  </a>
                <?php else: ?>
                  <span style="color:var(--dc-muted); font-size:0.8rem;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($log['status'] === 'sent'): ?>
                  <span class="badge badge-success">Sent</span>
                <?php elseif ($log['status'] === 'failed'): ?>
                  <span class="badge badge-danger">Failed</span>
                <?php else: ?>
                  <span class="badge badge-neutral">Skipped</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.8rem;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($log['response_message']) ?>">
                <?= e($log['response_message'] ?: '—') ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleWaMode(mode) {
  const testSec = document.getElementById('waTestSection');
  const liveSec = document.getElementById('waLiveSection');
  if (mode === 'test') {
    testSec.style.opacity = '1';
    liveSec.style.opacity = '0.65';
  } else {
    testSec.style.opacity = '0.65';
    liveSec.style.opacity = '1';
  }
}

function toggleTestMessageInput(type) {
  const container = document.getElementById('customMessageContainer');
  if (type === 'text') {
    container.style.display = 'block';
  } else {
    container.style.display = 'none';
  }
}

async function handleWaTestSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('waTestForm');
  const btn = document.getElementById('btnSendTestWa');
  const spinner = document.getElementById('btnSendSpinner');
  const output = document.getElementById('waTestOutput');

  // Copy current credentials from settings form if user hasn't saved yet
  const formData = new FormData(form);
  const settingsForm = document.getElementById('settingsForm');
  if (settingsForm) {
    const sData = new FormData(settingsForm);
    for (let [k, v] of sData.entries()) {
      if (!formData.has(k)) {
        formData.append(k, v);
      }
    }
  }

  btn.disabled = true;
  spinner.style.display = 'inline';
  output.style.display = 'block';
  output.innerHTML = '<div style="background:#F3F4F6;padding:14px 18px;border-radius:8px;color:#4B5563;font-size:0.88rem;">Connecting to Meta WhatsApp Cloud API...</div>';

  try {
    const res = await fetch(window.location.href, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const rawText = await res.text();
    let data;
    try {
      data = JSON.parse(rawText);
    } catch (parseErr) {
      output.innerHTML = `
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 18px;color:#991B1B;font-size:0.88rem;">
          <strong>Server Response Error:</strong> Server did not return valid JSON.
          <details style="margin-top:8px;">
            <summary style="cursor:pointer;font-weight:600;">View Server Output</summary>
            <pre style="background:#fff;border:1px solid #E5E7EB;padding:8px;border-radius:6px;max-height:200px;overflow:auto;margin-top:6px;font-size:0.75rem;">${rawText.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
          </details>
        </div>
      `;
      return;
    }

    if (data.ok) {
      output.innerHTML = `
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:16px 20px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:1.4rem;">✅</span>
            <strong style="color:#065F46;font-size:1rem;">WhatsApp Message Sent Successfully!</strong>
          </div>
          <div style="font-size:0.86rem;color:#047857;line-height:1.6;">
            <div><strong>Recipient:</strong> <code>${data.recipient || 'N/A'}</code></div>
            <div><strong>Meta Message ID:</strong> <code>${data.message_id || 'N/A'}</code></div>
            <div><strong>Mode:</strong> ${data.mode === 'test' ? '🧪 Sandbox / Test Mode' : '🚀 Live Mode'}</div>
            <div><strong>HTTP Status:</strong> ${data.http_code || 200} OK</div>
          </div>
        </div>
      `;
    } else {
      let helpBox = data.help ? `
        <div style="margin-top:10px;padding:10px 14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;color:#92400E;font-size:0.83rem;">
          ${data.help}
        </div>
      ` : '';

      let rawDetails = data.raw ? `
        <details style="margin-top:10px;font-size:0.75rem;color:#6B7280;">
          <summary style="cursor:pointer;font-weight:600;">View Raw Meta API Response</summary>
          <pre style="background:#1F2937;color:#F9FAFB;padding:10px;border-radius:6px;overflow-x:auto;margin-top:6px;">${JSON.stringify(data.raw, null, 2)}</pre>
        </details>
      ` : '';

      output.innerHTML = `
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px 20px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:1.4rem;">❌</span>
            <strong style="color:#991B1B;font-size:1rem;">WhatsApp Delivery Failed</strong>
          </div>
          <div style="font-size:0.86rem;color:#B91C1C;line-height:1.6;">
            <div><strong>Error:</strong> ${data.error || 'Unknown error occurred'}</div>
            ${data.error_code ? `<div><strong>Meta Error Code:</strong> <code>${data.error_code}</code></div>` : ''}
            ${data.http_code ? `<div><strong>HTTP Status:</strong> ${data.http_code}</div>` : ''}
          </div>
          ${helpBox}
          ${rawDetails}
        </div>
      `;
    }
  } catch (err) {
    output.innerHTML = `
      <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 18px;color:#991B1B;font-size:0.88rem;">
        <strong>Network Error:</strong> Failed to reach test endpoint. Error: ${err.message}
      </div>
    `;
  } finally {
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}

async function handleEmailTestSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('emailTestForm');
  const btn = document.getElementById('btnSendTestEmail');
  const spinner = document.getElementById('btnEmailSpinner');
  const output = document.getElementById('emailTestOutput');

  // Copy current credentials from settings form so unsaved inputs are tested
  const formData = new FormData(form);
  const settingsForm = document.getElementById('settingsForm');
  if (settingsForm) {
    const sData = new FormData(settingsForm);
    for (let [k, v] of sData.entries()) {
      if (!formData.has(k)) {
        formData.append(k, v);
      }
    }
  }

  btn.disabled = true;
  spinner.style.display = 'inline';
  output.style.display = 'block';
  output.innerHTML = '<div style="background:#F3F4F6;padding:14px 18px;border-radius:8px;color:#4B5563;font-size:0.88rem;">Connecting to SMTP server & negotiating handshake...</div>';

  try {
    const res = await fetch(window.location.href, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const rawText = await res.text();
    let data;
    try {
      data = JSON.parse(rawText);
    } catch (parseErr) {
      output.innerHTML = `
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 18px;color:#991B1B;font-size:0.88rem;">
          <strong>Server Response Error:</strong> Server did not return valid JSON.
          <details style="margin-top:8px;">
            <summary style="cursor:pointer;font-weight:600;">View Server Output</summary>
            <pre style="background:#fff;border:1px solid #E5E7EB;padding:8px;border-radius:6px;max-height:200px;overflow:auto;margin-top:6px;font-size:0.75rem;">${rawText.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
          </details>
        </div>
      `;
      return;
    }

    let transcriptHtml = '';
    if (data.transcript && data.transcript.length > 0) {
      transcriptHtml = `
        <details style="margin-top:12px;font-size:0.78rem;color:#4B5563;">
          <summary style="cursor:pointer;font-weight:600;color:#374151;">View SMTP Protocol Dialogue / Transcript</summary>
          <pre style="background:#111827;color:#F3F4F6;padding:12px;border-radius:6px;overflow-x:auto;margin-top:6px;font-family:monospace;font-size:0.76rem;line-height:1.5;">${data.transcript.join('\n').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
        </details>
      `;
    }

    if (data.ok) {
      output.innerHTML = `
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:16px 20px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:1.4rem;">✅</span>
            <strong style="color:#065F46;font-size:1rem;">Email Sent Successfully via SMTP!</strong>
          </div>
          <div style="font-size:0.86rem;color:#047857;line-height:1.6;">
            <div><strong>Recipient:</strong> <code>${data.recipient || 'N/A'}</code></div>
            <div><strong>Status:</strong> ${data.message || 'Delivered to SMTP server.'}</div>
            ${data.message_id ? `<div><strong>Message-ID:</strong> <code>${data.message_id}</code></div>` : ''}
          </div>
          ${transcriptHtml}
        </div>
      `;
    } else {
      let helpBox = data.help ? `
        <div style="margin-top:10px;padding:10px 14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;color:#92400E;font-size:0.83rem;">
          ${data.help}
        </div>
      ` : '';

      output.innerHTML = `
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px 20px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:1.4rem;">❌</span>
            <strong style="color:#991B1B;font-size:1rem;">SMTP Email Delivery Failed</strong>
          </div>
          <div style="font-size:0.86rem;color:#B91C1C;line-height:1.6;">
            <div><strong>Error:</strong> ${data.error || 'Unknown error occurred'}</div>
          </div>
          ${helpBox}
          ${transcriptHtml}
        </div>
      `;
    }
  } catch (err) {
    output.innerHTML = `
      <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 18px;color:#991B1B;font-size:0.88rem;">
        <strong>Network Error:</strong> Failed to reach test endpoint. Error: ${err.message}
      </div>
    `;
  } finally {
    btn.disabled = false;
    spinner.style.display = 'none';
  }
}
</script>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
