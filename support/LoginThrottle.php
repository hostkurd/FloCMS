<?php
declare(strict_types=1);

namespace FloCMS\Support;

use FloCMS\Api\RateLimit\FileRateLimiter;
use FloCMS\Api\RateLimit\RateLimiterInterface;
use FloCMS\Core\Config;

/**
 * Brute-force protection for the admin login: counts attempts per client IP
 * and per email address. Every attempt counts; a successful login clears the
 * email counter.
 *
 * Config::set('login_throttle', [
 *     'max_per_ip'    => 20,   // attempts per IP per window
 *     'max_per_email' => 5,    // attempts per email per window
 *     'window'        => 900,  // seconds
 * ]);
 */
final class LoginThrottle
{
    public function __construct(
        private readonly RateLimiterInterface $limiter,
        private readonly int $maxPerIp = 20,
        private readonly int $maxPerEmail = 5,
        private readonly int $window = 900
    ) {
    }

    public static function fromConfig(): self
    {
        $config = (array) Config::get('login_throttle', []);

        return new self(
            new FileRateLimiter(ROOT . DS . 'storage' . DS . 'cache' . DS . 'login-throttle'),
            max(1, (int) ($config['max_per_ip'] ?? 20)),
            max(1, (int) ($config['max_per_email'] ?? 5)),
            max(1, (int) ($config['window'] ?? 900))
        );
    }

    /**
     * Record a login attempt.
     *
     * @return int seconds until the next attempt is allowed; 0 when this one may proceed
     */
    public function attempt(string $ip, string $email): int
    {
        $byIp = $this->limiter->hit(self::ipKey($ip), $this->maxPerIp, $this->window);
        $byEmail = $this->limiter->hit(self::emailKey($email), $this->maxPerEmail, $this->window);

        $wait = 0;
        foreach ([$byIp, $byEmail] as $result) {
            if (!$result->allowed) {
                $wait = max($wait, $result->retryAfter, 1);
            }
        }

        return $wait;
    }

    /**
     * Reset the email counter after a successful login. The IP counter keeps
     * running, so one valid account cannot be used to reset an IP's budget.
     */
    public function clear(string $email): void
    {
        $this->limiter->clear(self::emailKey($email));
    }

    /**
     * Unlock a client IP right away (`php flo login:unlock <ip>`): the IP
     * counter otherwise runs until the window ends.
     */
    public function unlockIp(string $ip): void
    {
        $this->limiter->clear(self::ipKey($ip));
    }

    private static function ipKey(string $ip): string
    {
        return 'login:ip:' . $ip;
    }

    private static function emailKey(string $email): string
    {
        return 'login:email:' . strtolower(trim($email));
    }
}
