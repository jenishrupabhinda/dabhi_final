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
$savedId = (int)get('saved');
$errors  = [];
$editRow = null;

// ── Action: Toggle Show/Hide (is_active) ─────────────────────────────────
if ($action === 'toggle' && $editId) {
    csrfVerifyToken(get('_token'));
    $ok = Menu::toggle($editId);
    if (isAjax()) {
        header('Content-Type: application/json; charset=utf-8');
        $updated = Menu::getById($editId);
        echo json_encode([
            'ok'        => (bool)$ok,
            'id'        => $editId,
            'is_active' => (int)($updated['is_active'] ?? 0),
            'title'     => $updated['title'] ?? 'Menu item',
            'message'   => $ok ? 'Visibility toggled successfully.' : 'Menu item not found.'
        ]);
        exit;
    }
    if ($ok) {
        flashSet('success', 'Menu item visibility toggled successfully.');
    } else {
        flashSet('error', 'Menu item not found.');
    }
    redirect('/admin/menus.php#menu-item-' . $editId);
}

// ── Action: Delete Menu Item ─────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    csrfVerifyToken(get('_token'));
    $res = Menu::delete($editId);
    if (isAjax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => (bool)$res['ok'],
            'id'      => $editId,
            'message' => $res['ok'] ? 'Menu item removed successfully.' : ($res['error'] ?? 'Could not delete menu item.')
        ]);
        exit;
    }
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

// ── Action: Edit Mode via direct URL ─────────────────────────────────────
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
        $savedId = (int)($result['id'] ?? $id);
        flashSet('success', $id ? 'Menu item updated successfully.' : 'New menu item created successfully.');
        redirect('/admin/menus.php?saved=' . $savedId . '#menu-item-' . $savedId);
    } else {
        $errors[] = $result['error'];
        $editRow  = $data;
        $editRow['id'] = $id;
    }
}

// Fetch tree and flat items for display & parent selector
$tree       = Menu::getTree('primary', false); // Get all items including inactive
$parentList = Menu::getParents('primary');
$csrfToken  = csrfToken();

// Compute stats
$statTotalItems    = 0;
$statTotalParents  = count($tree);
$statTotalSubmenus = 0;
$statTotalVisible  = 0;
$statTotalHidden   = 0;

