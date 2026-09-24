<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/helpers/upload.php';
Auth::require(['superadmin','admin','employee']);

$activePage      = 'products';
$editId          = (int)get('id');
$pageHeading     = $editId ? 'Edit Product' : 'Add Product';
$pageBreadcrumbs = [['label'=>'Products','url'=>'/admin/products.php'], ['label'=>$pageHeading]];

$errors  = [];
$product = null;

if ($editId) {
    $product = Product::getById($editId);
    if (!$product) {
        flashSet('error', 'Product not found.');
        redirect('/admin/products.php');
    }
}

// ── Handle POST ───────────────────────────────────────────────────────
if (isPost()) {
    csrfVerify();

    // ── Product data
    $data = [
        'category_id'       => (int)post('category_id'),
        'name'              => post('name'),
        'slug'              => post('slug'),
        'short_description' => post('short_description'),
        'description'       => post('description'),
        'custom_badges'     => trim(post('custom_badges') ?? ''),
        'custom_card_badges'=> trim(post('custom_card_badges') ?? ''),
        'hsn_code'          => post('hsn_code'),
        'gst_rate_percent'  => (float)post('gst_rate_percent') ?: 5.0,
        'is_active'         => (int)post('is_active'),
        'is_featured'       => (int)post('is_featured'),
        'meta_title'        => post('meta_title'),
        'meta_description'  => post('meta_description'),
    ];

    if ($data['category_id'] < 1) $errors[] = 'Category is required.';
    if (trim($data['name']) === '') $errors[] = 'Product name is required.';

    // ── Variants
    $variantIds    = $_POST['variant_id']    ?? [];
    $variantSkus   = $_POST['variant_sku']   ?? [];
    $variantWeights = $_POST['variant_weight'] ?? [];
    $variantMrps   = $_POST['variant_mrp']   ?? [];
    $variantSells  = $_POST['variant_sell']  ?? [];
    $variantReorders = $_POST['variant_reorder'] ?? [];
    $variantActives = $_POST['variant_active'] ?? [];

    $variants = [];
    for ($i = 0, $n = count($variantSkus); $i < $n; $i++) {
        $sku = trim($variantSkus[$i]);
        if ($sku === '') continue;
        $weight = (int)($variantWeights[$i] ?? 0);
        $mrp    = (float)($variantMrps[$i]  ?? 0);
        $sell   = (float)($variantSells[$i] ?? 0);
        if ($weight < 1 || $mrp < 1 || $sell < 1) {
            $errors[] = "Variant #{$sku}: weight, MRP and selling price must all be > 0.";
            continue;
        }
        if ($sell > $mrp) {
            $errors[] = "Variant #{$sku}: selling price cannot exceed MRP.";
        }
        $variants[] = [
            'id'           => ($variantIds[$i] ?? '') !== '' ? (int)$variantIds[$i] : null,
            'sku'          => $sku,
            'weight_grams' => $weight,
            'mrp'          => $mrp,
            'selling_price'=> $sell,
            'reorder_level'=> max(0, (int)($variantReorders[$i] ?? 10)),
            'is_active'    => isset($variantActives[$i]) ? 1 : 0,
        ];
    }

    if (empty($variants)) $errors[] = 'At least one weight variant is required.';

    // ── New image uploads
    $newImagePaths = [];
    if (!empty($_FILES['product_images']['name'][0])) {
        foreach ($_FILES['product_images']['name'] as $k => $fname) {
            if (!$fname) continue;
            $file = [
                'name'     => $_FILES['product_images']['name'][$k],
                'tmp_name' => $_FILES['product_images']['tmp_name'][$k],
                'error'    => $_FILES['product_images']['error'][$k],
                'size'     => $_FILES['product_images']['size'][$k],
            ];
            $upload = uploadImage($file, 'products');
            if ($upload['ok']) {
                $newImagePaths[] = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        }
    }

    if (empty($errors)) {
        $result = Product::save($data, $variants, $editId ?: null);
        if ($result['ok']) {
            $pid = $result['id'];

            // Save new images
            $primarySet = (bool)Database::fetchOne('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1', [$pid]);
            foreach ($newImagePaths as $idx => $path) {
                $isPrimary = (!$primarySet && $idx === 0) ? 1 : 0;
                Database::query(
                    'INSERT INTO product_images (product_id, image_path, sort_order, is_primary) VALUES (?, ?, ?, ?)',
                    [$pid, $path, $idx, $isPrimary]
                );
                if ($isPrimary) $primarySet = true;
            }

            // Handle primary image selection
            $selectedPrimary = (int)post('primary_image_id');
            if ($selectedPrimary) {
                Database::query('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$pid]);
                Database::query('UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?', [$selectedPrimary, $pid]);
            }

            // Handle image deletions
            $deleteImages = $_POST['delete_image'] ?? [];
            foreach ($deleteImages as $imgId) {
                $img = Database::fetchOne('SELECT image_path FROM product_images WHERE id = ? AND product_id = ?', [(int)$imgId, $pid]);
                if ($img) {
                    $realPath = dirname(__DIR__, 2) . '/public_html' . $img['image_path'];
                    if (file_exists($realPath)) @unlink($realPath);
                    Database::query('DELETE FROM product_images WHERE id = ?', [(int)$imgId]);
                }
            }

            flashSet('success', $editId ? 'Product updated successfully.' : 'Product created successfully.');
            redirect('/admin/product-edit.php?id=' . $pid);
        } else {
            $errors[] = $result['error'];
        }
    }
    // Re-load product on error
    if ($editId) $product = Product::getById($editId);
}

$categories = Category::getAll();
$variants   = $product ? $product['variants'] : [
    // Default blank variant row
    ['id'=>'','sku'=>'','weight_grams'=>250,'mrp'=>'','selling_price'=>'','reorder_level'=>10,'is_active'=>1]
];
$images = $product ? $product['images'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?= e($pageHeading) ?> | <?= e(APP_NAME) ?> Admin</title>
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

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="<?= url('admin/product-edit.php' . ($editId ? '?id=' . (int)$editId : '')) ?>" enctype="multipart/form-data" id="productForm">
        <?= csrfField() ?>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

          <!-- ── Left: Main fields ── -->
          <div>

            <!-- Basic info -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header"><h3 class="card-title">Product Details</h3></div>
              <div class="form-group">
                <label class="form-label">Product Name <span>*</span></label>
                <input type="text" name="name" class="form-control" required id="prodNameInput"
                  value="<?= e($product['name'] ?? '') ?>" placeholder="e.g. Gur Mungfali Chikki">
              </div>
              <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">Slug</label>
                  <input type="text" name="slug" class="form-control" id="prodSlugInput"
                    value="<?= e($product['slug'] ?? '') ?>" placeholder="auto-generated">
                </div>
                <div class="form-group">
                  <label class="form-label">Category <span>*</span></label>
                  <select name="category_id" class="form-control form-select" required>
                    <option value="">Select category…</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected':'' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Short Description</label>
                <textarea name="short_description" class="form-control" rows="2" maxlength="300"
                  placeholder="Appears in product cards (max 300 chars)"><?= e($product['short_description'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Full Description</label>
                <textarea name="description" class="form-control" rows="6"
                  id="descEditor" placeholder="Detailed product description"><?= e($product['description'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Weight Variants -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header">
                <h3 class="card-title">Weight Variants &amp; Pricing</h3>
                <button type="button" id="addVariantBtn" class="btn btn-secondary btn-sm">+ Add Variant</button>
              </div>
              <div class="table-wrap">
                <table class="dc-table" id="variantTable">
                  <thead>
                    <tr>
                      <th>SKU</th>
                      <th>Weight (g)</th>
                      <th>MRP (₹)</th>
                      <th>Selling (₹)</th>
                      <th>Disc %</th>
                      <th>Reorder at</th>
                      <th>Active</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody id="variantBody">
                    <?php foreach ($variants as $i => $v): ?>
                    <tr class="variant-row">
                      <td>
                        <input type="hidden" name="variant_id[]" value="<?= e($v['id'] ?? '') ?>">
                        <input type="text" name="variant_sku[]" class="form-control" placeholder="SKU" required
                          value="<?= e($v['sku'] ?? '') ?>" style="width:110px;">
                      </td>
                      <td>
                        <input type="number" name="variant_weight[]" class="form-control" placeholder="250" required min="1"
                          value="<?= (int)($v['weight_grams'] ?? '') ?>" style="width:90px;">
                      </td>
                      <td>
                        <input type="number" name="variant_mrp[]" class="form-control variant-mrp" step=".01" placeholder="0" required min="0.01"
                          value="<?= $v['mrp'] ?? '' ?>" style="width:90px;">
                      </td>
                      <td>
                        <input type="number" name="variant_sell[]" class="form-control variant-sell" step=".01" placeholder="0" required min="0.01"
                          value="<?= $v['selling_price'] ?? '' ?>" style="width:90px;">
                      </td>
                      <td class="disc-pct" style="font-size:0.85rem;font-weight:600;color:var(--dc-success);">
                        <?php
                          $mrp  = (float)($v['mrp'] ?? 0);
                          $sell = (float)($v['selling_price'] ?? 0);
                          echo ($mrp > 0 && $sell > 0) ? Product::discountPct($mrp, $sell) . '%' : '—';
                        ?>
                      </td>
                      <td>
                        <input type="number" name="variant_reorder[]" class="form-control" min="0"
                          value="<?= (int)($v['reorder_level'] ?? 10) ?>" style="width:70px;">
                      </td>
                      <td>
                        <input type="checkbox" name="variant_active[<?= $i ?>]" value="1"
                          <?= ($v['is_active'] ?? 1) ? 'checked' : '' ?>>
                      </td>
                      <td>
                        <?php if (!empty($v['id'])): ?>
                          <span style="font-size:0.75rem;color:var(--dc-muted);">Stock: <?= number_format((int)($v['stock'] ?? 0)) ?></span>
                        <?php else: ?>
                          <button type="button" class="btn btn-danger btn-sm remove-variant-btn">✕</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- SEO -->
            <div class="card">
              <div class="card-header"><h3 class="card-title">SEO</h3></div>
              <div class="form-group">
                <label class="form-label">Meta Title <small style="color:var(--dc-muted);">(max 160)</small></label>
                <input type="text" name="meta_title" class="form-control" maxlength="160"
                  value="<?= e($product['meta_title'] ?? '') ?>" placeholder="Defaults to product name">
              </div>
              <div class="form-group">
                <label class="form-label">Meta Description <small style="color:var(--dc-muted);">(max 320)</small></label>
                <textarea name="meta_description" class="form-control" rows="2" maxlength="320"
                  placeholder="Displayed in Google search results"><?= e($product['meta_description'] ?? '') ?></textarea>
              </div>
            </div>

          </div><!-- /left -->

          <!-- ── Right: Images + Settings ── -->
          <div>

            <!-- Settings -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header"><h3 class="card-title">Settings</h3></div>

              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control form-select">
                  <option value="1" <?= ($product['is_active'] ?? 1) ? 'selected':'' ?>>Active (visible in store)</option>
                  <option value="0" <?= !($product['is_active'] ?? 1) ? 'selected':'' ?>>Hidden</option>
                </select>
              </div>

              <div class="form-check form-group">
                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                  <?= ($product['is_featured'] ?? 0) ? 'checked':'' ?>>
                <label for="is_featured">⭐ Feature on homepage</label>
              </div>

              <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">HSN Code</label>
                  <input type="text" name="hsn_code" class="form-control" maxlength="10"
                    value="<?= e($product['hsn_code'] ?? '') ?>" placeholder="2106">
                  <p class="form-hint">Required for GST invoices.</p>
                </div>
                <div class="form-group">
                  <label class="form-label">GST Rate (%)</label>
                  <select name="gst_rate_percent" class="form-control form-select">
                    <?php foreach ([0,5,12,18,28] as $rate): ?>
                      <option value="<?= $rate ?>" <?= ((float)($product['gst_rate_percent'] ?? 5)) === (float)$rate ? 'selected':'' ?>><?= $rate ?>%</option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <?php if ($editId): ?>
              <div style="margin-top:8px;">
                <a href="<?= url('product.php?slug=' . urlencode($product['slug'])) ?>" target="_blank" class="btn btn-ghost btn-sm">🌐 View in store</a>
                <a href="<?= url('admin/inventory-batch.php?variant_id=' . (int)$editId) ?>" class="btn btn-ghost btn-sm">📦 Manage Stock</a>
              </div>
              <?php endif; ?>
            </div>

            <!-- Custom Storefront Badges -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header"><h3 class="card-title">🏷️ Custom Badges (Optional)</h3></div>
              <div class="form-group">
                <label class="form-label">Product Page Capsules</label>
                <input type="text" name="custom_badges" class="form-control"
                  value="<?= e($product['custom_badges'] ?? '') ?>"
                  placeholder="e.g. ROASTED PEANUTS, HIGH PROTEIN">
                <p class="form-hint">Leave blank to use default store badges from <a href="<?= url('admin/modules.php') ?>" target="_blank">Modules &amp; Content</a>.</p>
              </div>
              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Homepage Card Badges</label>
                <input type="text" name="custom_card_badges" class="form-control"
                  value="<?= e($product['custom_card_badges'] ?? '') ?>"
                  placeholder="e.g. Pure Jaggery, 100% Natural">
                <p class="form-hint">Leave blank to use default store card badges.</p>
              </div>
            </div>

            <!-- Images -->
            <div class="card" style="margin-bottom:20px;">
              <div class="card-header"><h3 class="card-title">Product Images</h3></div>

              <?php if (!empty($images)): ?>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px;">
                <?php foreach ($images as $img): ?>
                <div style="position:relative;text-align:center;">
                  <img src="<?= e(imageUrl($img['image_path'])) ?>" style="width:100%;height:70px;object-fit:cover;border-radius:6px;border:2px solid <?= $img['is_primary'] ? 'var(--dc-yellow)' : 'var(--dc-border)' ?>;" alt="">
                  <div style="margin-top:4px;display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
                    <?php if (!$img['is_primary']): ?>
                      <label style="font-size:0.65rem;cursor:pointer;">
                        <input type="radio" name="primary_image_id" value="<?= $img['id'] ?>" style="width:10px;"> Primary
                      </label>
                    <?php else: ?>
                      <span style="font-size:0.65rem;color:var(--dc-yellow-dark);font-weight:600;">★ Primary</span>
                    <?php endif; ?>
                    <label style="font-size:0.65rem;cursor:pointer;color:var(--dc-danger);">
                      <input type="checkbox" name="delete_image[]" value="<?= $img['id'] ?>" style="width:10px;"> Delete
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <div class="form-group">
                <label class="form-label">Upload New Images</label>
                <input type="file" name="product_images[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                <p class="form-hint">Max 5MB each. First image = primary if none exists. JPEG/PNG/WebP.</p>
              </div>
            </div>

            <!-- Save button -->
            <button type="submit" class="btn btn-primary btn-block btn-lg" id="saveBtn">
              <?= $editId ? '💾 Update Product' : '✅ Create Product' ?>
            </button>
            <a href="<?= url('admin/products.php') ?>" class="btn btn-ghost btn-block" style="margin-top:8px;">Cancel</a>

          </div><!-- /right -->

        </div><!-- /grid -->
      </form>

    </div>
  </div>
</div>

<script>
// Auto slug
(function(){
  const n = document.getElementById('prodNameInput');
  const s = document.getElementById('prodSlugInput');
  if(n && s){
    n.addEventListener('input',function(){
      if(s.dataset.manual) return;
      s.value = this.value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');
    });
    s.addEventListener('input',function(){ this.dataset.manual='1'; });
  }
})();

// Discount % live update
function updateDisc(row){
  const mrp  = parseFloat(row.querySelector('.variant-mrp')?.value) || 0;
  const sell = parseFloat(row.querySelector('.variant-sell')?.value) || 0;
  const td   = row.querySelector('.disc-pct');
  if(td && mrp > 0 && sell > 0){
    const pct = Math.round(((mrp - sell) / mrp) * 100);
    td.textContent = pct > 0 ? pct + '%' : '—';
    td.style.color = pct > 0 ? 'var(--dc-success)' : 'var(--dc-muted)';
  }
}
document.querySelectorAll('.variant-row').forEach(function(row){
  row.querySelector('.variant-mrp')?.addEventListener('input',()=>updateDisc(row));
  row.querySelector('.variant-sell')?.addEventListener('input',()=>updateDisc(row));
});

// Add variant row
let variantIdx = <?= count($variants) ?>;
document.getElementById('addVariantBtn')?.addEventListener('click', function(){
  const tbody = document.getElementById('variantBody');
  const row   = document.createElement('tr');
  row.className = 'variant-row';
  row.innerHTML = `
    <td><input type="hidden" name="variant_id[]" value="">
        <input type="text" name="variant_sku[]" class="form-control" placeholder="SKU" required style="width:110px;"></td>
    <td><input type="number" name="variant_weight[]" class="form-control" placeholder="250" required min="1" style="width:90px;"></td>
    <td><input type="number" name="variant_mrp[]" class="form-control variant-mrp" step=".01" placeholder="0" required min="0.01" style="width:90px;"></td>
    <td><input type="number" name="variant_sell[]" class="form-control variant-sell" step=".01" placeholder="0" required min="0.01" style="width:90px;"></td>
    <td class="disc-pct" style="font-size:0.85rem;font-weight:600;color:var(--dc-muted);">—</td>
    <td><input type="number" name="variant_reorder[]" class="form-control" min="0" value="10" style="width:70px;"></td>
    <td><input type="checkbox" name="variant_active[${variantIdx}]" value="1" checked></td>
    <td><button type="button" class="btn btn-danger btn-sm remove-variant-btn">✕</button></td>`;
  tbody.appendChild(row);
  variantIdx++;
  row.querySelector('.variant-mrp')?.addEventListener('input',()=>updateDisc(row));
  row.querySelector('.variant-sell')?.addEventListener('input',()=>updateDisc(row));
  row.querySelector('.remove-variant-btn')?.addEventListener('click',()=>row.remove());
});

// Remove existing rows
document.querySelectorAll('.remove-variant-btn').forEach(function(btn){
  btn.addEventListener('click', function(){ this.closest('tr').remove(); });
});
</script>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
