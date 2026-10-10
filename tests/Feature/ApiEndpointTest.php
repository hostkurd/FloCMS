<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Backlog #6: /api/v1 is served by public/api.php (flocms-api Kernel).
 */
final class ApiEndpointTest extends TestCase
{
    private static ?AppServer $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server = AppServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    public function testHtaccessSendsApiV1ToApiPhp(): void
    {
        $htaccess = (string) file_get_contents(dirname(__DIR__, 2) . '/public/.htaccess');

        self::assertSame(1, preg_match('/^\s*RewriteRule\s+(\S+)\s+api\.php\s+\[L,QSA\]/m', $htaccess, $m));
        $pattern = '#' . $m[1] . '#';
        self::assertSame(1, preg_match($pattern, 'api/v1/health'));
        self::assertSame(1, preg_match($pattern, 'api/v1'));
        self::assertSame(0, preg_match($pattern, 'api/users/list'), 'legacy /api routes stay on index.php');
        self::assertSame(0, preg_match($pattern, 'api/v10/x'));
        // The API rule must come before the catch-all index.php rule
        self::assertLessThan(strpos($htaccess, 'index.php'), strpos($htaccess, 'api.php'));
    }

    public function testHealthRoute(): void
    {
        $response = self::$server->get('/api/v1/health');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringStartsWith('application/json', $response['headers']['content-type'] ?? '');
        self::assertStringContainsString('"status":"ok"', $response['body']);
        self::assertArrayHasKey('x-request-id', $response['headers']);
        self::assertSame('nosniff', $response['headers']['x-content-type-options'] ?? null);
    }

    public function testUnknownRouteIsJson404(): void
    {
        $response = self::$server->get('/api/v1/nope');

        self::assertSame(404, $response['status']);
        self::assertStringStartsWith('application/json', $response['headers']['content-type'] ?? '');
    }

    public function testWrongMethodIs405WithoutCsrf(): void
    {
        // No session, no CSRF token: the API path is not part of the session-based app
        $response = self::$server->request('POST', '/api/v1/health', [], ['Content-Type' => 'application/json']);

        self::assertSame(405, $response['status']);
        self::assertStringContainsString('GET', $response['headers']['allow'] ?? '');
    }

    public function testRequestsAreRateLimited(): void
    {
        $server = AppServer::start(['API_RATE_LIMIT' => '2']);

        try {
            self::assertSame(200, $server->get('/api/v1/health')['status']);
            self::assertSame(200, $server->get('/api/v1/health')['status']);
            self::assertSame(429, $server->get('/api/v1/health')['status']);
        } finally {
            $server->stop();
        }
    }
}