foreach ($tree as $p) {
    $statTotalItems++;
    if ($p['is_active']) {
        $statTotalVisible++;
    } else {
        $statTotalHidden++;
    }
    if (!empty($p['children'])) {
        foreach ($p['children'] as $c) {
            $statTotalItems++;
            $statTotalSubmenus++;
            if ($c['is_active']) {
                $statTotalVisible++;
            } else {
                $statTotalHidden++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Navigation Menus | <?= e(APP_NAME) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="stylesheet" href="<?= url('admin/assets/css/admin-shell.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>">
  <script src="<?= url('admin/assets/js/admin-shell.js') ?>" defer></script>
  <script>
    try {
      if (localStorage.getItem('adminSidebarCollapsed') === 'true' && window.innerWidth >= 992) {
        document.documentElement.classList.add('sidebar-is-collapsed');
      }
    } catch(e) {}
  </script>

  <style>
    /* =====================================================================
       NAVIGATION MENUS CONTROL CENTER — COMPREHENSIVE RESPONSIVE STYLES
       ===================================================================== */
    :root {
      --mc-terracotta: #c7613d;
      --mc-terracotta-dark: #a44b2d;
      --mc-chocolate: #210e0d;
      --mc-cream: #fbf8f3;
      --mc-cream-dark: #f3eae0;
      --mc-border: #ded3c3;
      --mc-border-light: #f1eae1;
      --mc-gold: #f6dc94;
      --mc-emerald: #1b8a5a;
      --mc-ruby: #d40924;
    }

    /* Outer Container */
    .menu-page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 20px;
    }
    .menu-page-header-text h2 {
      font-family: var(--dc-font-heading);
      font-size: 1.45rem;
      font-weight: 700;
      color: #2b1311;
      margin: 0 0 4px 0;
      letter-spacing: -0.01em;
    }
    .menu-page-header-text p {
      font-size: 0.88rem;
      color: #69574f;
      margin: 0;
    }
    .menu-header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Stats Quick Bar */
    .menu-stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 12px;
      margin-bottom: 22px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-stat-pill {
      background: #ffffff;
      border: 1px solid var(--mc-border);
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 2px 8px rgba(43, 19, 17, 0.03);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      min-width: 0;
      box-sizing: border-box;
    }
    .menu-stat-pill:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(43, 19, 17, 0.06);
    }
    .menu-stat-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      background: var(--mc-cream);
      color: var(--mc-terracotta);
      flex-shrink: 0;
    }
    .menu-stat-content {
      line-height: 1.2;
      min-width: 0;
      overflow: hidden;
    }
    .menu-stat-val {
      font-size: 1.15rem;
      font-weight: 700;
      color: #2b1311;
      font-family: var(--dc-font-heading);
    }
    .menu-stat-lbl {
      font-size: 0.72rem;
      color: #7b6860;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Responsive Segmented Tabs (Mobile / Tablet Switcher) */
    .menu-mobile-tabs {
      display: none;
      grid-template-columns: 1fr 1fr;
      background: #eadecf;
      padding: 4px;
      border-radius: 12px;
      margin-bottom: 16px;
      gap: 4px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-mobile-tab-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 12px;
      border: none;
      background: transparent;
      border-radius: 9px;
      font-weight: 600;
      font-size: 0.88rem;
      color: #55332e;
      cursor: pointer;
      transition: all 0.2s ease;
      touch-action: manipulation;
      min-width: 0;
    }
    .menu-mobile-tab-btn.active {
      background: #ffffff;
      color: var(--mc-chocolate);
      box-shadow: 0 2px 8px rgba(33, 14, 13, 0.12);
    }
    .menu-mobile-tab-btn .badge-pill {
      background: var(--mc-terracotta);
      color: #ffffff;
      font-size: 10px;
      padding: 2px 7px;
      border-radius: 999px;
      font-weight: 700;
      flex-shrink: 0;
    }

    /* Main Responsive Grid */
    .menu-manager-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 390px;
      gap: 20px;
      align-items: start;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }

    /* Form 2-Column Responsive Grid */
    .menu-form-row-2 {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 12px;
      margin-bottom: 16px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }

    /* Sticky Add/Edit Form Panel */
    .menu-form-panel {
      position: sticky;
      top: 80px;
      max-height: calc(100vh - 96px);
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      box-sizing: border-box;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(43, 19, 17, 0.08);
      border: 1px solid var(--mc-border);
      background: #ffffff;
      width: 100%;
      max-width: 100%;
    }
    #menuItemForm {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-height: 0;
      margin: 0 !important;
      padding: 0 !important;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-form-scrollable-body {
      flex: 1 1 auto;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      padding: 18px 20px;
      min-height: 0;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-form-footer-bar {
      position: sticky;
      bottom: 0;
      background: #ffffff;
      padding: 12px 16px;
      border-top: 1px solid var(--mc-border);
      display: flex;
      align-items: center;
      gap: 10px;
      z-index: 20;
      border-radius: 0 0 16px 16px;
      box-shadow: 0 -4px 14px rgba(43, 19, 17, 0.06);
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-form-footer-bar #btnSubmitForm {
      flex: 1 1 auto;
      min-width: 0;
      padding: 11px 14px;
      font-size: 0.9rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      min-height: 44px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      box-sizing: border-box;
    }
    .menu-form-footer-bar #btnFormCancelBottom {
      flex: 0 0 auto;
      padding: 11px 14px;
      font-size: 0.88rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      white-space: nowrap;
      box-sizing: border-box;
    }
    .btn-sub-icon {
      font-size: 1.1em;
      line-height: 1;
      flex-shrink: 0;
    }
    .btn-sub-text {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Search & Filter Toolbar */
    .menu-search-toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 18px;
      background: #fbf8f3;
      border-bottom: 1px solid var(--mc-border-light);
      flex-wrap: wrap;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-search-box {
      flex: 1;
      min-width: 200px;
      position: relative !important;
      display: flex;
      align-items: center;
      box-sizing: border-box;
    }
    .menu-search-box input {
      width: 100%;
      padding: 9px 12px 9px 36px;
      border: 1px solid var(--mc-border);
      border-radius: 10px;
      font-size: 0.88rem;
      background: #ffffff;
      box-sizing: border-box;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .menu-search-box input:focus {
      outline: none;
      border-color: var(--mc-terracotta);
      box-shadow: 0 0 0 3px rgba(199, 97, 61, 0.15);
    }
    .menu-search-box .search-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 14px;
      color: #8c776e;
      pointer-events: none;
      line-height: 1;
      z-index: 2;
    }
    .menu-search-box .clear-search-btn {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #999;
      cursor: pointer;
      font-size: 14px;
      display: none;
      padding: 4px;
    }

    /* Table Styles */
    .menu-desktop-table-wrap {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .menu-parent-row {
      background-color: #faf6f0;
      transition: background-color 0.2s ease;
    }
    .menu-parent-row:hover {
      background-color: #f5ede2;
    }
    .menu-child-row {
      background-color: #ffffff;
      transition: background-color 0.2s ease;
    }
    .menu-child-row:hover {
      background-color: #fdfaf6;
    }
    .menu-indent {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 22px;
      color: #a44b2d;
      font-weight: 700;
      font-size: 15px;
    }

    /* Active Editing Row Highlight */
    .row-editing {
      background-color: #fff6ed !important;
      position: relative;
    }
    .row-editing td {
      border-top: 1.5px solid var(--mc-terracotta) !important;
      border-bottom: 1.5px solid var(--mc-terracotta) !important;
    }
    .row-editing td:first-child {
      border-left: 4px solid var(--mc-terracotta) !important;
    }
    .row-editing td:last-child {
      border-right: 1.5px solid var(--mc-terracotta) !important;
    }

    /* Highlight Pulse Animation (Used on Saved / Found items) */
    @keyframes highlightFlash {
      0% { background-color: rgba(199, 97, 61, 0.35); transform: scale(1); }
      50% { background-color: rgba(246, 220, 148, 0.4); transform: scale(1.008); }
      100% { background-color: transparent; transform: scale(1); }
    }
    .menu-item-highlight {
      animation: highlightFlash 2.4s ease-out forwards;
    }

    /* Badges */
    .badge-preview {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 9999px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      box-shadow: 0 1px 4px rgba(0,0,0,0.1);
      white-space: nowrap;
    }
    .visibility-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      user-select: none;
      border: 1px solid transparent;
    }
    .visibility-pill.is-active {
      background: rgba(27, 138, 90, 0.12);
      color: #126842;
      border-color: rgba(27, 138, 90, 0.25);
    }
    .visibility-pill.is-hidden {
      background: rgba(100, 100, 100, 0.1);
      color: #666666;
      border-color: rgba(100, 100, 100, 0.2);
    }
    .visibility-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
    }
    .visibility-pill.is-active .visibility-dot {
      background: #1b8a5a;
      box-shadow: 0 0 6px rgba(27, 138, 90, 0.6);
    }
    .visibility-pill.is-hidden .visibility-dot {
      background: #888888;
    }

    /* Action Buttons */
    .btn-icon-action {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      border: 1px solid var(--mc-border-light);
      background: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      color: #444;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.18s ease;
      touch-action: manipulation;
    }
    .btn-icon-action:hover {
      background: var(--mc-cream);
      border-color: var(--mc-border);
      transform: translateY(-1px);
    }
    .btn-icon-action.btn-toggle-vis:hover {
      color: #1b8a5a;
      background: rgba(27, 138, 90, 0.08);
      border-color: rgba(27, 138, 90, 0.3);
    }
    .btn-icon-action.btn-edit-action:hover {
      color: var(--mc-terracotta);
      background: rgba(199, 97, 61, 0.08);
      border-color: rgba(199, 97, 61, 0.3);
    }
    .btn-icon-action.btn-delete-action:hover {
      color: var(--mc-ruby);
      background: rgba(212, 9, 36, 0.08);
      border-color: rgba(212, 9, 36, 0.3);
    }

    /* =====================================================================
       MOBILE CARDS VIEW (Displayed on screens < 768px instead of table)
       ===================================================================== */
    .menu-mobile-cards-wrap {
      display: none;
      flex-direction: column;
      gap: 12px;
      padding: 14px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
      overflow-x: hidden;
    }
    .menu-mobile-card {
      background: #ffffff;
      border: 1px solid var(--mc-border);
      border-radius: 14px;
      padding: 14px;
      box-shadow: 0 2px 8px rgba(43, 19, 17, 0.04);
      display: flex;
      flex-direction: column;
      gap: 10px;
      position: relative;
      transition: all 0.2s ease;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .menu-mobile-card.menu-card-parent {
      border-left: 4px solid var(--mc-terracotta);
      background: #fdfaf6;
    }
    .menu-mobile-card.menu-card-child {
      margin-left: 8px;
      width: calc(100% - 8px);
      max-width: calc(100% - 8px);
      border-left: 3px solid #e5c46e;
      background: #ffffff;
      position: relative;
      box-sizing: border-box;
    }
    .menu-mobile-card.menu-card-child::before {
      content: '';
      position: absolute;
      left: -8px;
      top: 20px;
      width: 6px;
      height: 2px;
      background: #d8c3b0;
    }
    .menu-mobile-card.row-editing {
      border: 2px solid var(--mc-terracotta) !important;
      background: #fff8f2 !important;
      box-shadow: 0 4px 16px rgba(199, 97, 61, 0.18) !important;
    }
    .mobile-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 10px;
    }
    .mobile-card-title-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      min-width: 0;
    }
    .mobile-card-icon {
      font-size: 1.25rem;
      flex-shrink: 0;
      line-height: 1;
    }
    .mobile-card-title {
      font-size: 0.98rem;
      font-weight: 700;
      color: #2b1311;
      margin: 0;
      word-break: break-word;
    }
    .mobile-card-subtitle {
      font-size: 0.78rem;
      color: #7b6860;
      margin-top: 2px;
    }
    .mobile-card-mid {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
    }
    .mobile-card-url {
      font-family: var(--dc-font-mono, monospace);
      font-size: 0.75rem;
      color: #632420;
      background: var(--mc-cream-dark);
      padding: 3px 8px;
      border-radius: 6px;
      word-break: break-all;
      max-width: 100%;
    }
    .mobile-card-actions-bar {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
      padding-top: 10px;
      border-top: 1px solid var(--mc-border-light);
    }
    .mobile-action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 9px 8px;
      min-height: 44px; /* iOS Human Interface Guidelines 44px min touch target */
      border-radius: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid var(--mc-border);
      background: #ffffff;
      color: #3d1412;
      touch-action: manipulation;
      transition: background 0.15s ease, transform 0.15s ease;
    }
    .mobile-action-btn:active {
      transform: scale(0.97);
    }
    .mobile-action-btn.btn-m-toggle {
      color: #126842;
      background: rgba(27, 138, 90, 0.08);
      border-color: rgba(27, 138, 90, 0.25);
    }
    .mobile-action-btn.btn-m-edit {
      color: var(--mc-terracotta);
      background: rgba(199, 97, 61, 0.08);
      border-color: rgba(199, 97, 61, 0.25);
    }
    .mobile-action-btn.btn-m-delete {
      color: var(--mc-ruby);
      background: rgba(212, 9, 36, 0.08);
      border-color: rgba(212, 9, 36, 0.25);
    }

    /* Live Storefront Preview Box */
    .preview-box {
      background: linear-gradient(135deg, #2b1311 0%, #1a0a09 100%);
      border-radius: 12px;
      padding: 14px 16px;
      color: #ffffff;
      margin-bottom: 18px;
      box-shadow: 0 4px 14px rgba(33, 14, 13, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .preview-box-header {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--mc-gold);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .preview-item-render {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.08);
      padding: 9px 12px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .preview-item-icon {
      font-size: 1.25rem;
      line-height: 1;
      min-width: 22px;
      text-align: center;
    }
    .preview-item-title {
      font-weight: 600;
      font-size: 0.95rem;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    .preview-item-sub {
      font-size: 0.76rem;
      color: rgba(255, 255, 255, 0.65);
      margin-top: 1px;
    }

    /* Emoji Picker Quick Grid */
    .emoji-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }
    .emoji-chip-btn {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 1px solid var(--mc-border);
      background: #ffffff;
      font-size: 15px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: transform 0.15s ease, background 0.15s ease;
      touch-action: manipulation;
    }
    .emoji-chip-btn:hover {
      transform: scale(1.15);
      background: var(--mc-cream);
    }

    /* Color Swatch Picker */
    .color-swatches {
      display: flex;
      gap: 6px;
      margin-top: 6px;
      align-items: center;
    }
    .color-swatch-btn {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid #ffffff;
      box-shadow: 0 0 0 1px #ccc;
      cursor: pointer;
      transition: transform 0.15s ease;
    }
    .color-swatches {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 6px;
      align-items: center;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .color-swatch-btn {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 2px solid #ffffff;
      box-shadow: 0 0 0 1px #ccc;
      cursor: pointer;
      transition: transform 0.15s ease;
      flex-shrink: 0;
    }
    .color-swatch-btn:hover {
      transform: scale(1.2);
    }

    /* URL Quick Shortcuts */
    .url-shortcuts {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 6px;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .url-shortcut-btn {
      background: var(--mc-cream);
      border: 1px solid var(--mc-border);
      border-radius: 6px;
      font-size: 0.72rem;
      color: #5d2522;
      padding: 3px 8px;
      cursor: pointer;
      transition: background 0.15s ease;
      max-width: 100%;
      box-sizing: border-box;
    }
    .url-shortcut-btn:hover {
      background: #eedcc8;
    }

    /* Active Editing Banner in Form Header */
    .editing-status-banner {
      display: none;
      align-items: center;
      justify-content: space-between;
      background: #fff0e2;
      border: 1px solid #f6cfb0;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 14px;
      font-size: 0.85rem;
      color: #8c3b1e;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .editing-status-banner.show {
      display: flex;
    }

    /* Floating Mobile Add Button */
    .mobile-float-add {
      display: none;
      position: fixed;
      bottom: max(20px, env(safe-area-inset-bottom));
      right: 16px;
      z-index: 100;
      background: var(--mc-terracotta);
      color: #ffffff;
      border: 1px solid var(--mc-terracotta-dark);
      padding: 12px 18px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.92rem;
      box-shadow: 0 6px 20px rgba(199, 97, 61, 0.45);
      cursor: pointer;
      touch-action: manipulation;
      transition: transform 0.15s ease;
      max-width: calc(100vw - 32px);
      box-sizing: border-box;
      white-space: nowrap;
    }
    .mobile-float-add:active {
      transform: scale(0.95);
    }

    /* Back to List Button on Form for Mobile */
    .btn-back-to-list {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--mc-terracotta);
      background: transparent;
      border: none;
      padding: 0 0 12px 0;
      cursor: pointer;
      touch-action: manipulation;
    }

    /* Media Queries */
    @media (max-width: 1100px) {
      .menu-manager-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
      .menu-mobile-tabs {
        display: grid;
      }
      .menu-form-panel {
        position: static;
        max-height: none;
        overflow: visible;
      }
      .menu-form-scrollable-body {
        overflow-y: visible;
        padding: 16px;
      }
      .menu-form-footer-bar {
        position: sticky;
        bottom: 0;
        background: #ffffff;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.12);
        z-index: 100;
        border-radius: 0 0 12px 12px;
      }
      /* Tab switching behavior */
      .menu-tab-content-list.tab-hidden,
      .menu-tab-content-form.tab-hidden {
        display: none !important;
      }
      .btn-back-to-list {
        display: inline-flex;
      }
      .mobile-float-add {
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
    }

    @media (max-width: 768px) {
      .menu-page-header {
        flex-direction: column;
        align-items: stretch;
      }
      .menu-header-actions {
        width: 100%;
        justify-content: stretch;
      }
      .menu-header-actions .btn {
        flex: 1;
        justify-content: center;
      }
      .menu-desktop-table-wrap {
        display: none !important;
      }
      .menu-mobile-cards-wrap {
        display: flex !important;
      }
      .card-header {
        padding: 14px 16px;
      }
      .card-body {
        padding: 16px !important;
      }
      #menuItemForm {
        padding: 0 !important;
      }
      /* Prevent iOS Safari 16px auto-zoom on input focus */
      .form-control, select.form-control, input.form-control {
        font-size: 16px !important;
      }
    }

    @media (max-width: 600px) {
      .menu-stats-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px;
        margin-bottom: 16px;
      }
      .menu-stat-pill:last-child {
        grid-column: span 2;
      }
      .menu-stat-pill {
        padding: 8px 10px;
        gap: 8px;
      }
      .menu-stat-val {
        font-size: 1.05rem;
      }
      .menu-stat-lbl {
        font-size: 0.68rem;
      }
      .menu-search-toolbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
      }
      .menu-search-box {
        min-width: 100% !important;
        width: 100% !important;
      }
      #menuVisibilityFilter {
        width: 100% !important;
      }
      .menu-form-row-2 {
        grid-template-columns: 1fr !important;
        gap: 14px;
      }
      .mobile-card-actions-bar {
        gap: 6px;
      }
      .mobile-action-btn {
        padding: 8px 6px;
        font-size: 0.78rem;
      }
      .mobile-float-add {
        bottom: max(16px, env(safe-area-inset-bottom));
        right: 12px;
        padding: 10px 16px;
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body class="admin-page">
<div class="admin-layout" id="adminApp">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php require_once __DIR__ . '/partials/topbar.php'; ?>
    <div class="admin-content">

      <!-- Flash Messages -->
      <?php flashRender(); ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= e($e) ?></div>
      <?php endforeach; ?>

      <!-- ── Page Header ────────────────────────────────────────────── -->
      <div class="menu-page-header">
        <div class="menu-page-header-text">
          <h2>Storefront Navigation Menus</h2>
          <p>Configure links, dropdown submenus, badges, and emojis displayed in desktop navbar and mobile drawer.</p>
        </div>
        <div class="menu-header-actions">
          <a href="<?= url('admin/menus.php?action=seed_defaults&_token=' . $csrfToken) ?>"
             class="btn btn-outline btn-sm"
             onclick="return confirm('Restore all navigation menus and submenus to default Dabhi Chikki storefront configuration? Custom changes will be overwritten.');"
             title="Reset all menus to defaults">
            ↺ Restore Defaults
          </a>
          <button type="button" class="btn btn-primary btn-sm" id="btnHeaderAddNew">
            ➕ Add Menu Item
          </button>
        </div>
      </div>

      <!-- ── Quick Metric Pills ─────────────────────────────────────── -->
      <div class="menu-stats-bar">
        <div class="menu-stat-pill">
          <div class="menu-stat-icon">🧭</div>
          <div class="menu-stat-content">
            <div class="menu-stat-val" id="statValTotal"><?= $statTotalItems ?></div>
            <div class="menu-stat-lbl">Total Items</div>
          </div>
        </div>
        <div class="menu-stat-pill">
          <div class="menu-stat-icon">📁</div>
          <div class="menu-stat-content">
            <div class="menu-stat-val" id="statValParents"><?= $statTotalParents ?></div>
            <div class="menu-stat-lbl">Top-Level Menus</div>
          </div>
        </div>
        <div class="menu-stat-pill">
          <div class="menu-stat-icon">↳</div>
          <div class="menu-stat-content">
            <div class="menu-stat-val" id="statValSubmenus"><?= $statTotalSubmenus ?></div>
            <div class="menu-stat-lbl">Submenus</div>
          </div>
        </div>
        <div class="menu-stat-pill">
          <div class="menu-stat-icon" style="color:#1b8a5a;">🟢</div>
          <div class="menu-stat-content">
            <div class="menu-stat-val" style="color:#1b8a5a;" id="statValVisible"><?= $statTotalVisible ?></div>
            <div class="menu-stat-lbl">Visible</div>
          </div>
        </div>
        <div class="menu-stat-pill">
          <div class="menu-stat-icon" style="color:#777;">🙈</div>
          <div class="menu-stat-content">
            <div class="menu-stat-val" style="color:#777;" id="statValHidden"><?= $statTotalHidden ?></div>
            <div class="menu-stat-lbl">Hidden</div>
          </div>
        </div>
      </div>

      <!-- ── Mobile / Tablet Segmented Tabs ────────────────────────── -->
      <div class="menu-mobile-tabs" id="mobileMenuTabs">
        <button type="button" class="menu-mobile-tab-btn active" id="tabBtnList" data-tab="list">
          📋 Menu Tree <span class="badge-pill" id="tabListBadge"><?= $statTotalItems ?></span>
        </button>
        <button type="button" class="menu-mobile-tab-btn" id="tabBtnForm" data-tab="form">
          <span id="tabFormIcon">➕</span> <span id="tabFormTitleText"><?= $editRow ? 'Edit Item #' . $editRow['id'] : 'Add New Item' ?></span>
        </button>
      </div>

      <!-- ── Main Responsive Grid Layout ───────────────────────────── -->
      <div class="menu-manager-grid">

        <!-- ══════════════════════════════════════════════════════════════
             LEFT COLUMN: MENU HIERARCHY TREE
             ══════════════════════════════════════════════════════════════ -->
        <div class="card menu-tab-content-list" id="menuHierarchyCard">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
              <h3 class="card-title">Live Storefront Hierarchy</h3>
              <p style="font-size:12px;color:#777;margin:3px 0 0 0;">
                Click ✏️ to edit in place, 👁️ to toggle visibility immediately, or 🗑️ to delete.
              </p>
            </div>
            <div style="font-size:12px;color:#8c776e;" id="menuFilterCountText">
              Showing <?= $statTotalItems ?> menu items
            </div>
          </div>

          <!-- Instant Search & Filter Toolbar -->
          <div class="menu-search-toolbar">
            <div class="menu-search-box">
              <span class="search-icon">🔍</span>
              <input type="text" id="menuSearchInput" placeholder="Filter menus by title, URL or badge..." autocomplete="off">
              <button type="button" class="clear-search-btn" id="clearSearchBtn" title="Clear filter">✕</button>
            </div>
            <select id="menuVisibilityFilter" class="form-control" style="width:auto;padding:8px 12px;font-size:13px;border-radius:10px;">
              <option value="all">All Visibility</option>
              <option value="visible">Visible Only (Active)</option>
              <option value="hidden">Hidden Only (Inactive)</option>
            </select>
          </div>

          <!-- Desktop Table View (>= 768px) -->
          <div class="menu-desktop-table-wrap">
            <table class="dc-table" id="menuDesktopTable">
              <thead>
                <tr>
                  <th style="min-width:180px;">Title &amp; Subtitle</th>
                  <th style="min-width:140px;max-width:180px;">Destination URL</th>
                  <th style="width:95px;">Badge / Icon</th>
                  <th style="text-align:center;width:55px;">Sort</th>
                  <th style="text-align:center;width:95px;">Visibility</th>
                  <th style="text-align:right;width:115px;">Actions</th>
                </tr>
              </thead>
              <tbody id="menuTreeTbody">
                <?php if (empty($tree)): ?>
                  <tr id="emptyMenuRow">
                    <td colspan="6" style="text-align:center;padding:40px 20px;color:#888;">
                      <div style="font-size:32px;margin-bottom:8px;">🧭</div>
                      <strong>No menu items found.</strong><br>
                      Click <strong>+ Add Menu Item</strong> or <strong>Restore Defaults</strong> to populate navigation.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($tree as $parent): ?>
                    <?php
                      $parentDataJson = htmlspecialchars(json_encode($parent), ENT_QUOTES, 'UTF-8');
                      $isEditingParent = ($editRow && (int)($editRow['id'] ?? 0) === (int)$parent['id']);
                      $isSavedParent   = ($savedId && $savedId === (int)$parent['id']);
                    ?>
                    <!-- Parent Row -->
                    <tr class="menu-parent-row <?= $isEditingParent ? 'row-editing' : '' ?> <?= $isSavedParent ? 'menu-item-highlight' : '' ?>"
                        id="menu-item-<?= $parent['id'] ?>"
                        data-id="<?= $parent['id'] ?>"
                        data-parent-id=""
                        data-title="<?= e(strtolower($parent['title'])) ?>"
                        data-url="<?= e(strtolower($parent['url'])) ?>"
                        data-badge="<?= e(strtolower($parent['badge'] ?? '')) ?>"
                        data-is-active="<?= (int)$parent['is_active'] ?>"
                        data-menu="<?= $parentDataJson ?>">
                      <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                          <?php if (!empty($parent['icon'])): ?>
                            <span style="font-size:18px;line-height:1;"><?= e($parent['icon']) ?></span>
                          <?php else: ?>
                            <span style="font-size:16px;opacity:0.4;">📁</span>
                          <?php endif; ?>
                          <div>
                            <strong style="color:#2b1311;font-size:0.95rem;" class="item-title-text"><?= e($parent['title']) ?></strong>
                            <?php if (!empty($parent['children'])): ?>
                              <span style="font-size:11px;background:#eedcc8;color:#6b281f;padding:1px 6px;border-radius:10px;margin-left:4px;font-weight:600;">
                                <?= count($parent['children']) ?> submenus
                              </span>
                            <?php endif; ?>
                            <?php if (!empty($parent['subtitle'])): ?>
                              <div style="font-size:11px;color:#7b6860;font-weight:normal;margin-top:1px;"><?= e($parent['subtitle']) ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td>
                        <code style="font-size:11px;color:#541f21;background:#f3eae0;padding:3px 7px;border-radius:5px;display:inline-block;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($parent['url']) ?>">
                          <?= e($parent['url']) ?>
                        </code>
                      </td>
                      <td>
                        <?php if (!empty($parent['badge'])): ?>
                          <span class="badge-preview" style="background-color:<?= e($parent['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                            <?= e($parent['badge']) ?>
                          </span>
                        <?php else: ?>
                          <span style="color:#bbb;font-size:12px;">—</span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align:center;font-weight:600;color:#555;font-size:0.9rem;">
                        <?= (int)$parent['sort_order'] ?>
                      </td>
                      <td style="text-align:center;">
                        <span class="visibility-pill <?= $parent['is_active'] ? 'is-active' : 'is-hidden' ?> btn-quick-toggle"
                              data-id="<?= $parent['id'] ?>"
                              title="Click to toggle visibility immediately">
                          <span class="visibility-dot"></span>
                          <span class="vis-text"><?= $parent['is_active'] ? 'Visible' : 'Hidden' ?></span>
                        </span>
                      </td>
                      <td style="text-align:right;white-space:nowrap;">
                        <div style="display:inline-flex;gap:4px;align-items:center;">
                          <!-- Toggle Button -->
                          <button type="button"
                                  class="btn-icon-action btn-toggle-vis"
                                  data-id="<?= $parent['id'] ?>"
                                  title="<?= $parent['is_active'] ? 'Hide this menu item from storefront' : 'Show this menu item on storefront' ?>">
                            <span class="vis-icon"><?= $parent['is_active'] ? '👁️' : '🙈' ?></span>
                          </button>
                          <!-- Edit Button (Instant in-place fill) -->
                          <button type="button"
                                  class="btn-icon-action btn-edit-action"
                                  data-id="<?= $parent['id'] ?>"
                                  title="Edit this menu item">
                            ✏️
                          </button>
                          <!-- Delete Button (AJAX confirm) -->
                          <button type="button"
                                  class="btn-icon-action btn-delete-action"
                                  data-id="<?= $parent['id'] ?>"
                                  data-title="<?= e($parent['title']) ?>"
                                  title="Delete this menu item">
                            🗑️
                          </button>
                        </div>
                      </td>
                    </tr>

                    <!-- Child Submenus Rows -->
                    <?php if (!empty($parent['children'])): ?>
                      <?php foreach ($parent['children'] as $child): ?>
                        <?php
                          $childDataJson = htmlspecialchars(json_encode($child), ENT_QUOTES, 'UTF-8');
                          $isEditingChild = ($editRow && (int)($editRow['id'] ?? 0) === (int)$child['id']);
                          $isSavedChild   = ($savedId && $savedId === (int)$child['id']);
                        ?>
                        <tr class="menu-child-row <?= $isEditingChild ? 'row-editing' : '' ?> <?= $isSavedChild ? 'menu-item-highlight' : '' ?>"
                            id="menu-item-<?= $child['id'] ?>"
                            data-id="<?= $child['id'] ?>"
                            data-parent-id="<?= $parent['id'] ?>"
                            data-title="<?= e(strtolower($child['title'])) ?>"
                            data-url="<?= e(strtolower($child['url'])) ?>"
                            data-badge="<?= e(strtolower($child['badge'] ?? '')) ?>"
                            data-is-active="<?= (int)$child['is_active'] ?>"
                            data-menu="<?= $childDataJson ?>">
                          <td style="padding-left:26px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                              <span class="menu-indent">↳</span>
                              <?php if (!empty($child['icon'])): ?>
                                <span style="font-size:16px;line-height:1;"><?= e($child['icon']) ?></span>
                              <?php else: ?>
                                <span style="font-size:14px;opacity:0.35;">📄</span>
                              <?php endif; ?>
                              <div>
                                <span style="color:#222;font-size:0.92rem;" class="item-title-text"><?= e($child['title']) ?></span>
                                <?php if (!empty($child['subtitle'])): ?>
                                  <div style="font-size:11px;color:#888;margin-top:1px;"><?= e($child['subtitle']) ?></div>
                                <?php endif; ?>
                              </div>
                            </div>
                          </td>
                          <td>
                            <code style="font-size:11px;color:#666;background:#f5f5f5;padding:3px 7px;border-radius:5px;display:inline-block;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($child['url']) ?>">
                              <?= e($child['url']) ?>
                            </code>
                          </td>
                          <td>
                            <?php if (!empty($child['badge'])): ?>
                              <span class="badge-preview" style="background-color:<?= e($child['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                                <?= e($child['badge']) ?>
                              </span>
                            <?php else: ?>
                              <span style="color:#bbb;font-size:12px;">—</span>
                            <?php endif; ?>
                          </td>
                          <td style="text-align:center;color:#666;font-size:0.9rem;">
                            <?= (int)$child['sort_order'] ?>
                          </td>
                          <td style="text-align:center;">
                            <span class="visibility-pill <?= $child['is_active'] ? 'is-active' : 'is-hidden' ?> btn-quick-toggle"
                                  data-id="<?= $child['id'] ?>"
                                  title="Click to toggle visibility immediately">
                              <span class="visibility-dot"></span>
                              <span class="vis-text"><?= $child['is_active'] ? 'Visible' : 'Hidden' ?></span>
                            </span>
                          </td>
                          <td style="text-align:right;white-space:nowrap;">
                            <div style="display:inline-flex;gap:4px;align-items:center;">
                              <!-- Toggle Button -->
                              <button type="button"
                                      class="btn-icon-action btn-toggle-vis"
                                      data-id="<?= $child['id'] ?>"
                                      title="<?= $child['is_active'] ? 'Hide this submenu from storefront' : 'Show this submenu on storefront' ?>">
                                <span class="vis-icon"><?= $child['is_active'] ? '👁️' : '🙈' ?></span>
                              </button>
                              <!-- Edit Button -->
                              <button type="button"
                                      class="btn-icon-action btn-edit-action"
                                      data-id="<?= $child['id'] ?>"
                                      title="Edit this submenu item">
                                ✏️
                              </button>
                              <!-- Delete Button -->
                              <button type="button"
                                      class="btn-icon-action btn-delete-action"
                                      data-id="<?= $child['id'] ?>"
                                      data-title="<?= e($child['title']) ?>"
                                      title="Delete this submenu item">
                                🗑️
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>

                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div><!-- /menu-desktop-table-wrap -->

          <!-- Mobile Cards View (< 768px) -->
          <div class="menu-mobile-cards-wrap" id="menuMobileCardsWrap">
            <?php if (empty($tree)): ?>
              <div style="text-align:center;padding:32px 16px;color:#888;">
                No menu items found. Tap <strong>+ Add New Item</strong> to get started.
              </div>
            <?php else: ?>
              <?php foreach ($tree as $parent): ?>
                <?php
                  $parentDataJson = htmlspecialchars(json_encode($parent), ENT_QUOTES, 'UTF-8');
                  $isEditingParent = ($editRow && (int)($editRow['id'] ?? 0) === (int)$parent['id']);
                  $isSavedParent   = ($savedId && $savedId === (int)$parent['id']);
                ?>
                <!-- Parent Mobile Card -->
                <div class="menu-mobile-card menu-card-parent <?= $isEditingParent ? 'row-editing' : '' ?> <?= $isSavedParent ? 'menu-item-highlight' : '' ?>"
                     id="m-menu-item-<?= $parent['id'] ?>"
                     data-id="<?= $parent['id'] ?>"
                     data-parent-id=""
                     data-title="<?= e(strtolower($parent['title'])) ?>"
                     data-url="<?= e(strtolower($parent['url'])) ?>"
                     data-badge="<?= e(strtolower($parent['badge'] ?? '')) ?>"
                     data-is-active="<?= (int)$parent['is_active'] ?>"
                     data-menu="<?= $parentDataJson ?>">
                  <div class="mobile-card-top">
                    <div class="mobile-card-title-wrap">
                      <span class="mobile-card-icon"><?= $parent['icon'] ? e($parent['icon']) : '📁' ?></span>
                      <div>
                        <h4 class="mobile-card-title item-title-text"><?= e($parent['title']) ?></h4>
                        <?php if (!empty($parent['subtitle'])): ?>
                          <div class="mobile-card-subtitle"><?= e($parent['subtitle']) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div>
                      <span class="visibility-pill <?= $parent['is_active'] ? 'is-active' : 'is-hidden' ?> btn-quick-toggle"
                            data-id="<?= $parent['id'] ?>">
                        <span class="visibility-dot"></span>
                        <span class="vis-text"><?= $parent['is_active'] ? 'Visible' : 'Hidden' ?></span>
                      </span>
                    </div>
                  </div>

                  <div class="mobile-card-mid">
                    <span class="mobile-card-url"><?= e($parent['url']) ?></span>
                    <?php if (!empty($parent['badge'])): ?>
                      <span class="badge-preview" style="background-color:<?= e($parent['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                        <?= e($parent['badge']) ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($parent['children'])): ?>
                      <span style="font-size:11px;background:#eedcc8;color:#6b281f;padding:2px 7px;border-radius:10px;font-weight:600;">
                        ↳ <?= count($parent['children']) ?> submenus
                      </span>
                    <?php endif; ?>
                    <span style="font-size:11px;color:#888;margin-left:auto;">Sort: <?= (int)$parent['sort_order'] ?></span>
                  </div>

                  <div class="mobile-card-actions-bar">
                    <button type="button" class="mobile-action-btn btn-m-toggle btn-toggle-vis" data-id="<?= $parent['id'] ?>">
                      <span class="vis-icon"><?= $parent['is_active'] ? '👁️' : '🙈' ?></span>
                      <span class="vis-label"><?= $parent['is_active'] ? 'Hide' : 'Show' ?></span>
                    </button>
                    <button type="button" class="mobile-action-btn btn-m-edit btn-edit-action" data-id="<?= $parent['id'] ?>">
                      ✏️ Edit
                    </button>
                    <button type="button" class="mobile-action-btn btn-m-delete btn-delete-action" data-id="<?= $parent['id'] ?>" data-title="<?= e($parent['title']) ?>">
                      🗑️ Delete
                    </button>
                  </div>
                </div>

                <!-- Child Mobile Cards -->
                <?php if (!empty($parent['children'])): ?>
                  <?php foreach ($parent['children'] as $child): ?>
                    <?php
                      $childDataJson = htmlspecialchars(json_encode($child), ENT_QUOTES, 'UTF-8');
                      $isEditingChild = ($editRow && (int)($editRow['id'] ?? 0) === (int)$child['id']);
                      $isSavedChild   = ($savedId && $savedId === (int)$child['id']);
                    ?>
                    <div class="menu-mobile-card menu-card-child <?= $isEditingChild ? 'row-editing' : '' ?> <?= $isSavedChild ? 'menu-item-highlight' : '' ?>"
                         id="m-menu-item-<?= $child['id'] ?>"
                         data-id="<?= $child['id'] ?>"
                         data-parent-id="<?= $parent['id'] ?>"
                         data-title="<?= e(strtolower($child['title'])) ?>"
                         data-url="<?= e(strtolower($child['url'])) ?>"
                         data-badge="<?= e(strtolower($child['badge'] ?? '')) ?>"
                         data-is-active="<?= (int)$child['is_active'] ?>"
                         data-menu="<?= $childDataJson ?>">
                      <div class="mobile-card-top">
                        <div class="mobile-card-title-wrap">
                          <span class="mobile-card-icon"><?= $child['icon'] ? e($child['icon']) : '↳' ?></span>
                          <div>
                            <h4 class="mobile-card-title item-title-text"><?= e($child['title']) ?></h4>
                            <div style="font-size:11px;color:#a44b2d;font-weight:600;">under: <?= e($parent['title']) ?></div>
                            <?php if (!empty($child['subtitle'])): ?>
                              <div class="mobile-card-subtitle"><?= e($child['subtitle']) ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div>
                          <span class="visibility-pill <?= $child['is_active'] ? 'is-active' : 'is-hidden' ?> btn-quick-toggle"
                                data-id="<?= $child['id'] ?>">
                            <span class="visibility-dot"></span>
                            <span class="vis-text"><?= $child['is_active'] ? 'Visible' : 'Hidden' ?></span>
                          </span>
                        </div>
                      </div>

                      <div class="mobile-card-mid">
                        <span class="mobile-card-url"><?= e($child['url']) ?></span>
                        <?php if (!empty($child['badge'])): ?>
                          <span class="badge-preview" style="background-color:<?= e($child['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                            <?= e($child['badge']) ?>
                          </span>
                        <?php endif; ?>
                        <span style="font-size:11px;color:#888;margin-left:auto;">Sort: <?= (int)$child['sort_order'] ?></span>
                      </div>

                      <div class="mobile-card-actions-bar">
                        <button type="button" class="mobile-action-btn btn-m-toggle btn-toggle-vis" data-id="<?= $child['id'] ?>">
                          <span class="vis-icon"><?= $child['is_active'] ? '👁️' : '🙈' ?></span>
                          <span class="vis-label"><?= $child['is_active'] ? 'Hide' : 'Show' ?></span>
                        </button>
                        <button type="button" class="mobile-action-btn btn-m-edit btn-edit-action" data-id="<?= $child['id'] ?>">
                          ✏️ Edit
                        </button>
                        <button type="button" class="mobile-action-btn btn-m-delete btn-delete-action" data-id="<?= $child['id'] ?>" data-title="<?= e($child['title']) ?>">
                          🗑️ Delete
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php endif; ?>
          </div><!-- /menu-mobile-cards-wrap -->

        </div><!-- /card (Hierarchy) -->


        <!-- ══════════════════════════════════════════════════════════════
             RIGHT COLUMN: STICKY ADD / EDIT FORM PANEL
             ══════════════════════════════════════════════════════════════ -->
        <div class="card menu-form-panel menu-tab-content-form tab-hidden" id="menuFormPanel">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>
              <button type="button" class="btn-back-to-list" id="btnBackToList">
                ← Back to Hierarchy
              </button>
              <h3 class="card-title" id="formHeaderTitle">
                <?= $editRow ? '✏️ Edit Menu Item' : '➕ Add New Menu Item' ?>
              </h3>
              <p style="font-size:12px;color:#777;margin:3px 0 0 0;" id="formHeaderSubtitle">
                <?= $editRow ? 'Updating settings for item #' . $editRow['id'] : 'Create top-level link or dropdown submenu' ?>
              </p>
            </div>
            <button type="button"
                    class="btn btn-outline btn-sm"
                    id="btnCancelEdit"
                    style="<?= $editRow ? '' : 'display:none;' ?>font-size:12px;padding:4px 10px;">
              ✕ Cancel
            </button>
          </div>

          <form action="<?= url('admin/menus.php') ?>" method="POST" id="menuItemForm" style="display:flex;flex-direction:column;flex:1;min-height:0;margin:0;">
            <?= csrfField() ?>
            <input type="hidden" name="_token" id="formCsrfToken" value="<?= $csrfToken ?>">
            <input type="hidden" name="menu_id" id="formMenuId" value="<?= e($editRow['id'] ?? '') ?>">

            <div class="menu-form-scrollable-body">
              <!-- Active Editing Status Banner -->
              <div class="editing-status-banner <?= $editRow ? 'show' : '' ?>" id="editingStatusBanner">
                <span id="editingStatusText">Currently Editing: <strong><?= e($editRow['title'] ?? '') ?></strong> (#<?= e($editRow['id'] ?? '') ?>)</span>
                <button type="button" class="btn btn-ghost btn-sm" id="btnBannerCancel" style="padding:2px 8px;font-size:11px;color:#8c3b1e;">Cancel</button>
              </div>

              <!-- Live Storefront Navigation Preview Box -->
              <div class="preview-box">
                <div class="preview-box-header">
                  <span>Storefront Link Preview</span>
                  <span style="font-size:10px;opacity:0.7;">Live update</span>
                </div>
                <div class="preview-item-render">
                  <span class="preview-item-icon" id="previewIcon"><?= !empty($editRow['icon']) ? e($editRow['icon']) : '🥜' ?></span>
                  <div style="flex:1;min-width:0;">
                    <div class="preview-item-title">
                      <span id="previewTitle"><?= !empty($editRow['title']) ? e($editRow['title']) : 'Menu Title Preview' ?></span>
                      <span class="badge-preview" id="previewBadge" style="<?= empty($editRow['badge']) ? 'display:none;' : '' ?>background-color:<?= e($editRow['badge_color'] ?: '#c7613d') ?>;color:#fff;">
                        <?= e($editRow['badge'] ?? '') ?>
                      </span>
                    </div>
                    <div class="preview-item-sub" id="previewSubtitle" style="<?= empty($editRow['subtitle']) ? 'display:none;' : '' ?>">
                      <?= e($editRow['subtitle'] ?? '') ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Title Field -->
              <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;display:flex;justify-content:space-between;">
                  <span>Title <span style="color:#d40924;">*</span></span>
                  <span style="font-size:11px;color:#888;font-weight:normal;">Shown in nav</span>
                </label>
                <input type="text"
                       name="title"
                       id="inputTitle"
                       class="form-control"
                       value="<?= e($editRow['title'] ?? '') ?>"
                       placeholder="e.g. Our Chikki, Mandvi Peanut Chikki"
                       required>
              </div>

              <!-- URL Field with Shortcut Chips -->
              <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;display:flex;justify-content:space-between;">
                  <span>Link / URL <span style="color:#d40924;">*</span></span>
                  <span style="font-size:11px;color:#888;font-weight:normal;">Destination</span>
                </label>
                <input type="text"
                       name="url"
                       id="inputUrl"
                       class="form-control"
                       value="<?= e($editRow['url'] ?? '') ?>"
                       placeholder="e.g. index.php#products, track.php"
                       required>
                <div class="url-shortcuts">
                  <span style="font-size:11px;color:#777;align-self:center;">Shortcuts:</span>
                  <button type="button" class="url-shortcut-btn" data-url="index.php">Home</button>
                  <button type="button" class="url-shortcut-btn" data-url="index.php#products">Products</button>
                  <button type="button" class="url-shortcut-btn" data-url="track.php">Track Order</button>
                  <button type="button" class="url-shortcut-btn" data-url="about.php">About Us</button>
                  <button type="button" class="url-shortcut-btn" data-url="contact.php">Contact</button>
                </div>
              </div>

              <!-- Parent Dropdown -->
              <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;">Parent Menu</label>
                <select name="parent_id" id="inputParentId" class="form-control form-select">
                  <option value="">— None (Top Level Menu) —</option>
                  <?php foreach ($parentList as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (($editRow['parent_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                      Submenu under: <?= e($p['title']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="form-hint" style="font-size:11px;color:#888;margin-top:4px;">
                  Choose parent to nest inside a dropdown on storefront.
                </p>
              </div>

              <!-- Icon / Emoji Picker -->
              <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;display:flex;justify-content:space-between;">
                  <span>Icon / Emoji</span>
                  <span style="font-size:11px;color:#888;font-weight:normal;">Optional</span>
                </label>
                <div style="display:flex;gap:8px;">
                  <input type="text"
                         name="icon"
                         id="inputIcon"
                         class="form-control"
                         value="<?= e($editRow['icon'] ?? '') ?>"
                         placeholder="e.g. 🥜, 📦, ✨, 🎁"
                         style="font-size:1.1rem;min-width:0;flex:1;">
                  <button type="button"
                          class="btn btn-outline btn-sm"
                          id="btnClearIcon"
                          style="white-space:nowrap;padding:0 12px;font-size:12px;flex-shrink:0;">
                    ✕ Clear
                  </button>
                </div>
                <div class="emoji-chips">
                  <button type="button" class="emoji-chip-btn" data-emoji="🥜">🥜</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="📦">📦</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="🎁">🎁</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="✨">✨</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="🔥">🔥</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="🏷️">🏷️</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="ℹ️">ℹ️</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="📞">📞</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="📍">📍</button>
                  <button type="button" class="emoji-chip-btn" data-emoji="🛒">🛒</button>
                </div>
              </div>

              <!-- Sort Order & Open In Grid -->
              <div class="menu-form-row-2">
                <div class="form-group">
                  <label class="form-label" style="font-weight:600;">Sort Order</label>
                  <input type="number"
                         name="sort_order"
                         id="inputSortOrder"
                         class="form-control"
                         value="<?= (int)($editRow['sort_order'] ?? 0) ?>"
                         min="0">
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight:600;">Open Target</label>
                  <select name="target" id="inputTarget" class="form-control form-select">
                    <option value="_self" <?= (($editRow['target'] ?? '_self') === '_self') ? 'selected' : '' ?>>Same Tab (_self)</option>
                    <option value="_blank" <?= (($editRow['target'] ?? '') === '_blank') ? 'selected' : '' ?>>New Tab (_blank)</option>
                  </select>
                </div>
              </div>

              <!-- Subtitle / Hint -->
              <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;display:flex;justify-content:space-between;">
                  <span>Subtitle / Hint</span>
                  <span style="font-size:11px;color:#888;font-weight:normal;">Dropdown note</span>
                </label>
                <input type="text"
                       name="subtitle"
                       id="inputSubtitle"
                       class="form-control"
                       value="<?= e($editRow['subtitle'] ?? '') ?>"
                       placeholder="e.g. Classic Saurashtra crunch, 100% Pure Jaggery">
              </div>

              <!-- Badge Text & Badge Color Grid -->
              <div class="menu-form-row-2">
                <div class="form-group">
                  <label class="form-label" style="font-weight:600;">Badge Text</label>
                  <input type="text"
                         name="badge"
                         id="inputBadge"
                         class="form-control"
                         value="<?= e($editRow['badge'] ?? '') ?>"
                         placeholder="e.g. Bestseller, New">
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight:600;">Badge Color</label>
                  <div style="display:flex;gap:6px;align-items:center;">
                    <input type="text"
                           name="badge_color"
                           id="inputBadgeColor"
                           class="form-control"
                           value="<?= e($editRow['badge_color'] ?? '#c7613d') ?>"
                           placeholder="#c7613d"
                           style="min-width:0;flex:1;">
                    <input type="color"
                           id="inputColorPicker"
                           value="<?= !empty($editRow['badge_color']) ? e($editRow['badge_color']) : '#c7613d' ?>"
                           style="width:36px;height:38px;padding:0;border:none;border-radius:6px;cursor:pointer;flex-shrink:0;">
                  </div>
                  <div class="color-swatches">
                    <button type="button" class="color-swatch-btn" data-color="#c7613d" style="background:#c7613d;" title="Terracotta"></button>
                    <button type="button" class="color-swatch-btn" data-color="#d49b29" style="background:#d49b29;" title="Gold"></button>
                    <button type="button" class="color-swatch-btn" data-color="#1b8a5a" style="background:#1b8a5a;" title="Emerald"></button>
                    <button type="button" class="color-swatch-btn" data-color="#c53434" style="background:#c53434;" title="Red"></button>
                    <button type="button" class="color-swatch-btn" data-color="#2b70a8" style="background:#2b70a8;" title="Navy"></button>
                  </div>
                </div>
              </div>

              <!-- Visibility Selection -->
              <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label" style="font-weight:600;">Storefront Visibility</label>
                <select name="is_active" id="inputIsActive" class="form-control form-select">
                  <option value="1" <?= (($editRow['is_active'] ?? 1) == 1) ? 'selected' : '' ?>>Visible (Active on website)</option>
                  <option value="0" <?= (($editRow['is_active'] ?? 1) == 0) ? 'selected' : '' ?>>Hidden (Draft / Inactive)</option>
                </select>
              </div>

            </div><!-- /menu-form-scrollable-body -->

            <!-- Sticky Submit / Cancel Action Bar -->
            <div class="menu-form-footer-bar" id="menuFormFooterBar">
              <button type="submit" class="btn btn-primary" id="btnSubmitForm">
                <span class="btn-sub-icon"><?= $editRow ? '💾' : '➕' ?></span>
                <span class="btn-sub-text"><?= $editRow ? 'Update Menu Item' : 'Save Menu Item' ?></span>
              </button>
              <button type="button" class="btn btn-outline" id="btnFormCancelBottom" style="<?= $editRow ? '' : 'display:none;' ?>">
                ✕ Cancel
              </button>
            </div>

          </form>
        </div><!-- /card (Form) -->

      </div><!-- /menu-manager-grid -->

      <!-- Mobile Floating Add Button -->
      <button type="button" class="mobile-float-add" id="mobileFloatAddBtn">
        ➕ Add Menu Item
      </button>

    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div><!-- /admin-layout -->

<script>
(function() {
  'use strict';

  // ── DOM References ──────────────────────────────────────────────────────────
  const csrfToken         = <?= json_encode($csrfToken) ?>;
  const baseUrl           = <?= json_encode(url('admin/menus.php')) ?>;
  const initialEditRow    = <?= json_encode($editRow) ?>;
  const initialSavedId    = <?= json_encode($savedId) ?>;

  const form              = document.getElementById('menuItemForm');
  const formMenuId        = document.getElementById('formMenuId');
  const formHeaderTitle   = document.getElementById('formHeaderTitle');
  const formHeaderSub     = document.getElementById('formHeaderSubtitle');
  const btnSubmitForm     = document.getElementById('btnSubmitForm');
  const btnCancelEdit     = document.getElementById('btnCancelEdit');
  const btnCancelBottom   = document.getElementById('btnFormCancelBottom');
  const btnBannerCancel   = document.getElementById('btnBannerCancel');
  const statusBanner      = document.getElementById('editingStatusBanner');
  const statusBannerText  = document.getElementById('editingStatusText');
  const btnHeaderAddNew   = document.getElementById('btnHeaderAddNew');
  const mobileFloatAddBtn = document.getElementById('mobileFloatAddBtn');
  const btnBackToList     = document.getElementById('btnBackToList');

  const tabBtnList        = document.getElementById('tabBtnList');
  const tabBtnForm        = document.getElementById('tabBtnForm');
  const tabFormTitleText  = document.getElementById('tabFormTitleText');
  const tabFormIcon       = document.getElementById('tabFormIcon');
  const cardHierarchy     = document.getElementById('menuHierarchyCard');
  const cardFormPanel     = document.getElementById('menuFormPanel');

  const inputTitle        = document.getElementById('inputTitle');
  const inputUrl          = document.getElementById('inputUrl');
  const inputParentId     = document.getElementById('inputParentId');
  const inputIcon         = document.getElementById('inputIcon');
  const inputSortOrder    = document.getElementById('inputSortOrder');
  const inputSubtitle     = document.getElementById('inputSubtitle');
  const inputBadge        = document.getElementById('inputBadge');
  const inputBadgeColor   = document.getElementById('inputBadgeColor');
  const inputColorPicker  = document.getElementById('inputColorPicker');
  const inputTarget       = document.getElementById('inputTarget');
  const inputIsActive     = document.getElementById('inputIsActive');
  const btnClearIcon      = document.getElementById('btnClearIcon');

  const previewIcon       = document.getElementById('previewIcon');
  const previewTitle      = document.getElementById('previewTitle');
  const previewBadge      = document.getElementById('previewBadge');
  const previewSubtitle   = document.getElementById('previewSubtitle');

  const searchInput       = document.getElementById('menuSearchInput');
  const clearSearchBtn    = document.getElementById('clearSearchBtn');
  const visFilter         = document.getElementById('menuVisibilityFilter');
  const filterCountText   = document.getElementById('menuFilterCountText');

  // ── Tab Management for Mobile / Tablet (< 1100px) ─────────────────────────
  let activeEditingItemId = initialEditRow ? initialEditRow.id : null;
  let currentMobileTab = (activeEditingItemId || initialEditRow) ? 'form' : 'list';

  function switchMobileTab(tab, shouldScroll = false) {
    currentMobileTab = tab;
    if (tab === 'form') {
      if (tabBtnForm) tabBtnForm.classList.add('active');
      if (tabBtnList) tabBtnList.classList.remove('active');
      if (cardHierarchy) cardHierarchy.classList.add('tab-hidden');
      if (cardFormPanel) cardFormPanel.classList.remove('tab-hidden');
      if (mobileFloatAddBtn) mobileFloatAddBtn.style.display = 'none';
      if (shouldScroll && cardFormPanel) {
        cardFormPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } else {
      if (tabBtnList) tabBtnList.classList.add('active');
      if (tabBtnForm) tabBtnForm.classList.remove('active');
      if (cardHierarchy) cardHierarchy.classList.remove('tab-hidden');
      if (cardFormPanel) cardFormPanel.classList.add('tab-hidden');
      if (mobileFloatAddBtn && window.innerWidth <= 1100) {
        mobileFloatAddBtn.style.display = 'inline-flex';
      }
      if (shouldScroll && cardHierarchy) {
        cardHierarchy.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  }

  if (tabBtnList) {
    tabBtnList.addEventListener('click', () => switchMobileTab('list', true));
  }
  if (tabBtnForm) {
    tabBtnForm.addEventListener('click', () => switchMobileTab('form', true));
  }
  if (btnBackToList) {
    btnBackToList.addEventListener('click', () => switchMobileTab('list', true));
  }
  if (mobileFloatAddBtn) {
    mobileFloatAddBtn.addEventListener('click', () => {
      resetFormToAdd();
      switchMobileTab('form', true);
    });
  }

  // Handle Initial view on load for tablet/mobile
  function checkResponsiveTabs() {
    if (window.innerWidth <= 1100) {
      if (activeEditingItemId || initialEditRow) {
        switchMobileTab('form', false);
      } else {
        switchMobileTab(currentMobileTab || 'list', false);
      }
    } else {
      // Desktop: both are visible
      if (cardHierarchy) cardHierarchy.classList.remove('tab-hidden');
      if (cardFormPanel) cardFormPanel.classList.remove('tab-hidden');
      if (mobileFloatAddBtn) mobileFloatAddBtn.style.display = 'none';
    }
  }

  // Guard against vertical-only resize events (such as mobile virtual keyboard or address bar scrolling)
  let lastInnerWidth = window.innerWidth;
  window.addEventListener('resize', () => {
    const newWidth = window.innerWidth;
    if (newWidth === lastInnerWidth) {
      return; // Pure height change (virtual keyboard or URL bar) -> DO NOT TOUCH TABS OR SCROLL!
    }
    lastInnerWidth = newWidth;
    checkResponsiveTabs();
  });
  checkResponsiveTabs();

  // ── Live Storefront Preview Update ─────────────────────────────────────────
  function updatePreview() {
    const icon = inputIcon.value.trim();
    previewIcon.textContent = icon || '🥜';

    const title = inputTitle.value.trim();
    previewTitle.textContent = title || 'Menu Title Preview';

    const badge = inputBadge.value.trim();
    const badgeColor = inputBadgeColor.value.trim() || '#c7613d';
    if (badge) {
      previewBadge.style.display = 'inline-block';
      previewBadge.textContent = badge;
      previewBadge.style.backgroundColor = badgeColor;
    } else {
      previewBadge.style.display = 'none';
    }

    const sub = inputSubtitle.value.trim();
    if (sub) {
      previewSubtitle.style.display = 'block';
      previewSubtitle.textContent = sub;
    } else {
      previewSubtitle.style.display = 'none';
    }
  }

  [inputTitle, inputIcon, inputBadge, inputBadgeColor, inputSubtitle].forEach(el => {
    if (el) el.addEventListener('input', updatePreview);
  });

  if (inputColorPicker) {
    inputColorPicker.addEventListener('input', e => {
      inputBadgeColor.value = e.target.value;
      updatePreview();
    });
  }
  if (inputBadgeColor) {
    inputBadgeColor.addEventListener('input', e => {
      if (/^#[0-9a-f]{6}$/i.test(e.target.value)) {
        inputColorPicker.value = e.target.value;
      }
    });
  }

  // Swatch buttons
  document.querySelectorAll('.color-swatch-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const color = btn.getAttribute('data-color');
      inputBadgeColor.value = color;
      inputColorPicker.value = color;
      updatePreview();
    });
  });

  // Emoji chips
  document.querySelectorAll('.emoji-chip-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      inputIcon.value = btn.getAttribute('data-emoji');
      updatePreview();
    });
  });

  // Clear icon
  if (btnClearIcon) {
    btnClearIcon.addEventListener('click', () => {
      inputIcon.value = '';
      updatePreview();
    });
  }

  // URL Shortcuts
  document.querySelectorAll('.url-shortcut-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      inputUrl.value = btn.getAttribute('data-url');
    });
  });

  // ── Populate Form for In-Place Edit (Zero Page Reload!) ─────────────────────
  function startEditItem(item) {
    if (!item) return;
    activeEditingItemId = item.id;

    // Remove any previous editing row highlight
    document.querySelectorAll('.row-editing').forEach(el => el.classList.remove('row-editing'));

    // Highlight row on desktop table and mobile card
    const desktopRow = document.getElementById('menu-item-' + item.id);
    const mobileCard = document.getElementById('m-menu-item-' + item.id);
    if (desktopRow) desktopRow.classList.add('row-editing');
    if (mobileCard) mobileCard.classList.add('row-editing');

    // Populate form fields
    formMenuId.value      = item.id;
    inputTitle.value      = item.title || '';
    inputUrl.value        = item.url || '';
    inputParentId.value   = item.parent_id || '';
    inputIcon.value       = item.icon || '';
    inputSortOrder.value  = item.sort_order || 0;
    inputSubtitle.value   = item.subtitle || '';
    inputBadge.value      = item.badge || '';
    inputBadgeColor.value = item.badge_color || '#c7613d';
    if (inputColorPicker && item.badge_color) inputColorPicker.value = item.badge_color;
    inputTarget.value     = item.target || '_self';
    inputIsActive.value   = item.is_active !== undefined ? item.is_active : 1;

    // Prevent selecting self as parent
    Array.from(inputParentId.options).forEach(opt => {
      opt.disabled = (opt.value && parseInt(opt.value, 10) === parseInt(item.id, 10));
    });

    // Update UI headers
    formHeaderTitle.textContent  = '✏️ Edit Menu Item';
    formHeaderSub.textContent    = 'Updating settings for #' + item.id + ' (' + item.title + ')';
    btnSubmitForm.innerHTML      = '<span class="btn-sub-icon">💾</span> <span class="btn-sub-text">Update Menu Item</span>';
    if (btnCancelEdit) btnCancelEdit.style.display = 'inline-flex';
    if (btnCancelBottom) btnCancelBottom.style.display = 'inline-flex';

    if (statusBanner) {
      statusBanner.classList.add('show');
      statusBannerText.innerHTML = 'Currently Editing: <strong>' + escapeHtml(item.title) + '</strong> (#' + item.id + ')';
    }

    if (tabFormIcon) tabFormIcon.textContent = '✏️';
    if (tabFormTitleText) tabFormTitleText.textContent = 'Edit #' + item.id;

    updatePreview();

    // Update URL hash / history without reloading page
    history.replaceState(null, '', baseUrl + '?action=edit&id=' + item.id);

    // If on mobile/tablet (< 1100px), switch to form view and gently ensure form panel is in view
    if (window.innerWidth <= 1100) {
      switchMobileTab('form', false);
      if (cardFormPanel) {
        cardFormPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } else {
      // Desktop: smoothly ensure form is in view and focus title
      cardFormPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      const scrollableBody = cardFormPanel.querySelector('.menu-form-scrollable-body');
      if (scrollableBody) scrollableBody.scrollTop = 0;
      inputTitle.focus();
    }
  }

  // ── Reset Form to "Add New Item" ──────────────────────────────────────────
  function resetFormToAdd() {
    activeEditingItemId = null;
    document.querySelectorAll('.row-editing').forEach(el => el.classList.remove('row-editing'));

    formMenuId.value      = '';
    inputTitle.value      = '';
    inputUrl.value        = '';
    inputParentId.value   = '';
    inputIcon.value       = '';
    inputSortOrder.value  = 0;
    inputSubtitle.value   = '';
    inputBadge.value      = '';
    inputBadgeColor.value = '#c7613d';
    if (inputColorPicker) inputColorPicker.value = '#c7613d';
    inputTarget.value     = '_self';
    inputIsActive.value   = '1';

    // Re-enable all parent options
    Array.from(inputParentId.options).forEach(opt => { opt.disabled = false; });

    formHeaderTitle.textContent  = '➕ Add New Menu Item';
    formHeaderSub.textContent    = 'Create a top-level link or dropdown submenu';
    btnSubmitForm.innerHTML      = '<span class="btn-sub-icon">➕</span> <span class="btn-sub-text">Save Menu Item</span>';
    if (btnCancelEdit) btnCancelEdit.style.display = 'none';
    if (btnCancelBottom) btnCancelBottom.style.display = 'none';
    if (statusBanner) statusBanner.classList.remove('show');

    if (tabFormIcon) tabFormIcon.textContent = '➕';
    if (tabFormTitleText) tabFormTitleText.textContent = 'Add New Item';

    updatePreview();
    history.replaceState(null, '', baseUrl);
  }

  [btnCancelEdit, btnCancelBottom, btnBannerCancel].forEach(btn => {
    if (btn) {
      btn.addEventListener('click', () => {
        const prevId = activeEditingItemId;
        resetFormToAdd();
        if (window.innerWidth <= 1100) {
          switchMobileTab('list');
          if (prevId) {
            const targetEl = document.getElementById('m-menu-item-' + prevId) || document.getElementById('menu-item-' + prevId);
            if (targetEl) targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      });
    }
  });

  if (btnHeaderAddNew) {
    btnHeaderAddNew.addEventListener('click', () => {
      resetFormToAdd();
      if (window.innerWidth <= 1024) {
        switchMobileTab('form');
      } else {
        inputTitle.focus();
      }
    });
  }

  // ── Delegated Click Handler for Edit, Toggle, and Delete Buttons ───────────
  document.addEventListener('click', function(e) {
    // 1. Edit button clicked
    const editBtn = e.target.closest('.btn-edit-action');
    if (editBtn) {
      e.preventDefault();
      const parentRow = editBtn.closest('[data-menu]');
      if (parentRow && parentRow.dataset.menu) {
        try {
          const item = JSON.parse(parentRow.dataset.menu);
          startEditItem(item);
        } catch (err) {
          console.error('Error parsing menu data', err);
        }
      }
      return;
    }

    // 2. Toggle Visibility button or pill clicked
    const toggleBtn = e.target.closest('.btn-toggle-vis, .btn-quick-toggle');
    if (toggleBtn) {
      e.preventDefault();
      const id = toggleBtn.getAttribute('data-id');
      if (!id) return;
      toggleVisibility(id);
      return;
    }

    // 3. Delete button clicked
    const deleteBtn = e.target.closest('.btn-delete-action');
    if (deleteBtn) {
      e.preventDefault();
      const id = deleteBtn.getAttribute('data-id');
      const title = deleteBtn.getAttribute('data-title') || 'this menu item';
      if (!id) return;
      confirmDelete(id, title);
      return;
    }
  });

  // ── AJAX Visibility Toggle (In-Place, No Reload, No Scroll) ────────────────
  function toggleVisibility(id) {
    const desktopRow = document.getElementById('menu-item-' + id);
    const mobileCard = document.getElementById('m-menu-item-' + id);

    // Show quick feedback
    const pills = document.querySelectorAll('[data-id="' + id + '"] .visibility-pill, .btn-quick-toggle[data-id="' + id + '"]');
    pills.forEach(p => p.style.opacity = '0.5');

    fetch(baseUrl + '?action=toggle&id=' + id + '&_token=' + csrfToken, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      pills.forEach(p => p.style.opacity = '1');
      if (data && data.ok) {
        const isActive = data.is_active === 1;

        // Update Desktop row
        if (desktopRow) {
          desktopRow.dataset.isActive = isActive ? '1' : '0';
          const rowPill = desktopRow.querySelector('.visibility-pill');
          if (rowPill) {
            rowPill.className = 'visibility-pill ' + (isActive ? 'is-active' : 'is-hidden') + ' btn-quick-toggle';
            const visText = rowPill.querySelector('.vis-text');
            if (visText) visText.textContent = isActive ? 'Visible' : 'Hidden';
          }
          const toggleIcon = desktopRow.querySelector('.btn-toggle-vis .vis-icon');
          if (toggleIcon) toggleIcon.textContent = isActive ? '👁️' : '🙈';
        }

        // Update Mobile card
        if (mobileCard) {
          mobileCard.dataset.isActive = isActive ? '1' : '0';
          const cardPill = mobileCard.querySelector('.visibility-pill');
          if (cardPill) {
            cardPill.className = 'visibility-pill ' + (isActive ? 'is-active' : 'is-hidden') + ' btn-quick-toggle';
            const visText = cardPill.querySelector('.vis-text');
            if (visText) visText.textContent = isActive ? 'Visible' : 'Hidden';
          }
          const mToggleIcon = mobileCard.querySelector('.btn-toggle-vis .vis-icon');
          if (mToggleIcon) mToggleIcon.textContent = isActive ? '👁️' : '🙈';
          const mToggleLabel = mobileCard.querySelector('.btn-toggle-vis .vis-label');
          if (mToggleLabel) mToggleLabel.textContent = isActive ? 'Hide' : 'Show';
        }

        // Update data-menu on elements
        [desktopRow, mobileCard].forEach(el => {
          if (el && el.dataset.menu) {
            try {
              const d = JSON.parse(el.dataset.menu);
              d.is_active = isActive ? 1 : 0;
              el.dataset.menu = JSON.stringify(d);
            } catch(e) {}
          }
        });

        // Recalculate stats counters
        recalculateStats();

        // Toast notification
        if (window.showToast) {
          window.showToast('"' + (data.title || 'Item') + '" is now ' + (isActive ? 'Visible' : 'Hidden') + ' on storefront!', 'success');
        }
      } else {
        alert(data.message || 'Could not update visibility.');
      }
    })
    .catch(err => {
      pills.forEach(p => p.style.opacity = '1');
      console.error(err);
      // Fallback: normal link redirect
      window.location.href = baseUrl + '?action=toggle&id=' + id + '&_token=' + csrfToken;
    });
  }

  // ── AJAX Delete (In-Place, Smooth Animation, No Reload) ────────────────────
  function confirmDelete(id, title) {
    if (!confirm("Are you sure you want to delete '" + title + "' and any dropdown submenus?\n\nThis cannot be undone.")) {
      return;
    }

    fetch(baseUrl + '?action=delete&id=' + id + '&_token=' + csrfToken, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data && data.ok) {
        // Smoothly fade out and remove rows
        const elementsToRemove = [
          document.getElementById('menu-item-' + id),
          document.getElementById('m-menu-item-' + id)
        ];

        // Also check if any children had this as parent
        document.querySelectorAll('[data-parent-id="' + id + '"]').forEach(child => {
          elementsToRemove.push(child);
        });

        elementsToRemove.forEach(el => {
          if (el) {
            el.style.transition = 'all 0.3s ease';
            el.style.opacity = '0';
            el.style.transform = 'scaleY(0)';
            setTimeout(() => el.remove(), 300);
          }
        });

        // If currently editing this item, reset form
        if (formMenuId.value && parseInt(formMenuId.value, 10) === parseInt(id, 10)) {
          resetFormToAdd();
        }

        setTimeout(recalculateStats, 350);

        if (window.showToast) {
          window.showToast('Menu item removed successfully.', 'success');
        }
      } else {
        alert(data.message || 'Could not delete item.');
      }
    })
    .catch(err => {
      console.error(err);
      // Fallback: normal navigation
      window.location.href = baseUrl + '?action=delete&id=' + id + '&_token=' + csrfToken;
    });
  }

  // ── Recalculate Stats in Real Time ─────────────────────────────────────────
  function recalculateStats() {
    const parentRows = document.querySelectorAll('#menuTreeTbody .menu-parent-row');
    const childRows  = document.querySelectorAll('#menuTreeTbody .menu-child-row');
    const totalCount = parentRows.length + childRows.length;

    let visibleCount = 0;
    let hiddenCount  = 0;

    parentRows.forEach(r => {
      if (r.dataset.isActive === '1') visibleCount++; else hiddenCount++;
    });
    childRows.forEach(r => {
      if (r.dataset.isActive === '1') visibleCount++; else hiddenCount++;
    });

    const statValTotal    = document.getElementById('statValTotal');
    const statValParents  = document.getElementById('statValParents');
    const statValSubmenus = document.getElementById('statValSubmenus');
    const statValVisible  = document.getElementById('statValVisible');
    const statValHidden   = document.getElementById('statValHidden');
    const tabListBadge    = document.getElementById('tabListBadge');

    if (statValTotal)    statValTotal.textContent    = totalCount;
    if (statValParents)  statValParents.textContent  = parentRows.length;
    if (statValSubmenus) statValSubmenus.textContent = childRows.length;
    if (statValVisible)  statValVisible.textContent  = visibleCount;
    if (statValHidden)   statValHidden.textContent   = hiddenCount;
    if (tabListBadge)    tabListBadge.textContent    = totalCount;

    applyFilter();
  }

  // ── Live Instant Search & Visibility Filter ────────────────────────────────
  function applyFilter() {
    const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
    const vis = visFilter ? visFilter.value : 'all';

    if (clearSearchBtn) {
      clearSearchBtn.style.display = query.length > 0 ? 'block' : 'none';
    }

    let matchCount = 0;

    // Filter desktop rows
    const parentRows = document.querySelectorAll('#menuTreeTbody .menu-parent-row');
    parentRows.forEach(parentRow => {
      const pId = parentRow.dataset.id;
      const childRows = document.querySelectorAll('#menuTreeTbody .menu-child-row[data-parent-id="' + pId + '"]');

      const pTitle = parentRow.dataset.title || '';
      const pUrl   = parentRow.dataset.url || '';
      const pBadge = parentRow.dataset.badge || '';
      const pActive = parentRow.dataset.isActive === '1';

      const pMatchesText = !query || pTitle.includes(query) || pUrl.includes(query) || pBadge.includes(query);
      const pMatchesVis  = (vis === 'all') || (vis === 'visible' && pActive) || (vis === 'hidden' && !pActive);

      let childMatchFound = false;
      childRows.forEach(childRow => {
        const cTitle = childRow.dataset.title || '';
        const cUrl   = childRow.dataset.url || '';
        const cBadge = childRow.dataset.badge || '';
        const cActive = childRow.dataset.isActive === '1';

        const cMatchesText = !query || cTitle.includes(query) || cUrl.includes(query) || cBadge.includes(query);
        const cMatchesVis  = (vis === 'all') || (vis === 'visible' && cActive) || (vis === 'hidden' && !cActive);

        if (cMatchesText && cMatchesVis) {
          childRow.style.display = '';
          childMatchFound = true;
          matchCount++;
        } else {
          childRow.style.display = 'none';
        }
      });

      if ((pMatchesText && pMatchesVis) || childMatchFound) {
        parentRow.style.display = '';
        matchCount++;
      } else {
        parentRow.style.display = 'none';
      }
    });

    // Filter mobile cards
    const mobileParentCards = document.querySelectorAll('#menuMobileCardsWrap .menu-card-parent');
    mobileParentCards.forEach(parentCard => {
      const pId = parentCard.dataset.id;
      const childCards = document.querySelectorAll('#menuMobileCardsWrap .menu-card-child[data-parent-id="' + pId + '"]');

      const pTitle = parentCard.dataset.title || '';
      const pUrl   = parentCard.dataset.url || '';
      const pBadge = parentCard.dataset.badge || '';
      const pActive = parentCard.dataset.isActive === '1';

      const pMatchesText = !query || pTitle.includes(query) || pUrl.includes(query) || pBadge.includes(query);
      const pMatchesVis  = (vis === 'all') || (vis === 'visible' && pActive) || (vis === 'hidden' && !pActive);

      let childMatchFound = false;
      childCards.forEach(childCard => {
        const cTitle = childCard.dataset.title || '';
        const cUrl   = childCard.dataset.url || '';
        const cBadge = childCard.dataset.badge || '';
        const cActive = childCard.dataset.isActive === '1';

        const cMatchesText = !query || cTitle.includes(query) || cUrl.includes(query) || cBadge.includes(query);
        const cMatchesVis  = (vis === 'all') || (vis === 'visible' && cActive) || (vis === 'hidden' && !cActive);

        if (cMatchesText && cMatchesVis) {
          childCard.style.display = 'flex';
          childMatchFound = true;
        } else {
          childCard.style.display = 'none';
        }
      });

      if ((pMatchesText && pMatchesVis) || childMatchFound) {
        parentCard.style.display = 'flex';
      } else {
        parentCard.style.display = 'none';
      }
    });

    if (filterCountText) {
      filterCountText.textContent = query || vis !== 'all' ? 'Found ' + matchCount + ' matching items' : 'Showing all items';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilter);
  }
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
      searchInput.value = '';
      applyFilter();
      searchInput.focus();
    });
  }
  if (visFilter) {
    visFilter.addEventListener('change', applyFilter);
  }

  // ── Auto-Scroll & Highlight on Load (If Saved or Edit ID Provided) ──────────
  window.addEventListener('DOMContentLoaded', () => {
    // Check hash or savedId
    let targetId = initialSavedId;
    if (!targetId && window.location.hash) {
      const match = window.location.hash.match(/#menu-item-(\d+)/);
      if (match) targetId = parseInt(match[1], 10);
    }

    if (targetId) {
      const targetEl = document.getElementById('menu-item-' + targetId) || document.getElementById('m-menu-item-' + targetId);
      if (targetEl) {
        setTimeout(() => {
          targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          targetEl.classList.add('menu-item-highlight');
        }, 150);
      }
    }

    updatePreview();
  });

  // Utility escape
  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, function(m) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
    });
  }

})();
</script>
</body>
</html>
