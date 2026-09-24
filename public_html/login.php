<?php
/**
 * login.php — Unified redirect to auth.php
 */
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: auth.php' . $query);
exit;
