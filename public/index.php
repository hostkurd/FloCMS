<?php
ob_start();

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

require_once ROOT . '/lib/init.php';

// Load Environment Variables (needed for APP_DEBUG)
Env::load(ROOT . '/.env');

// Register global error handler early
ErrorHandler::register();

// now the rest...
define('SITE_URI', Env::get('APP_URL'));
define('API_URI', Env::get('API_URL'));

// Database Parameters
Config::set('db.host', Env::get('DB_HOST', 'localhost'));
Config::set('db.name', Env::get('DB_NAME', null));
Config::set('db.user', Env::get('DB_USERNAME', null));
Config::set('db.pass', Env::get('DB_PASSWORD', null));
Config::set('db.charset', Env::get('DB_CHARSET', 'utf8mb4'));

$router = new Router($_SERVER['REQUEST_URI']);

$vendor = ROOT . "/vendor/autoload.php";
if (file_exists($vendor)) require_once $vendor;

include ROOT . '/public/conf_global.php';

session_start();

App::Run($_SERVER['REQUEST_URI']);

if (ob_get_level() > 0) ob_end_flush();