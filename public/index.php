<?php
use FloCMS\Core\App;

ob_start();

define('ROOT', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);

// Composer autoload (primary)
$vendor = ROOT . "/vendor/autoload.php";
if (file_exists($vendor)) require_once $vendor;

// Load app bootstrap (env + config + error handler)
require ROOT . '/Config/bootstrap.php';

session_start();

App::Run($_SERVER['REQUEST_URI']);

if (ob_get_level() > 0) ob_end_flush();
