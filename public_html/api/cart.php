<?php
$bootstrap = null;
foreach ([
    __DIR__ . '/../../includes/bootstrap.php',
    __DIR__ . '/../includes/bootstrap.php',
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

// Cart AJAX endpoint — handles add/update/remove/count
header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

$cart   = Cart::getOrCreate();
$cartId = (int)$cart['id'];

switch ($action) {
    case 'add':
        $variantId = (int)($input['variant_id'] ?? 0);
        $quantity  = max(1, (int)($input['quantity'] ?? 1));
        $boxGroup  = $input['box_group_id'] ?? null;

        if (!$variantId) {
            echo json_encode(['ok' => false, 'error' => 'Invalid variant.']); exit;
        }

        $result = Cart::addItem($variantId, $quantity, $boxGroup);
        if (!empty($result['ok'])) {
            $variant = Database::fetchOne(
                'SELECT p.name FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?',
                [$variantId]
            );
            $prodName = $variant['name'] ?? 'Chikki';
            $result['product_name'] = $prodName;
            $result['message'] = 'Added ' . $prodName;

            $items   = Cart::getItems($cartId);
            $summary = Cart::getSummary($items);
            $result  = array_merge($result, $summary);
            $result['cart_count'] = $summary['item_count'];
        } else {
            $result['cart_count'] = Cart::itemCount($cartId);
        }
        echo json_encode($result);
        break;

    case 'update':
        $itemId   = (int)($input['item_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 0);

        $result = Cart::updateItem($itemId, $quantity);
        if ($result['ok']) {
            $items   = Cart::getItems($cartId);
            $summary = Cart::getSummary($items);
            $result  = array_merge($result, $summary);
        }
        $result['cart_count'] = Cart::itemCount($cartId);
        echo json_encode($result);
        break;

    case 'remove':
        $itemId = (int)($input['item_id'] ?? 0);
        $result = Cart::removeItem($itemId);
        $items  = Cart::getItems($cartId);
        $summary= Cart::getSummary($items);
        $result = array_merge($result, $summary);
        $result['cart_count'] = Cart::itemCount($cartId);
        echo json_encode($result);
        break;

    case 'count':
        $items   = Cart::getItems($cartId);
        $summary = Cart::getSummary($items);
        echo json_encode([
            'ok'           => true,
            'cart_count'   => $summary['item_count'],
            'subtotal'     => $summary['subtotal'],
            'total_weight' => $summary['total_weight'],
        ]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}
