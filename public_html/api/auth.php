<?php
/**
 * auth.php — Storefront Authentication API endpoint
 * Handles: login, register, logout
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

// Handle GET logout (simple link click)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'logout') {
    Auth::logout();
    header('Location: ../logout.php');
    exit;
}

header('Content-Type: application/json');

if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {

    // ── Login ──────────────────────────────────────────
    case 'login':
        $email    = trim($input['email']    ?? '');
        $password = trim($input['password'] ?? '');

        if (!$email || !$password) {
            echo json_encode(['ok' => false, 'error' => 'Email and password are required.']);
            exit;
        }

        $result = Auth::attempt($email, $password);
        if ($result['ok']) {
            $user = $result['user'] ?? Auth::user();
            $role = $user['role'] ?? 'buyer';
            $isStaff = in_array($role, ['superadmin', 'admin', 'employee'], true);

            // Merge guest cart for customer/buyer accounts
            if (!$isStaff) {
                Cart::mergeGuestCart(Auth::id());
            }

            // Route staff to Admin Dashboard and buyers to Account
            $redirect = $isStaff ? 'admin/index.php' : 'account.php';
            if (!empty($input['redirect'])) {
                $redirect = $input['redirect'];
            }

            echo json_encode([
                'ok' => true,
                'user_id' => Auth::id(),
                'role' => $role,
                'is_staff' => $isStaff,
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Invalid credentials.']);
        }
        break;

    // ── Register ───────────────────────────────────────
    case 'register':
        $name     = trim($input['full_name'] ?? '');
        $email    = trim($input['email']     ?? '');
        $phone    = trim($input['phone']     ?? '');
        $password = trim($input['password']  ?? '');

        if (!$name || !$email || !$password) {
            echo json_encode(['ok' => false, 'error' => 'Name, email, and password are required.']);
            exit;
        }
        if (strlen($password) < 8) {
            echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid email address.']);
            exit;
        }

        // Use Auth::register() which handles duplicates and hashing
        $result = Auth::register($name, $email, $phone, $password);
        if (!$result['ok']) {
            echo json_encode(['ok' => false, 'error' => $result['error']]);
            exit;
        }
        $userId = $result['userId'];

        // Auto-login
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_role'] = 'buyer';

        // Merge guest cart
        Cart::mergeGuestCart($userId);

        echo json_encode(['ok' => true, 'user_id' => $userId]);
        break;

    // ── Logout ─────────────────────────────────────────
    case 'logout':
        Auth::logout();
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}
