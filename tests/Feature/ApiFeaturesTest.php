<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.6.0 (D3): public/api.php wiring with flocms-api 1.1.
 */
final class ApiFeaturesTest extends TestCase
{
    private const ORIGIN = 'https://app.example.com';

    private static ?AppServer $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server = AppServer::start(['API_CORS_ORIGINS' => self::ORIGIN . ',https://*.example.org']);
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    public function testErrorResponsesHaveSecurityAndCorsHeaders(): void
    {
        $notFound = self::$server->request('GET', '/api/v1/nope', [], ['Origin' => self::ORIGIN]);
        $notAllowed = self::$server->request('DELETE', '/api/v1/health', [], ['Origin' => self::ORIGIN]);

        foreach ([[$notFound, 404], [$notAllowed, 405]] as [$response, $status]) {
            self::assertSame($status, $response['status']);
            self::assertSame('nosniff', $response['headers']['x-content-type-options'] ?? null);
            self::assertSame('no-store', $response['headers']['cache-control'] ?? null);
            self::assertSame(self::ORIGIN, $response['headers']['access-control-allow-origin'] ?? null);
            self::assertStringStartsWith('application/json', $response['headers']['content-type'] ?? '');
        }
    }

    public function testOriginPatternsAndPreflights(): void
    {
        $preflight = self::$server->request('OPTIONS', '/api/v1/contact', [], [
            'Origin' => 'https://shop.example.org',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ]);

        self::assertSame(204, $preflight['status']);
        self::assertSame('https://shop.example.org', $preflight['headers']['access-control-allow-origin'] ?? null);
        self::assertSame('Content-Type', $preflight['headers']['access-control-allow-headers'] ?? null);
    }

    public function testHealthReportsTheDatabaseAndTheLocale(): void
    {
        $response = self::$server->request('GET', '/api/v1/health?lang=ar');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringContainsString('"status":"ok"', $response['body']);
        self::assertStringContainsString('"db":"not_configured"', $response['body']);
        self::assertSame('ar', $response['headers']['content-language'] ?? null);
    }

    public function testContactValidation(): void
    {
        $invalid = self::$server->json('POST', '/api/v1/contact', ['name' => 'Sara', 'email' => 'not-an-email']);

        self::assertSame(422, $invalid['status'], $invalid['body']);
        $errors = json_decode($invalid['body'], true)['errors'];
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('message', $errors);

        $malformed = self::$server->request('POST', '/api/v1/contact', '{"name":', ['Content-Type' => 'application/json']);
        self::assertSame(400, $malformed['status']);
    }

    public function testContactIsThrottledAndIdempotent(): void
    {
        $server = AppServer::start();

        try {
            $message = ['name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Is the sea villa still available?'];

            $first = $server->json('POST', '/api/v1/contact', $message, ['Idempotency-Key' => 'contact-1']);
            $replay = $server->json('POST', '/api/v1/contact', $message, ['Idempotency-Key' => 'contact-1']);
            self::assertSame(202, $first['status'], $first['body']);
            self::assertSame(202, $replay['status']);
            self::assertSame('true', $replay['headers']['idempotent-replayed'] ?? null);
            self::assertSame(422, $server->json('POST', '/api/v1/contact', ['name' => 'Other'] + $message, ['Idempotency-Key' => 'contact-1'])['status']);

            // 'forms' allows 5 per minute; throttling runs before idempotency, so the
            // three requests above already count.
            $statuses = [];
            for ($i = 0; $i < 6; $i++) {
                $statuses[] = $server->json('POST', '/api/v1/contact', $message)['status'];
            }
            self::assertContains(429, $statuses);
            self::assertSame(202, $statuses[0]);
        } finally {
            $server->stop();
        }
    }

    public function testUsersNeedAuthentication(): void
    {
        $response = self::$server->get('/api/v1/users');

        self::assertSame(401, $response['status']);
    }

    public function testOpenApiDocumentInDebugMode(): void
    {
        $response = self::$server->get('/api/v1/openapi.json');
        $doc = json_decode($response['body'], true);

        self::assertSame(200, $response['status'], $response['body']);
        self::assertSame('3.1.0', $doc['openapi']);
        self::assertArrayHasKey('/api/v1/contact', $doc['paths']);
        self::assertSame('Send a message to the site owner', $doc['paths']['/api/v1/contact']['post']['summary']);
        self::assertSame(['Users'], $doc['paths']['/api/v1/users/{id}']['get']['tags']);
    }

    public function testOpenApiIsHiddenInProduction(): void
    {
        $server = AppServer::start(['APP_DEBUG' => 'false']);

        try {
            self::assertSame(404, $server->get('/api/v1/openapi.json')['status']);
        } finally {
            $server->stop();
        }
    }

    public function testFloRunsApiCommands(): void
    {
        $list = self::$server->flo(['api:list']);
        self::assertSame(0, $list['exit'], $list['output']);
        self::assertStringContainsString('api:key:create', $list['output']);

        $routes = self::$server->flo(['api:routes']);
        self::assertSame(0, $routes['exit'], $routes['output']);
        self::assertMatchesRegularExpression('~POST\s+/api/v1/contact\s+v1\.contact\.store\s+throttle:forms,idempotent~', $routes['output']);
        self::assertMatchesRegularExpression('~GET\s+/api/v1/users\s+v1\.users\.index\s+auth:users\.manage~', $routes['output']);
    }

    public function testLegacyApiRouteIsOptIn(): void
    {
        $routes = static function (string $legacy): array {
            $script = 'define("DS", DIRECTORY_SEPARATOR); define("ROOT", getcwd());'
                . 'require "vendor/autoload.php"; require "includes/compat.php"; require "config/config.php";'
                . 'echo json_encode(FloCMS\Core\Config::get("routes"));';
            $output = shell_exec(
                'LEGACY_API=' . escapeshellarg($legacy) . ' LANGUAGES=en ' . escapeshellarg(PHP_BINARY)
                . ' -r ' . escapeshellarg($script)
            );

            return json_decode((string) $output, true) ?? [];
        };

        $root = dirname(__DIR__, 2);
        $cwd = getcwd();
        chdir($root);
        try {
            self::assertSame(['default' => '', 'admin' => 'admin_', 'login' => 'login_'], $routes('false'));
            self::assertSame(['default' => '', 'admin' => 'admin_', 'api' => 'api_', 'login' => 'login_'], $routes('true'));
        } finally {
            chdir((string) $cwd);
        }
    }
}
