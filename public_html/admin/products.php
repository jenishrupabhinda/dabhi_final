<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['superadmin','admin','employee']);

$activePage  = 'products';
$pageHeading = 'Products';

// Filters
$page      = max(1, (int)get('page', '1'));
$search    = get('search', '');
$catFilter = (int)get('category', '0');
$status    = get('status', '');

// Quick toggle / delete actions
$action = get('action');
$id     = (int)get('id');

if ($action === 'toggle' && $id) {
    csrfVerifyToken(get('_token'));
    Product::toggle($id);
    flashSet('success', 'Product visibility updated.');
    redirect('/admin/products.php?' . http_build_query(['search'=>$search,'category'=>$catFilter,'status'=>$status,'page'=>$page]));
}

$result     = Product::adminList($page, 20, $search, $catFilter, $status);
$products   = $result['rows'];
$total      = $result['total'];
$lastPage   = $result['last_page'];
$categories = Category::getAll();

$pageTitle   = 'Products';
$pageHeading = 'Products';
$activePage  = 'products';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">

  <?php flashRender(); ?>

  <!-- ── Filters bar ── -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" action="<?= url('admin/products.php') ?>" class="adm-filter-bar">
        <div class="form-group" style="flex:2;min-width:180px;margin:0;">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control"
            placeholder="Product name or slug…" value="<?= e($search) ?>">
        </div>
        <div class="form-group" style="flex:1;min-width:150px;margin:0;">
          <label class="form-label">Category</label>
          <select name="category" class="form-control form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:1;min-width:130px;margin:0;">
          <label class="form-label">Status</label>
          <select name="status" class="form-control form-select">
            <option value="">All</option>
            <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Hidden</option>
          </select>
        </div>
        <div class="adm-filter-actions">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= url('admin/products.php') ?>" class="btn btn-ghost">Clear</a>
          <a href="<?= url('admin/product-edit.php') ?>" class="btn btn-secondary">+ Add Product</a>
        </div>
      </form>
    </div>
  </div>

      <!-- ── Product table ── -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <?= number_format($total) ?> product<?= $total !== 1 ? 's' : '' ?>
            <?= $search ? ' matching "'.e($search).'"' : '' ?>
          </h3>
        </div>
        <!-- Desktop Table (>= 768px) -->
        <div class="table-wrap product-desktop-table">
          <table class="dc-table">
            <thead>
              <tr>
                <th style="width:48px;"></th>
                <th>Name</th>
                <th>Category</th>
                <th>Variants</th>
                <th>Stock</th>
                <th>Price (from)</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p):
                $variants = Product::getVariants((int)$p['id']);
                $stock = (int)$p['total_stock'];
              ?>
              <tr>
                <td>
                  <?php if ($p['primary_image']): ?>
                    <img src="<?= e(imageUrl($p['primary_image'])) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;" alt="<?= e($p['name']) ?>">
                  <?php else: ?>
                    <span style="width:40px;height:40px;background:var(--dc-border);border-radius:6px;display:inline-flex;align-items:center;justify-content:center;">🛍</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div>
                    <a href="<?= url('admin/product-edit.php?id=' . (int)$p['id']) ?>" style="font-weight:600;"><?= e($p['name']) ?></a>
                    <?php if ($p['is_featured']): ?>
                      <span class="badge badge-yellow" style="font-size:0.65rem;margin-left:4px;">★ Featured</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:0.75rem;color:var(--dc-muted);"><?= e($p['slug']) ?></div>
                </td>
                <td><?= e($p['category_name']) ?></td>
                <td><?= count($variants) ?></td>
                <td>
                  <span <?= $stock <= 10 ? 'style="color:var(--dc-warning);font-weight:600;"' : '' ?>>
                    <?= number_format($stock) ?>g
                  </span>
                </td>
                <td>
                  <?php if (!empty($variants)):
                    $minPrice = min(array_column($variants, 'selling_price'));
                  ?>
                    <?= formatINR((float)$minPrice) ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-<?= $p['is_active'] ? 'success' : 'neutral' ?>">
                    <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="<?= url('admin/product-edit.php?id=' . (int)$p['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <a href="<?= url('admin/products.php?action=toggle&id=' . (int)$p['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                       class="btn btn-ghost btn-sm"
                       onclick="return confirm('Toggle \'<?= e(addslashes($p['name'])) ?>\'?')">
                      <?= $p['is_active'] ? 'Hide' : 'Show' ?>
                    </a>
                    <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-ghost btn-sm" title="View on store">🌐</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($products)): ?>
                <tr><td colspan="8" class="text-muted text-center" style="padding:32px;">No products found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile Products Cards View (< 768px) with Direct Action Buttons -->
        <div class="product-mobile-cards">
          <?php if (empty($products)): ?>
            <div class="text-muted text-center" style="padding:28px;">No products found.</div>
          <?php else: ?>
            <?php foreach ($products as $p):
              $variants = Product::getVariants((int)$p['id']);
              $stock = (int)$p['total_stock'];
              $minPrice = !empty($variants) ? min(array_column($variants, 'selling_price')) : null;
            ?>
            <div class="category-mobile-card">
              <div class="category-card-top">
                <div class="category-card-thumb">
                  <?php if ($p['primary_image']): ?>
                    <img src="<?= e(imageUrl($p['primary_image'])) ?>" alt="<?= e($p['name']) ?>">
                  <?php else: ?>
                    <span class="cat-icon-fallback">🛍</span>
                  <?php endif; ?>
                </div>
                <div class="category-card-main">
                  <div class="category-card-name">
                    <a href="<?= url('admin/product-edit.php?id=' . (int)$p['id']) ?>" style="color:inherit;text-decoration:none;">
                      <?= e($p['name']) ?>
                    </a>
                    <?php if ($p['is_featured']): ?>
                      <span class="badge badge-yellow" style="font-size:0.65rem;margin-left:4px;">★ Featured</span>
                    <?php endif; ?>
                  </div>
                  <div class="category-card-parent"><?= e($p['category_name']) ?> &bull; <?= count($variants) ?> variants</div>
                </div>
                <div class="category-card-status">
                  <span class="badge badge-<?= $p['is_active'] ? 'success' : 'neutral' ?>">
                    <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
                  </span>
                </div>
              </div>

              <div class="category-card-meta">
                <div class="cat-meta-pill">
                  <span class="cat-meta-label">Stock:</span>
                  <span class="cat-meta-val" <?= $stock <= 10 ? 'style="color:var(--dc-warning);"' : '' ?>><?= number_format($stock) ?>g</span>
                </div>
                <div class="cat-meta-pill">
                  <span class="cat-meta-label">Price:</span>
                  <span class="cat-meta-val"><?= $minPrice !== null ? formatINR((float)$minPrice) : '—' ?></span>
                </div>
              </div>

              <div class="category-card-actions">
                <a href="<?= url('admin/product-edit.php?id=' . (int)$p['id']) ?>" class="btn btn-outline btn-sm">
                  ✏️ Edit
                </a>
                <a href="<?= url('admin/products.php?action=toggle&id=' . (int)$p['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                   class="btn btn-secondary btn-sm"
                   onclick="return confirm('Toggle \'<?= e(addslashes($p['name'])) ?>\'?')">
                  <?= $p['is_active'] ? '👁️ Hide' : '👁️ Show' ?>
                </a>
                <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>" target="_blank" class="btn btn-ghost btn-sm" title="View on store">
                  🌐 Store
                </a>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
        <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
          <?php for ($p2 = 1; $p2 <= $lastPage; $p2++): ?>
            <a href="?<?= http_build_query(['page'=>$p2,'search'=>$search,'category'=>$catFilter,'status'=>$status]) ?>"
               class="btn btn-sm <?= $p2 == $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p2 ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /admin-content -->
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
