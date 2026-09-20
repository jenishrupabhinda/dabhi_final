<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_notifications');

$activePage  = 'notification-settings';
$pageHeading = 'Notification Settings';
$pageTitle   = 'Notifications';

if (isPost()) {
    csrfVerify();

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
        'whatsapp_api_token',
        'whatsapp_phone_number_id',
        'sms_api_key',
        'sms_sender_id',
    ];

    foreach ($fields as $key) {
        $val = post($key);
        if (in_array($key, ['email_notifications_enabled', 'whatsapp_enabled', 'sms_enabled'], true)) {
            $val = $val ? '1' : '0';
        }
        Database::query(
            "INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)",
            [$key, (string)$val, Auth::id()]
        );
    }

    flashSet('success', 'Notification preferences saved.');
    redirect('/admin/notification-settings.php');
}

$emailEnabled = getSetting('email_notifications_enabled', '1') === '1';
$waEnabled    = getSetting('whatsapp_enabled', '0') === '1';
$smsEnabled   = getSetting('sms_enabled', '0') === '1';

$smtpHost     = getSetting('smtp_host', 'smtp.gmail.com');
$smtpPort     = getSetting('smtp_port', '587');
$smtpUser     = getSetting('smtp_user', '');
$smtpPass     = getSetting('smtp_pass', '');
$smtpFromEmail= getSetting('smtp_from_email', 'orders@dabhichikki.com');
$smtpFromName = getSetting('smtp_from_name', 'Dabhi Chikki');

$waToken      = getSetting('whatsapp_api_token', '');
$waPhoneId    = getSetting('whatsapp_phone_number_id', '');

$smsKey       = getSetting('sms_api_key', '');
$smsSenderId  = getSetting('sms_sender_id', 'DABHIC');

$recentLogs   = Database::fetchAll(
    "SELECT nl.*, o.order_number
     FROM notifications_log nl
     LEFT JOIN orders o ON o.id = nl.order_id
     ORDER BY nl.sent_at DESC LIMIT 15"
);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="margin-bottom:20px;">
    <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
    <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Configure automated order confirmation, shipment, and delivery alerts across Email, WhatsApp, and SMS.</p>
  </div>

  <form method="POST">
    <?= csrfField() ?>

    <!-- Channel Toggles -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">📢 Active Channels</h3></div>
      <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:14px;">
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="email_notifications_enabled" value="1" <?= $emailEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          ✉️ Email Alerts
        </label>
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="whatsapp_enabled" value="1" <?= $waEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          💬 WhatsApp Business
        </label>
        <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-weight:600;">
          <input type="checkbox" name="sms_enabled" value="1" <?= $smsEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
          📱 SMS Gateway
        </label>
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
      </div>
    </div>

    <!-- WhatsApp Business API -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header"><h3 class="card-title">💬 WhatsApp Cloud API</h3></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin-top:12px;">
        <div class="form-group">
          <label class="form-label">Permanent Access Token</label>
          <input type="password" name="whatsapp_api_token" class="form-control" value="<?= e($waToken) ?>" placeholder="EAAG...">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number ID</label>
          <input type="text" name="whatsapp_phone_number_id" class="form-control" value="<?= e($waPhoneId) ?>" placeholder="1029384756...">
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

    <button type="submit" class="btn btn-primary btn-lg" style="margin-bottom:24px;">Save Notification Settings</button>
  </form>

  <!-- Notification Logs -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">📋 Recent Dispatch Log</h3></div>
    <?php if (empty($recentLogs)): ?>
      <p class="text-muted text-center" style="padding:20px;">No notifications recorded yet.</p>
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
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $log): ?>
            <tr>
              <td style="font-size:0.8rem;color:var(--dc-muted);"><?= date('d M Y, g:i a', strtotime($log['sent_at'])) ?></td>
              <td><span class="badge badge-secondary"><?= strtoupper($log['channel']) ?></span></td>
              <td><?= e($log['event_type']) ?></td>
              <td><?= e($log['recipient']) ?></td>
              <td><?= e($log['order_number'] ?? '—') ?></td>
              <td>
                <span class="badge badge-<?= $log['status'] === 'sent' ? 'success' : ($log['status'] === 'failed' ? 'danger' : 'neutral') ?>">
                  <?= ucfirst($log['status']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
