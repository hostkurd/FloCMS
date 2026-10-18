<?php
declare(strict_types=1);
namespace HostKurd\Flocms\Tests\Feature;
use HostKurd\Flocms\Tests\Support\{AppServer, MySqlConfig, TestDatabase};
use PHPUnit\Framework\TestCase;

/**
 * Core 2.4 roles loader (used by flocms-admin's Roles page): the login asks
 * Auth::hasAdminAccess(), so a role added at runtime signs in at once and a role
 * whose panel access was taken away does not.
 */
final class RolesLoaderLoginTest extends TestCase
{
    private ?AppServer $server = null;

    protected function setUp(): void
    {
        $db = MySqlConfig::require(); $pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($pdo);
        TestDatabase::addUser($pdo, 'author@example.test', 4);
        TestDatabase::addUser($pdo, 'editor@example.test', 1);
        $this->server = AppServer::start(MySqlConfig::env($db), static function (string $root): void {
            file_put_contents($root . '/config/config.php', <<<'PHP'

\FloCMS\Core\Auth::loadRolesUsing(static fn (): array => [
    'roles' => [0 => 'User', 1 => 'Editor', 2 => 'Admin', 3 => 'Super Admin', 4 => 'Author'],
    'permissions' => [0 => [], 1 => ['content.*'], 2 => ['content.*'], 3 => ['*'], 4 => ['content.posts.*']],
    'admin_access_roles' => ['2', '3', '4'],
    'levels' => [4 => 1],
]);
// Without the session refresh, only the login itself decides admin access.
Config::set('auth.user_loader', null);
PHP, FILE_APPEND);
        });
    }

    protected function tearDown(): void { $this->server?->stop(); }

    private function login(string $email): array
    {
        $page = $this->server->get('/en/admin/users/login');
        preg_match('/name="_token" value="([^"]+)"/', $page['body'], $match);
        return $this->server->request('POST', '/en/admin/users/login', ['_token' => $match[1] ?? '', 'email' => $email, 'password' => TestDatabase::PASSWORD]);
    }

    public function testARoleFromTheLoaderOpensTheAdminArea(): void
    {
        self::assertSame(302, $this->login('author@example.test')['status']);
        $home = $this->server->get('/en/admin/');
        self::assertSame(200, $home['status'], 'admin access from the loader, not from the login\'s own list');
        self::assertStringContainsString('Author', $home['body'], 'the role name from Auth::roles()');
    }

    public function testARoleWithoutPanelAccessStaysOut(): void
    {
        $this->login('editor@example.test');
        $home = $this->server->get('/en/admin/');
        self::assertSame(302, $home['status']);
        self::assertStringContainsString('/admin/users/login', $home['headers']['location']);
    }
}
