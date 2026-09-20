<?php
/**
 * DABHI CHIKKI — Site configuration
 *
 * SETUP: Copy this file to `config.php` (same folder) and fill in real
 * values. `config.php` itself is git-ignored and must NEVER be committed
 * or placed inside public_html — that's what keeps DB credentials and API
 * keys off the public web even if someone guesses the filename.
 *
 * On cPanel: this file lives in the folder ABOVE public_html
 * (e.g. /home/yourcpaneluser/includes/config.php), which is not served
 * over HTTP at all — only public_html/ is your document root.
 */

// ---------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------
define('APP_ENV', 'development');           // 'development' | 'production'
define('APP_DEBUG', true);                  // false in production — hides stack traces from visitors
define('APP_URL', 'https://dabhichikki.com');
define('APP_NAME', 'Dabhi Chikki');

// ---------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'dabhichikki_db');
define('DB_USER', 'dabhichikki_user');
define('DB_PASS', 'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// Session / security
// ---------------------------------------------------------------------
define('SESSION_LIFETIME_MINUTES', 120);
define('PASSWORD_RESET_TOKEN_TTL_MINUTES', 30);
define('OTP_TTL_MINUTES', 5);
define('OTP_MAX_ATTEMPTS', 5);

// ---------------------------------------------------------------------
// Cashfree Payment Gateway
// Test-mode keys go here during development; swap to live keys + flip
// the `cashfree_mode` row in the `settings` table only when going live.
// ---------------------------------------------------------------------
define('CASHFREE_APP_ID', 'your-test-app-id');
define('CASHFREE_SECRET_KEY', 'your-test-secret-key');
define('CASHFREE_API_VERSION', '2023-08-01');

// ---------------------------------------------------------------------
// Email (SMTP — used by includes/classes/notifications/EmailSender.php)
// ---------------------------------------------------------------------
define('SMTP_HOST', 'smtp.yourhost.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'orders@dabhichikki.com');
define('SMTP_PASS', 'CHANGE_ME');
define('SMTP_FROM_EMAIL', 'orders@dabhichikki.com');
define('SMTP_FROM_NAME', 'Dabhi Chikki');

// ---------------------------------------------------------------------
// SMS / WhatsApp — provider keys (DLT registration required in India
// before transactional SMS/WhatsApp templates will actually deliver;
// see docs/NOTIFICATIONS_COMPLIANCE.md)
// ---------------------------------------------------------------------
define('SMS_PROVIDER', 'msg91');             // e.g. 'msg91', 'twilio'
define('SMS_API_KEY', '');
define('SMS_SENDER_ID', 'DBHCHK');            // DLT-approved sender ID

define('WHATSAPP_PROVIDER', 'meta_cloud_api'); // or a BSP like 'gupshup', 'interakt'
define('WHATSAPP_API_TOKEN', '');
define('WHATSAPP_PHONE_NUMBER_ID', '');

// ---------------------------------------------------------------------
// File upload paths (writable folders inside public_html)
// ---------------------------------------------------------------------
define('UPLOAD_PATH_PRODUCTS', __DIR__ . '/../public_html/uploads/products/');
define('UPLOAD_PATH_INVOICES', __DIR__ . '/../public_html/uploads/invoices/');
define('UPLOAD_PATH_LABELS',   __DIR__ . '/../public_html/uploads/labels/');
define('UPLOAD_PATH_REVIEWS',  __DIR__ . '/../public_html/uploads/reviews/');
