<?php
/**
 * DABHI CHIKKI — Development Config (WAMP Server)
 *
 * This file must NEVER be committed to version control.
 * Edit DB_* values to match your WAMP MySQL credentials.
 * For production: copy to your server's `includes/` folder (above public_html)
 * and update APP_ENV, APP_DEBUG, SMTP_* and payment keys.
 */

// -------------------------------------------------------------------------
// Environment
// -------------------------------------------------------------------------
define('APP_ENV',   'development');   // 'development' | 'production'
define('APP_DEBUG', true);            // set false before going live
// Auto-detect URL for dev environments (e.g. WAMP subdir, virtualhost, CLI)
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_match('#^(.*?/public_html)#i', $script, $m) ? $m[1] : '';
    define('APP_URL', rtrim($protocol . $host . $basePath, '/'));
}
define('APP_NAME',  'Dabhi Chikki');

// -------------------------------------------------------------------------
// Database  (WAMP defaults)
// -------------------------------------------------------------------------
define('DB_HOST',    'localhost');
define('DB_NAME',    'dabhi_final');   // create this DB in phpMyAdmin first
define('DB_USER',    'root');             // WAMP default MySQL user
define('DB_PASS',    'root');                 // WAMP default has no password; set if yours does
define('DB_CHARSET', 'utf8mb4');

// -------------------------------------------------------------------------
// Session / security
// -------------------------------------------------------------------------
define('SESSION_LIFETIME_MINUTES',       120);
define('PASSWORD_RESET_TOKEN_TTL_MINUTES', 30);
define('OTP_TTL_MINUTES',                  5);
define('OTP_MAX_ATTEMPTS',                 5);
define('MAX_LOGIN_ATTEMPTS',               5);   // before 15-min lockout

// -------------------------------------------------------------------------
// Cashfree Payment Gateway
// Leave as dummy values during development when cashfree_mode='test' and
// COD is the only active method. Swap for real keys before going live.
// -------------------------------------------------------------------------
define('CASHFREE_APP_ID',      'your-test-app-id');
define('CASHFREE_SECRET_KEY',  'your-test-secret-key');
define('CASHFREE_API_VERSION', '2023-08-01');
// Aliases used by CashfreeGateway.php
define('CASHFREE_SECRET',   CASHFREE_SECRET_KEY);
define('CASHFREE_SANDBOX',  APP_ENV === 'development');  // true = sandbox, false = production

// -------------------------------------------------------------------------
// Email (SMTP)
// For local dev, use MailHog (bundled with some WAMP variants) or Mailtrap.
// If neither is available, mails will silently fail but the app won't crash.
// -------------------------------------------------------------------------
define('SMTP_HOST',       'smtp.mailtrap.io');  // replace with your SMTP host
define('SMTP_PORT',        587);
define('SMTP_USER',       '');
define('SMTP_PASS',       '');
define('SMTP_FROM_EMAIL', 'orders@dabhichikki.com');
define('SMTP_FROM_NAME',  'Dabhi Chikki');
define('SMTP_ENCRYPTION', 'tls');  // 'tls' | 'ssl' | ''

// -------------------------------------------------------------------------
// SMS / WhatsApp (off by default in dev — toggle in admin settings table)
// -------------------------------------------------------------------------
define('SMS_PROVIDER',           'msg91');
define('SMS_API_KEY',            '');
define('SMS_SENDER_ID',          'DBHCHK');

define('WHATSAPP_PROVIDER',      'meta_cloud_api');
define('WHATSAPP_API_TOKEN',     '');
define('WHATSAPP_PHONE_NUMBER_ID','');

// -------------------------------------------------------------------------
// File upload paths
// -------------------------------------------------------------------------
define('UPLOAD_PATH_PRODUCTS', __DIR__ . '/../public_html/uploads/products/');
define('UPLOAD_PATH_INVOICES', __DIR__ . '/../public_html/uploads/invoices/');
define('UPLOAD_PATH_LABELS',   __DIR__ . '/../public_html/uploads/labels/');
define('UPLOAD_PATH_REVIEWS',  __DIR__ . '/../public_html/uploads/reviews/');
