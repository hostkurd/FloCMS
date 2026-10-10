<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Settings for tests that need a real MySQL/MariaDB server. Set
 * FLO_TEST_MYSQL_HOST (and optionally _PORT, _USER, _PASS, _NAME) to run them.
 */
final class MySqlConfig
{
    /** @return array{host: string, port: int, user: string, pass: string, name: string} */
    public static function require(): array
    {
        $host = getenv('FLO_TEST_MYSQL_HOST');

        if ($host === false || $host === '') {
            TestCase::markTestSkipped('Set FLO_TEST_MYSQL_HOST to run MySQL/MariaDB tests.');
        }

        return [
            'host' => $host,
            'port' => (int) (getenv('FLO_TEST_MYSQL_PORT') ?: 3306),
            'user' => (string) (getenv('FLO_TEST_MYSQL_USER') ?: 'root'),
            'pass' => (string) (getenv('FLO_TEST_MYSQL_PASS') ?: ''),
            'name' => (string) (getenv('FLO_TEST_MYSQL_NAME') ?: 'flocms_test'),
        ];
    }

    /** @return array<string, string> .env overrides for AppServer */
    public static function env(array $db): array
    {
        return [
            'DB_HOST' => $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_USERNAME' => $db['user'],
            'DB_PASSWORD' => $db['pass'],
            'DB_NAME' => $db['name'],
        ];
    }
}
