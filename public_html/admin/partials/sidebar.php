<?php
/**
 * Admin sidebar partial.
 * Expects $adminUser to be set (Auth::user() result) before including.
 * Expects $activePage to be set — e.g. 'dashboard', 'products', 'orders', 'payment-settings'
 */
if (!isset($activePage)) $activePage = '';
$_user = Auth::user();
$_role = Auth::role();

// Helper: robust active class with auto-detection fallback
function sidebarActive(string $page): string {
    global $activePage;
    if (!empty($activePage)) {
        if ($activePage === $page) return 'active';
    }
    // Auto-detect based on current PHP filename
    $script = basename($_SERVER['PHP_SELF'] ?? '', '.php');
    if ($script === $page) return 'active';
    if ($page === 'dashboard' && ($script === 'index' || $script === '')) return 'active';
    if ($page === 'products' && $script === 'product-edit') return 'active';
    if ($page === 'users' && ($script === 'user-edit' || $script === 'permissions')) return 'active';
    if ($page === 'orders' && $script === 'order-detail') return 'active';
    return '';
}

// Helper: can current user see this link?
function sidebarCan(string $perm): bool {
    return Auth::role() === 'superadmin' || RBAC::can(Auth::id(), $perm);
}
?>
<aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="Admin Navigation">

  <!-- Brand Section -->
  <div class="sidebar-brand">
    <a href="<?= url('admin/index.php') ?>" title="Dabhi Chikki Admin Dashboard">
      <img src="<?= asset('images/logo.png') ?>" alt="Dabhi Chikki">
      <div style="min-width:0;">
        <div class="sidebar-brand-name">Dabhi Chikki</div>
        <div class="sidebar-brand-sub">Admin Panel</div>
      </div>
    </a>
    <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation sidebar">✕</button>
  </div>

  <!-- Navigation Items -->
  <nav class="sidebar-nav">

    <!-- Dashboard -->
    <div class="sidebar-section">
      <a href="<?= url('admin/index.php') ?>" class="sidebar-link <?= sidebarActive('dashboard') ?>" data-tooltip="Dashboard">
        <span class="icon">🏠</span>
        <span class="link-label">Dashboard</span>
      </a>
    </div>

    <!-- Catalog -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Catalog</span>
      <?php if (sidebarCan('manage_products') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/products.php') ?>" class="sidebar-link <?= sidebarActive('products') ?>" data-tooltip="Products">
        <span class="icon">🛍️</span>
        <span class="link-label">Products</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_categories') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/categories.php') ?>" class="sidebar-link <?= sidebarActive('categories') ?>" data-tooltip="Categories">
        <span class="icon">🗂️</span>
        <span class="link-label">Categories</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_reviews')): ?>
      <a href="<?= url('admin/reviews.php') ?>" class="sidebar-link <?= sidebarActive('reviews') ?>" data-tooltip="Reviews">
        <span class="icon">⭐</span>
        <span class="link-label">Reviews</span>
      </a>
      <?php endif; ?>

      <?php if ($_role === 'superadmin' || $_role === 'admin' || sidebarCan('manage_settings')): ?>
      <a href="<?= url('admin/coupons.php') ?>" class="sidebar-link <?= sidebarActive('coupons') ?>" data-tooltip="Coupons & Discounts">
        <span class="icon">🏷️</span>
        <span class="link-label">Coupons</span>
      </a>
      <?php endif; ?>
    </div>

    <!-- Storefront CMS & Marketing -->
    <?php if ($_role === 'superadmin' || $_role === 'admin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Storefront CMS</span>
      <a href="<?= url('admin/modules.php') ?>" class="sidebar-link <?= sidebarActive('modules') ?>" data-tooltip="Modules & Content">
        <span class="icon">🎛️</span>
        <span class="link-label">Modules &amp; Content</span>
      </a>
      <a href="<?= url('admin/menus.php') ?>" class="sidebar-link <?= sidebarActive('menus') ?>" data-tooltip="Navigation Menu">
        <span class="icon">🧭</span>
        <span class="link-label">Navigation Menu</span>
      </a>
      <a href="<?= url('admin/instagram-reels.php') ?>" class="sidebar-link <?= sidebarActive('instagram-reels') ?>" data-tooltip="Instagram Reels">
        <span class="icon">📸</span>
        <span class="link-label">Instagram Reels</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- Orders -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Orders</span>
      <?php if (sidebarCan('manage_orders') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/orders.php') ?>" class="sidebar-link <?= sidebarActive('orders') ?>" data-tooltip="Orders">
        <span class="icon">📦</span>
        <span class="link-label">Orders</span>
      </a>
      <?php endif; ?>
    </div>

    <!-- Inventory -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Inventory</span>
      <?php if (sidebarCan('manage_inventory') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/inventory.php') ?>" class="sidebar-link <?= sidebarActive('inventory') ?>" data-tooltip="Stock Overview">
        <span class="icon">📊</span>
        <span class="link-label">Stock Overview</span>
      </a>
      <a href="<?= url('admin/inventory-batch.php') ?>" class="sidebar-link <?= sidebarActive('inventory-batch') ?>" data-tooltip="Batch Manager">
        <span class="icon">🗃️</span>
        <span class="link-label">Batch Manager</span>
      </a>
      <?php endif; ?>
    </div>

    <!-- Analytics & Reports -->
    <?php if (sidebarCan('view_product_analytics') || sidebarCan('generate_reports') || $_role === 'superadmin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Analytics</span>
      <?php if (sidebarCan('view_product_analytics') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/analytics.php') ?>" class="sidebar-link <?= sidebarActive('analytics') ?>" data-tooltip="Analytics">
        <span class="icon">📈</span>
        <span class="link-label">Analytics</span>
      </a>
      <?php endif; ?>
      <?php if (sidebarCan('generate_reports') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/reports.php') ?>" class="sidebar-link <?= sidebarActive('reports') ?>" data-tooltip="Reports">
        <span class="icon">📋</span>
        <span class="link-label">Reports</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Settings -->
    <?php if ($_role === 'superadmin' || $_role === 'admin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Settings</span>

      <?php if (sidebarCan('manage_settings') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/settings.php') ?>" class="sidebar-link <?= sidebarActive('settings') ?>" data-tooltip="General Settings">
        <span class="icon">⚙️</span>
        <span class="link-label">General Settings</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_shipping_rules') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/shipping-settings.php') ?>" class="sidebar-link <?= sidebarActive('shipping-settings') ?>" data-tooltip="Shipping & Zones">
        <span class="icon">🚚</span>
        <span class="link-label">Shipping &amp; Zones</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_payment_settings') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/payment-settings.php') ?>" class="sidebar-link <?= sidebarActive('payment-settings') ?>" data-tooltip="Payment Settings">
        <span class="icon">💳</span>
        <span class="link-label">Payment Settings</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_gst') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/gst-settings.php') ?>" class="sidebar-link <?= sidebarActive('gst-settings') ?>" data-tooltip="GST Settings">
        <span class="icon">🧾</span>
        <span class="link-label">GST</span>
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_notifications') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/notification-settings.php') ?>" class="sidebar-link <?= sidebarActive('notification-settings') ?>" data-tooltip="Notifications">
        <span class="icon">🔔</span>
        <span class="link-label">Notifications</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Users -->
    <?php if ($_role === 'superadmin' || sidebarCan('manage_employees') || sidebarCan('manage_admins')): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Users</span>
      <a href="<?= url('admin/users.php') ?>" class="sidebar-link <?= sidebarActive('users') ?>" data-tooltip="User Management">
        <span class="icon">👥</span>
        <span class="link-label">User Management</span>
      </a>
      <?php if ($_role === 'superadmin' || sidebarCan('view_audit_log')): ?>
      <a href="<?= url('admin/audit-log.php') ?>" class="sidebar-link <?= sidebarActive('audit-log') ?>" data-tooltip="Audit Log">
        <span class="icon">🔍</span>
        <span class="link-label">Audit Log</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </nav><!-- /sidebar-nav -->

  <!-- User info at bottom -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($_user['full_name'] ?? 'U', 0, 1)) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($_user['full_name'] ?? '') ?></div>
        <div class="sidebar-user-role"><?= e(ucfirst($_role ?? '')) ?></div>
      </div>
      <a href="<?= url('logout.php') ?>" class="sidebar-logout-btn" title="Sign out" aria-label="Sign out">
        ⎋
      </a>
    </div>
  </div>

</aside><!-- /admin-sidebar -->

<!-- Off-Canvas Backdrop for Mobile Devices -->
<div class="admin-sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
