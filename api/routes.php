<?php

use FloCMS\Api\Router;

/*
 * API routes, served by public/api.php under /api (e.g. GET /api/v1/health).
 *
 * Handlers may be closures or [Controller::class, 'method'] pairs; controller
 * constructor and method arguments are resolved from the container, and a
 * returned array becomes a JSON response.
 */
return static function (Router $router): void {
    $router->get('/v1/health', static fn (): array => ['status' => 'ok'])->name('health');
};
