<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('manage_users');

$tab = get('tab', 'buyers');

if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'create_staff') {
        $result = Auth::createStaffAccount([
            'full_name' => post('full_name'),
            'email'     => post('email'),
            'phone'     => post('phone'),
            'role'      => post('role'),
        ], Auth::id());
        flashSet($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Staff account created. Temp password: ' . $result['temp_password'] : $result['error']);
    }

    if ($action === 'toggle_user') {
        $uid  = (int)post('user_id');
        $curr = Database::fetchOne('SELECT is_active FROM users WHERE id=?', [$uid])['is_active'] ?? 0;
        Database::query('UPDATE users SET is_active=? WHERE id=?', [$curr ? 0 : 1, $uid]);
    }

    redirect('/admin/users.php?tab=' . $tab);
}

// Lists
$buyers = Database::fetchAll(
    "SELECT id, full_name, email, phone, is_active, last_login_at, created_at
     FROM users WHERE role='buyer' ORDER BY created_at DESC LIMIT 100"
);
$staff = Database::fetchAll(
    "SELECT id, full_name, email, role, is_active, last_login_at, created_at
     FROM users WHERE role IN ('admin','employee') ORDER BY created_at DESC"
);

$pageTitle = 'User Management';
$pageHeading = 'User Management';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--dc-border);">
    <a href="?tab=buyers" class="<?= $tab==='buyers' ? 'tab-active' : '' ?>" style="padding:8px 18px;font-weight:600;text-decoration:none;color:<?= $tab==='buyers' ? 'var(--dc-terracotta)' : 'var(--dc-muted)' ?>;border-bottom:2px solid <?= $tab==='buyers' ? 'var(--dc-terracotta)' : 'transparent' ?>;margin-bottom:-2px;">Buyers</a>
    <a href="?tab=staff" class="<?= $tab==='staff' ? 'tab-active' : '' ?>" style="padding:8px 18px;font-weight:600;text-decoration:none;color:<?= $tab==='staff' ? 'var(--dc-terracotta)' : 'var(--dc-muted)' ?>;border-bottom:2px solid <?= $tab==='staff' ? 'var(--dc-terracotta)' : 'transparent' ?>;margin-bottom:-2px;">Staff & Admins</a>
  </div>

  <?php if ($tab === 'buyers'): ?>
  <div class="card">
    <div class="card-header"><h3 class="card-title">Buyers (<?= count($buyers) ?>)</h3></div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Last Login</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($buyers as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['phone']) ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td><?= $u['last_login_at'] ? date('d M Y', strtotime($u['last_login_at'])) : '—' ?></td>
        <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Blocked' ?></span></td>
        <td>
          <form method="POST" style="display:inline;">
            <?= csrfField() ?><input type="hidden" name="action" value="toggle_user">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm"><?= $u['is_active'] ? 'Block' : 'Unblock' ?></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php else: ?>

  <!-- Create staff -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 class="card-title">Create Staff Account</h3></div>
    <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <?= csrfField() ?><input type="hidden" name="action" value="create_staff">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
      <div class="form-group"><label class="form-label">Role</label>
        <select name="role" class="form-control">
          <option value="employee">Employee</option>
          <?php if (Auth::role() === 'superadmin'): ?><option value="admin">Admin</option><?php endif; ?>
        </select>
      </div>
      <div style="grid-column:1/-1;"><button type="submit" class="btn btn-primary">Create Account</button></div>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Staff & Admins</h3></div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($staff as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge badge-secondary"><?= ucfirst($u['role']) ?></span></td>
        <td><?= $u['last_login_at'] ? date('d M Y', strtotime($u['last_login_at'])) : '—' ?></td>
        <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Blocked' ?></span></td>
        <td>
          <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="<?= url('admin/user-edit.php?id=' . (int)$u['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
            <a href="<?= url('admin/permissions.php?user=' . (int)$u['id']) ?>" class="btn btn-ghost btn-sm" title="Permissions">🔑</a>
            <form method="POST" style="display:inline;">
              <?= csrfField() ?><input type="hidden" name="action" value="toggle_user">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm"><?= $u['is_active'] ? 'Block' : 'Unblock' ?></button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
