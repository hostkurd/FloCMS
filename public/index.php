<?php
use FloCMS\Core\App;

ob_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));

// Session cookie settings (name: flocms_session), shared with captcha.php
require_once ROOT . '/includes/session.php';

// Composer autoload
$vendor = ROOT . '/vendor/autoload.php';
if (!file_exists($vendor)) {
    http_response_code(500);
    exit('Autoloader not found.');
}
require_once $vendor;

// Start session before bootstrap if bootstrap may use session/config flash/auth
flo_session_start();

// Load app bootstrap
require ROOT . '/config/bootstrap.php';

// Maintenance mode (php flo down): 503 for everyone but the allowed IPs
require_once ROOT . '/includes/maintenance.php';
flo_maintenance_respond(ROOT, (array) \FloCMS\Core\Config::get('trusted_proxies', []));

App::run($_SERVER['REQUEST_URI']);

if (ob_get_level() > 0) {
    ob_end_flush();
}