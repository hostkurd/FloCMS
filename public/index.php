<?php
use FloCMS\Core\App;

ob_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

// Composer autoload
$vendor = ROOT . '/vendor/autoload.php';
if (!file_exists($vendor)) {
    http_response_code(500);
    exit('Autoloader not found.');
}
require_once $vendor;

// Start session before bootstrap if bootstrap may use session/config flash/auth
session_start();

// Load app bootstrap
require ROOT . '/config/bootstrap.php';

App::run($_SERVER['REQUEST_URI']);

if (ob_get_level() > 0) {
    ob_end_flush();
}