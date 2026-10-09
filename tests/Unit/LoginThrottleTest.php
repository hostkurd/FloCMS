<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use FloCMS\Api\RateLimit\FileRateLimiter;
use FloCMS\Support\LoginThrottle;
use PHPUnit\Framework\TestCase;

final class LoginThrottleTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/flocms-throttle-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    private function throttle(int $perIp = 20, int $perEmail = 5): LoginThrottle
    {
        return new LoginThrottle(new FileRateLimiter($this->dir), $perIp, $perEmail, 900);
    }

    public function testBlocksAnEmailAfterItsLimit(): void
    {
        $throttle = $this->throttle();

        for ($i = 1; $i <= 5; $i++) {
            self::assertSame(0, $throttle->attempt('203.0.113.' . $i, 'admin@example.com'), "attempt {$i}");
        }

        $wait = $throttle->attempt('203.0.113.99', 'Admin@Example.com ');
        self::assertGreaterThan(0, $wait);
        self::assertLessThanOrEqual(900, $wait);
    }

    public function testBlocksAnIpAfterItsLimit(): void
    {
        $throttle = $this->throttle(perIp: 3);

        self::assertSame(0, $throttle->attempt('198.51.100.7', 'a@example.com'));
        self::assertSame(0, $throttle->attempt('198.51.100.7', 'b@example.com'));
        self::assertSame(0, $throttle->attempt('198.51.100.7', 'c@example.com'));
        self::assertGreaterThan(0, $throttle->attempt('198.51.100.7', 'd@example.com'));
        // Other IPs are not affected
        self::assertSame(0, $throttle->attempt('198.51.100.8', 'e@example.com'));
    }

    public function testClearResetsTheEmailCounterOnly(): void
    {
        $throttle = $this->throttle(perIp: 6, perEmail: 2);

        $throttle->attempt('192.0.2.1', 'user@example.com');
        $throttle->attempt('192.0.2.1', 'user@example.com');
        $throttle->clear('user@example.com');

        self::assertSame(0, $throttle->attempt('192.0.2.1', 'user@example.com'));
        self::assertSame(0, $throttle->attempt('192.0.2.1', 'user@example.com'));
        // IP budget (6) is not reset by a successful login: attempts 5 and 6 pass, 7 fails
        $throttle->clear('user@example.com');
        self::assertSame(0, $throttle->attempt('192.0.2.1', 'user@example.com'));
        self::assertSame(0, $throttle->attempt('192.0.2.1', 'user@example.com'));
        self::assertGreaterThan(0, $throttle->attempt('192.0.2.1', 'other@example.com'));
    }
}
