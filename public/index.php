<?php
ob_start();

 define('ROOT', dirname(dirname(__FILE__)));
 define('DS', DIRECTORY_SEPARATOR);

require_once ROOT . '/lib/init.php';

$router = new Router($_SERVER['REQUEST_URI']);

$vendor = ROOT . "/vendor/autoload.php";
if (file_exists($vendor)) require_once $vendor;

include ROOT . '/public/conf_global.php';

session_start();

App::Run($_SERVER['REQUEST_URI']);

if (ob_get_level() > 0) ob_end_flush();