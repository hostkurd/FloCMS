<?php
/**
 * Router for PHP's built-in server that mirrors public/.htaccess:
 * /api/v1/* -> api.php, existing files as-is, everything else -> index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$public = $_SERVER['DOCUMENT_ROOT'];

if (preg_match('#^/api/v1(/.*)?$#', $path)) {
    $_SERVER['SCRIPT_NAME'] = '/api.php';
    $_SERVER['SCRIPT_FILENAME'] = $public . '/api.php';
    require $public . '/api.php';
    return true;
}

if ($path !== '/' && is_file($public . $path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $public . '/index.php';
require $public . '/index.php';
return true;
