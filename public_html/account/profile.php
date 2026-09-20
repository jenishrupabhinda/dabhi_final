<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');

$userId = Auth::id();
$user   = Database::fetchOne('SELECT * FROM users WHERE id=?', [$userId]);
$errors = [];

if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'update_profile') {
        $fullName = trim(post('full_name'));
        $phone    = preg_replace('/\D/', '', post('phone'));

        if (!$fullName) $errors[] = 'Name is required.';
        if (strlen($phone) !== 10) $errors[] = 'Valid 10-digit phone is required.';

        // Check duplicate phone (excluding self)
        $dup = Database::fetchOne('SELECT id FROM users WHERE phone=? AND id!=?', [$phone, $userId]);
        if ($dup) $errors[] = 'This phone is already registered.';

        if (empty($errors)) {
            Database::query('UPDATE users SET full_name=?, phone=? WHERE id=?', [$fullName, $phone, $userId]);
            flashSet('success', 'Profile updated.');
            redirect('/account/profile.php');
        }
    }

    if ($action === 'change_password') {
        $current = post('current_password');
        $new     = post('new_password');
        $confirm = post('confirm_password');

        if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($new !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            Database::query('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $userId]);
            flashSet('success', 'Password changed successfully.');
            redirect('/account/profile.php');
        }
    }
}

$pageTitle = 'My Profile';
$pageDesc  = 'Manage your account details.';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--dc-space-xl);padding-bottom:var(--dc-space-xl);max-width:600px;">
  <h1 style="margin-bottom:24px;">My Profile</h1>
  <?php flashRender(); ?>
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= e($e) ?></div><?php endforeach; ?>

  <!-- Profile info -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 class="card-title">Personal Details</h3></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="update_profile">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email (read-only)</label>
        <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="tel" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
  </div>

  <!-- Change password -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" minlength="8" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
