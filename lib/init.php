<?php

use HostKurd\Flocms\Lib\Env;
use HostKurd\Flocms\Lib\ErrorHandler;

// register environment Variables
Env::load(ROOT . DS . ".env");

// register Error Handler
ErrorHandler::register();

// Defining Global Variables
if (!defined('SITE_URI')) define('SITE_URI', rtrim((string)Env::get('APP_URL'), '/'));
if (!defined('API_URI'))  define('API_URI',  rtrim((string)Env::get('API_URL'), '/'));


// backward-compatibility aliases (optional; remove later)
require_once __DIR__ . DS . "compat.php";
require_once ROOT . DS . "config" . DS . "config.php";
