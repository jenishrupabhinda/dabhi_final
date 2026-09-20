<?php
/**
 * POST: admin/employee updates an order's status; writes order_status_history and triggers NotificationDispatcher.
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
Auth::require(['superadmin','admin','employee']);
RBAC::requireCan(Auth::id(), 'manage_orders');

// TODO: build this page — next development phase.
