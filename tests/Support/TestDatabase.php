<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Support;

use PDO;

/**
 * Seeds the MySQL/MariaDB test database used by feature tests.
 */
final class TestDatabase
{
    public const PASSWORD = 'correct horse battery staple';

    public static function connect(array $db): PDO
    {
        return new PDO(
            'mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';dbname=' . $db['name'] . ';charset=utf8mb4',
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function resetUsers(PDO $pdo): void
    {
        $pdo->exec((string) file_get_contents(dirname(__DIR__) . '/fixtures/users.sql'));
    }

    public static function addUser(PDO $pdo, string $email, int $role, int $status = 1): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, login, password, email, role, status, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$email, $email, password_hash(self::PASSWORD, PASSWORD_DEFAULT), $email, $role, $status]);

        return (int) $pdo->lastInsertId();
    }
}
