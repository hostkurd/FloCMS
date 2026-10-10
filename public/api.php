<?php
/**
 * API front controller (flocms-api), the recommended way to build APIs.
 *
 * public/.htaccess sends /api/v1/* here. Routes live in api/routes.php and
 * controllers in api/ (namespace App\Api). Routes are method-aware, return
 * JSON errors, and run through the middleware below. This path has no
 * session and no CSRF check by default: protect private routes with the
 * 'auth' middleware (see api/routes.php).
 */

use FloCMS\Api\Auth\BearerTokenAuthenticator;
use FloCMS\Api\Auth\FirstMatchAuthenticator;
use FloCMS\Api\Auth\SessionAuthenticator;
use FloCMS\Api\Auth\TokenRepository;
use FloCMS\Api\Database\Connection;
use FloCMS\Api\Idempotency\FileIdempotencyStore;
use FloCMS\Api\Kernel;
use FloCMS\Api\Middleware\AuthenticateMiddleware;
use FloCMS\Api\Middleware\BodySizeMiddleware;
use FloCMS\Api\Middleware\CorsMiddleware;
use FloCMS\Api\Middleware\ExceptionMiddleware;
use FloCMS\Api\Middleware\IdempotencyMiddleware;
use FloCMS\Api\Middleware\JsonBodyMiddleware;
use FloCMS\Api\Middleware\LocaleMiddleware;
use FloCMS\Api\Middleware\MaintenanceMiddleware;
use FloCMS\Api\Middleware\RateLimitMiddleware;
use FloCMS\Api\Middleware\SecurityHeadersMiddleware;
use FloCMS\Api\OpenApi\OpenApiController;
use FloCMS\Api\OpenApi\OpenApiGenerator;
use FloCMS\Api\RateLimit\ApcuRateLimiter;
use FloCMS\Api\RateLimit\FileRateLimiter;
use FloCMS\Api\RateLimit\RateLimiterRegistry;
use FloCMS\Api\RouteLoader;
use FloCMS\Api\Router;
use FloCMS\Api\Security\ClientIpResolver;
use FloCMS\Core\Config;
use FloCMS\Core\Env;
use FloCMS\Core\Http\Request;
use FloCMS\Core\Logger;
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
require_once ROOT . '/includes/maintenance.php';

$debug = Env::get('APP_DEBUG') === true;
$logger = new Logger(ROOT . '/storage/logs/api.log');
$csv = static fn (mixed $value): array => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

// Base path when the site lives in a sub-directory (e.g. /flocms/public/api.php -> /flocms)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

$maintenance = flo_maintenance(ROOT);
$container = new Container();
$clientIp = new ClientIpResolver(array_values((array) Config::get('trusted_proxies', [])));

// Strict routing in debug mode: a route that can never match, or a duplicate
// route name, is an error instead of a logged warning.
$router = new Router($logger, strict: $debug);
$router->group($basePath . '/api', static function (Router $router) use ($container, $debug): void {
    (new RouteLoader($router, $container))->load(ROOT . '/api/routes.php');

    if ($debug) {
        $router->get('/v1/openapi.json', new OpenApiController(new OpenApiGenerator($router, (string) Env::get('APP_NAME', 'FloCMS') . ' API')))
            ->name('openapi');
    }
});

// Named rate limits for routes: ->middleware('throttle:forms')
$rateLimitStore = ApcuRateLimiter::isAvailable()
    ? new ApcuRateLimiter()
    : new FileRateLimiter(ROOT . '/storage/cache/api-rate-limit');
$limiters = new RateLimiterRegistry($rateLimitStore, $clientIp, [
    'public' => [max(1, (int) Env::get('API_RATE_LIMIT', 60)), 60],
    'forms'  => [5, 60],
    'auth'   => [10, 900],
]);

$kernel = new Kernel(router: $router, container: $container, debug: $debug, logger: $logger);
$kernel->aliases([
    'throttle' => $limiters->aliasFactory(),

    // 'auth' or 'auth:users.manage,content.edit' (permissions from config/config.php):
    // personal access tokens (php flo api:token:create) or the admin session
    // from same-origin JavaScript (with the X-CSRF-TOKEN header).
    'auth' => static fn (string ...$permissions): AuthenticateMiddleware => new AuthenticateMiddleware(
        new FirstMatchAuthenticator(
            new BearerTokenAuthenticator(new TokenRepository(new Connection())),
            new SessionAuthenticator()
        ),
        permissions: $permissions
    ),

    // Retries with the same Idempotency-Key header get the stored response.
    'idempotent' => static fn (): IdempotencyMiddleware => new IdempotencyMiddleware(
        new FileIdempotencyStore(ROOT . '/storage/cache/api-idempotency'),
        clientIp: $clientIp
    ),
]);

// Outermost first.
$kernel->middleware([
    new SecurityHeadersMiddleware(),
    new ExceptionMiddleware(debug: $debug, logger: $logger),
    new CorsMiddleware($csv(Env::get('API_CORS_ORIGINS', ''))),
    // `php flo down` (storage/framework/down.json) or the offline_mode setting
    new MaintenanceMiddleware(
        isDown: static fn (): bool => $maintenance !== null || (string) Config::getSetting('offline_mode', '0') === '1',
        allowedIps: array_values(array_unique([...$csv(Env::get('API_MAINTENANCE_ALLOWED_IPS', '')), ...($maintenance['allow'] ?? [])])),
        retryAfter: $maintenance['retry'] ?? 600,
        clientIp: $clientIp,
        bypass: static fn (Request $request): bool => str_ends_with($request->path(), '/v1/health'),
        message: $maintenance['message'] ?? 'The site is under maintenance. Please try again later.'
    ),
    new LocaleMiddleware($csv(implode(',', (array) Config::get('languages', []))) ?: ['en'], (string) Config::get('default_language', 'en')),
    new RateLimitMiddleware(
        $rateLimitStore,
        $clientIp,
        'api',
        max(1, (int) Env::get('API_RATE_LIMIT', 60)),
        60
    ),
    new BodySizeMiddleware(),
    new JsonBodyMiddleware(),
]);

$kernel->handle(Request::fromGlobals())->send();
