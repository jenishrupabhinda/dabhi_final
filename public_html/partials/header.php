<?php
/**
 * Site header partial — include at the top of every buyer-facing page.
 * Usage:
 *   $pageTitle = 'Shop';   // set before including
 *   $pageDesc  = '...';    // optional meta description
 *   require_once __DIR__ . '/partials/header.php';
 */
if (!isset($pageTitle)) $pageTitle = APP_NAME;
if (!isset($pageDesc))  $pageDesc  = 'Authentic handcrafted chikki, sweets, and namkeen - made fresh with love from Gujarat.';

// Active category for nav highlight
$_activeCat = get('category', '');

// Cart item count (guest or logged-in)
$_cartCount = 0;
if (Auth::check()) {
    $cartRow = Database::fetchOne(
        'SELECT COALESCE(SUM(ci.quantity),0) AS cnt
         FROM carts c JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.user_id = ?',
        [Auth::id()]
    );
    $_cartCount = (int)($cartRow['cnt'] ?? 0);
} elseif (!empty($_SESSION['guest_cart_token'])) {
    $cartRow = Database::fetchOne(
        'SELECT COALESCE(SUM(ci.quantity),0) AS cnt
         FROM carts c JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.session_token = ?',
        [$_SESSION['guest_cart_token']]
    );
    $_cartCount = (int)($cartRow['cnt'] ?? 0);
}

// Active categories for the category nav bar
$_navCategories = Database::fetchAll(
    'SELECT id, name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <link rel="canonical" href="<?= e(APP_URL . $_SERVER['REQUEST_URI']) ?>">

  <!-- Open Graph -->
  <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
  <meta property="og:title"       content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDesc) ?>">
  <meta property="og:type"        content="website">

  <!-- Favicon -->
  <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>

<!-- ===== Top Announcement Bar ===== -->
<div class="top-announcement-bar" role="region" aria-label="Announcement">
  <div class="container announcement-content">
    <span>✨ <strong>Special Offer:</strong> Free Shipping Across India on Orders Above ₹499!</span>
    <span class="announcement-sep">•</span>
    <span>🌿 100% Desi Jaggery &amp; Pure Ingredients</span>
    <span class="announcement-sep">•</span>
    <span>📦 Handcrafted Fresh in Gujarat</span>
  </div>
</div>

<!-- ===== Main Navigation ===== -->
<header class="site-nav" role="banner">
  <div class="container site-nav-container">
    <a href="<?= url('/') ?>" class="nav-logo" aria-label="<?= e(APP_NAME) ?> Home">
      <img src="<?= asset('images/logo.png') ?>" alt="<?= e(APP_NAME) ?>" loading="eager">
    </a>

    <nav aria-label="Main navigation">
      <ul class="nav-links" role="list">
        <li><a href="<?= url('/') ?>" class="<?= currentPage() === 'index' ? 'active' : '' ?>">Home</a></li>
        <li><a href="<?= url('shop.php') ?>" class="<?= currentPage() === 'shop' ? 'active' : '' ?>">Shop</a></li>
        <li><a href="<?= url('build-your-box.php') ?>" class="<?= currentPage() === 'build-your-box' ? 'active' : '' ?>">🎁 Build Your Box</a></li>
        <li><a href="<?= url('track-order.php') ?>" class="<?= currentPage() === 'track-order' ? 'active' : '' ?>">Track Order</a></li>
      </ul>
    </nav>

    <div class="nav-actions">
      <!-- Search -->
      <form action="<?= url('shop.php') ?>" method="GET" class="nav-search-bar" role="search">
        <input type="search" name="q" class="nav-search-input" placeholder="Search chikki, sweets..." aria-label="Search products" value="<?= e(get('q', '')) ?>" autocomplete="off">
        <button type="submit" class="nav-search-btn" aria-label="Submit search">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
      </form>

      <!-- Auth Actions -->
      <?php if (Auth::check() && Auth::role() === 'buyer'): ?>
        <a href="<?= url('account/index.php') ?>" class="nav-btn nav-btn-account" aria-label="My Account">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span><?= e(explode(' ', Auth::user()['full_name'] ?? '')[0]) ?></span>
        </a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="nav-btn nav-btn-login">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          <span>Sign in</span>
        </a>
        <a href="<?= url('register.php') ?>" class="nav-btn nav-btn-register">Register</a>
      <?php endif; ?>

      <!-- Cart Button -->
      <a href="<?= url('cart.php') ?>" class="nav-btn nav-btn-cart cart-btn" aria-label="View cart (<?= $_cartCount ?> items)">
        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span>Cart</span>
        <?php if ($_cartCount > 0): ?>
          <span class="nav-cart-badge cart-count"><?= $_cartCount ?></span>
        <?php endif; ?>
      </a>

      <!-- Mobile Hamburger (Only shows on mobile) -->
      <button class="nav-hamburger" id="navHamburger" aria-label="Toggle navigation menu" aria-expanded="false">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>
    </div>
  </div>
</header>

<!-- ===== Category Nav Bar ===== -->
<?php if (!empty($_navCategories)): ?>
<nav class="cat-nav" aria-label="Category navigation">
  <div class="container">
    <?php foreach ($_navCategories as $cat): ?>
      <a href="<?= url('shop.php?category=' . urlencode($cat['slug'])) ?>"
         class="cat-nav-item <?= $_activeCat === $cat['slug'] ? 'active' : '' ?>"
         aria-current="<?= $_activeCat === $cat['slug'] ? 'page' : 'false' ?>">
        <?= e($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<!-- ===== Flash Messages ===== -->
<?php
$_flashMessages = flashGet();
if (!empty($_flashMessages)): ?>
<div class="container mt-md" role="alert" aria-live="polite">
  <?php foreach ($_flashMessages as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ===== Page Content ===== -->
<main id="main-content">
