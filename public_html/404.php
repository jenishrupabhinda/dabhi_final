<?php
/**
 * 404.php — Custom 404 Page for Dabhi Chikki
 */
http_response_code(404);
$pageTitle = '404 — Page Not Found · Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';
?>

<section class="not-found-page">
  <div>
    <div class="not-found-num">404</div>
    <h2 style="font-size:1.875rem;margin-bottom:0.75rem">Oops! Page not found.</h2>
    <p style="margin-bottom:2rem;max-width:400px;margin-left:auto;margin-right:auto">
      Looks like this page took a wrong turn! But don't worry — the chikki is still here.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <a href="index.php" class="btn btn-primary btn-lg">Go to Homepage</a>
      <a href="track.php" class="btn btn-outline btn-lg">Track Order</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
