<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.7.0: on a new site, `php flo migrate` creates the users table
 * (database/migrations) and `php flo user:create` the first admin.
 */
final class FirstAdminTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testMigrateThenCreateTheFirstAdmin(): void
    {
        $env = ['DB_TYPE' => 'sqlite', 'DB_NAME' => 'storage/site.sqlite'];
        $this->server = AppServer::start($env);

        $migrate = $this->server->flo(['migrate'], $env);
        self::assertSame(0, $migrate['exit'], $migrate['output']);
        self::assertStringContainsString('create_users_table', $migrate['output']);
        self::assertStringContainsString('hostkurd/flocms-api:2026_10_10_000000_create_api_tables', $migrate['output']);

        $create = $this->server->flo(['user:create', '-n', '--name=Site Admin', '--email=admin@example.com', '--password-stdin'], $env, "correct horse battery\n");
        self::assertSame(0, $create['exit'], $create['output']);
        self::assertStringContainsString('admin@example.com (Super Admin (3), active)', $create['output']);

        $list = $this->server->flo(['user:list', '--format=json'], $env);
        $users = json_decode($list['output'], true);
        self::assertSame('admin@example.com', $users[0]['email']);
        self::assertSame(3, $users[0]['role']);

        $pdo = new \PDO('sqlite:' . $this->server->root() . '/storage/site.sqlite');
        $hash = (string) $pdo->query("SELECT password FROM users WHERE email = 'admin@example.com'")->fetchColumn();
        self::assertTrue(password_verify('correct horse battery', $hash));
    }
}
