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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin') ?> | <?= e(getSetting('app_name', APP_NAME)) ?> Admin</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <link rel="icon" href="<?= asset('images/logo.png') ?>" type="image/png">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="admin-main">
    <?php require_once __DIR__ . '/topbar.php'; ?>
