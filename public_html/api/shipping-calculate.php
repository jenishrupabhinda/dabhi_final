<?php
/**
 * POST {pincode, total_weight_grams, is_cod, subtotal}: returns {ok, rate, cod_extra, total_charge, is_free, zone_name}
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
$pincode  = trim($input['pincode'] ?? '');
$weight   = max(1, (int)($input['total_weight_grams'] ?? $input['weight'] ?? 500));
$isCod    = !empty($input['is_cod']) || !empty($input['cod']);
$subtotal = (float)($input['subtotal'] ?? 0);

$result = Shipping::calculate($pincode, $weight, $isCod, $subtotal);
echo json_encode($result);
