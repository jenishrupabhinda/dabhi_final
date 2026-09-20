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

    /* Mobile Navigation Drawer (GimiGimi style with sliding panels) */
    #mobile-drawer {
      position: fixed !important;
      left: 0 !important;
      top: 0 !important;
      bottom: 0 !important;
      width: 90% !important;
      max-width: 380px !important;
      height: 100vh !important;
      height: 100dvh !important;
      background-color: #fdfbf7 !important;
      color: #2b1311 !important;
      z-index: 99999 !important;
      transform: translateX(-100%) !important;
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
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

    /* Drawer Red Header */
    .mobile-drawer-header {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 12px 18px !important;
      background-color: #541f21 !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
      flex-shrink: 0 !important;
    }
    .mobile-drawer-close-btn {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 36px !important;
      height: 36px !important;
      border-radius: 9999px !important;
      color: #ffffff !important;
      background: transparent !important;
      border: none !important;
      cursor: pointer !important;
      transition: background-color 0.2s ease, transform 0.1s ease !important;
    }
    .mobile-drawer-close-btn:hover {
      background-color: rgba(255, 255, 255, 0.15) !important;
    }

    /* Panels Sliding Container */
    .mobile-drawer-panels-wrap {
      position: relative !important;
      flex: 1 !important;
      width: 100% !important;
      overflow: hidden !important;
      background-color: #fdfbf7 !important;
    }
    .mobile-panel {
      position: absolute !important;
      inset: 0 !important;
      width: 100% !important;
      height: 100% !important;
      overflow-y: auto !important;
      background-color: #fdfbf7 !important;
      transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease !important;
      will-change: transform, opacity !important;
      scrollbar-width: none !important;
    }
    .mobile-panel::-webkit-scrollbar { display: none !important; }

    /* Panel sliding animations */
    .mobile-panel.mobile-panel-active {
      transform: translateX(0) !important;
      opacity: 1 !important;
      pointer-events: auto !important;
    }
    .mobile-panel.mobile-panel-shifted {
      transform: translateX(-35%) !important;
      opacity: 0.15 !important;
      pointer-events: none !important;
    }
    .mobile-panel.mobile-panel-hidden {
      transform: translateX(100%) !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }

    /* Main Menu Rows (GimiGimi bold uppercase style) */
    .mobile-menu-list {
      display: flex !important;
      flex-direction: column !important;
    }
    .mobile-menu-row {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 18px 20px !important;
      border-bottom: 1px solid #ede8e0 !important;
      background: transparent !important;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      text-decoration: none !important;
      color: #3d1412 !important;
      cursor: pointer !important;
      transition: background-color 0.18s ease !important;
      width: 100% !important;
      text-align: left !important;
    }
    .mobile-menu-row:hover, .mobile-menu-row:active {
      background-color: #f5eedf !important;
    }
    .mobile-menu-item-left {
      display: flex !important;
      align-items: center !important;
      gap: 14px !important;
    }
    .mobile-menu-icon {
      font-size: 1.35rem !important;
      line-height: 1 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 28px !important;
      height: 28px !important;
    }
    .mobile-menu-title {
      font-family: var(--font-sans, 'Poppins', sans-serif) !important;
      font-weight: 800 !important;
      font-size: 1.15rem !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #3d1412 !important;
    }
    .mobile-menu-item-right {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
    }
    .mobile-chevron-right {
      color: #c7613d !important;
      stroke: #c7613d !important;
      transition: transform 0.2s ease !important;
    }
    .mobile-menu-row:hover .mobile-chevron-right {
      transform: translateX(3px) !important;
    }

    /* Submenu Header Bar */
    .mobile-sub-header {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 14px 18px !important;
      background-color: #f7f1e6 !important;
      border-bottom: 2px solid #ecd8bb !important;
    }
    .mobile-sub-back-btn {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      background: transparent !important;
      border: none !important;
      cursor: pointer !important;
      font-family: var(--font-sans, 'Poppins', sans-serif) !important;
      font-weight: 800 !important;
      font-size: 1rem !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      color: #541f21 !important;
      padding: 4px 0 !important;
    }
    .mobile-sub-back-btn:hover {
      color: #c7613d !important;
    }
    .mobile-sub-view-link {
      font-size: 0.75rem !important;
      font-weight: 600 !important;
      color: #c7613d !important;
      text-decoration: underline !important;
    }

    /* Submenu Cards */
    .mobile-submenu-items {
      padding: 12px 14px !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 10px !important;
    }
    .mobile-sub-item-card {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 12px 14px !important;
      background: #ffffff !important;
      border: 1px solid #ebd9c5 !important;
      border-radius: 14px !important;
      text-decoration: none !important;
      transition: all 0.2s ease !important;
      box-shadow: 0 2px 8px rgba(84, 31, 33, 0.04) !important;
    }
    .mobile-sub-item-card:hover {
      background-color: #fdf7ed !important;
      border-color: #c7613d !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 14px rgba(84, 31, 33, 0.08) !important;
    }
    .mobile-sub-item-left {
      display: flex !important;
      align-items: center !important;
      gap: 12px !important;
    }
    .mobile-sub-thumb {
      font-size: 1.5rem !important;
      width: 40px !important;
      height: 40px !important;
      border-radius: 10px !important;
      background: #faf4e8 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      flex-shrink: 0 !important;
      border: 1px solid #eedcc8 !important;
    }
    .mobile-sub-name {
      font-family: var(--font-sans, 'Poppins', sans-serif) !important;
      font-weight: 700 !important;
      font-size: 0.875rem !important;
      color: #3d1412 !important;
      line-height: 1.25 !important;
    }
    .mobile-sub-desc {
      font-size: 0.72rem !important;
      color: #8c7365 !important;
      margin-top: 2px !important;
    }
    .mobile-sub-item-right {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      flex-shrink: 0 !important;
    }
    .mobile-sub-badge {
      display: inline-block !important;
      padding: 2px 7px !important;
      border-radius: 9999px !important;
      font-family: var(--font-mono, 'Cascadia Code', monospace) !important;
      font-size: 0.58rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
    }
    .mobile-sub-arrow {
      width: 28px !important;
      height: 28px !important;
      border-radius: 8px !important;
      border: 1px solid #ebd9c5 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: #541f21 !important;
      background-color: #ffffff !important;
      transition: all 0.2s ease !important;
    }
    .mobile-sub-item-card:hover .mobile-sub-arrow {
      background-color: #541f21 !important;
      color: #ffffff !important;
      border-color: #541f21 !important;
    }

    /* Drawer Footer (GimiGimi style) */
    .mobile-drawer-footer {
      padding: 14px 18px 16px 18px !important;
      background-color: #f7f1e6 !important;
      border-top: 1px solid #ebd9c5 !important;
      flex-shrink: 0 !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 10px !important;
    }
    .mobile-footer-auth-row {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      width: 100% !important;
    }
    .mobile-footer-auth-btn {
      flex: 1 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 12px 16px !important;
      border: 2px solid #3d1412 !important;
      border-radius: 10px !important;
      background-color: #ffffff !important;
      color: #3d1412 !important;
      font-family: var(--font-sans, 'Poppins', sans-serif) !important;
      font-weight: 800 !important;
      font-size: 0.85rem !important;
      letter-spacing: 0.04em !important;
      text-transform: uppercase !important;
      text-decoration: none !important;
      transition: all 0.2s ease !important;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06) !important;
    }
    .mobile-footer-auth-btn:hover {
      background-color: #3d1412 !important;
      color: #ffffff !important;
    }
    .mobile-footer-whatsapp-btn {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 48px !important;
      height: 48px !important;
      border-radius: 10px !important;
      background-color: #ffffff !important;
      border: 2px solid #25D366 !important;
      transition: transform 0.15s ease, background-color 0.15s ease !important;
      flex-shrink: 0 !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.06) !important;
    }
    .mobile-footer-whatsapp-btn:hover {
      background-color: #e9fbf0 !important;
      transform: scale(1.05) !important;
    }
    .mobile-footer-trust-badge {
      text-align: center !important;
      font-size: 0.68rem !important;
      color: #665249 !important;
      font-weight: 600 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 6px !important;
    }
    .trust-stars {
      color: #c7613d !important;
      letter-spacing: 2px !important;
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
            class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/15 active:scale-95"
            aria-label="Open navigation menu"
            title="Menu"
          >
            <svg role="presentation" stroke-width="2" focusable="false" width="28" height="28" class="icon-hamburger-custom text-white" viewBox="0 0 30 30" fill="none">
              <path d="M3.14852 7.45157C4.33548 7.40404 5.0225 7.77332 5.68582 8.13C6.31955 8.46993 6.92069 8.79305 7.98175 8.75093C9.01579 8.71227 9.51933 8.26731 10.0503 7.79874C10.6331 7.28692 11.2373 6.75539 12.4542 6.70849C13.6651 6.66146 14.2809 7.14212 14.876 7.61026C15.421 8.03702 15.9367 8.43896 16.9737 8.40036C18.0077 8.3617 18.5113 7.91674 19.0423 7.44817C19.6251 6.93635 20.2293 6.40482 21.4402 6.35779C22.6511 6.31077 23.2669 6.79143 23.862 7.25956C24.407 7.68632 24.9196 8.08821 25.9537 8.04955C25.9686 8.04986 26.2174 8.03898 26.3791 7.76018L26.8561 8.28629C26.5054 8.86752 26.008 8.88122 25.9541 8.88008C24.7522 8.9273 24.1365 8.4426 23.5413 7.9785C22.9963 7.55174 22.4837 7.14986 21.4496 7.18852C20.4156 7.22718 19.912 7.67214 19.381 8.14071C18.7982 8.65253 18.194 9.18406 16.9831 9.23109C15.7692 9.27805 15.1504 8.79732 14.5554 8.32919C14.0103 7.90243 13.4977 7.50055 12.4637 7.53921C11.4266 7.57781 10.9231 8.02277 10.3891 8.49127C9.80629 9.0031 9.20218 9.5306 7.99118 9.58165C6.80123 9.62912 6.11421 9.25984 5.44789 8.9031C4.81417 8.56316 4.21594 8.24413 3.15795 8.28229L3.1486 7.44754L3.14852 7.45157Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/>
              <path d="M3.14852 14.4748C4.33548 14.4273 5.0225 14.7966 5.68582 15.1533C6.31955 15.4932 6.92069 15.8163 7.98175 15.7742C9.01579 15.7355 9.51933 15.2906 10.0503 14.822C10.6331 14.3102 11.2373 13.7786 12.4542 13.7317C13.6651 13.6847 14.2809 14.1654 14.876 14.6335C15.421 15.0603 15.9367 15.4622 16.9737 15.4236C18.0077 15.385 18.5113 14.94 19.0423 14.4714C19.6251 13.9596 20.2293 13.4281 21.4402 13.381C22.6511 13.334 23.2669 13.8147 23.862 14.2828C24.407 14.7096 24.9196 15.1115 25.9537 15.0728C25.9686 15.0731 26.2174 15.0622 26.3791 14.7834L26.8561 15.3095C26.5054 15.8908 26.008 15.9045 25.9541 15.9033C24.7522 15.9506 24.1365 15.4659 23.5413 15.0018C22.9963 14.575 22.4837 14.1731 21.4496 14.2118C20.4156 14.2504 19.912 14.6954 19.381 15.164C18.7982 15.6758 18.194 16.2073 16.9831 16.2543C15.7692 16.3013 15.1504 15.8206 14.5554 15.3524C14.0103 14.9257 13.4977 14.5238 12.4637 14.5625C11.4266 14.6011 10.9231 15.046 10.3891 15.5145C9.80629 16.0263 9.20218 16.5539 7.99118 16.6049C6.80123 16.6524 6.11421 16.2831 5.44789 15.9263C4.81417 15.5864 4.21594 15.2674 3.15795 15.3055L3.1486 14.4708L3.14852 14.4748Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/>
              <path d="M3.14852 21.4981C4.33548 21.4505 5.0225 21.8198 5.68582 22.1765C6.31955 22.5164 6.92069 22.8396 7.98175 22.7974C9.01579 22.7588 9.51933 22.3138 10.0503 21.8452C10.6331 21.3334 11.2373 20.8019 12.4542 20.755C13.6651 20.708 14.2809 21.1886 14.876 21.6568C15.421 22.0835 15.9367 22.4855 16.9737 22.4469C18.0077 22.4082 18.5113 21.9633 19.0423 21.4947C19.6251 20.9829 20.2293 20.4513 21.4402 20.4043C22.6511 20.3573 23.2669 20.8379 23.862 21.3061C24.407 21.7328 24.9196 22.1347 25.9537 22.0961C25.9686 22.0964 26.2174 22.0855 26.3791 21.8067L26.8561 22.3328C26.5054 22.914 26.008 22.9277 25.9541 22.9266C24.7522 22.9738 24.1365 22.4891 23.5413 22.025C22.9963 21.5983 22.4837 21.1964 21.4496 21.235C20.4156 21.2737 19.912 21.7186 19.381 22.1872C18.7982 22.699 18.194 23.2306 16.9831 23.2776C15.7692 23.3246 15.1504 22.8438 14.5554 22.3757C14.0103 21.9489 13.4977 21.5471 12.4637 21.5857C11.4266 21.6243 10.9231 22.0693 10.3891 22.5378C9.80629 23.0496 9.20218 23.5771 7.99118 23.6282C6.80123 23.6756 6.11421 23.3064 5.44789 22.9496C4.81417 22.6097 4.21594 22.2906 3.15795 22.3288L3.1486 21.494L3.14852 21.4981Z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/>
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
    <div id="mobile-drawer-overlay" onclick="closeMobileDrawer()"></div>

    <aside id="mobile-drawer">
      
      <!-- Drawer Header (Solid Burgundy #541f21 matching brand) -->
      <div class="mobile-drawer-header">
        <a href="index.php" onclick="closeMobileDrawer()" aria-label="Dabhi Chikki Home" class="flex items-center">
          <img src="assets/images/logo.png" alt="Dabhi Chikki" style="height: 38px; width: auto; object-fit: contain;"/>
        </a>
        <button id="mobile-drawer-close-btn" type="button" class="mobile-drawer-close-btn" onclick="closeMobileDrawer()" aria-label="Close menu">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
      </div>

      <!-- Drawer Sliding Panels Wrapper -->
      <div class="mobile-drawer-panels-wrap">
        
        <!-- ── MAIN PANEL (Level 0) ── -->
        <div id="mobile-panel-main" class="mobile-panel mobile-panel-active">
          <div class="mobile-menu-list">
            <?php 
            // Fallback icon helper if icon column in DB is empty
            $getMenuFallbackIcon = function($item) {
                if (!empty($item['icon'])) return $item['icon'];
                $t = strtolower($item['title'] ?? '');
                if (str_contains($t, 'home')) return '🏠';
                if (str_contains($t, 'chikki') || str_contains($t, 'shop') || str_contains($t, 'product')) return '🥜';
                if (str_contains($t, 'craft') || str_contains($t, 'story') || str_contains($t, 'about')) return '📖';
                if (str_contains($t, 'reel') || str_contains($t, 'video')) return '🎬';
                if (str_contains($t, 'review') || str_contains($t, 'testimonial')) return '⭐';
                if (str_contains($t, 'track') || str_contains($t, 'order')) return '📦';
                return '✦';
            };
            ?>

            <?php if (!empty($navTree)): ?>
              <?php foreach ($navTree as $item): ?>
                <?php $hasChildren = !empty($item['children']); ?>
                <?php if ($hasChildren): ?>
                  <!-- Parent Row with Chevron Button -->
                  <button type="button" class="mobile-menu-row mobile-menu-parent-btn" data-target-panel="submenu-panel-<?= $item['id'] ?>" aria-haspopup="true" aria-expanded="false">
                    <div class="mobile-menu-item-left">
                      <div class="mobile-menu-icon">
                        <?= $getMenuFallbackIcon($item) ?>
                      </div>
                      <span class="mobile-menu-title"><?= htmlspecialchars($item['title']) ?></span>
                    </div>
                    <div class="mobile-menu-item-right">
                      <?php if (!empty($item['badge'])): ?>
                        <span class="mobile-sub-badge" style="background-color:<?= htmlspecialchars($item['badge_color'] ?: '#c7613d') ?>;color:<?= ($item['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                          <?= htmlspecialchars($item['badge']) ?>
                        </span>
                      <?php endif; ?>
                      <svg class="mobile-chevron-right" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </div>
                  </button>
                <?php else: ?>
                  <!-- Direct Nav Link -->
                  <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target'] ?? '_self') ?>" class="mobile-menu-row" onclick="closeMobileDrawer()">
                    <div class="mobile-menu-item-left">
                      <div class="mobile-menu-icon">
                        <?= $getMenuFallbackIcon($item) ?>
                      </div>
                      <span class="mobile-menu-title"><?= htmlspecialchars($item['title']) ?></span>
                    </div>
                    <div class="mobile-menu-item-right">
                      <?php if (!empty($item['badge'])): ?>
                        <span class="mobile-sub-badge" style="background-color:<?= htmlspecialchars($item['badge_color'] ?: '#c7613d') ?>;color:<?= ($item['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                          <?= htmlspecialchars($item['badge']) ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Fallback Default Items -->
              <a href="index.php" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">🏠</div>
                  <span class="mobile-menu-title">Home</span>
                </div>
              </a>
              <a href="index.php#products" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">🥜</div>
                  <span class="mobile-menu-title">Our Chikki</span>
                </div>
              </a>
              <a href="index.php#our-story" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">📖</div>
                  <span class="mobile-menu-title">Our Craft</span>
                </div>
              </a>
              <a href="index.php#reels-section" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">🎬</div>
                  <span class="mobile-menu-title">Reels</span>
                </div>
                <div class="mobile-menu-item-right">
                  <span class="mobile-sub-badge" style="background-color:#c7613d;color:#fff;">Live</span>
                </div>
              </a>
              <a href="index.php#reviews" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">⭐</div>
                  <span class="mobile-menu-title">Reviews</span>
                </div>
              </a>
              <a href="track.php" class="mobile-menu-row" onclick="closeMobileDrawer()">
                <div class="mobile-menu-item-left">
                  <div class="mobile-menu-icon">📦</div>
                  <span class="mobile-menu-title">Track Order</span>
                </div>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- ── SUBMENU PANELS (Level 1 Sliding Panels) ── -->
        <?php if (!empty($navTree)): ?>
          <?php foreach ($navTree as $item): ?>
            <?php if (!empty($item['children'])): ?>
              <div id="submenu-panel-<?= $item['id'] ?>" class="mobile-panel mobile-panel-sub mobile-panel-hidden">
                <!-- Submenu Header Bar with Back Button & View All Link -->
                <div class="mobile-sub-header">
                  <button type="button" class="mobile-sub-back-btn" data-back-to="mobile-panel-main" aria-label="Back to main menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span><?= htmlspecialchars($item['title']) ?></span>
                  </button>
                  <?php if (!empty($item['url'])): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="mobile-sub-view-link" onclick="closeMobileDrawer()">View All →</a>
                  <?php endif; ?>
                </div>

                <!-- Submenu Items List -->
                <div class="mobile-submenu-items">
                  <?php foreach ($item['children'] as $child): ?>
                    <a href="<?= htmlspecialchars($child['url']) ?>" target="<?= htmlspecialchars($child['target'] ?? '_self') ?>" class="mobile-sub-item-card" onclick="closeMobileDrawer()">
                      <div class="mobile-sub-item-left">
                        <div class="mobile-sub-thumb">
                          <?= !empty($child['icon']) ? htmlspecialchars($child['icon']) : '🥜' ?>
                        </div>
                        <div>
                          <div class="mobile-sub-name"><?= htmlspecialchars($child['title']) ?></div>
                          <?php if (!empty($child['subtitle'])): ?>
                            <div class="mobile-sub-desc"><?= htmlspecialchars($child['subtitle']) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="mobile-sub-item-right">
                        <?php if (!empty($child['badge'])): ?>
                          <span class="mobile-sub-badge" style="background-color:<?= htmlspecialchars($child['badge_color'] ?: '#c7613d') ?>;color:<?= ($child['badge_color'] ?? '') === '#f6dc94' ? '#541f21' : '#ffffff' ?>;">
                            <?= htmlspecialchars($child['badge']) ?>
                          </span>
                        <?php endif; ?>
                        <div class="mobile-sub-arrow">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <!-- Drawer Footer: Auth + WhatsApp + Trust Badge (Matching GimiGimi) -->
      <div class="mobile-drawer-footer">
        <div class="mobile-footer-auth-row">
          <?php if ($isLoggedIn): ?>
            <a href="account.php" class="mobile-footer-auth-btn" onclick="closeMobileDrawer()">
              <span>👤</span>
              <span>My Account</span>
            </a>
          <?php else: ?>
            <a href="auth.php" class="mobile-footer-auth-btn" onclick="closeMobileDrawer()">
              <span>🔐</span>
              <span>Login / Sign Up</span>
            </a>
          <?php endif; ?>
          <a href="https://wa.me/919876543210" target="_blank" rel="noreferrer" class="mobile-footer-whatsapp-btn" title="Chat on WhatsApp" aria-label="Chat on WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#25D366"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
          </a>
        </div>
        <div class="mobile-footer-trust-badge">
          <span class="trust-stars">★★★★★</span>
          <span>100% Pure Jaggery • Handcrafted Since 2009</span>
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
        // Reset sliding panels after drawer transition finishes
        setTimeout(function() {
          const mainPanel = document.getElementById('mobile-panel-main');
          if (mainPanel) {
            mainPanel.classList.remove('mobile-panel-shifted');
            mainPanel.classList.add('mobile-panel-active');
          }
          document.querySelectorAll('.mobile-panel-sub').forEach(function(panel) {
            panel.classList.remove('mobile-panel-active');
            panel.classList.add('mobile-panel-hidden');
          });
        }, 360);
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

      // Mobile Drawer open / close event bindings
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

      // Mobile sliding submenu panel transitions
      document.querySelectorAll('.mobile-menu-parent-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('data-target-panel');
          const targetPanel = document.getElementById(targetId);
          const mainPanel = document.getElementById('mobile-panel-main');
          if (targetPanel && mainPanel) {
            mainPanel.classList.remove('mobile-panel-active');
            mainPanel.classList.add('mobile-panel-shifted');
            targetPanel.classList.remove('mobile-panel-hidden');
            targetPanel.classList.add('mobile-panel-active');
            targetPanel.scrollTop = 0;
          }
        });
      });

      document.querySelectorAll('.mobile-sub-back-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const currentPanel = this.closest('.mobile-panel');
          const mainPanel = document.getElementById('mobile-panel-main');
          if (currentPanel && mainPanel) {
            currentPanel.classList.remove('mobile-panel-active');
            currentPanel.classList.add('mobile-panel-hidden');
            mainPanel.classList.remove('mobile-panel-shifted');
            mainPanel.classList.add('mobile-panel-active');
          }
        });
      });
    })();
    </script>
