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

// Fetch dynamic storefront navigation
$navTree = [];
try {
    if (class_exists('Menu')) {
        $navTree = Menu::getTree('primary', true);
    }
} catch (\Throwable $e) {
    $navTree = [];
}
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
    
    /* ══ HEADER RESPONSIVE LAYOUT & MENU SYSTEM ══ */
    .site-header-container {
      position: relative !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
    }
    .site-brand-logo-container {
      display: flex !important;
      align-items: center !important;
      flex-shrink: 0 !important;
    }
    .site-desktop-nav {
      display: flex !important;
      align-items: center !important;
      gap: 0.35rem !important;
      font-size: 0.875rem !important;
      font-weight: 500 !important;
    }
    .header-nav-link {
      font-family: var(--font-sans, 'Poppins', sans-serif);
      font-weight: 500;
      letter-spacing: 0.01em;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      padding: 0.375rem 0.875rem;
      border-radius: 9999px;
      color: rgba(255, 255, 255, 0.9) !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 0.3rem !important;
      text-decoration: none !important;
    }
    .header-nav-link:hover {
      color: #f6dc94 !important;
      background-color: rgba(255, 255, 255, 0.12) !important;
    }
    .nav-dropdown-wrap {
      position: relative !important;
      display: inline-block !important;
    }
    .nav-dropdown-wrap:hover .dropdown-chevron {
      transform: rotate(180deg);
    }
    .nav-dropdown-menu {
      position: absolute !important;
      left: 50% !important;
      top: 100% !important;
      transform: translateX(-50%) translateY(8px) !important;
      padding-top: 8px !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.22s !important;
      z-index: 999 !important;
    }
    .nav-dropdown-wrap:hover .nav-dropdown-menu,
    .nav-dropdown-wrap:focus-within .nav-dropdown-menu {
      opacity: 1 !important;
      visibility: visible !important;
      transform: translateX(-50%) translateY(0) !important;
      pointer-events: auto !important;
    }

    /* 100% Solid, Non-Transparent Desktop Dropdown Card */
    .nav-dropdown-card {
      width: 330px !important;
      border-radius: 1rem !important;
      background-color: #2b0e0d !important;
      background: #2b0e0d !important;
      border: 1px solid rgba(246, 220, 148, 0.3) !important;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.75), 0 4px 16px rgba(0, 0, 0, 0.5) !important;
      padding: 0.65rem !important;
      color: #ffffff !important;
      text-align: left !important;
    }
    .nav-dropdown-header {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 0.35rem 0.75rem 0.5rem 0.75rem !important;
      font-family: var(--font-mono, 'Cascadia Code', monospace) !important;
      font-size: 0.625rem !important;
      text-transform: uppercase !important;
      letter-spacing: 0.2em !important;
      color: #f6dc94 !important;
      font-weight: 600 !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
      margin-bottom: 0.35rem !important;
    }
    .nav-dropdown-item {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      gap: 0.625rem !important;
      border-radius: 0.75rem !important;
      padding: 0.5rem 0.75rem !important;
      text-decoration: none !important;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
      background-color: transparent !important;
    }
    .nav-dropdown-item:hover {
      background-color: rgba(255, 255, 255, 0.1) !important;
    }
    .nav-dropdown-item:hover .nav-item-title {
      color: #f6dc94 !important;
    }
    .nav-item-title {
      font-size: 0.775rem !important;
      font-weight: 600 !important;
      color: #ffffff !important;
      transition: color 0.2s ease !important;
      line-height: 1.2 !important;
    }
    .nav-item-sub {
      font-size: 0.6875rem !important;
      color: rgba(255, 255, 255, 0.6) !important;
      margin-top: 2px !important;
      line-height: 1.2 !important;
    }
    .nav-item-badge {
      display: inline-block !important;
      border-radius: 9999px !important;
      padding: 0.125rem 0.5rem !important;
      font-family: var(--font-mono, 'Cascadia Code', monospace) !important;
      font-size: 0.5625rem !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.05em !important;
      white-space: nowrap !important;
      line-height: 1.4 !important;
    }

    /* Mobile Breakpoint: < 1024px */
    @media (max-width: 1023px) {
      #mobile-menu-btn-wrap {
        display: flex !important;
      }
      .site-desktop-nav {
        display: none !important;
      }
      .site-brand-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10 !important;
      }
    }

    /* Desktop Breakpoint: >= 1024px */
    @media (min-width: 1024px) {
      #mobile-menu-btn-wrap {
        display: none !important;
      }
      .site-desktop-nav {
        display: flex !important;
      }
      .site-brand-logo-container {
        position: static !important;
        transform: none !important;
      }
    }

    /* Mobile Navigation Drawer & Backdrop */
    #mobile-drawer {
      position: fixed !important;
      left: 0 !important;
      top: 0 !important;
      bottom: 0 !important;
      width: 85% !important;
      max-width: 320px !important;
      height: 100vh !important;
      height: 100dvh !important;
      background-color: #210e0d !important;
      color: #ffffff !important;
      z-index: 99999 !important;
      transform: translateX(-100%) !important;
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
      display: flex !important;
      flex-direction: column !important;
      will-change: transform;
    }
    #mobile-drawer.drawer-open {
      transform: translateX(0) !important;
    }
    #mobile-drawer-overlay {
      position: fixed !important;
      inset: 0 !important;
      background: rgba(0, 0, 0, 0.65) !important;
      backdrop-filter: blur(4px) !important;
      -webkit-backdrop-filter: blur(4px) !important;
      z-index: 99998 !important;
      opacity: 0 !important;
      pointer-events: none !important;
      transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    #mobile-drawer-overlay.overlay-open {
      opacity: 1 !important;
      pointer-events: auto !important;
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

    <!-- ══ STICKY HEADER (Theme Matching Deep Roasted Jaggery Burgundy) ══ -->
    <header id="site-header" class="site-sticky-header header-unscrolled sticky top-0 z-40 transition-all duration-300">
      <div class="site-header-container mx-auto max-w-6xl px-3 sm:px-4 py-2.5 md:px-6">
        
        <!-- ── Left: Hamburger Menu Button (Mobile View, Hidden on Desktop) ── -->
        <div id="mobile-menu-btn-wrap" class="items-center z-10">
          <button
            id="mobile-menu-btn"
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-white/10 active:scale-95"
            aria-label="Open navigation menu"
            title="Menu"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
              <line x1="4" x2="20" y1="12" y2="12"></line>
              <line x1="4" x2="20" y1="6" y2="6"></line>
              <line x1="4" x2="20" y1="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- ── Brand Logo: Moved to Center in Mobile, Natural Left on Desktop ── -->
        <div class="site-brand-logo-container z-10">
          <a href="index.php" class="flex items-center gap-2.5" aria-label="Dabhi Chikki Home">
            <img src="assets/images/logo.png" alt="Dabhi Chikki" class="site-logo-img h-10 w-auto md:h-12 object-contain shrink-0" draggable="false"/>
          </a>
        </div>

        <!-- ── Center: Desktop Navigation Bar (Visible on lg+) ── -->
        <nav class="site-desktop-nav">
          <?php if (!empty($navTree)): ?>
            <?php foreach ($navTree as $item): ?>
              <?php if (!empty($item['children'])): ?>
                <!-- Item with Dropdown Submenu -->
                <div class="nav-dropdown-wrap">
                  <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target'] ?? '_self') ?>" class="header-nav-link cursor-pointer">
                    <?php if (!empty($item['icon'])): ?>
                      <span class="text-sm"><?= htmlspecialchars($item['icon']) ?></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($item['title']) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="dropdown-chevron transition-transform duration-200"><path d="m6 9 6 6 6-6"/></svg>
                  </a>

                  <!-- 100% Solid Opaque Dropdown Menu Card -->
                  <div class="nav-dropdown-menu">
                    <div class="nav-dropdown-card">
                      <div class="nav-dropdown-header">
                        <span><?= htmlspecialchars($item['title']) ?></span>
                        <span style="color:rgba(255,255,255,0.45);text-transform:none;letter-spacing:normal;font-size:10px;">Pure Jaggery</span>
                      </div>
                      <div style="display:flex;flex-direction:column;gap:3px;">
                        <?php foreach ($item['children'] as $child): ?>
                          <a href="<?= htmlspecialchars($child['url']) ?>" target="<?= htmlspecialchars($child['target'] ?? '_self') ?>" class="nav-dropdown-item">
                            <div style="display:flex;align-items:center;gap:10px;">
                              <?php if (!empty($child['icon'])): ?>
                                <span style="font-size:1.15rem;"><?= htmlspecialchars($child['icon']) ?></span>
                              <?php endif; ?>
                              <div>
                                <div class="nav-item-title"><?= htmlspecialchars($child['title']) ?></div>
                                <?php if (!empty($child['subtitle'])): ?>
                                  <div class="nav-item-sub"><?= htmlspecialchars($child['subtitle']) ?></div>
                                <?php endif; ?>
                              </div>
                            </div>
                            <?php if (!empty($child['badge'])): ?>
                              <span class="nav-item-badge" style="background-color:<?= htmlspecialchars($child['badge_color'] ?: '#c7613d') ?>;color:<?= ($child['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                                <?= htmlspecialchars($child['badge']) ?>
                              </span>
                            <?php endif; ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <!-- Direct Nav Link -->
                <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target'] ?? '_self') ?>" class="header-nav-link">
                  <?php if (!empty($item['icon'])): ?>
                    <?php if ($item['icon'] === '🔴'): ?>
                      <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background-color:#f87171;margin-right:2px;"></span>
                    <?php else: ?>
                      <span><?= htmlspecialchars($item['icon']) ?></span>
                    <?php endif; ?>
                  <?php endif; ?>
                  <span><?= htmlspecialchars($item['title']) ?></span>
                  <?php if (!empty($item['badge'])): ?>
                    <span class="nav-item-badge" style="background-color:<?= htmlspecialchars($item['badge_color'] ?: '#c7613d') ?>;color:<?= ($item['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                      <?= htmlspecialchars($item['badge']) ?>
                    </span>
                  <?php endif; ?>
                </a>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback Static Nav Links -->
            <a href="index.php" class="header-nav-link"><span>Home</span></a>
            <a href="index.php#products" class="header-nav-link"><span>Our Chikki</span></a>
            <a href="index.php#our-story" class="header-nav-link"><span>Our Craft</span></a>
            <a href="index.php#reels-section" class="header-nav-link">
              <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background-color:#f87171;margin-right:2px;"></span>
              <span>Reels</span>
            </a>
            <a href="index.php#reviews" class="header-nav-link"><span>Reviews</span></a>
            <a href="track.php" class="header-nav-link"><span>📦</span><span>Track Order</span></a>
          <?php endif; ?>
        </nav>

        <!-- ── Right Action Icons: User Account + Cart Bag ── -->
        <div class="site-header-actions flex items-center gap-1.5 sm:gap-2 shrink-0 z-10">
          <!-- User profile link -->
          <?php if ($isLoggedIn): ?>
            <a href="account.php" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Account" title="My Account">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
          <?php else: ?>
            <a href="auth.php" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Sign in" title="Sign In">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
          <?php endif; ?>

          <!-- Shopping Bag Button -->
          <button id="cart-toggle-btn" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10" style="color:#ffffff !important;" aria-label="Cart" title="Cart Bag">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
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

    <!-- ══ MOBILE NAVIGATION DRAWER & BACKDROP ══ -->
    <div id="mobile-drawer-overlay" class="site-drawer-backdrop" onclick="closeMobileDrawer()"></div>

    <aside id="mobile-drawer" class="site-mobile-drawer border-r border-[#f6dc94]/20">
      
      <!-- Drawer Header -->
      <div class="flex items-center justify-between border-b border-white/10 px-5 py-3.5 bg-[#2b1211]">
        <a href="index.php" class="flex items-center gap-2" onclick="closeMobileDrawer()" aria-label="Dabhi Chikki Home">
          <img src="assets/images/logo.png" alt="Dabhi Chikki" class="h-9 w-auto object-contain"/>
        </a>
        <button id="mobile-drawer-close-btn" type="button" class="flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" onclick="closeMobileDrawer()" aria-label="Close menu">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
      </div>

      <!-- Drawer Scrollable Body -->
      <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4" style="scrollbar-width:none">
        
        <!-- Section: Navigation -->
        <div>
          <p class="px-2 mb-1.5 text-[10px] font-mono uppercase tracking-[0.2em] text-[#f6dc94] font-semibold">Explore</p>
          <div class="space-y-1">
            <?php if (!empty($navTree)): ?>
              <?php foreach ($navTree as $item): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target'] ?? '_self') ?>" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                  <div class="flex items-center gap-3">
                    <?php if (!empty($item['icon'])): ?>
                      <span class="text-base"><?= htmlspecialchars($item['icon']) ?></span>
                    <?php else: ?>
                      <span class="text-xs text-[#f6dc94]">✦</span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($item['title']) ?></span>
                  </div>
                  <?php if (!empty($item['badge'])): ?>
                    <span class="rounded-full px-2 py-0.5 text-[9px] font-mono font-bold uppercase tracking-wider" style="background-color:<?= htmlspecialchars($item['badge_color'] ?: '#c7613d') ?>;color:<?= ($item['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                      <?= htmlspecialchars($item['badge']) ?>
                    </span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <a href="index.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <span class="text-base">🏠</span><span>Home</span>
              </a>
              <a href="index.php#our-story" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <span class="text-base">📖</span><span>Our Story &amp; Craft</span>
              </a>
              <a href="index.php#reels-section" class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <div class="flex items-center gap-3"><span class="text-base">🎬</span><span>Reels &amp; Videos</span></div>
                <span class="rounded-full bg-red-500/20 text-red-300 px-2 py-0.5 text-[9px] font-mono font-bold uppercase tracking-wider">Live</span>
              </a>
              <a href="index.php#reviews" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <span class="text-base">⭐</span><span>Customer Reviews</span>
              </a>
              <a href="track.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <span class="text-base">📦</span><span>Track Order</span>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <?php 
        // Render any active submenu groups
        $submenuGroups = array_filter($navTree, fn($t) => !empty($t['children']));
        ?>
        <?php foreach ($submenuGroups as $group): ?>
          <div class="border-t border-white/10"></div>
          <div>
            <div class="flex items-center justify-between px-2 mb-1.5">
              <p class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#f6dc94] font-semibold"><?= htmlspecialchars($group['title']) ?></p>
              <a href="<?= htmlspecialchars($group['url']) ?>" class="text-[11px] text-[#f6dc94] hover:underline" onclick="closeMobileDrawer()">View All →</a>
            </div>
            <div class="space-y-1">
              <?php foreach ($group['children'] as $child): ?>
                <a href="<?= htmlspecialchars($child['url']) ?>" target="<?= htmlspecialchars($child['target'] ?? '_self') ?>" class="flex items-center justify-between rounded-xl px-3 py-2 transition hover:bg-white/10" onclick="closeMobileDrawer()">
                  <div class="flex items-center gap-2.5">
                    <?php if (!empty($child['icon'])): ?>
                      <span class="text-base"><?= htmlspecialchars($child['icon']) ?></span>
                    <?php endif; ?>
                    <div>
                      <div class="text-xs font-semibold text-white"><?= htmlspecialchars($child['title']) ?></div>
                      <?php if (!empty($child['subtitle'])): ?>
                        <div class="text-[10px] text-[#f6dc94]"><?= htmlspecialchars($child['subtitle']) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if (!empty($child['badge'])): ?>
                    <span class="rounded-full px-2 py-0.5 text-[9px] font-mono font-bold uppercase tracking-wider" style="background-color:<?= htmlspecialchars($child['badge_color'] ?: '#c7613d') ?>;color:<?= ($child['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                      <?= htmlspecialchars($child['badge']) ?>
                    </span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="border-t border-white/10"></div>

        <!-- Section: Account & Support -->
        <div>
          <p class="px-2 mb-1.5 text-[10px] font-mono uppercase tracking-[0.2em] text-[#f6dc94] font-semibold">Account</p>
          <div class="space-y-1">
            <?php if ($isLoggedIn): ?>
              <a href="account.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10" onclick="closeMobileDrawer()">
                <span class="text-base">👤</span>
                <span>My Profile &amp; Orders</span>
              </a>
              <a href="logout.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-red-300 transition hover:bg-white/10" onclick="closeMobileDrawer()">
                <span class="text-base">🚪</span>
                <span>Log Out</span>
              </a>
            <?php else: ?>
              <a href="auth.php" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/10 hover:text-[#f6dc94]" onclick="closeMobileDrawer()">
                <span class="text-base">🔐</span>
                <span>Sign In / Create Account</span>
              </a>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Drawer Footer: WhatsApp & Quality Promise -->
      <div class="border-t border-white/10 bg-[#1a0b0a] p-4 space-y-2">
        <a href="https://wa.me/919876543210" target="_blank" rel="noreferrer" class="flex items-center justify-center gap-2 w-full rounded-xl bg-emerald-700/80 hover:bg-emerald-700 py-2.5 px-3 text-xs font-semibold text-white shadow-sm transition">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          <span>WhatsApp Support (+91 98765 43210)</span>
        </a>
        <div class="text-center text-[10px] text-white/50 font-mono">
          100% Pure Jaggery • Handcrafted Since 2009
        </div>
      </div>

    </aside>

    <script>
    function openMobileDrawer() {
      const drawer = document.getElementById('mobile-drawer');
      const overlay = document.getElementById('mobile-drawer-overlay');
      if (drawer && overlay) {
        drawer.classList.add('drawer-open');
        overlay.classList.add('overlay-open');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeMobileDrawer() {
      const drawer = document.getElementById('mobile-drawer');
      const overlay = document.getElementById('mobile-drawer-overlay');
      if (drawer && overlay) {
        drawer.classList.remove('drawer-open');
        overlay.classList.remove('overlay-open');
        document.body.style.overflow = '';
      }
    }

    (function() {
      // Header scroll background effect
      const header = document.getElementById('site-header');
      if (header) {
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
      }

      // Mobile Drawer event bindings
      const menuBtn = document.getElementById('mobile-menu-btn');
      if (menuBtn) {
        menuBtn.addEventListener('click', openMobileDrawer);
      }
      const overlay = document.getElementById('mobile-drawer-overlay');
      if (overlay) {
        overlay.addEventListener('click', closeMobileDrawer);
      }
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeMobileDrawer();
        }
      });
    })();
    </script>
