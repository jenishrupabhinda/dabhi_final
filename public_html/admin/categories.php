<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['superadmin','admin','employee']);
RBAC::requireCan(Auth::id(), 'manage_categories');

$activePage  = 'categories';
$pageHeading = 'Categories';
$pageBreadcrumbs = [];

// ── Handle actions ─────────────────────────────────────────────────────
$action  = get('action');
$editId  = (int)get('id');
$errors  = [];
$editRow = null;

if ($action === 'toggle' && $editId) {
    csrfVerifyToken(get('_token'));
    Category::toggle($editId);
    flashSet('success', 'Category visibility updated.');
    redirect('/admin/categories.php');
}

if ($action === 'delete' && $editId) {
    csrfVerifyToken(get('_token'));
    $result = Category::delete($editId);
    if ($result['ok']) {
        flashSet('success', 'Category deleted.');
    } else {
        flashSet('error', $result['error']);
    }
    redirect('/admin/categories.php');
}

if ($action === 'edit' && $editId) {
    $editRow = Category::getById($editId);
    if (!$editRow) {
        flashSet('error', 'Category not found.');
        redirect('/admin/categories.php');
    }
}

// ── Handle POST (save) ─────────────────────────────────────────────────
if (isPost()) {
    csrfVerify();
    $id   = (int)post('category_id');

    // Handle image upload
    $imagePath = post('existing_image') ?: null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image'], 'categories');
        if ($upload['ok']) {
            $imagePath = $upload['path'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    $data = [
        'parent_id'        => post('parent_id') ?: null,
        'name'             => post('name'),
        'slug'             => post('slug'),
        'description'      => post('description'),
        'image_path'       => $imagePath,
        'is_active'        => (int)post('is_active'),
        'sort_order'       => (int)post('sort_order'),
        'meta_title'       => post('meta_title'),
        'meta_description' => post('meta_description'),
    ];

    if (empty($errors)) {
        $result = Category::save($data, $id ?: null);
        if ($result['ok']) {
            flashSet('success', $id ? 'Category updated.' : 'Category created.');
            redirect('/admin/categories.php');
        } else {
            $errors[] = $result['error'];
            $editRow  = $data;
            $editRow['id'] = $id;
        }
    } else {
        $editRow = $data + ['id' => $id];
    }
}

$categories = Category::getAll();
$topLevel   = array_filter($categories, fn($c) => !$c['parent_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Categories | <?= e(APP_NAME) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php require_once __DIR__ . '/partials/topbar.php'; ?>
    <div class="admin-content">

      <?php flashRender(); ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= e($e) ?></div>
      <?php endforeach; ?>

      <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

        <!-- ── Category List ─────────────────────────────────────── -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">All Categories</h3>
            <a href="<?= url('admin/categories.php?action=add') ?>" class="btn btn-primary btn-sm">+ Add Category</a>
          </div>
          <div class="table-wrap">
            <table class="dc-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Parent</th>
                  <th>Sort</th>
                  <th>Products</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $cat):
                  $prodCount = (int)(Database::fetchOne(
                    'SELECT COUNT(*) as c FROM products WHERE category_id = ?', [$cat['id']]
                  )['c'] ?? 0);
                ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <?php if ($cat['image_path']): ?>
                        <img src="<?= e(imageUrl($cat['image_path'])) ?>" style="width:28px;height:28px;border-radius:6px;object-fit:cover;" alt="">
                      <?php else: ?>
                        <span style="width:28px;height:28px;border-radius:6px;background:var(--dc-border);display:flex;align-items:center;justify-content:center;font-size:12px;">🗂</span>
                      <?php endif; ?>
                      <strong><?= e($cat['name']) ?></strong>
                    </div>
                  </td>
                  <td><?= e($cat['parent_name'] ?? '—') ?></td>
                  <td><?= (int)$cat['sort_order'] ?></td>
                  <td><?= $prodCount ?></td>
                  <td>
                    <span class="badge badge-<?= $cat['is_active'] ? 'success' : 'neutral' ?>">
                      <?= $cat['is_active'] ? 'Active' : 'Hidden' ?>
                    </span>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <a href="<?= url('admin/categories.php?action=edit&id=' . (int)$cat['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
                      <a href="<?= url('admin/categories.php?action=toggle&id=' . (int)$cat['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                         class="btn btn-ghost btn-sm"
                         onclick="return confirm('Toggle \'<?= e(addslashes($cat['name'])) ?>\'?')">
                        <?= $cat['is_active'] ? 'Hide' : 'Show' ?>
                      </a>
                      <?php if ($prodCount === 0): ?>
                      <a href="<?= url('admin/categories.php?action=delete&id=' . (int)$cat['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                         class="btn btn-danger btn-sm"
                         onclick="return confirm('Delete category \'<?= e(addslashes($cat['name'])) ?>\'? Products will become uncategorised.')">
                        Delete
                      </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                  <tr><td colspan="6" class="text-muted text-center">No categories yet. Add one →</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ── Add / Edit Form ───────────────────────────────────── -->
        <div class="card" id="catForm">
          <div class="card-header">
            <h3 class="card-title"><?= $editRow ? 'Edit Category' : 'Add Category' ?></h3>
          </div>
          <form method="POST" action="<?= url('admin/categories.php') ?>" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="category_id" value="<?= (int)($editRow['id'] ?? 0) ?>">
            <input type="hidden" name="existing_image" value="<?= e($editRow['image_path'] ?? '') ?>">

            <div class="form-group">
              <label class="form-label">Name <span>*</span></label>
              <input type="text" name="name" class="form-control" required autofocus
                value="<?= e($editRow['name'] ?? '') ?>" id="catNameInput">
            </div>

            <div class="form-group">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" class="form-control" id="catSlugInput"
                value="<?= e($editRow['slug'] ?? '') ?>" placeholder="auto-generated">
              <p class="form-hint">Leave blank to auto-generate from name.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Parent Category</label>
              <select name="parent_id" class="form-control form-select">
                <option value="">— Top level —</option>
                <?php foreach ($topLevel as $tl): ?>
                  <?php if (($editRow['id'] ?? 0) == $tl['id']) continue; // can't be own parent ?>
                  <option value="<?= $tl['id'] ?>" <?= ($editRow['parent_id'] ?? '') == $tl['id'] ? 'selected' : '' ?>>
                    <?= e($tl['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Short category description"><?= e($editRow['description'] ?? '') ?></textarea>
            </div>

            <div class="grid-2" style="gap:12px;">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($editRow['sort_order'] ?? 0) ?>" min="0">
              </div>
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control form-select">
                  <option value="1" <?= (($editRow['is_active'] ?? 1) == 1) ? 'selected' : '' ?>>Active</option>
                  <option value="0" <?= (($editRow['is_active'] ?? 1) == 0) ? 'selected' : '' ?>>Hidden</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Category Image</label>
              <?php if (!empty($editRow['image_path'])): ?>
                <img src="<?= e(imageUrl($editRow['image_path'])) ?>" style="width:80px;height:60px;object-fit:cover;border-radius:6px;margin-bottom:8px;display:block;" alt="current">
              <?php endif; ?>
              <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
              <p class="form-hint">JPEG/PNG/WebP. Recommended: 400×300px.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Meta Title</label>
              <input type="text" name="meta_title" class="form-control" maxlength="160"
                value="<?= e($editRow['meta_title'] ?? '') ?>" placeholder="SEO title (optional)">
            </div>

            <div class="form-group">
              <label class="form-label">Meta Description</label>
              <textarea name="meta_description" class="form-control" rows="2" maxlength="320"
                placeholder="SEO description"><?= e($editRow['meta_description'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:8px;">
              <button type="submit" class="btn btn-primary"><?= $editRow ? 'Update Category' : 'Create Category' ?></button>
              <?php if ($editRow): ?>
                <a href="<?= url('admin/categories.php') ?>" class="btn btn-ghost">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

      </div><!-- /grid -->
    </div>
  </div>
</div>

<script>
// Auto-generate slug from name
(function(){
  const nameInput = document.getElementById('catNameInput');
  const slugInput = document.getElementById('catSlugInput');
  if(!nameInput || !slugInput) return;
  nameInput.addEventListener('input', function(){
    if(slugInput.dataset.manual) return;
    slugInput.value = this.value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');
  });
  slugInput.addEventListener('input', function(){ this.dataset.manual = '1'; });
})();
</script>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
