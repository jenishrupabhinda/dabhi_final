<?php
/**
 * admin/logout.php — Admin Sign Out handler
 */
$bootstrap = null;
foreach ([
    __DIR__ . '/../../includes/bootstrap.php',
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

header('Location: ../auth.php');
exit;
