<?php
/**
 * Admin page wrapper — start.
 * Include AFTER setting $pageTitle and optionally $pageHeading.
 */
if (!isset($pageHeading)) $pageHeading = $pageTitle ?? 'Admin';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= e($pageTitle ?? 'Admin') ?> | <?= e(getSetting('app_name', APP_NAME)) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <?php
    $shellCssPath = dirname(__DIR__, 2) . '/public_html/admin/assets/css/admin-shell.css';
    $shellCssVer  = file_exists($shellCssPath) ? '?v=' . filemtime($shellCssPath) : '';
    $shellJsPath  = dirname(__DIR__, 2) . '/public_html/admin/assets/js/admin-shell.js';
    $shellJsVer   = file_exists($shellJsPath) ? '?v=' . filemtime($shellJsPath) : '';
  ?>
  <link rel="stylesheet" href="<?= url('admin/assets/css/admin-shell.css') . $shellCssVer ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
  <script src="<?= url('admin/assets/js/admin-shell.js') . $shellJsVer ?>" defer></script>
</head>
<body class="admin-page">
<div class="admin-layout" id="adminApp">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="admin-main">
    <?php require_once __DIR__ . '/topbar.php'; ?>
