<?php
/**
 * create_superadmin.php — CLI-only script to create the first superadmin.
 *
 * Run from the project root (NOT from a browser):
 *   php database/create_superadmin.php
 *
 * On WAMP / Windows, open a Command Prompt and run:
 *   "C:\wamp64\bin\php\php8.x.x\php.exe" database/create_superadmin.php
 *
 * This script is deliberately off the web (in /database, not /public_html).
 */

// Safety: block web execution
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script must be run from the command line only.\n");
}

// Bootstrap (same path as public pages but one folder deeper)
$bootstrapPath = __DIR__ . '/../includes/bootstrap.php';
if (!file_exists($bootstrapPath)) {
    die("Error: bootstrap.php not found. Make sure includes/config.php exists.\n");
}
require_once $bootstrapPath;

echo "\n========================================\n";
echo "  Dabhi Chikki — Create Superadmin\n";
echo "========================================\n\n";

// Check if a superadmin already exists
$existing = Database::fetchOne("SELECT id, email FROM users WHERE role = 'superadmin' LIMIT 1");
if ($existing) {
    echo "A superadmin already exists: {$existing['email']}\n";
    echo "If you need to reset their password, use the admin panel.\n\n";
    exit(0);
}

// Collect input
function prompt(string $label, bool $secret = false): string {
    echo $label . ': ';
    if ($secret && PHP_OS_FAMILY !== 'Windows') {
        // Hide input on Unix/mac
        system('stty -echo');
        $value = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $value = trim(fgets(STDIN));
    }
    return $value;
}

$fullName = prompt('Full name');
$email    = prompt('Email');
$phone    = prompt('Phone (10-digit Indian mobile)');
$password = prompt('Password', secret: true);
$confirm  = prompt('Confirm password', secret: true);

// Validate
if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
    echo "\nError: All fields are required.\n\n";
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\nError: Invalid email address.\n\n";
    exit(1);
}
if ($password !== $confirm) {
    echo "\nError: Passwords do not match.\n\n";
    exit(1);
}
if (strlen($password) < 8) {
    echo "\nError: Password must be at least 8 characters.\n\n";
    exit(1);
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $uuid = generateUuidV4();

    Database::query(
        'INSERT INTO users (uuid, role, full_name, email, phone, password_hash, is_active)
         VALUES (?, \'superadmin\', ?, ?, ?, ?, 1)',
        [$uuid, $fullName, strtolower(trim($email)), trim($phone), $hash]
    );

    $id = Database::lastInsertId();

    echo "\n✓ Superadmin created successfully!\n";
    echo "  ID    : $id\n";
    echo "  Name  : $fullName\n";
    echo "  Email : " . strtolower(trim($email)) . "\n\n";
    echo "Login at: http://localhost/admin/login.php\n\n";

} catch (\Throwable $e) {
    echo "\nDatabase error: " . $e->getMessage() . "\n\n";
    exit(1);
}
