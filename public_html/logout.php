<?php
/**
 * logout.php — Unified Sign Out handler for buyers and administrators.
 */
$bootstrap = null;
foreach ([
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/includes/bootstrap.php',
] as $b) {
    if (file_exists($b)) {
        $bootstrap = $b;
        break;
    }
}
if ($bootstrap) {
    require_once $bootstrap;
}

if (class_exists('Auth')) {
    Auth::logout();
} else {
    session_start();
    $_SESSION = [];
    session_destroy();
}

$redirect = $_GET['redirect'] ?? 'index.php';
// Prevent open external redirect vulnerabilities
if (str_starts_with($redirect, 'http://') || str_starts_with($redirect, 'https://') || str_starts_with($redirect, '//')) {
    $redirect = 'index.php';
}

header('Location: ' . $redirect);
exit;
