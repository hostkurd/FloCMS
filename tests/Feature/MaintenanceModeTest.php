<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.7.0: `php flo down` / `php flo up` (flocms-cli) put the website
 * and the API into maintenance mode through storage/framework/down.json.
 */
final class MaintenanceModeTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testDownAnswers503UntilUp(): void
    {
        $this->server = AppServer::start();
        self::assertSame(200, $this->server->get('/')['status']);

        $down = $this->server->flo(['down', '--message=Upgrading <now>', '--retry=120']);
        self::assertSame(0, $down['exit'], $down['output']);

        $page = $this->server->get('/');
        self::assertSame(503, $page['status']);
        self::assertSame('120', $page['headers']['retry-after'] ?? null);
        self::assertStringContainsString('Upgrading &lt;now&gt;', $page['body']);

        $api = $this->server->get('/api/v1/users');
        self::assertSame(503, $api['status']);
        self::assertSame('120', $api['headers']['retry-after'] ?? null);
        self::assertSame('Upgrading <now>', json_decode($api['body'], true)['message']);
        // Health checks keep working
        self::assertSame(200, $this->server->get('/api/v1/health')['status']);

        $up = $this->server->flo(['up']);
        self::assertSame(0, $up['exit'], $up['output']);
        self::assertSame(200, $this->server->get('/')['status']);
    }

    public function testAllowedIpsKeepAccess(): void
    {
        $this->server = AppServer::start();

        $this->server->flo(['down', '--allow=127.0.0.0/8']);

        self::assertSame(200, $this->server->get('/')['status']);
        self::assertNotSame(503, $this->server->get('/api/v1/users')['status']);
    }
}
