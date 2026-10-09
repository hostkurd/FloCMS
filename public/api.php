<?php
/**
 * API front controller (flocms-api), the recommended way to build APIs.
 *
 * public/.htaccess sends /api/v1/* here. Routes live in api/routes.php. Unlike
 * the legacy /api/<controller>/<action> route (api_ methods in controllers),
 * routes here are method-aware, return JSON errors, and run through the
 * middleware below. There are no sessions and no CSRF tokens on this path:
 * add FloCMS\Api\Middleware\AuthenticateMiddleware for protected routes.
 */

use FloCMS\Api\Kernel;
use FloCMS\Api\Middleware\CorsMiddleware;
use FloCMS\Api\Middleware\ExceptionMiddleware;
use FloCMS\Api\Middleware\JsonBodyMiddleware;
use FloCMS\Api\Middleware\RateLimitMiddleware;
use FloCMS\Api\Middleware\SecurityHeadersMiddleware;
use FloCMS\Api\RateLimit\FileRateLimiter;
use FloCMS\Api\RouteLoader;
use FloCMS\Api\Router;
use FloCMS\Api\Security\ClientIpResolver;
use FloCMS\Core\Config;
use FloCMS\Core\Env;
use FloCMS\Core\Http\Request;
use FloCMS\Core\Support\Container;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));

$vendor = ROOT . '/vendor/autoload.php';
if (!file_exists($vendor)) {
    http_response_code(500);
    exit('Autoloader not found.');
}
require_once $vendor;

require ROOT . '/config/bootstrap.php';

$debug = Env::get('APP_DEBUG') === true;

// Base path when the site lives in a sub-directory (e.g. /flocms/public/api.php -> /flocms)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

$container = new Container();
$router = new Router();
$router->group($basePath . '/api', static function (Router $router) use ($container): void {
    (new RouteLoader($router, $container))->load(ROOT . '/api/routes.php');
});

$origins = array_values(array_filter(array_map('trim', explode(',', (string) Env::get('API_CORS_ORIGINS', '')))));
$trustedProxies = array_values((array) Config::get('trusted_proxies', []));

$kernel = new Kernel(router: $router, container: $container, debug: $debug);
$kernel->middleware([
    new ExceptionMiddleware(debug: $debug),
    new SecurityHeadersMiddleware(),
    new CorsMiddleware($origins),
    new RateLimitMiddleware(
        new FileRateLimiter(ROOT . '/storage/cache/api-rate-limit'),
        new ClientIpResolver($trustedProxies),
        'api',
        (int) Env::get('API_RATE_LIMIT', 60),
        60
    ),
    new JsonBodyMiddleware(),
]);

$kernel->handle(Request::fromGlobals())->send();
