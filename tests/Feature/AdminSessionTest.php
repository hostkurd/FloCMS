<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use HostKurd\Flocms\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Backlog #2: suspended/demoted users lose admin access immediately, and the
 * admin login is throttled. Needs MySQL/MariaDB (FLO_TEST_MYSQL_HOST).
 */
final class AdminSessionTest extends TestCase
{
    private ?AppServer $server = null;
    private PDO $pdo;

    protected function setUp(): void
    {
        $db = MySqlConfig::require();
        $this->pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($this->pdo);
        $this->server = AppServer::start(MySqlConfig::env($db));
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function login(string $email, string $password = TestDatabase::PASSWORD): array
    {
        $form = $this->server->get('/admin/users/login');
        self::assertSame(200, $form['status'], $form['body']);
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m));

        return $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function testSuspendedAdminIsLoggedOutOnNextRequest(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        $login = $this->login('admin@example.com');
        self::assertSame(302, $login['status']);
        self::assertSame(200, $this->server->get('/admin/users')['status']);

        $this->pdo->prepare('UPDATE users SET status = 3 WHERE id = ?')->execute([$id]);

        $next = $this->server->get('/admin/users');
        self::assertSame(302, $next['status']);
        self::assertStringContainsString('admin/users/login', $next['headers']['location'] ?? '');

        $page = $this->server->get('/admin/users/login');
        self::assertStringContainsString('Your session has ended', $page['body']);
        self::assertSame(302, $this->server->get('/admin/users')['status']);
    }

    public function testDemotedAdminLosesPermissionsOnNextRequest(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        $this->login('admin@example.com');
        self::assertSame(200, $this->server->get('/admin/users')['status']);

        // Editors (role 1) do not have users.manage
        $this->pdo->prepare('UPDATE users SET role = 1 WHERE id = ?')->execute([$id]);
        self::assertSame(403, $this->server->get('/admin/users')['status']);

        // Users (role 0) have no admin access at all
        $this->pdo->prepare('UPDATE users SET role = 0 WHERE id = ?')->execute([$id]);
        self::assertSame(302, $this->server->get('/admin/users')['status']);
    }

    public function testDeletedAdminIsLoggedOut(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 3);

        $this->login('admin@example.com');
        self::assertSame(200, $this->server->get('/admin/users')['status']);

        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        self::assertSame(302, $this->server->get('/admin/users')['status']);
    }

    public function testLoginIsThrottledPerEmail(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        for ($i = 1; $i <= 5; $i++) {
            $attempt = $this->login('admin@example.com', 'wrong-' . $i);
            self::assertSame(200, $attempt['status'], "attempt {$i}");
            self::assertStringContainsString('Login failed', $attempt['body']);
        }

        // Blocked even with the right password
        $blocked = $this->login('admin@example.com');
        self::assertSame(429, $blocked['status']);
        self::assertStringContainsString('Too many login attempts. Please try again in 15 minute(s).', $blocked['body']);
        self::assertSame(302, $this->server->get('/admin/users')['status']);
    }

    public function testSuccessfulLoginResetsTheEmailCounter(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        for ($i = 1; $i <= 4; $i++) {
            $this->login('admin@example.com', 'wrong');
        }
        self::assertSame(302, $this->login('admin@example.com')['status']);
        $this->server->get('/admin/users/logout');

        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(200, $this->login('admin@example.com', 'wrong')['status']);
        }
        self::assertSame(302, $this->login('admin@example.com')['status']);
    }
}
