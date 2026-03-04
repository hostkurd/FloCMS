<?php

use HostKurd\Flocms\Lib\Env;
use HostKurd\Flocms\Lib\ErrorHandler;

//Load env (now any exception is handled by ErrorHandler)
$envfile = ROOT . DS . '.env';
    if (!file_exists($envfile)) {
        render_static_page([
                'template' => ROOT . DS . 'views' . DS . 'errors' . DS . 'file-missing.html',
                'status'   => 500,
                'vars'     => [
                    'message' => 'Error: .env file not found! Please copy .env.example to .env.',
                    'path' => $envfile,
                ],
            ]);
    }
Env::load($envfile);

//Start error handling ASAP (must NOT depend on Env inside register)
ErrorHandler::register();

// Defining Global Variables
if (!defined('SITE_URI')) define('SITE_URI', rtrim((string)Env::get('APP_URL'), '/'));
if (!defined('API_URI'))  define('API_URI',  rtrim((string)Env::get('API_URL'), '/'));


// backward-compatibility aliases (optional; remove later)
require_once __DIR__ . DS . "compat.php";
require_once ROOT . DS . "config" . DS . "config.php";
