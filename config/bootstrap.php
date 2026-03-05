<?php
declare(strict_types=1);

use FloCMS\Core\Env;
use FloCMS\Core\ErrorHandler;

$envfile = ROOT . DS . '.env';
if (!file_exists($envfile)) {
    http_response_code(500);
    echo ".env not found. Copy .env.example to .env";
    exit;
}
Env::load($envfile);

// Register Error Handler
ErrorHandler::register();

// Controller namespace for resolver (core will read this)
define('APP_CONTROLLER_NAMESPACE', 'FloCMS\\Controllers');

// Paths (core can read these too)
define('APP_VIEWS_PATH', ROOT . DS . 'Views');
define('APP_STORAGE_PATH', ROOT . DS . 'Storage');


// Defining Global Variables
if (!defined('SITE_URI')) define('SITE_URI', rtrim((string)Env::get('APP_URL'), '/'));
if (!defined('API_URI'))  define('API_URI',  rtrim((string)Env::get('API_URL'), '/'));


// backward-compatibility aliases (optional; remove later)
require_once __DIR__ . DS . "includes" . DS  . "compat.php";
require_once ROOT . DS . "config" . DS . "config.php";