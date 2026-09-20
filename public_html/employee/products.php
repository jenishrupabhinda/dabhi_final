<?php
/**
 * Product listing/stock view. Price editing gated separately by manage_prices, which employees rarely hold.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::require(['employee']);
RBAC::requireCan(Auth::id(), 'manage_products');

// TODO: build this page — next development phase.
