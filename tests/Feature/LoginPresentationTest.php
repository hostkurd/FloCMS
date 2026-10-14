<?php
declare(strict_types=1);
namespace HostKurd\Flocms\Tests\Feature;
use HostKurd\Flocms\Tests\Support\{AppServer, MySqlConfig, TestDatabase};
use PHPUnit\Framework\TestCase;

/** An optional presentation hook must not bypass the skeleton's auth controls. */
final class LoginPresentationTest extends TestCase
{
    private ?AppServer $server = null;
    protected function setUp(): void
    {
        $db = MySqlConfig::require(); $pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($pdo); TestDatabase::addUser($pdo, 'theme@example.test', 2);
        $this->server = AppServer::start(MySqlConfig::env($db), static function (string $root): void {
            file_put_contents($root . '/config/config.php', <<<'PHP'

Config::set('auth.login_view', static function (): string {
    $flash = \FloCMS\Core\Session::getFlash();
    return '<section data-custom-login><form method="post">' . \FloCMS\Core\Csrf::field()
        . '<input name="email"><input name="password" type="password"><button>Sign in</button></form><p>'
        . htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p></section>';
});
PHP, FILE_APPEND);
        });
    }
    protected function tearDown(): void { $this->server?->stop(); }
    private function token(): string
    {
        $page = $this->server->get('/en/admin/users/login');
        self::assertSame(200, $page['status']); self::assertStringContainsString('data-custom-login', $page['body']);
        preg_match('/name="_token" value="([^"]+)"/', $page['body'], $match); self::assertNotEmpty($match[1] ?? ''); return $match[1];
    }
    public function testThemedLoginKeepsCsrfAndCredentialValidation(): void
    {
        $token = $this->token();
        self::assertSame(419, $this->server->request('POST', '/en/admin/users/login', ['email' => 'theme@example.test', 'password' => TestDatabase::PASSWORD])['status']);
        $invalid = $this->server->request('POST', '/en/admin/users/login', ['_token' => $token, 'email' => 'theme@example.test', 'password' => 'wrong']);
        self::assertSame(200, $invalid['status']); self::assertStringContainsString('Login failed', $invalid['body']);
        $signedIn = $this->server->request('POST', '/en/admin/users/login', ['_token' => $this->token(), 'email' => 'theme@example.test', 'password' => TestDatabase::PASSWORD]);
        self::assertSame(302, $signedIn['status']); self::assertStringContainsString('/en/admin/', $signedIn['headers']['location']);
        self::assertSame(302, $this->server->get('/en/admin/users/login')['status']);
    }
    public function testThemedLoginKeepsThrottling(): void
    {
        $token = $this->token();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            self::assertSame(200, $this->server->request('POST', '/en/admin/users/login', ['_token' => $token, 'email' => 'theme@example.test', 'password' => 'wrong'])['status']);
        }
        $blocked = $this->server->request('POST', '/en/admin/users/login', ['_token' => $token, 'email' => 'theme@example.test', 'password' => TestDatabase::PASSWORD]);
        self::assertSame(429, $blocked['status']); self::assertStringContainsString('Too many login attempts', $blocked['body']);
    }
}
