<?php
// Minimal 403 page — referenced by Auth::require()
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Access Denied</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= function_exists('asset') ? asset('css/style.css') : '/assets/css/style.css' ?>">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:40px;">
  <div>
    <div style="font-size:4rem;margin-bottom:16px;">🔒</div>
    <h1 style="font-size:2rem;">Access Denied</h1>
    <p style="color:var(--dc-muted);margin-bottom:24px;">You don't have permission to view this page.</p>
    <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
    &nbsp;
    <a href="<?= function_exists('url') ? url('/') : '/' ?>" class="btn btn-outline">Home</a>
  </div>
</div>
</body>
</html>
