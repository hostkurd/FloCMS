<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use PHPUnit\Framework\TestCase;

/**
 * Backlog #0: a fresh `composer create-project` must show the welcome page
 * without a database.
 */
final class FreshInstallTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testEnvExampleShipsWithoutDatabaseSettings(): void
    {
        $env = (string) file_get_contents(dirname(__DIR__, 2) . '/.env.example');

        self::assertMatchesRegularExpression('/^DB_NAME=$/m', $env);
        self::assertMatchesRegularExpression('/^DB_USERNAME=$/m', $env);
    }

    public function testWelcomePageWorksWithoutDatabase(): void
    {
        $this->server = AppServer::start();

        $response = $this->server->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringContainsString('The Most lightweight PHP Framework', $response['body']);
        self::assertStringContainsString('Database: not configured', $response['body']);
    }

    public function testWelcomePageShowsUnreachableServer(): void
    {
        // Port 1 is closed: connection refused
        $this->server = AppServer::start([
            'DB_NAME' => 'flocms',
            'DB_USERNAME' => 'root',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
        ]);

        $response = $this->server->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringContainsString('Database: not reachable', $response['body']);
        self::assertStringContainsString('Start MySQL/MariaDB', $response['body']);
        // .env.example ships APP_DEBUG=true, so the driver message is shown
        self::assertStringContainsString('[2002]', $response['body']);
    }

    public function testDriverMessageIsHiddenWithoutDebug(): void
    {
        $this->server = AppServer::start([
            'APP_DEBUG' => 'false',
            'DB_NAME' => 'flocms',
            'DB_USERNAME' => 'root',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
        ]);

        $response = $this->server->get('/');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Database: not reachable', $response['body']);
        self::assertStringNotContainsString('[2002]', $response['body']);
    }

    public function testDatabasePageShowsNoDbServerPage(): void
    {
        // users/verify needs the database; the server is down
        $this->server = AppServer::start([
            'APP_DEBUG' => 'false',
            'DB_NAME' => 'flocms',
            'DB_USERNAME' => 'root',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
        ]);

        $response = $this->server->get('/users/verify?token=abc');

        self::assertSame(503, $response['status']);
        self::assertStringContainsString('Database server unavailable', $response['body']);
        self::assertStringNotContainsString('[2002]', $response['body']);
    }

    public function testDatabasePageWithoutConfigShowsDbErrorPage(): void
    {
        $this->server = AppServer::start(['APP_DEBUG' => 'false']);

        $response = $this->server->get('/users/verify?token=abc');

        self::assertSame(500, $response['status']);
        self::assertStringContainsString('Database configuration error', $response['body']);
        self::assertStringContainsString('Database is not configured', $response['body']);
    }

    public function testUnknownDatabaseOnMySql(): void
    {
        $db = MySqlConfig::require();
        $db['name'] .= '_missing';
        $this->server = AppServer::start(MySqlConfig::env($db));

        $page = $this->server->get('/');
        self::assertSame(200, $page['status']);
        self::assertStringContainsString('Database not found.', $page['body']);
        self::assertStringContainsString('Create the database named in', $page['body']);

        $error = $this->server->get('/users/verify?token=abc');
        self::assertSame(500, $error['status']);
        self::assertStringContainsString('Database configuration error', $error['body']);
        // Debug mode (.env.example): driver message included
        self::assertStringContainsString('Unknown database', $error['body']);
    }

    public function testConfiguredDatabaseShowsNoCard(): void
    {
        $this->server = AppServer::start(MySqlConfig::env(MySqlConfig::require()));

        $response = $this->server->get('/');

        self::assertSame(200, $response['status']);
        self::assertStringNotContainsString('db-status', preg_replace('/<style.*?<\/style>/s', '', $response['body']));
    }
}
