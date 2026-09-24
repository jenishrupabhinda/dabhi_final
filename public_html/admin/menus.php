<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['superadmin', 'admin', 'employee']);

if (Auth::role() !== 'superadmin' && Auth::role() !== 'admin' && !RBAC::can(Auth::id(), 'manage_menus')) {
    flashSet('error', 'You do not have permission to manage navigation menus.');
    redirect('/admin/index.php');
}

$activePage      = 'menus';
$pageHeading     = 'Storefront Navigation Menus';
$pageBreadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('admin/index.php')],
    ['label' => 'Navigation Menus', 'url' => '']
];

$action  = get('action');
$editId  = (int)get('id');
$errors  = [];
$editRow = null;

// ── Action: Toggle Show/Hide (is_active) ─────────────────────────────────
if ($action === 'toggle' && $editId) {
    csrfVerifyToken(get('_token'));
    if (Menu::toggle($editId)) {
        flashSet('success', 'Menu item visibility toggled successfully.');
    } else {
        flashSet('error', 'Menu item not found.');
    }
    redirect('/admin/menus.php');
}

// ── Action: Delete Menu Item ─────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    csrfVerifyToken(get('_token'));
    $res = Menu::delete($editId);
    if ($res['ok']) {
        flashSet('success', 'Menu item removed successfully.');
    } else {
        flashSet('error', $res['error'] ?? 'Could not delete menu item.');
    }
    redirect('/admin/menus.php');
}

// ── Action: Seed / Reset to Defaults ─────────────────────────────────────
if ($action === 'seed_defaults') {
    csrfVerifyToken(get('_token'));
    Menu::seedDefaults();
    flashSet('success', 'Navigation menu restored to default storefront configuration.');
    redirect('/admin/menus.php');
}

// ── Action: Edit Mode ────────────────────────────────────────────────────
if ($action === 'edit' && $editId) {
    $editRow = Menu::getById($editId);
    if (!$editRow) {
        flashSet('error', 'Menu item not found.');
        redirect('/admin/menus.php');
    }
}

// ── Handle POST: Save (Create / Update) ───────────────────────────────────
if (isPost()) {
    csrfVerify();
    $id = (int)post('menu_id');

    $data = [
        'parent_id'   => post('parent_id') ?: null,
        'title'       => post('title'),
        'url'         => post('url'),
        'icon'        => post('icon'),
        'badge'       => post('badge'),
        'badge_color' => post('badge_color'),
        'subtitle'    => post('subtitle'),
        'target'      => post('target') ?: '_self',
        'sort_order'  => (int)post('sort_order'),
        'is_active'   => (int)post('is_active'),
        'location'    => post('location') ?: 'primary',
    ];

    $result = Menu::save($data, $id ?: null);
    if ($result['ok']) {
        flashSet('success', $id ? 'Menu item updated successfully.' : 'New menu item created successfully.');
        redirect('/admin/menus.php');
    } else {
        $errors[] = $result['error'];
        $editRow  = $data;
        $editRow['id'] = $id;
    }
}

