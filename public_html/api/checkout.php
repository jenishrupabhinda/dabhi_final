<?php
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

if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['ok' => false, 'error' => 'Bad request.']); exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {
    case 'validate_coupon':
        if (!Auth::check()) { echo json_encode(['ok'=>false,'error'=>'Login required.']); exit; }
        $result = Order::validateCouponCode(
            $input['code'] ?? '',
            Auth::id(),
            (float)($input['subtotal'] ?? 0)
        );
        if ($result['ok']) {
            echo json_encode([
                'ok'      => true,
                'discount'=> $result['discount'],
                'message' => number_format($result['discount'], 2) . ' discount applied!',
            ]);
        } else {
            echo json_encode($result);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}
