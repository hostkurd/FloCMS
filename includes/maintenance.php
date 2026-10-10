<?php
declare(strict_types=1);

/*
 * Maintenance mode set by `php flo down` (flocms-cli): while
 * storage/framework/down.json exists, the site answers 503 with Retry-After,
 * except for the IP addresses / CIDR ranges in its "allow" list. `php flo up`
 * removes it. (The offline_mode setting in the database still works as
 * before; `php flo down` sets it too.)
 *
 *     {"time": 1767225600, "message": "Back soon", "retry": 600, "allow": ["203.0.113.7"]}
 */

use FloCMS\Api\Security\ClientIpResolver;
use FloCMS\Api\Security\IpMatcher;
use FloCMS\Core\Http\Request;

if (!function_exists('flo_maintenance')) {
    /**
     * The maintenance state, or null when the site is up.
     *
     * @return array{message: string, retry: int|null, allow: list<string>}|null
     */
    function flo_maintenance(string $root): ?array
    {
        $file = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'down.json';
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        $data = is_array($data) ? $data : [];

        return [
            'message' => is_string($data['message'] ?? null) && $data['message'] !== '' ? $data['message'] : 'The site is under maintenance. Please try again later.',
            'retry' => isset($data['retry']) && is_numeric($data['retry']) ? max(0, (int) $data['retry']) : null,
            'allow' => array_values(array_filter((array) ($data['allow'] ?? []), static fn ($range): bool => is_string($range) && IpMatcher::isValidRange($range))),
        ];
    }
}

if (!function_exists('flo_maintenance_respond')) {
    /**
     * Front controllers call this after bootstrap: during maintenance it sends
     * the 503 page and stops, unless the client IP is allowed.
     *
     * @param list<string> $trustedProxies
     */
    function flo_maintenance_respond(string $root, array $trustedProxies = []): void
    {
        $state = flo_maintenance($root);
        if ($state === null) {
            return;
        }
        $ip = (new ClientIpResolver(array_values($trustedProxies)))->resolve(Request::fromGlobals());
        if ($state['allow'] !== [] && IpMatcher::matchesAny($ip, $state['allow'])) {
            return;
        }

        if (!headers_sent()) {
            header('Cache-Control: no-store');
            if ($state['retry'] !== null) {
                header('Retry-After: ' . $state['retry']);
            }
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        render_static_page([
            'template' => $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '503.html',
            'status' => 503,
            'vars' => ['message' => $state['message']],
        ]);
    }
}
