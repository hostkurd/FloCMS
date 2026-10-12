<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use HostKurd\Flocms\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;
use PDO;

final class LogoutRecoveryTest extends TestCase
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
    }

    private function login(string $email): array
    {
        $form = $this->server->get('/admin/users/login');
        self::assertSame(200, $form['status'], $form['body']);
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $match));
        return $this->server->request('POST', '/admin/users/login', [
            '_token' => $match[1], 'email' => $email, 'password' => TestDatabase::PASSWORD,
        ]);
    }

    public function testNonAdminCanLogOutAndThenSignInAsAnAdmin(): void
    {
        TestDatabase::addUser($this->pdo, 'user@example.test', 0);
        TestDatabase::addUser($this->pdo, 'admin@example.test', 2);
        self::assertSame(302, $this->login('user@example.test')['status']);
        $logout = $this->server->get('/admin/users/logout');
        self::assertStringEndsWith('/en/admin/', $logout['headers']['location'] ?? '');
        self::assertSame(302, $this->login('admin@example.test')['status']);
        $home = $this->server->get('/admin/');
        self::assertSame(200, $home['status']);
        self::assertStringContainsString('admin@example.test', $home['body']);
    }

    public function testDemotedAdminCanLogOutAndSwitchAccounts(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'demoted@example.test', 2);
        TestDatabase::addUser($this->pdo, 'admin@example.test', 2);
        self::assertSame(302, $this->login('demoted@example.test')['status']);
        $this->pdo->prepare('UPDATE users SET role = 0 WHERE id = ?')->execute([$id]);
        self::assertSame(302, $this->server->get('/admin/')['status']);
        $logout = $this->server->get('/admin/users/logout');
        self::assertStringEndsWith('/en/admin/', $logout['headers']['location'] ?? '');
        self::assertSame(302, $this->login('admin@example.test')['status']);
        self::assertSame(200, $this->server->get('/admin/')['status']);
    }
}
