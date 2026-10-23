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
        self::assertStringContainsString('create_settings_table', $migrate['output']);
        // flocms-api brings its tables when it is installed (2.0: not by default)
        if (class_exists(\FloCMS\Api\Kernel::class)) {
            self::assertStringContainsString('hostkurd/flocms-api:2026_10_10_000000_create_api_tables', $migrate['output']);
        } else {
            self::assertStringNotContainsString('create_api_tables', $migrate['output']);
        }

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

    /**
     * Release 1.8.5: `php flo migrate` also creates the settings table that
     * core reads for offline_mode (and flocms-admin uses for its settings pages),
     * so `php flo down` and `up` change the database switch on a new site too.
     */
    public function testMigrateCreatesTheSettingsTableUsedByMaintenanceMode(): void
    {
        $env = ['DB_TYPE' => 'sqlite', 'DB_NAME' => 'storage/site.sqlite', 'LANGUAGES' => 'en,ar', 'DEFAULT_LANG' => 'en'];
        $this->server = AppServer::start($env);
        $migrate = $this->server->flo(['migrate'], $env);
        self::assertSame(0, $migrate['exit'], $migrate['output']);

        $pdo = new \PDO('sqlite:' . $this->server->root() . '/storage/site.sqlite');
        $columns = array_column($pdo->query('PRAGMA table_info(settings)')->fetchAll(\PDO::FETCH_ASSOC), 'name');
        self::assertSame(['param', 'value', 'lang'], $columns);

        $down = $this->server->flo(['down'], $env);
        self::assertSame(0, $down['exit'], $down['output']);
        self::assertStringNotContainsString('was not changed', $down['output']);
        $rows = $pdo->query("SELECT lang, value FROM settings WHERE param = 'offline_mode' ORDER BY lang")->fetchAll(\PDO::FETCH_KEY_PAIR);
        self::assertSame(['ar' => '1', 'en' => '1'], $rows);

        $up = $this->server->flo(['up'], $env);
        self::assertSame(0, $up['exit'], $up['output']);
        self::assertSame(['0'], array_values(array_unique($pdo->query("SELECT value FROM settings WHERE param = 'offline_mode'")->fetchAll(\PDO::FETCH_COLUMN))));
    }
}
