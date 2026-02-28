<?php

// register environment Variables
require_once ROOT . DS . "lib" . DS . "env.php";
Env::load(ROOT . DS . ".env");

// register Error Handler
require_once ROOT . DS . "lib" . DS . "errorhandler.php";
ErrorHandler::register();

// Defining Global Variables
if (!defined('SITE_URI')) define('SITE_URI', rtrim((string)Env::get('APP_URL'), '/'));
if (!defined('API_URI'))  define('API_URI',  rtrim((string)Env::get('API_URL'), '/'));

spl_autoload_register(function ($class_name) {
    $class_lower = strtolower($class_name);

    $lib_file = ROOT.DS."lib".DS.$class_lower.".class.php";

    $c = preg_replace('/controller$/', '', $class_lower);
    $controller_file = ROOT.DS."controllers".DS.$c.".controller.php";

    $m = preg_replace('/model$/', '', $class_lower);
    $model_file = ROOT.DS."models".DS.$m.".model.php";

    if (file_exists($lib_file)) { require_once $lib_file; return; }
    if (file_exists($controller_file)) { require_once $controller_file; return; }
    if (file_exists($model_file)) { require_once $model_file; return; }

    return false;
}, true, true);

require_once ROOT . DS . "config" . DS . "config.php";

function __($key, $default_value = '') {
    return Lang::get($key, $default_value);
}