<?php
/**
 * POST: save the current box_group_id as a reusable build_boxes template.
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
Auth::require(['buyer']);

// TODO: build this page — next development phase.
