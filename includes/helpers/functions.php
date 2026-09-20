<?php
/**
 * Small, dependency-free helper functions used across the whole app.
 * Keep this file free of business logic — that belongs in includes/classes/*.
 */

/** Slugify a string for category/product URLs. */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim(htmlspecialchars_decode(iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text), '-');
    $text = strtolower($text ?: 'n-a');
    return preg_replace('~[^-\w]+~', '', $text) ?: 'n-a';
}

/** Format a number as Indian Rupees, e.g. 1234.5 -> "₹1,234.50" */
function formatINR(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

/** Format grams as a human label: 250 -> "250 g", 1000 -> "1 kg" */
function formatWeight(int $grams): string
{
    return $grams >= 1000
        ? rtrim(rtrim(number_format($grams / 1000, 2), '0'), '.') . ' kg'
        : $grams . ' g';
}

/** Generate a UUID v4 (used for user.uuid, cart_items.box_group_id, etc.) */
function generateUuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
function generateUuid(): string { return generateUuidV4(); }

/** Get base URL path (e.g. '/dabhi-chikki/public_html' or '' in production) */
function baseUrl(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^(.*?/public_html)#i', $script, $m)) {
            $base = rtrim($m[1], '/');
        } elseif (defined('APP_URL') && ($urlPath = parse_url(APP_URL, PHP_URL_PATH))) {
            $base = rtrim($urlPath, '/');
        } else {
            $base = '';
        }
    }
    $path = '/' . ltrim($path, '/');
    return ($path === '/' && $base !== '') ? $base . '/' : $base . $path;
}

/** Generate a relative or absolute site URL with proper base prefix */
function url(string $path = ''): string
{
    return baseUrl($path);
}

/** Generate an asset URL with proper base prefix and cache-busting timestamp */
function asset(string $path): string
{
    $cleanPath = ltrim($path, '/');
    $url = baseUrl('assets/' . $cleanPath);
    $realPath = dirname(__DIR__, 2) . '/public_html/assets/' . $cleanPath;
    if (file_exists($realPath)) {
        $url .= '?v=' . filemtime($realPath);
    }
    return $url;
}

/** Generate a proper image URL (handles root-relative paths, full URLs, and fallbacks) */
function imageUrl(?string $path, string $fallback = ''): string
{
    if (!$path) {
        return $fallback !== '' ? url(ltrim($fallback, '/')) : '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

/** Redirect and stop execution (supports internal relative paths and full URLs). */
function redirect(string $path): never
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

/** Escape for safe HTML output — use on every piece of user-supplied text. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Read a settings row from the `settings` table, with a default fallback. */
function getSetting(string $key, ?string $default = null): ?string
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $row = Database::fetchOne('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        $cache[$key] = $row['setting_value'] ?? $default;
    }
    return $cache[$key];
}

/** Flush the settings cache (call after saving a setting). */
function clearSettingCache(): void
{
    // PHP doesn't allow clearing static vars directly; trick: call with dummy to reset
    // Best approach: use a global for the cache instead. For now, this is a no-op.
}

/** True/false helper for boolean-flavoured settings ('1'/'0'). */
function settingEnabled(string $key): bool
{
    return getSetting($key, '0') === '1';
}

/** Indian financial year label for "today", e.g. '2026-2027'. FY runs Apr–Mar. */
function currentFinancialYear(): string
{
    $y = (int) date('Y');
    $m = (int) date('n');
    return $m >= 4 ? "$y-" . ($y + 1) : ($y - 1) . "-$y";
}

// =========================================================================
// CSRF Protection
// =========================================================================

/** Generate (or retrieve) the session CSRF token. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Render a hidden CSRF input field — include inside every <form>. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** Verify the CSRF token from POST; dies with 403 if invalid. */
function csrfVerify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('Request validation failed. Please go back and try again.');
    }
}

// =========================================================================
// Flash Messages
// =========================================================================

/**
 * Store a flash message that survives exactly one redirect.
 * $type: 'success' | 'error' | 'warning' | 'info'
 */
function flashSet(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Read and clear all pending flash messages.
 * Returns array of ['type'=>..., 'message'=>...].
 */
function flashGet(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Render flash messages as styled HTML alerts (uses .alert-* CSS classes). */
function flashRender(): void
{
    foreach (flashGet() as $flash) {
        $type = e($flash['type']);
        $msg  = e($flash['message']);
        echo "<div class=\"alert alert-{$type}\" role=\"alert\">{$msg}</div>\n";
    }
}

// =========================================================================
// Input / Validation helpers
// =========================================================================

/** Return a sanitised string from $_POST, or '' if key missing. */
function post(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

/** Return a sanitised string from $_GET, or '' if key missing. */
function get(string $key, string $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

/** Returns true if the current request is a POST. */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Basic input validation helper — returns list of error strings. */
function validateRequired(array $fields, array $labels = []): array
{
    $errors = [];
    foreach ($fields as $field => $value) {
        if ($value === '' || $value === null) {
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $errors[] = "{$label} is required.";
        }
    }
    return $errors;
}

/** Sanitise and validate a 10-digit Indian mobile number. */
function validateIndianPhone(string $phone): bool
{
    $phone = preg_replace('/\D/', '', $phone);
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

/** Validate password strength: min 8 chars, at least one letter and one number. */
function validatePasswordStrength(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/\d/', $password);
}

// =========================================================================
// Misc
// =========================================================================

/** Return the current page's base filename without .php, for nav active states. */
function currentPage(): string
{
    return basename($_SERVER['PHP_SELF'], '.php');
}

/** Dump a value and die — dev only. */
function dd(mixed ...$values): never
{
    echo '<pre style="background:#1A1A1A;color:#F5E58C;padding:16px;border-radius:8px;font-size:13px;">';
    foreach ($values as $v) {
        print_r($v);
        echo "\n---\n";
    }
    echo '</pre>';
    exit;
}

