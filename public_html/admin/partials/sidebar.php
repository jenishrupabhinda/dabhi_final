<?php
/**
 * Admin sidebar partial.
 * Expects $adminUser to be set (Auth::user() result) before including.
 * Expects $activePage to be set — e.g. 'dashboard', 'products', 'orders'
 */
if (!isset($activePage)) $activePage = '';
$_user = Auth::user();
$_role = Auth::role();

// Helper: active class
function sidebarActive(string $page): string {
    global $activePage;
    return $activePage === $page ? 'active' : '';
}
// Helper: can current user see this link?
function sidebarCan(string $perm): bool {
    return Auth::role() === 'superadmin' || RBAC::can(Auth::id(), $perm);
}
?>
<aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="Admin navigation">

  <!-- Brand -->
  <div class="sidebar-brand">
    <a href="<?= url('admin/index.php') ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
      <img src="<?= asset('images/logo.png') ?>" alt="Dabhi Chikki">
      <div>
        <div class="sidebar-brand-name">Dabhi Chikki</div>
        <div class="sidebar-brand-sub">Admin Panel</div>
      </div>
    </a>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">

    <!-- Overview -->
    <div class="sidebar-section">
      <a href="<?= url('admin/index.php') ?>" class="sidebar-link <?= sidebarActive('dashboard') ?>">
        <span class="icon">🏠</span> Dashboard
      </a>
    </div>

    <!-- Catalog -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Catalog</span>
      <?php if (sidebarCan('manage_products') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/products.php') ?>" class="sidebar-link <?= sidebarActive('products') ?>">
        <span class="icon">🛍️</span> Products
      </a>
      <?php endif; ?>
      <?php if (sidebarCan('manage_categories') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/categories.php') ?>" class="sidebar-link <?= sidebarActive('categories') ?>">
        <span class="icon">🗂️</span> Categories
      </a>
      <?php endif; ?>
      <?php if (sidebarCan('manage_reviews')): ?>
      <a href="<?= url('admin/reviews.php') ?>" class="sidebar-link <?= sidebarActive('reviews') ?>">
        <span class="icon">⭐</span> Reviews
      </a>
      <?php endif; ?>
    </div>

    <!-- Storefront CMS & Marketing -->
    <?php if ($_role === 'superadmin' || $_role === 'admin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Storefront CMS</span>
      <a href="<?= url('admin/modules.php') ?>" class="sidebar-link <?= sidebarActive('modules') ?>">
        <span class="icon">🎛️</span> Modules &amp; Content
      </a>
      <a href="<?= url('admin/instagram-reels.php') ?>" class="sidebar-link <?= sidebarActive('instagram-reels') ?>">
        <span class="icon">📸</span> Instagram Reels
      </a>
    </div>
    <?php endif; ?>

    <!-- Orders -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Orders</span>
      <?php if (sidebarCan('manage_orders') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/orders.php') ?>" class="sidebar-link <?= sidebarActive('orders') ?>">
        <span class="icon">📦</span> Orders
      </a>
      <?php endif; ?>
    </div>

    <!-- Inventory -->
    <div class="sidebar-section">
      <span class="sidebar-section-label">Inventory</span>
      <?php if (sidebarCan('manage_inventory') || $_role === 'superadmin' || $_role === 'admin'): ?>
      <a href="<?= url('admin/inventory.php') ?>" class="sidebar-link <?= sidebarActive('inventory') ?>">
        <span class="icon">📊</span> Stock Overview
      </a>
      <a href="<?= url('admin/inventory-batch.php') ?>" class="sidebar-link <?= sidebarActive('inventory-batch') ?>">
        <span class="icon">🗃️</span> Batch Manager
      </a>
      <?php endif; ?>
    </div>

    <!-- Analytics & Reports -->
    <?php if (sidebarCan('view_product_analytics') || sidebarCan('generate_reports') || $_role === 'superadmin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Analytics</span>
      <?php if (sidebarCan('view_product_analytics') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/analytics.php') ?>" class="sidebar-link <?= sidebarActive('analytics') ?>">
        <span class="icon">📈</span> Analytics
      </a>
      <?php endif; ?>
      <?php if (sidebarCan('generate_reports') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/reports.php') ?>" class="sidebar-link <?= sidebarActive('reports') ?>">
        <span class="icon">📋</span> Reports
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Settings -->
    <?php if ($_role === 'superadmin' || $_role === 'admin'): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Settings</span>

      <?php if (sidebarCan('manage_shipping_rules') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/shipping-settings.php') ?>" class="sidebar-link <?= sidebarActive('shipping-settings') ?>">
        <span class="icon">🚚</span> Shipping &amp; Zones
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_payment_settings') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/payment-settings.php') ?>" class="sidebar-link <?= sidebarActive('payment-settings') ?>">
        <span class="icon">💳</span> Payment
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_gst') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/gst-settings.php') ?>" class="sidebar-link <?= sidebarActive('gst-settings') ?>">
        <span class="icon">🧾</span> GST
      </a>
      <?php endif; ?>

      <?php if (sidebarCan('manage_notifications') || $_role === 'superadmin'): ?>
      <a href="<?= url('admin/notification-settings.php') ?>" class="sidebar-link <?= sidebarActive('notification-settings') ?>">
        <span class="icon">🔔</span> Notifications
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Users -->
    <?php if ($_role === 'superadmin' || sidebarCan('manage_employees') || sidebarCan('manage_admins')): ?>
    <div class="sidebar-section">
      <span class="sidebar-section-label">Users</span>
      <a href="<?= url('admin/users.php') ?>" class="sidebar-link <?= sidebarActive('users') ?>">
        <span class="icon">👥</span> User Management
      </a>
      <?php if ($_role === 'superadmin' || sidebarCan('view_audit_log')): ?>
      <a href="<?= url('admin/audit-log.php') ?>" class="sidebar-link <?= sidebarActive('audit-log') ?>">
        <span class="icon">🔍</span> Audit Log
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
      <a href="<?= url('logout.php') ?>" title="Logout" style="color:rgba(255,255,255,0.4);padding:4px;border-radius:4px;text-decoration:none;font-size:0.85rem;" aria-label="Logout">
        ⎋
      </a>
    </div>
  </div>

</aside><!-- /admin-sidebar -->
