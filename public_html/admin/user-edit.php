<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');

$activePage  = 'users';
$userId      = (int)get('id');
$pageHeading = $userId ? 'Edit Staff User' : 'Add Staff User';
$pageTitle   = $pageHeading;

$user = $userId ? Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]) : null;

if ($userId && !$user) {
    flashSet('error', 'User not found.');
    redirect('/admin/users.php');
}

if (isPost()) {
    csrfVerify();
    $fullName = trim(post('full_name'));
    $email    = strtolower(trim(post('email')));
    $phone    = trim(post('phone'));
    $role     = post('role');
    $isActive = post('is_active') ? 1 : 0;
    $resetPw  = post('must_reset_password') ? 1 : 0;
    $newPass  = trim(post('new_password'));

    // Validate role permissions
    if (Auth::role() !== 'superadmin' && $role === 'admin') {
        $role = 'employee';
    }

    if (!$fullName || !$email) {
        flashSet('error', 'Name and email are required.');
    } else {
        if ($userId) {
            // Update
            $sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, is_active=?, must_reset_password=?";
            $params = [$fullName, $email, $phone, $role, $isActive, $resetPw];

            if ($newPass !== '') {
                $sql .= ", password_hash=?";
                $params[] = password_hash($newPass, PASSWORD_BCRYPT);
            }

            $sql .= " WHERE id=?";
            $params[] = $userId;

            Database::query($sql, $params);
            flashSet('success', 'User profile updated.');
            redirect('/admin/user-edit.php?id=' . $userId);
        } else {
            // Create
            if ($newPass === '') {
                $newPass = bin2hex(random_bytes(4)) . '!Aa1';
            }
            $hash = password_hash($newPass, PASSWORD_BCRYPT);

            $existing = Database::fetchOne("SELECT id FROM users WHERE email=? OR phone=?", [$email, $phone]);
            if ($existing) {
                flashSet('error', 'A user with this email or phone number already exists.');
            } else {
                Database::query(
                    "INSERT INTO users (full_name, email, phone, role, password_hash, is_active, must_reset_password)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$fullName, $email, $phone, $role, $hash, $isActive, $resetPw]
                );
                $newId = Database::lastInsertId();
                flashSet('success', "Account created for {$fullName}. Temporary password: {$newPass}");
                redirect('/admin/permissions.php?user=' . $newId);
            }
        }
    }
}

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
      <h2 style="margin:0;"><?= e($pageHeading) ?></h2>
      <p style="margin:4px 0 0;color:var(--dc-muted);font-size:0.88rem;">Manage administrator and operational employee accounts.</p>
    </div>
    <div style="display:flex;gap:8px;">
      <?php if ($userId && $user['role'] !== 'buyer'): ?>
        <a href="<?= url('admin/permissions.php?user=' . $userId) ?>" class="btn btn-secondary">🔑 Manage Permissions</a>
      <?php endif; ?>
      <a href="<?= url('admin/users.php?tab=staff') ?>" class="btn btn-ghost">← Back to Staff</a>
    </div>
  </div>

  <div class="card" style="max-width:680px;">
    <form method="POST">
      <?= csrfField() ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Full Name <span style="color:var(--dc-danger);">*</span></label>
          <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address <span style="color:var(--dc-danger);">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="10-digit mobile">
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" class="form-control" required>
            <option value="employee" <?= ($user['role'] ?? '') === 'employee' ? 'selected' : '' ?>>Employee (Operational)</option>
            <?php if (Auth::role() === 'superadmin'): ?>
              <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Full Management)</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label"><?= $userId ? 'Change Password (leave blank to keep current)' : 'Initial Password (leave blank to auto-generate)' ?></label>
          <input type="text" name="new_password" class="form-control" placeholder="<?= $userId ? 'Enter new password...' : 'Auto-generated if left empty' ?>">
        </div>

        <div class="form-group" style="grid-column:1/-1;display:flex;flex-direction:column;gap:10px;">
          <label style="cursor:pointer;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="is_active" value="1" <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;">
            Active Account (can log into dashboard)
          </label>
          <label style="cursor:pointer;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="must_reset_password" value="1" <?= ($user['must_reset_password'] ?? 0) ? 'checked' : '' ?> style="width:18px;height:18px;">
            Force password change on next login
          </label>
        </div>
      </div>

      <div style="margin-top:20px;display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary btn-lg"><?= $userId ? 'Update User' : 'Create Staff Account' ?></button>
        <a href="<?= url('admin/users.php?tab=staff') ?>" class="btn btn-ghost btn-lg">Cancel</a>
      </div>
    </form>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
