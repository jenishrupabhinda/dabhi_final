<?php
/**
 * _header.php — Shared site header/nav for Dabhi Chikki storefront
 * Exact 1:1 replica of https://order.yogurtalley.com
 */
$bootstrap = null;
foreach ([
    __DIR__ . '/../../includes/bootstrap.php',
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/includes/bootstrap.php',
] as $b) {
    if (file_exists($b)) {
        $bootstrap = $b;
        break;
    }
}
if ($bootstrap) {
    require_once $bootstrap;
}

$pageTitle = $pageTitle ?? 'Dabhi Chikki — Handcrafted With Pure Jaggery Since 2009';
$pageDesc  = $pageDesc  ?? 'Tastes like dessert. Works like fuel. Authentic handmade Mandvi, TIL, Daliya, and 3 Mix Chikki made with pure jaggery. Pan-India fresh delivery.';

$cartCount = 0;
try {
    $cart = Cart::getOrCreate();
    $cartCount = Cart::itemCount((int)($cart['id'] ?? 0));
} catch (\Throwable $e) {
    $cartCount = 0;
}
$isLoggedIn = Auth::check();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <link rel="preload" as="image" href="assets/images/logo.png"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="format-detection" content="telephone=no"/>
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>"/>
  <meta property="og:site_name" content="Dabhi Chikki"/>
  <meta property="og:type" content="website"/>
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>"/>
  <meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>"/>
  <meta property="og:image" content="assets/images/logo.png"/>
  <link rel="icon" type="image/png" href="assets/images/logo.png"/>

  <!-- Google Fonts: Poppins & Cascadia Code (Exact as Yogurt Alley) -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;family=Cascadia+Code:wght@400;600&amp;display=swap"/>

  <!-- Clientside scripts use relative endpoints directly -->
  <script>window.APP_URL = '';</script>

  <!-- Subpage Component CSS -->
  <link rel="stylesheet" href="assets/css/main.css"/>
  <!-- Exact Yogurt Alley Compiled Production CSS with Recoleta Font-Face & Tokens -->
  <link rel="stylesheet" href="assets/css/yogurtalley.css"/>

  <style>
    html, body {
      overflow-x: clip !important;
      max-width: 100vw;
      width: 100%;
      position: relative;
    }
    header.site-sticky-header {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 40 !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
      background-color: #541f21 !important;
      transition: background-color 0.3s ease, box-shadow 0.3s ease !important;
    }
    header.site-sticky-header.header-unscrolled {
      background-color: #541f21 !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12) !important;
    }
    header.site-sticky-header.header-scrolled {
      background-color: rgba(84, 31, 33, 0.96) !important;
      backdrop-filter: blur(16px) !important;
      -webkit-backdrop-filter: blur(16px) !important;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.22) !important;
    }
    @media (min-width: 768px) {
      .site-logo-img { height: 48px !important; width: auto !important; }
    }
    @media (max-width: 767px) {
      .site-logo-img { height: 38px !important; width: auto !important; }
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .reels-scroll::-webkit-scrollbar { display: none; }
    
    /* Yogurt Alley Sonner Toast at Top Center */
    #toast-container {
      position: fixed;
      top: 1.25rem;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      pointer-events: none;
      width: auto;
      max-width: 90vw;
    }
    .toast-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.625rem;
      padding: 0.625rem 1.125rem;
      border-radius: 9999px;
      background: #ffffff;
      color: #2b1311;
      font-family: var(--font-sans, 'Poppins', sans-serif);
      font-size: 0.8125rem;
      font-weight: 500;
      border: 1px solid #ededed;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      pointer-events: auto;
      animation: toastSlideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      white-space: nowrap;
    }
    .toast-pill svg {
      width: 1rem;
      height: 1rem;
      flex-shrink: 0;
    }
    .toast-pill.success svg { color: #00bb7f; stroke: #00bb7f; }
    .toast-pill.error svg { color: #d40924; stroke: #d40924; }
    @keyframes toastSlideDown {
      from { opacity: 0; transform: translateY(-12px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes toastSlideUp {
      from { opacity: 1; transform: translateY(0) scale(1); }
      to { opacity: 0; transform: translateY(-12px) scale(0.96); }
    }
  </style>
</head>
<body>
<div class="min-h-dvh bg-background">
  <div class="pb-24 md:pb-0">

    <!-- ══ ANNOUNCEMENT BAR (Module Controlled) ══ -->
    <?php if (function_exists('settingEnabled') && settingEnabled('module_announcement', '1')): 
      $announcementText  = getSetting('announcement_text', '🎉 FREE shipping on orders above ₹499 | Authentic Rajkot Jaggery Chikki');
      $announcementBadge = getSetting('announcement_badge', 'Special Offer');
      $announcementLink  = getSetting('announcement_link', '#products');
    ?>
      <div id="site-announcement-bar" class="w-full text-center py-2 px-4 text-xs sm:text-sm font-medium transition-all duration-300 flex items-center justify-center gap-2" style="background: linear-gradient(90deg, #3d1412 0%, #c7613d 50%, #3d1412 100%); color: #ffffff; z-index: 45;">
        <?php if (!empty($announcementBadge)): ?>
          <span class="rounded-full bg-white/20 px-2.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wider text-[#f6dc94]">
            <?= htmlspecialchars($announcementBadge) ?>
          </span>
        <?php endif; ?>
        <?php if (!empty($announcementLink)): ?>
          <a href="<?= htmlspecialchars($announcementLink) ?>" class="hover:underline hover:text-[#f6dc94] flex items-center gap-1">
            <span><?= htmlspecialchars($announcementText) ?></span>
            <span class="text-[#f6dc94] text-xs">→</span>
          </a>
        <?php else: ?>
          <span><?= htmlspecialchars($announcementText) ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- ══ STICKY HEADER (Brown Header matching Brand Theme) ══ -->
    <header id="site-header" class="site-sticky-header header-unscrolled sticky top-0 z-40 transition-all duration-300">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 md:px-6">
        
        <!-- Brand Logo (Official Admin Emblem) -->
        <a href="index.php" class="flex items-center gap-2.5 shrink-0" aria-label="Dabhi Chikki home">
          <img src="assets/images/logo.png" alt="Dabhi Chikki" class="site-logo-img h-10 w-auto md:h-12 object-contain shrink-0" draggable="false"/>
        </a>

        <!-- Center Nav: Products & Track Order -->
        <nav class="hidden sm:flex items-center gap-6">
          <a href="index.php#products" class="text-sm font-medium text-white/90 hover:text-[#f6dc94] transition">Products</a>
          <a href="track.php" class="text-sm font-medium text-white/90 hover:text-[#f6dc94] transition flex items-center gap-1.5">
            <span>📦</span> Track Order
          </a>
        </nav>

        <!-- Right Action Icons: User Account + Cart Bag -->
        <div class="flex items-center gap-2 shrink-0">
          <a href="track.php" class="flex sm:hidden h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Track Order" title="Track Order">
            <span style="font-size:1.1rem;">📦</span>
          </a>
          <!-- User profile link -->
          <?php if ($isLoggedIn): ?>
            <a href="account.php" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Account">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user h-5 w-5" style="stroke:#ffffff !important;" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
          <?php else: ?>
            <a href="auth.php" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Sign in">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user h-5 w-5" style="stroke:#ffffff !important;" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
          <?php endif; ?>

          <!-- Shopping Bag Button -->
          <button id="cart-toggle-btn" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Cart">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-5 w-5" style="stroke:#ffffff !important;" aria-hidden="true">
              <path d="M16 10a4 4 0 0 1-8 0"></path>
              <path d="M3.103 6.034h17.794"></path>
              <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"></path>
            </svg>
            <span id="cart-badge" class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#f6dc94] px-1 text-[11px] font-bold text-[#541f21] shadow-soft <?= $cartCount > 0 ? '' : 'hidden' ?>">
              <?= $cartCount > 99 ? '99+' : $cartCount ?>
            </span>
          </button>
        </div>

      </div>
    </header>

    <script>
    (function() {
      const header = document.getElementById('site-header');
      if (!header) return;
      function updateHeader() {
        if (window.scrollY > 10) {
          header.classList.remove('header-unscrolled');
          header.classList.add('header-scrolled');
        } else {
          header.classList.remove('header-scrolled');
          header.classList.add('header-unscrolled');
        }
      }
      window.addEventListener('scroll', updateHeader, { passive: true });
      updateHeader();
    })();
    </script>
