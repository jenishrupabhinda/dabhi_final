<?php
/**
 * Admin topbar partial.
 * Set $pageHeading and optionally $pageBreadcrumbs before including.
 * $pageBreadcrumbs = [['label'=>'Orders', 'url'=>'/admin/orders.php'], ['label'=>'Detail']]
 */
if (!isset($pageHeading)) $pageHeading = 'Dashboard';
if (!isset($pageBreadcrumbs)) $pageBreadcrumbs = [];
?>
<div class="admin-topbar">
  <div style="display:flex;align-items:center;gap:12px;">
    <!-- Mobile sidebar toggle -->
    <button id="sidebarToggle" class="btn btn-ghost btn-sm" style="display:none;" aria-label="Toggle sidebar" aria-expanded="false">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div>
      <?php if (!empty($pageBreadcrumbs)): ?>
        <nav aria-label="Breadcrumb" style="font-size:0.75rem;color:var(--dc-muted);margin-bottom:2px;">
          <a href="<?= url('admin/index.php') ?>" style="color:var(--dc-muted);">Dashboard</a>
          <?php foreach ($pageBreadcrumbs as $crumb): ?>
            &nbsp;/&nbsp;
            <?php if (isset($crumb['url'])): ?>
              <a href="<?= e(str_starts_with($crumb['url'], 'http') ? $crumb['url'] : url(ltrim($crumb['url'], '/'))) ?>" style="color:var(--dc-muted);"><?= e($crumb['label']) ?></a>
            <?php else: ?>
              <span><?= e($crumb['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
      <h1 class="admin-topbar-title" style="margin:0;"><?= e($pageHeading) ?></h1>
    </div>
  </div>

  <div class="admin-topbar-actions">
    <a href="<?= url() ?>" target="_blank" class="btn btn-sm" style="background: rgba(199, 97, 61, 0.08); color: var(--dc-terracotta); border: 1px solid rgba(199, 97, 61, 0.22); border-radius: 9999px; font-weight: 600; padding: 6px 14px; text-decoration: none;" title="View live storefront">
      🌐 View Storefront ↗
    </a>
    <a href="<?= url('logout.php') ?>" class="btn btn-ghost btn-sm" style="border-radius: 9999px;">
      Sign out
    </a>
  </div>
</div>

<style>
@media(max-width:900px){
  #sidebarToggle { display:flex !important; }
}
</style>

<script>
(function(){
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  if(toggle && sidebar){
    toggle.addEventListener('click', function(){
      const open = sidebar.classList.toggle('open');
      this.setAttribute('aria-expanded', open);
    });
  }
})();
</script>
