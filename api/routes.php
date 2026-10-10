<?php

use App\Api\Controllers\ContactController;
use App\Api\Controllers\UsersController;
use FloCMS\Api\Http\HealthController;
use FloCMS\Api\Router;

/*
 * API routes, served by public/api.php under /api (e.g. GET /api/v1/health).
 *
 * Handlers may be closures or [Controller::class, 'method'] pairs; controller
 * constructor and method arguments are resolved from the container, and a
 * returned array or resource becomes a JSON response.
 *
 * Route middleware aliases (defined in public/api.php):
 *   'throttle:<name>'   named rate limit: public, forms, auth
 *   'auth[:perm,...]'   personal access token or admin session, with optional permissions
 *   'idempotent'        safe retries with the Idempotency-Key header
 *
 * List routes with `php flo api:routes`. In debug mode the OpenAPI document is
 * at /api/v1/openapi.json (or `php flo api:docs`).
 */
return static function (Router $router): void {
    $router->version('v1', static function (Router $router): void {
        // GET /api/v1/health -> {"status":"ok","db":"ok"}
        $router->get('/health', new HealthController())->name('health');

        // A public form endpoint: validation, a strict rate limit, safe retries.
        $router->post('/contact', [ContactController::class, 'store'])
            ->name('contact.store')
            ->middleware('throttle:forms', 'idempotent');

        // A resource controller for signed-in admins: authentication with a
        // permission, pagination, filters/sorting and a resource that hides
        // internal columns.
        $router->group('/users', static function (Router $router): void {
            $router->get('', [UsersController::class, 'index'])->name('index');
            $router->get('/{id:\d+}', [UsersController::class, 'show'])->name('show');
        }, ['auth:users.manage'], namePrefix: 'users.');
    });
};
