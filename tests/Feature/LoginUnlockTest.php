<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use FloCMS\Api\RateLimit\FileRateLimiter;
use FloCMS\Support\LoginThrottle;
use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.7.0: `php flo login:unlock`, the example application command
 * in commands/.
 */
final class LoginUnlockTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    private function throttle(): LoginThrottle
    {
        // Same storage and limits as LoginThrottle::fromConfig() with config.php's values
        return new LoginThrottle(new FileRateLimiter($this->server->root() . '/storage/cache/login-throttle'), 20, 5, 900);
    }

    public function testUnlocksAnEmailAddress(): void
    {
        $this->server = AppServer::start();
        for ($i = 0; $i < 5; $i++) {
            self::assertSame(0, $this->throttle()->attempt('198.51.100.' . $i, 'admin@example.com'));
        }
        self::assertGreaterThan(0, $this->throttle()->attempt('198.51.100.9', 'admin@example.com'));

        $result = $this->server->flo(['login:unlock', 'admin@example.com']);

        self::assertSame(0, $result['exit'], $result['output']);
        self::assertStringContainsString('Logins for admin@example.com are unlocked.', $result['output']);
        self::assertSame(0, $this->throttle()->attempt('198.51.100.10', 'admin@example.com'));
    }

    public function testUnlocksAnIpAddress(): void
    {
        $this->server = AppServer::start();
        for ($i = 0; $i < 20; $i++) {
            $this->throttle()->attempt('203.0.113.7', 'user' . $i . '@example.com');
        }
        self::assertGreaterThan(0, $this->throttle()->attempt('203.0.113.7', 'other@example.com'));

        $result = $this->server->flo(['login:unlock', '203.0.113.7']);

        self::assertSame(0, $result['exit'], $result['output']);
        self::assertSame(0, $this->throttle()->attempt('203.0.113.7', 'new@example.com'));
    }

    public function testRejectsOtherInput(): void
    {
        $this->server = AppServer::start();

        $result = $this->server->flo(['login:unlock', 'not an address']);

        self::assertSame(2, $result['exit']);
        self::assertStringContainsString('neither an email address nor an IP address', $result['output']);
    }
}
