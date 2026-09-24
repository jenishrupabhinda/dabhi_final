<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Already logged in as staff? Route to dashboard
if (Auth::check() && in_array(Auth::role(), ['superadmin', 'admin', 'employee'])) {
    redirect('/admin/index.php');
}

// Forward to unified login page
if (!isPost()) {
    redirect('/auth.php?redirect=' . urlencode('admin/index.php'));
}

$errors    = [];
$formEmail = '';

if (isPost()) {
    csrfVerify();

    $emailOrPhone = post('email');
    $password     = post('password');
    $formEmail    = $emailOrPhone;

    if ($emailOrPhone === '' || $password === '') {
        $errors[] = 'Please enter your email/phone and password.';
    } else {
        $result = Auth::attempt($emailOrPhone, $password);
        if ($result['ok']) {
            $user = $result['user'];
            // Only allow staff login here
            if (!in_array($user['role'], ['superadmin', 'admin', 'employee'])) {
                Auth::logout();
                $errors[] = 'This login is for staff only. Buyers: please use the main store login.';
            } elseif ($user['must_reset_password']) {
                $_SESSION['force_reset_user_id'] = $user['id'];
                redirect('/reset-password.php?forced=1');
            } else {
                redirect('/admin/index.php');
            }
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Login | <?= e(APP_NAME) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
</head>
<body>
<div class="admin-auth-page">

  <!-- Left panel — brand -->
  <div class="admin-auth-left">
    <div class="admin-auth-pattern" aria-hidden="true"></div>

    <img src="<?= asset('images/logo.png') ?>" alt="<?= e(APP_NAME) ?>" style="position:relative;z-index:1;">
    <h2 style="position:relative;z-index:1;margin-top:var(--dc-space-lg);">
      <?= e(APP_NAME) ?><br>Admin Panel
    </h2>
    <p style="position:relative;z-index:1;">
      Manage products, orders, inventory, analytics, GST reports, and more — all from one place.
    </p>

    <!-- Feature badges -->
    <div style="position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:var(--dc-space-lg);">
      <?php foreach (['📦 Inventory','📋 Reports','🚚 Shipping','🧾 GST','📈 Analytics','🎁 Build-Your-Box'] as $f): ?>
        <span class="badge badge-yellow" style="font-size:0.75rem;"><?= $f ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right panel — form -->
  <div class="admin-auth-right">
    <div class="admin-auth-form">

      <h1>Staff Sign In</h1>
      <p class="subtitle">Superadmin · Admin · Employee</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error" role="alert"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="<?= url('admin/login.php') ?>" novalidate>
        <?= csrfField() ?>

        <div class="form-group">
          <label class="form-label" for="email">Email or Phone <span>*</span></label>
          <div class="input-group">
            <span class="input-group-icon">✉️</span>
            <input type="text" id="email" name="email" class="form-control <?= !empty($errors) ? 'is-invalid' : '' ?>"
              value="<?= e($formEmail) ?>" placeholder="admin@dabhichikki.com"
              autocomplete="username" autofocus required>
          </div>
        </div>

        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <label class="form-label" for="password" style="margin:0;">Password <span>*</span></label>
            <a href="<?= url('forgot-password.php') ?>" style="font-size:0.82rem;">Forgot?</a>
          </div>
          <div class="input-group">
            <span class="input-group-icon">🔒</span>
            <input type="password" id="password" name="password" class="form-control"
              placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" id="togglePwd" class="input-group-icon"
              style="cursor:pointer;background:none;border:none;padding:0 12px;"
              aria-label="Toggle password visibility">👁</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:var(--dc-space-sm);">
          Sign In to Panel →
        </button>
      </form>

      <div style="margin-top:var(--dc-space-lg);padding-top:var(--dc-space-md);border-top:1px solid var(--dc-border);font-size:0.82rem;color:var(--dc-muted);text-align:center;">
        Customer? <a href="<?= url('login.php') ?>">Shop login →</a>
      </div>

      <?php if (APP_DEBUG): ?>
      <div class="alert alert-warning mt-md" style="font-size:0.8rem;">
        🛠 Debug mode on. Run <code>php database/create_superadmin.php</code> to create your first login.
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<script>
(function(){
  const btn = document.getElementById('togglePwd');
  const inp = document.getElementById('password');
  if(btn && inp){
    btn.addEventListener('click', function(){
      inp.type = inp.type === 'password' ? 'text' : 'password';
      this.textContent = inp.type === 'password' ? '👁' : '🙈';
    });
  }
})();
</script>
</body>
</html>
