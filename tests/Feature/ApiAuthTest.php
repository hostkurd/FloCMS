<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use HostKurd\Flocms\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.6.0: personal access tokens from `php flo api:token:create`
 * protect /api/v1/users (MySQL/MariaDB).
 */
final class ApiAuthTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testTokenAuthenticatedUsersEndpoint(): void
    {
        $db = MySqlConfig::require();
        $pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($pdo);
        $pdo->exec('DROP TABLE IF EXISTS api_tokens, api_keys, api_idempotency');
        $admin = TestDatabase::addUser($pdo, 'admin@example.com', 2);
        $member = TestDatabase::addUser($pdo, 'member@example.com', 0);
        $suspended = TestDatabase::addUser($pdo, 'suspended@example.com', 2, 0);

        $env = MySqlConfig::env($db);
        $this->server = AppServer::start($env);

        $install = $this->server->flo(['api:install-schema'], $env);
        self::assertSame(0, $install['exit'], $install['output']);

        $token = function (int $userId) use ($env): string {
            $result = $this->server->flo(['api:token:create', (string) $userId, 'Test'], $env);
            self::assertSame(0, $result['exit'], $result['output']);
            self::assertSame(1, preg_match('/^(flt_\d+_[0-9a-f]{40})$/m', $result['output'], $matches), $result['output']);

            return $matches[1];
        };
        $get = fn (string $path, string $token): array => $this->server->request('GET', $path, [], ['Authorization' => 'Bearer ' . $token]);

        $adminToken = $token($admin);
        $list = $get('/api/v1/users?per_page=2&sort=-id', $adminToken);
        self::assertSame(200, $list['status'], $list['body']);
        $payload = json_decode($list['body'], true);
        self::assertSame(['page' => 1, 'per_page' => 2, 'total' => 3, 'last_page' => 2], $payload['meta']);
        self::assertSame([$suspended, $member], array_column($payload['data'], 'id'));
        self::assertSame(['id', 'name', 'email', 'role', 'status', 'verified'], array_keys($payload['data'][0]));
        self::assertStringNotContainsString('password', $list['body']);
        self::assertStringContainsString('page=2', (string) $payload['links']['next']);

        $filtered = json_decode($get('/api/v1/users?filter[role]=0', $adminToken)['body'], true);
        self::assertSame([$member], array_column($filtered['data'], 'id'));

        self::assertSame(422, $get('/api/v1/users?sort=password', $adminToken)['status']);
        self::assertSame($admin, json_decode($get('/api/v1/users/' . $admin, $adminToken)['body'], true)['data']['id']);
        self::assertSame(404, $get('/api/v1/users/999999', $adminToken)['status']);

        // Role 0 lacks users.manage; suspended users lose access; unknown tokens are rejected.
        self::assertSame(403, $get('/api/v1/users', $token($member))['status']);
        self::assertSame(401, $get('/api/v1/users', $token($suspended))['status']);
        self::assertSame(401, $get('/api/v1/users', 'flt_1_' . str_repeat('0', 40))['status']);

        // The admin session works too: same-origin JavaScript in the admin panel.
        $form = $this->server->get('/admin/users/login');
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m));
        $login = $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => 'admin@example.com',
            'password' => TestDatabase::PASSWORD,
        ]);
        self::assertSame(302, $login['status']);
        self::assertSame(200, $this->server->get('/api/v1/users')['status']);
        $this->server->forgetCookies();
        self::assertSame(401, $this->server->get('/api/v1/users')['status']);

        // Revoking all of a user's tokens takes effect immediately.
        $revoke = $this->server->flo(['api:token:revoke', '--user=' . $admin], $env);
        self::assertSame(0, $revoke['exit'], $revoke['output']);
        self::assertSame(401, $get('/api/v1/users', $adminToken)['status']);
    }
}
