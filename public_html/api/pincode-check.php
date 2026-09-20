<?php
/**
 * pincode-check.php — Delivery serviceability and rate check
 * Connects directly to Shipping class configured in admin panel
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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$pincode = trim($input['pincode'] ?? $_POST['pincode'] ?? $_GET['pincode'] ?? '');
$weight  = max(1, (int)($input['weight'] ?? $_POST['weight'] ?? $_GET['weight'] ?? 500));
$isCod   = !empty($input['is_cod']) || !empty($_POST['is_cod']) || !empty($_GET['is_cod']) || ($input['payment_method'] ?? '') === 'cod';
$subtotal= (float)($input['subtotal'] ?? $_POST['subtotal'] ?? $_GET['subtotal'] ?? 0);

if (!$pincode || strlen($pincode) !== 6 || !ctype_digit($pincode)) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid 6-digit PIN code.']);
    exit;
}

try {
    $result = Shipping::calculate($pincode, $weight, $isCod, $subtotal);
    if ($result['ok']) {
        echo json_encode([
            'ok'           => true,
            'pincode'      => $pincode,
            'zone_name'    => $result['zone_name'],
            'charge'       => $result['rate'],
            'base_rate'    => $result['base_rate'],
            'cod_extra'    => $result['cod_extra'],
            'total_charge' => $result['total_charge'],
            'is_free'      => $result['is_free'],
            'free_above'   => $result['free_threshold'],
            'cod_available'=> $result['cod_available'],
            'message'      => 'Delivery available (' . $result['zone_name'] . ').',
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => $result['error']]);
    }
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Could not verify PIN code: ' . $e->getMessage()]);
}
