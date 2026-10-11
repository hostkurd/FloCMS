<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * /admin/ is where login and logout redirect to. Without a session it must send
 * visitors to the login form (no database needed for that).
 * The logged-in dashboard is covered in AdminSessionTest (needs MySQL).
 */
final class AdminHomeTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testAdminHomeRedirectsGuestsToLogin(): void
    {
        $this->server = AppServer::start();

        foreach (['/admin', '/admin/', '/en/admin/'] as $path) {
            $response = $this->server->get($path);

            self::assertSame(302, $response['status'], $path . ': ' . $response['body']);
            self::assertStringContainsString('admin/users/login', $response['headers']['location'] ?? '', $path);
        }
    }
}
