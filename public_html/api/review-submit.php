<?php
/**
 * review-submit.php — Customer Product Review Submission Endpoint
 * Inserts review as pending (is_approved = 0) for admin moderation in admin/reviews.php
 */
$bootstrap = null;
foreach ([
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/../../includes/bootstrap.php',
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

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$productId = (int)($input['product_id'] ?? 0);
$rating    = max(1, min(5, (int)($input['rating'] ?? 5)));
$comment   = trim($input['comment'] ?? '');
$name      = trim($input['name'] ?? '');
$email     = trim($input['email'] ?? '');

if (!$productId) {
    echo json_encode(['ok' => false, 'error' => 'Please select a valid product.']);
    exit;
}

// Verify product exists
$product = Database::fetchOne('SELECT id, name FROM products WHERE id = ?', [$productId]);
if (!$product) {
    echo json_encode(['ok' => false, 'error' => 'Product not found.']);
    exit;
}

if (strlen($comment) < 3) {
    echo json_encode(['ok' => false, 'error' => 'Please write a review of at least a few words.']);
    exit;
}

// Identify user
$userId = null;
if (Auth::check()) {
    $userId = Auth::id();
} else {
    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'error' => 'Please enter your name and a valid email address.']);
        exit;
    }

    $existing = Database::fetchOne('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
    if ($existing) {
        $userId = (int)$existing['id'];
    } else {
        $uuid = generateUuid();
        $randomPass = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
        Database::query(
            'INSERT INTO users (uuid, role, full_name, email, password_hash, is_active)
             VALUES (?, "buyer", ?, ?, ?, 1)',
            [$uuid, $name, $email, $randomPass]
        );
        $userId = (int)Database::lastInsertId();
    }
}

try {
    Database::query(
        'INSERT INTO reviews (product_id, user_id, rating, comment, is_approved, created_at)
         VALUES (?, ?, ?, ?, 0, NOW())',
        [$productId, $userId, $rating, $comment]
    );

    echo json_encode([
        'ok'      => true,
        'message' => 'Thank you! Your review has been submitted and will appear once approved by our team.',
    ]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Could not save review: ' . $e->getMessage()]);
}
