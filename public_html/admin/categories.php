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

$pageTitle   = 'Categories';
$pageHeading = 'Categories';
$activePage  = 'categories';
require_once __DIR__ . '/partials/page-start.php';
?>
<style>
/* Category Responsive Mobile Architecture */
.category-mobile-cards {
  display: none;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.category-mobile-card {
  background: #ffffff;
  border: 1px solid var(--adm-border, #ded3c3);
  border-radius: var(--adm-radius, 12px);
  padding: 14px;
  box-shadow: 0 2px 8px rgba(43, 19, 17, 0.04);
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.category-card-top {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  box-sizing: border-box;
}

.category-card-thumb {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--adm-cream, #fbf8f3);
  border: 1px solid var(--adm-border-subtle, #f0e7dd);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.category-card-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.category-card-thumb .cat-icon-fallback {
  font-size: 20px;
}

.category-card-main {
  flex: 1;
  min-width: 0;
}

.category-card-name {
  font-weight: 700;
  font-size: 0.96rem;
  color: var(--adm-text-main, #2b1311);
  line-height: 1.3;
  word-break: break-word;
}

.category-card-parent {
  font-size: 0.78rem;
  color: var(--adm-text-muted, #7c6864);
  margin-top: 2px;
}

.category-card-status {
  flex-shrink: 0;
}

.category-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 8px 10px;
  background: var(--adm-cream, #fbf8f3);
  border-radius: 8px;
  font-size: 0.8rem;
  width: 100%;
  box-sizing: border-box;
}

.cat-meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--adm-text-main, #2b1311);
}

.cat-meta-label {
  color: var(--adm-text-muted, #7c6864);
  font-weight: 500;
}

.cat-meta-val {
  font-weight: 700;
}

.category-card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  box-sizing: border-box;
}

.category-card-actions .btn {
  flex: 1;
  justify-content: center;
  min-height: 38px;
  font-size: 0.84rem;
  padding: 6px 10px;
}

/* Category Mobile Segmented Tabs (< 992px) */
.category-mobile-tabs {
  display: none;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  background: #eedfd0;
  padding: 4px;
  border-radius: 12px;
  margin-bottom: 16px;
  width: 100%;
  box-sizing: border-box;
}

.category-tab-btn {
  padding: 10px 14px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.88rem;
  border: none;
  background: transparent;
  color: var(--adm-text-muted, #7c6864);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s ease;
  touch-action: manipulation;
}

.category-tab-btn.active {
  background: #ffffff;
  color: var(--adm-chocolate, #210e0d);
  box-shadow: 0 2px 8px rgba(33, 14, 13, 0.12);
}

@media (max-width: 991px) {
  .category-mobile-tabs {
    display: grid !important;
  }
  .cat-tab-content.cat-tab-hidden {
    display: none !important;
  }
}

@media (max-width: 768px) {
  .category-desktop-table {
    display: none !important;
  }
  .category-mobile-cards {
    display: flex !important;
  }
}
</style>

<div class="admin-content">

  <?php flashRender(); ?>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= e($e) ?></div>
  <?php endforeach; ?>

  <!-- Mobile Segmented Tabs (< 992px) -->
  <div class="category-mobile-tabs" id="catMobileTabs">
    <button type="button" class="category-tab-btn <?= $editRow ? '' : 'active' ?>" id="tabBtnCatList" onclick="switchCatTab('list', false)">
      📁 Categories (<?= count($categories) ?>)
    </button>
    <button type="button" class="category-tab-btn <?= $editRow ? 'active' : '' ?>" id="tabBtnCatForm" onclick="switchCatTab('form', false)">
      <?= $editRow ? '✏️ Edit: ' . e(mb_strimwidth($editRow['name'], 0, 16, '...')) : '➕ Add Category' ?>
    </button>
  </div>

  <div class="adm-category-grid">

        <!-- ── Category List ─────────────────────────────────────── -->
        <div class="card cat-tab-content <?= $editRow ? 'cat-tab-hidden' : '' ?>" id="catListPanel">
          <div class="card-header">
            <div>
              <h3 class="card-title">All Categories (<?= count($categories) ?>)</h3>
              <p style="font-size:0.8rem;color:var(--adm-text-muted);margin:2px 0 0 0;">Manage store departments and navigation</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="switchCatTab('form', true)">+ Add Category</button>
          </div>

          <!-- Desktop Table (>= 768px) -->
          <div class="table-wrap category-desktop-table">
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
                        <img src="<?= e(imageUrl($cat['image_path'])) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover;" alt="">
                      <?php else: ?>
                        <span style="width:32px;height:32px;border-radius:6px;background:var(--dc-border);display:flex;align-items:center;justify-content:center;font-size:14px;">🗂</span>
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
                  <tr><td colspan="6" class="text-muted text-center" style="padding:28px;">No categories yet. Add one →</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards View (< 768px) with Direct Action Buttons (Zero Horizontal Scroll) -->
          <div class="category-mobile-cards">
            <?php if (empty($categories)): ?>
              <div class="text-muted text-center" style="padding:24px;">No categories yet. Click "+ Add Category" above.</div>
            <?php else: ?>
              <?php foreach ($categories as $cat):
                $prodCount = (int)(Database::fetchOne(
                  'SELECT COUNT(*) as c FROM products WHERE category_id = ?', [$cat['id']]
                )['c'] ?? 0);
              ?>
              <div class="category-mobile-card">
                <div class="category-card-top">
                  <div class="category-card-thumb">
                    <?php if ($cat['image_path']): ?>
                      <img src="<?= e(imageUrl($cat['image_path'])) ?>" alt="">
                    <?php else: ?>
                      <span class="cat-icon-fallback">🗂</span>
                    <?php endif; ?>
                  </div>
                  <div class="category-card-main">
                    <div class="category-card-name"><?= e($cat['name']) ?></div>
                    <?php if (!empty($cat['parent_name'])): ?>
                      <div class="category-card-parent">↳ Subcategory of <strong><?= e($cat['parent_name']) ?></strong></div>
                    <?php else: ?>
                      <div class="category-card-parent">Main Department (Top Level)</div>
                    <?php endif; ?>
                  </div>
                  <div class="category-card-status">
                    <span class="badge badge-<?= $cat['is_active'] ? 'success' : 'neutral' ?>">
                      <?= $cat['is_active'] ? 'Active' : 'Hidden' ?>
                    </span>
                  </div>
                </div>

                <div class="category-card-meta">
                  <div class="cat-meta-pill">
                    <span class="cat-meta-label">Products:</span>
                    <span class="cat-meta-val"><?= $prodCount ?></span>
                  </div>
                  <div class="cat-meta-pill">
                    <span class="cat-meta-label">Sort:</span>
                    <span class="cat-meta-val"><?= (int)$cat['sort_order'] ?></span>
                  </div>
                  <?php if (!empty($cat['slug'])): ?>
                  <div class="cat-meta-pill" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <span class="cat-meta-label">Slug:</span>
                    <span class="cat-meta-val"><?= e($cat['slug']) ?></span>
                  </div>
                  <?php endif; ?>
                </div>

                <!-- Fully Visible Mobile Action Bar (No horizontal scroll needed) -->
                <div class="category-card-actions">
                  <a href="<?= url('admin/categories.php?action=edit&id=' . (int)$cat['id']) ?>" class="btn btn-outline btn-sm">
                    ✏️ Edit
                  </a>
                  <a href="<?= url('admin/categories.php?action=toggle&id=' . (int)$cat['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                     class="btn btn-secondary btn-sm"
                     onclick="return confirm('Toggle \'<?= e(addslashes($cat['name'])) ?>\'?')">
                    <?= $cat['is_active'] ? '👁️ Hide' : '👁️ Show' ?>
                  </a>
                  <?php if ($prodCount === 0): ?>
                  <a href="<?= url('admin/categories.php?action=delete&id=' . (int)$cat['id'] . '&_token=' . urlencode(csrfToken())) ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Delete category \'<?= e(addslashes($cat['name'])) ?>\'? Products will become uncategorised.')">
                    🗑️ Delete
                  </a>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div><!-- /card (List) -->

        <!-- ── Add / Edit Form ───────────────────────────────────── -->
        <div class="card cat-tab-content <?= $editRow ? '' : 'cat-tab-hidden' ?>" id="catFormPanel">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>
              <button type="button" class="btn btn-ghost btn-sm" style="margin-bottom:6px;padding:2px 0;display:inline-flex;align-items:center;gap:4px;" onclick="switchCatTab('list', true)">
                ← Back to Categories List
              </button>
              <h3 class="card-title"><?= $editRow ? '✏️ Edit Category' : '➕ Add Category' ?></h3>
            </div>
            <?php if ($editRow): ?>
              <a href="<?= url('admin/categories.php') ?>" class="btn btn-ghost btn-sm" style="font-size:0.8rem;">
                ✕ Add New Instead
              </a>
            <?php endif; ?>
          </div>

          <div class="card-body">
            <?php if ($editRow): ?>
            <div style="background:#fff3e6;border:1px solid #ffd8b3;border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <div style="font-size:0.86rem;color:#8c3b1e;">Currently editing: <strong><?= e($editRow['name']) ?></strong> (#<?= (int)$editRow['id'] ?>)</div>
              <a href="<?= url('admin/categories.php') ?>" style="font-size:0.8rem;color:#8c3b1e;font-weight:600;text-decoration:underline;">Cancel</a>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('admin/categories.php') ?>" enctype="multipart/form-data" id="catForm">
              <?= csrfField() ?>
              <input type="hidden" name="category_id" value="<?= (int)($editRow['id'] ?? 0) ?>">
              <input type="hidden" name="existing_image" value="<?= e($editRow['image_path'] ?? '') ?>">

              <div class="form-group">
                <label class="form-label">Name <span style="color:var(--adm-terracotta);">*</span></label>
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
                  <option value="">— Top level (Department) —</option>
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

              <div class="adm-form-grid-2">
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

              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button type="submit" class="btn btn-primary" style="flex:1;min-height:42px;">
                  <?= $editRow ? '💾 Update Category' : '➕ Create Category' ?>
                </button>
                <?php if ($editRow): ?>
                  <a href="<?= url('admin/categories.php') ?>" class="btn btn-ghost" style="min-height:42px;display:inline-flex;align-items:center;">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div><!-- /card (Form) -->

      </div><!-- /adm-category-grid -->
    </div><!-- /admin-content -->

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

// Mobile Tab Switcher
function switchCatTab(tab, shouldScroll) {
  const listPanel = document.getElementById('catListPanel');
  const formPanel = document.getElementById('catFormPanel');
  const btnList   = document.getElementById('tabBtnCatList');
  const btnForm   = document.getElementById('tabBtnCatForm');

  if (window.innerWidth >= 992) {
    if (listPanel) listPanel.classList.remove('cat-tab-hidden');
    if (formPanel) formPanel.classList.remove('cat-tab-hidden');
    if (shouldScroll && tab === 'form' && formPanel) {
      formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    return;
  }

  if (tab === 'form') {
    if (listPanel) listPanel.classList.add('cat-tab-hidden');
    if (formPanel) formPanel.classList.remove('cat-tab-hidden');
    if (btnList) btnList.classList.remove('active');
    if (btnForm) btnForm.classList.add('active');
    if (shouldScroll && formPanel) {
      formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  } else {
    if (formPanel) formPanel.classList.add('cat-tab-hidden');
    if (listPanel) listPanel.classList.remove('cat-tab-hidden');
    if (btnForm) btnForm.classList.remove('active');
    if (btnList) btnList.classList.add('active');
    if (shouldScroll && listPanel) {
      listPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}
</script>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
