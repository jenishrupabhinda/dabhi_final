<?php
/**
 * Receive stock batches, view FIFO dispatch order.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['employee']);
RBAC::requireCan(Auth::id(), 'manage_inventory');

// TODO: build this page — next development phase.
