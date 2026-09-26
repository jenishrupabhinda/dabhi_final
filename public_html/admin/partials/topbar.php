<?php
/**
 * Admin topbar partial.
 * Set $pageHeading and optionally $pageBreadcrumbs before including.
 * $pageBreadcrumbs = [['label'=>'Orders', 'url'=>'/admin/orders.php'], ['label'=>'Detail']]
 */
if (!isset($pageHeading)) $pageHeading = 'Dashboard';
if (!isset($pageBreadcrumbs)) $pageBreadcrumbs = [];
$_topUser = Auth::user();
?>
<header class="admin-topbar" role="banner">
  <div class="admin-topbar-left">
    <!-- Sidebar Toggle Button (Handles both desktop collapse and mobile drawer) -->
    <button type="button"
            id="adminSidebarToggle"
            class="admin-topbar-toggle-btn"
            aria-label="Toggle navigation menu"
            aria-controls="adminSidebar"
            aria-expanded="false">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <div class="admin-topbar-headings">
      <?php if (!empty($pageBreadcrumbs)): ?>
        <nav class="admin-breadcrumb" aria-label="Breadcrumbs">
          <a href="<?= url('admin/index.php') ?>">Dashboard</a>
          <?php foreach ($pageBreadcrumbs as $crumb): ?>
            <span>/</span>
            <?php if (!empty($crumb['url'])): ?>
              <a href="<?= e(str_starts_with($crumb['url'], 'http') ? $crumb['url'] : url(ltrim($crumb['url'], '/'))) ?>"><?= e($crumb['label']) ?></a>
            <?php else: ?>
              <span aria-current="page"><?= e($crumb['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
      <h1 class="admin-topbar-title"><?= e($pageHeading) ?></h1>
    </div>
  </div>

  <div class="admin-topbar-right">
    <a href="<?= url() ?>" target="_blank" rel="noopener noreferrer" class="admin-storefront-link" title="Open live storefront in new tab">
      <span>🌐</span>
      <span class="link-text">View Storefront ↗</span>
    </a>

    <div style="height:24px;width:1px;background:var(--adm-border);margin:0 2px;"></div>

    <a href="<?= url('logout.php') ?>" class="admin-signout-link" title="Sign out of admin panel">
      <span>Sign out</span>
    </a>
  </div>
</header>