// Fetch tree and flat items for display & parent selector
$tree       = Menu::getTree('primary', false); // Get all items including inactive
$parentList = Menu::getParents('primary', $editRow['id'] ?? null);
$csrfToken  = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navigation Menus | <?= e(APP_NAME) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>">
  <style>
    .menu-parent-row {
      background-color: #faf7f2;
      font-weight: 600;
    }
    .menu-child-row {
      background-color: #ffffff;
    }
    .menu-indent {
      display: inline-block;
      width: 24px;
      color: #9c6c5b;
      font-weight: bold;
    }
    .badge-preview {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 9999px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    .btn-icon {
      padding: 4px 8px;
      font-size: 13px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
  </style>
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

      <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;">

        <!-- ── Left Column: Menu Items Tree Table ────────────────────────── -->
        <div class="card">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
              <h3 class="card-title">Storefront Navigation Hierarchy</h3>
              <p style="font-size:12px;color:#777;margin:3px 0 0 0;">
                Live navigation displayed in the desktop header and mobile drawer.
              </p>
            </div>
            <div style="display:flex;gap:8px;">
              <a href="<?= url('admin/menus.php?action=seed_defaults&_token=' . $csrfToken) ?>"
                 class="btn btn-outline btn-sm"
                 onclick="return confirm('Reset all navigation menus and dropdown submenus to the default Dabhi Chikki configuration? Custom additions will be overwritten.');"
                 title="Reset to default storefront menus">
                ↺ Restore Defaults
              </a>
              <a href="<?= url('admin/menus.php') ?>" class="btn btn-primary btn-sm">+ Add Item</a>
            </div>
          </div>

          <div class="table-wrap">
            <table class="dc-table">
              <thead>
                <tr>
                  <th style="width:280px;">Title &amp; Subtitle</th>
                  <th>URL / Destination</th>
                  <th>Badge / Icon</th>
                  <th style="text-align:center;width:60px;">Sort</th>
                  <th style="text-align:center;width:90px;">Visibility</th>
                  <th style="text-align:right;width:130px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($tree)): ?>
                  <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:#888;">
                      No navigation items found. Click <strong>+ Add Item</strong> or <strong>Restore Defaults</strong> to get started.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($tree as $parent): ?>
                    <!-- Parent Menu Item Row -->
                    <tr class="menu-parent-row">
                      <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                          <?php if (!empty($parent['icon'])): ?>
                            <span style="font-size:16px;"><?= e($parent['icon']) ?></span>
                          <?php endif; ?>
                          <div>
                            <strong style="color:#3d1412;"><?= e($parent['title']) ?></strong>
                            <?php if (!empty($parent['children'])): ?>
                              <span style="font-size:11px;background:#eedcc8;color:#6b281f;padding:1px 6px;border-radius:10px;margin-left:4px;">
                                <?= count($parent['children']) ?> submenus
                              </span>
                            <?php endif; ?>
                            <?php if (!empty($parent['subtitle'])): ?>
                              <div style="font-size:11px;color:#777;font-weight:normal;"><?= e($parent['subtitle']) ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td>
                        <code style="font-size:11px;color:#541f21;background:#f3eae0;padding:2px 6px;border-radius:4px;">
                          <?= e($parent['url']) ?>
                        </code>
                      </td>
                      <td>
                        <?php if (!empty($parent['badge'])): ?>
                          <span class="badge-preview" style="background-color:<?= e($parent['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                            <?= e($parent['badge']) ?>
                          </span>
                        <?php else: ?>
                          <span style="color:#aaa;font-size:12px;">—</span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align:center;font-weight:600;color:#555;">
                        <?= (int)$parent['sort_order'] ?>
                      </td>
                      <td style="text-align:center;">
                        <?php if ($parent['is_active']): ?>
                          <span class="badge badge-success" style="font-size:11px;">Visible</span>
                        <?php else: ?>
                          <span class="badge badge-secondary" style="font-size:11px;opacity:0.7;">Hidden</span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align:right;white-space:nowrap;">
                        <!-- Toggle Show/Hide -->
                        <a href="<?= url('admin/menus.php?action=toggle&id=' . $parent['id'] . '&_token=' . $csrfToken) ?>"
                           class="btn-icon btn-ghost"
                           title="<?= $parent['is_active'] ? 'Hide this menu item from storefront' : 'Show this menu item on storefront' ?>"
                           style="color:<?= $parent['is_active'] ? '#00bb7f' : '#888' ?>;">
                          <?= $parent['is_active'] ? '👁️' : '🙈' ?>
                        </a>
                        <!-- Edit -->
                        <a href="<?= url('admin/menus.php?action=edit&id=' . $parent['id']) ?>"
                           class="btn-icon btn-ghost"
                           title="Edit this menu item">
                          ✏️
                        </a>
                        <!-- Delete -->
                        <a href="<?= url('admin/menus.php?action=delete&id=' . $parent['id'] . '&_token=' . $csrfToken) ?>"
                           class="btn-icon btn-ghost"
                           style="color:#d40924;"
                           title="Delete this menu item"
                           onclick="return confirm('Delete \'<?= e(addslashes($parent['title'])) ?>\' and all of its submenus?');">
                          🗑️
                        </a>
                      </td>
                    </tr>

                    <!-- Child Submenus -->
                    <?php if (!empty($parent['children'])): ?>
                      <?php foreach ($parent['children'] as $child): ?>
                        <tr class="menu-child-row">
                          <td style="padding-left:28px;">
                            <div style="display:flex;align-items:center;gap:6px;">
                              <span class="menu-indent">↳</span>
                              <?php if (!empty($child['icon'])): ?>
                                <span style="font-size:15px;"><?= e($child['icon']) ?></span>
                              <?php endif; ?>
                              <div>
                                <span style="color:#222;"><?= e($child['title']) ?></span>
                                <?php if (!empty($child['subtitle'])): ?>
                                  <div style="font-size:11px;color:#888;"><?= e($child['subtitle']) ?></div>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td>
                            <code style="font-size:11px;color:#666;background:#f5f5f5;padding:2px 6px;border-radius:4px;">
                              <?= e($child['url']) ?>
                            </code>
                          </td>
                          <td>
                            <?php if (!empty($child['badge'])): ?>
                              <span class="badge-preview" style="background-color:<?= e($child['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                                <?= e($child['badge']) ?>
                              </span>
                            <?php else: ?>
                              <span style="color:#aaa;font-size:12px;">—</span>
                            <?php endif; ?>
                          </td>
                          <td style="text-align:center;color:#666;">
                            <?= (int)$child['sort_order'] ?>
                          </td>
                          <td style="text-align:center;">
                            <?php if ($child['is_active']): ?>
                              <span class="badge badge-success" style="font-size:11px;">Visible</span>
                            <?php else: ?>
                              <span class="badge badge-secondary" style="font-size:11px;opacity:0.7;">Hidden</span>
                            <?php endif; ?>
                          </td>
                          <td style="text-align:right;white-space:nowrap;">
                            <!-- Toggle Show/Hide -->
                            <a href="<?= url('admin/menus.php?action=toggle&id=' . $child['id'] . '&_token=' . $csrfToken) ?>"
                               class="btn-icon btn-ghost"
                               title="<?= $child['is_active'] ? 'Hide this submenu from storefront' : 'Show this submenu on storefront' ?>"
                               style="color:<?= $child['is_active'] ? '#00bb7f' : '#888' ?>;">
                              <?= $child['is_active'] ? '👁️' : '🙈' ?>
                            </a>
                            <!-- Edit -->
                            <a href="<?= url('admin/menus.php?action=edit&id=' . $child['id']) ?>"
                               class="btn-icon btn-ghost"
                               title="Edit this submenu">
                              ✏️
                            </a>
                            <!-- Delete -->
                            <a href="<?= url('admin/menus.php?action=delete&id=' . $child['id'] . '&_token=' . $csrfToken) ?>"
                               class="btn-icon btn-ghost"
                               style="color:#d40924;"
                               title="Delete this submenu"
                               onclick="return confirm('Delete submenu item \'<?= e(addslashes($child['title'])) ?>\'?');">
                              🗑️
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ── Right Column: Add / Edit Form ────────────────────────────── -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><?= $editRow ? 'Edit Menu Item' : 'Add New Menu Item' ?></h3>
            <p style="font-size:12px;color:#777;margin:3px 0 0 0;">
              <?= $editRow ? 'Update settings for item #' . $editRow['id'] : 'Create a top-level link or dropdown submenu' ?>
            </p>
          </div>

          <form action="<?= url('admin/menus.php') ?>" method="POST" style="padding:20px;">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="menu_id" value="<?= e($editRow['id'] ?? '') ?>">

            <div class="form-group">
              <label class="form-label" style="font-weight:600;">Title <span style="color:#d40924;">*</span></label>
              <input type="text" name="title" class="form-control"
                     value="<?= e($editRow['title'] ?? '') ?>"
                     placeholder="e.g. Our Chikki, Mandvi Peanut Chikki" required>
            </div>

            <div class="form-group">
              <label class="form-label" style="font-weight:600;">Link / URL <span style="color:#d40924;">*</span></label>
              <input type="text" name="url" class="form-control"
                     value="<?= e($editRow['url'] ?? '') ?>"
                     placeholder="e.g. index.php#products, product.php?slug=til-chikki" required>
              <p class="form-hint" style="font-size:11px;color:#888;margin-top:4px;">
                Internal page (e.g. <code>index.php#products</code>, <code>track.php</code>) or full URL.
              </p>
            </div>

            <div class="form-group">
              <label class="form-label" style="font-weight:600;">Parent Menu</label>
              <select name="parent_id" class="form-control form-select">
                <option value="">— None (Top Level Menu) —</option>
                <?php foreach ($parentList as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= (($editRow['parent_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                    Submenu under: <?= e($p['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="form-hint" style="font-size:11px;color:#888;margin-top:4px;">
                Select a parent menu to display this as a dropdown submenu item.
              </p>
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Icon / Emoji</label>
                <div style="display:flex;gap:6px;">
                  <input type="text" name="icon" id="menu_icon_input" class="form-control"
                         value="<?= e($editRow['icon'] ?? '') ?>"
                         placeholder="e.g. 🥜, 📦, ✨, 🔴 (leave blank for no icon)">
                  <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('menu_icon_input').value='';" title="Clear Icon" style="white-space:nowrap;padding:0 12px;font-size:12px;">
                    ✕ Clear
                  </button>
                </div>
                <p class="form-hint" style="font-size:11px;color:#888;margin-top:4px;">
                  Leave blank or click <strong>Clear</strong> to remove icon from menu bar.
                </p>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Sort Order</label>
                <input type="number" name="sort_order" class="form-control"
                       value="<?= (int)($editRow['sort_order'] ?? 0) ?>" min="0">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" style="font-weight:600;">Subtitle / Note (Dropdown item hint)</label>
              <input type="text" name="subtitle" class="form-control"
                     value="<?= e($editRow['subtitle'] ?? '') ?>"
                     placeholder="e.g. Classic Saurashtra crunch, Rich in natural calcium">
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Badge Text</label>
                <input type="text" name="badge" class="form-control"
                       value="<?= e($editRow['badge'] ?? '') ?>"
                       placeholder="e.g. Bestseller, Signature, New">
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Badge Color</label>
                <input type="text" name="badge_color" class="form-control"
                       value="<?= e($editRow['badge_color'] ?? '') ?>"
                       placeholder="e.g. #c7613d, #f6dc94">
              </div>
            </div>

            <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Open In</label>
                <select name="target" class="form-control form-select">
                  <option value="_self" <?= (($editRow['target'] ?? '_self') === '_self') ? 'selected' : '' ?>>Same Tab (_self)</option>
                  <option value="_blank" <?= (($editRow['target'] ?? '') === '_blank') ? 'selected' : '' ?>>New Window (_blank)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight:600;">Visibility</label>
                <select name="is_active" class="form-control form-select">
                  <option value="1" <?= (($editRow['is_active'] ?? 1) == 1) ? 'selected' : '' ?>>Visible (Active)</option>
                  <option value="0" <?= (($editRow['is_active'] ?? 1) == 0) ? 'selected' : '' ?>>Hidden (Inactive)</option>
                </select>
              </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:16px;">
              <button type="submit" class="btn btn-primary" style="flex:1;">
                <?= $editRow ? '💾 Update Menu Item' : '➕ Save Menu Item' ?>
              </button>
              <?php if ($editRow): ?>
                <a href="<?= url('admin/menus.php') ?>" class="btn btn-ghost">Cancel</a>
              <?php endif; ?>
            </div>

          </form>
        </div>

      </div><!-- /grid -->
    </div>
  </div>
</div>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
