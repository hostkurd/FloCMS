<?php
declare(strict_types=1);

use FloCMS\Core\Env;
use FloCMS\Core\ErrorHandler;
use FloCMS\Core\AppUrlValidator;

$envfile = ROOT . DS . '.env';
    if (!file_exists($envfile)) {
        render_static_page([
                'template' => ROOT . DS . 'templates' . DS . 'default' . DS . 'errors' . DS . 'file-missing.html',
                'status'   => 500,
                'vars'     => [
                    'message' => 'Error: .env file not found! Please copy .env.example to .env.',
                    'path' => $envfile,
                ],
            ]);
    }
Env::load($envfile);

// Register Error Handler
ErrorHandler::register();

// Basic APP_URL validation only
$appUrl = AppUrlValidator::validateBasic((string) Env::get('APP_URL'));

// Define Global Variables
if (!defined('SITE_URI')) {define('SITE_URI', $appUrl);}
if (!defined('API_URI')) {define('API_URI', rtrim((string) Env::get('API_URL'), '/'));}

// Paths (core can read these too)
define('APP_VIEWS_PATH', ROOT . DS . 'views');
define('APP_TEMPLATES_PATH', ROOT . DS . 'templates');
define('APP_STORAGE_PATH', ROOT . DS . 'storage');

// backward-compatibility aliases (optional; remove later)
require_once ROOT . DS . "includes" . DS  . "compat.php";
require_once ROOT . DS . "config" . DS . "config.php";