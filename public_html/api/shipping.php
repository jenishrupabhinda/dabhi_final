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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action   = $input['action'] ?? get('action', 'check');
$pincode  = trim($input['pincode'] ?? get('pincode', ''));
$weight   = max(1, (int)($input['weight'] ?? get('weight', '500')));
$cod      = !empty($input['cod']) || get('cod', '0') === '1';
$subtotal = (float)($input['subtotal'] ?? get('subtotal', '0'));

if ($action === 'check') {
    $result = Shipping::isServiceable($pincode);
    echo json_encode($result);
    exit;
}

if ($action === 'calculate') {
    $result = Shipping::calculate($pincode, $weight, $cod, $subtotal);
    echo json_encode($result);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
