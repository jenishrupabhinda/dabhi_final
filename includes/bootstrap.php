<?php
/**
 * bootstrap.php — required at the very top of every entry-point script
 * (every file in public_html/, public_html/admin/, /employee/, /account/, /api/).
 *
 * Usage in an entry-point file:
 *     require_once __DIR__ . '/../../includes/bootstrap.php';
 * (adjust the number of ../ to reach the includes/ folder from that file)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------
// 1. Config
// ---------------------------------------------------------------------
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('Configuration missing. Copy includes/config.sample.php to includes/config.php and fill in real values.');
}
require_once $configFile;

// ---------------------------------------------------------------------
// 2. Error handling — verbose in dev, silent (but logged) in production
// ---------------------------------------------------------------------
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/php-error.log');
}

// ---------------------------------------------------------------------
// 3. Simple PSR-4-ish autoloader for includes/classes and includes/db
//    (no Composer required for the app's own classes; Composer is only
//    used for third-party libraries — see composer.json)
// ---------------------------------------------------------------------
spl_autoload_register(function (string $class) {
    $paths = [
        __DIR__ . '/classes/' . $class . '.php',
        __DIR__ . '/classes/payment/' . $class . '.php',
        __DIR__ . '/classes/notifications/' . $class . '.php',
        __DIR__ . '/db/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Composer autoload for third-party libraries (PDF generation, PHPMailer,
// Cashfree SDK / HTTP client). Safe to skip silently until `composer
// install` has been run for the first time in this environment.
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ---------------------------------------------------------------------
// 4. Session
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME_MINUTES * 60,
        'path'     => '/',
        'secure'   => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------
// 5. Timezone — Indian business, Indian invoices
// ---------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');

// ---------------------------------------------------------------------
// 6. Helper functions
// ---------------------------------------------------------------------
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/upload.php';
