<?php
/**
 * Router for PHP's built-in server that mirrors public/.htaccess:
 * /api/v1/* -> api.php (when the API is installed), existing files as-is,
 * everything else -> index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$public = $_SERVER['DOCUMENT_ROOT'];

// Test copies symlink vendor/ to the skeleton's, whose autoloader maps
// App\Api\ to the skeleton's api/ folder. Load the copy's own (php flo api:install).
spl_autoload_register(static function (string $class) use ($public): void {
    $file = dirname($public) . '/api/' . str_replace('\\', '/', substr($class, 8)) . '.php';
    if (str_starts_with($class, 'App\\Api\\') && is_file($file)) {
        require $file;
    }
});

if (preg_match('#^/api/v1(/.*)?$#', $path) === 1 && is_file($public . '/api.php')) {
    $_SERVER['SCRIPT_NAME'] = '/api.php';
    $_SERVER['SCRIPT_FILENAME'] = $public . '/api.php';
    require $public . '/api.php';
    return true;
}

if ($path !== '/' && is_file($public . $path)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $public . '/index.php';
require $public . '/index.php';
return true;
