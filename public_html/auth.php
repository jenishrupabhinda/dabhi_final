<?php
/**
 * auth.php — Login / Register Page for Dabhi Chikki
 */
require_once __DIR__ . '/partials/_header.php';

// Redirect if already logged in
if (Auth::check()) {
    $redirect = $_GET['redirect'] ?? 'account.php';
    header('Location: ' . $redirect);
    exit;
}

$pageTitle    = 'Login or Register — Dabhi Chikki';
$extraScripts = ['assets/js/auth.js'];
$activeTab    = $_GET['tab'] ?? 'login'; // login | register
?>

<section class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="assets/images/logo.png" alt="Dabhi Chikki" width="64" height="64">
      <h2>Welcome back</h2>
      <p>Login or create your Dabhi Chikki account</p>
    </div>

    <!-- Tabs -->
    <div class="auth-tabs">
      <div class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" data-tab="login" id="tab-login">Login</div>
      <div class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="register" id="tab-register">Register</div>
    </div>

    <!-- Login Panel -->
    <div class="auth-form-panel" id="panel-login" style="display:<?= $activeTab === 'login' ? 'block' : 'none' ?>">
      <div class="alert alert-error" id="login-error" style="display:none;margin-bottom:1rem"></div>
      <form id="login-form" novalidate>
        <div class="form-group">
          <label class="form-label" for="login-email">Email Address</label>
          <input type="email" id="login-email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="form-group">
          <label class="form-label" for="login-password">
            Password
            <a href="#" style="float:right;color:var(--primary);font-weight:400;font-size:0.8125rem">Forgot password?</a>
          </label>
          <input type="password" id="login-password" name="password" class="form-control" placeholder="Your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="position:relative">
          <span class="spinner"></span>
          <span class="btn-text">Login</span>
        </button>
      </form>
      <div class="auth-footer">
        Don't have an account?
        <a href="#" onclick="document.getElementById('tab-register').click();return false">Create one</a>
      </div>
    </div>

    <!-- Register Panel -->
    <div class="auth-form-panel" id="panel-register" style="display:<?= $activeTab === 'register' ? 'block' : 'none' ?>">
      <div class="alert alert-error" id="register-error" style="display:none;margin-bottom:1rem"></div>
      <form id="register-form" novalidate>
        <div class="form-group">
          <label class="form-label" for="reg-name">Full Name</label>
          <input type="text" id="reg-name" name="full_name" class="form-control" placeholder="Your full name" required autocomplete="name">
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-email">Email Address</label>
          <input type="email" id="reg-email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-phone">Phone Number</label>
          <input type="tel" id="reg-phone" name="phone" class="form-control" placeholder="10-digit mobile" pattern="[6-9][0-9]{9}" autocomplete="tel">
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-pwd">Password</label>
          <input type="password" id="reg-pwd" name="password" class="form-control" placeholder="Min 8 characters" required autocomplete="new-password" minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-pwd2">Confirm Password</label>
          <input type="password" id="reg-pwd2" name="password2" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="position:relative">
          <span class="spinner"></span>
          <span class="btn-text">Create Account</span>
        </button>
      </form>
      <div class="auth-footer">
        Already have an account?
        <a href="#" onclick="document.getElementById('tab-login').click();return false">Login</a>
      </div>
    </div>

    <p style="text-align:center;font-size:0.76rem;color:var(--text-light);margin-top:1.25rem">
      By continuing, you agree to our
      <a href="policy.php?page=terms" style="color:var(--primary)">Terms</a> &amp;
      <a href="policy.php?page=privacy" style="color:var(--primary)">Privacy Policy</a>
    </p>
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
