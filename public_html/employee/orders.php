<?php
/**
 * Update order status, print shipping labels.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['employee']);
RBAC::requireCan(Auth::id(), 'manage_orders');

// TODO: build this page — next development phase.
