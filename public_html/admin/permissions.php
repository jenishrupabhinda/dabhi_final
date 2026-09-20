<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');

$activePage  = 'users';
$pageHeading = 'Staff Permissions';
$pageTitle   = 'Permissions Matrix';

$targetUserId = (int)get('user');

// If no user specified, default to first non-superadmin staff member
if (!$targetUserId) {
    $firstStaff = Database::fetchOne("SELECT id FROM users WHERE role IN ('admin', 'employee') ORDER BY id ASC LIMIT 1");
    if ($firstStaff) {
        $targetUserId = (int)$firstStaff['id'];
    }
}

$targetUser = $targetUserId ? Database::fetchOne("SELECT * FROM users WHERE id = ?", [$targetUserId]) : null;

// Handle POST: Grant / Revoke permissions
if (isPost()) {
    csrfVerify();
    $postedUserId = (int)post('user_id');
    $enabledPerms = $_POST['perms'] ?? []; // array of permission_key => "1"

    if ($postedUserId) {
        $allPermissions = Database::fetchAll("SELECT permission_key FROM permissions");
        $granterId = Auth::id();

        foreach ($allPermissions as $p) {
            $key     = $p['permission_key'];
            $enabled = isset($enabledPerms[$key]) && $enabledPerms[$key] === '1';
            RBAC::grant($granterId, $postedUserId, $key, $enabled);
        }

        flashSet('success', 'User permissions updated successfully.');
        redirect('/admin/permissions.php?user=' . $postedUserId);
    }
}

// Fetch all staff accounts for selector
$staffList = Database::fetchAll(
    "SELECT id, full_name, email, role FROM users 
     WHERE role IN ('admin', 'employee') 
     ORDER BY role DESC, full_name ASC"
);

// Fetch permissions for current target
$userPerms = $targetUserId ? RBAC::allForUser($targetUserId) : [];

// Group permissions by category
$groupedPerms = [];
foreach ($userPerms as $up) {
    $cat = $up['category'] ?: 'General';
    $groupedPerms[$cat][] = $up;
}

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
      <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
      <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Manage granular feature and data access permissions for admin and employee staff.</p>
    </div>
    <div>
      <a href="<?= url('admin/users.php') ?>" class="btn btn-ghost">← Back to Users</a>
    </div>
  </div>

  <!-- User Selector -->
  <div class="card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin:0;min-width:240px;">
        <label class="form-label">Select Staff Member</label>
        <select name="user" class="form-control" onchange="this.form.submit();">
          <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $targetUserId === (int)$s['id'] ? 'selected' : '' ?>>
              <?= e($s['full_name']) ?> (<?= ucfirst(e($s['role'])) ?> &bull; <?= e($s['email']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Load Permissions</button>
    </form>
  </div>

  <?php if ($targetUser): ?>
  <form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="user_id" value="<?= $targetUser['id'] ?>">

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;margin-bottom:24px;">
      <?php foreach ($groupedPerms as $categoryName => $perms): ?>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title" style="text-transform:capitalize;">📁 <?= e($categoryName) ?> Access</h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;margin-top:10px;">
          <?php foreach ($perms as $p): ?>
          <label style="cursor:pointer;display:flex;align-items:flex-start;gap:10px;">
            <input type="checkbox" name="perms[<?= e($p['permission_key']) ?>]" value="1" <?= $p['is_enabled'] ? 'checked' : '' ?> style="width:18px;height:18px;margin-top:2px;">
            <div>
              <div style="font-weight:600;font-size:0.92rem;"><?= e($p['label']) ?></div>
              <?php if ($p['description']): ?>
                <div style="font-size:0.75rem;color:var(--dc-muted);margin-top:2px;"><?= e($p['description']) ?></div>
              <?php endif; ?>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save Permissions for <?= e($targetUser['full_name']) ?></button>
  </form>
  <?php else: ?>
    <div class="card">
      <p class="text-muted text-center" style="padding:28px;">No staff accounts found. Create an employee or admin account first in <a href="<?= url('admin/users.php?tab=staff') ?>">User Management</a>.</p>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
