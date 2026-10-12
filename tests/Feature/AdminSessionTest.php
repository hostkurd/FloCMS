<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use HostKurd\Flocms\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Backlog #2: suspended/demoted users lose admin access immediately, and the
 * admin login is throttled. Needs MySQL/MariaDB (FLO_TEST_MYSQL_HOST).
 */
final class AdminSessionTest extends TestCase
{
    private ?AppServer $server = null;
    private PDO $pdo;

    protected function setUp(): void
    {
        $db = MySqlConfig::require();
        $this->pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($this->pdo);
        $this->server = AppServer::start(MySqlConfig::env($db), static function (string $root): void {
            // A protected admin page like `php flo make:controller Reports --admin` creates
            file_put_contents($root . '/controllers/ReportsController.php', <<<'CONTROLLER'
<?php
namespace FloCMS\Controllers;

use FloCMS\Core\Controller;

class ReportsController extends Controller
{
    protected array $actionPermissions = ['*' => 'users.manage'];

    public function admin_index(): void
    {
    }
}
CONTROLLER);
            mkdir($root . '/views/reports');
            file_put_contents($root . '/views/reports/admin_index.html', '<p>Reports</p>');
            // vendor/ is a link to the real skeleton, so Composer's autoloader looks
            // there; load the copy's new controller explicitly
            file_put_contents($root . '/config/config.php', "\nrequire_once ROOT . '/controllers/ReportsController.php';\n", FILE_APPEND);
        });
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function login(string $email, string $password = TestDatabase::PASSWORD): array
    {
        $form = $this->server->get('/admin/users/login');
        self::assertSame(200, $form['status'], $form['body']);
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m));

        return $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function testSuspendedAdminIsLoggedOutOnNextRequest(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        $login = $this->login('admin@example.com');
        self::assertSame(302, $login['status']);
        self::assertSame(200, $this->server->get('/admin/reports')['status']);

        $this->pdo->prepare('UPDATE users SET status = 3 WHERE id = ?')->execute([$id]);

        $next = $this->server->get('/admin/reports');
        self::assertSame(302, $next['status']);
        self::assertStringContainsString('admin/users/login', $next['headers']['location'] ?? '');

        $page = $this->server->get('/admin/users/login');
        self::assertStringContainsString('Your session has ended', $page['body']);
        self::assertSame(302, $this->server->get('/admin/reports')['status']);
    }

    public function testLoginLandsOnTheAdminDashboard(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        $login = $this->login('admin@example.com');
        self::assertSame(302, $login['status']);
        self::assertStringEndsWith('/admin/', $login['headers']['location'] ?? '');

        $home = $this->server->get('/admin/');
        self::assertSame(200, $home['status'], $home['body']);
        self::assertStringContainsString('Dashboard', $home['body']);
        self::assertStringContainsString('admin@example.com', $home['body']);
        self::assertStringContainsString('admin/users/logout"', $home['body']);
        self::assertStringContainsString('hostkurd/flocms-admin', $home['body']);
    }

    public function testEditorDashboardShowsTheRole(): void
    {
        TestDatabase::addUser($this->pdo, 'editor@example.com', 1);

        $this->login('editor@example.com');
        $home = $this->server->get('/admin/');

        self::assertSame(200, $home['status'], $home['body']);
        self::assertStringContainsString('Editor', $home['body']);
    }

    public function testAdminHomeSendsTheDashboardToAnAdminPackage(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);
        $this->login('admin@example.com');

        // What an admin package (hostkurd/flocms-admin) or the site sets
        file_put_contents($this->server->root() . '/config/config.php', "\nConfig::set('admin.home', 'reports');\n", FILE_APPEND);
        $home = $this->server->get('/admin/');
        self::assertSame(302, $home['status'], $home['body']);
        self::assertStringEndsWith('/en/admin/reports', $home['headers']['location'] ?? '');

        // Unsafe values are ignored: the skeleton's own page is shown
        file_put_contents($this->server->root() . '/config/config.php', "\nConfig::set('admin.home', 'https://evil.test');\n", FILE_APPEND);
        self::assertSame(200, $this->server->get('/admin/')['status']);
    }

    public function testRemovedUserPagesAreGone(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 3);
        $this->login('admin@example.com');

        foreach (['/admin/users', '/admin/users/add', '/admin/users/edit/1', '/admin/users/profile', '/users/verify?token=abc'] as $url) {
            self::assertSame(404, $this->server->get($url)['status'], $url);
        }
    }

    public function testDemotedAdminLosesPermissionsOnNextRequest(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        $this->login('admin@example.com');
        self::assertSame(200, $this->server->get('/admin/reports')['status']);

        // Editors (role 1) do not have users.manage
        $this->pdo->prepare('UPDATE users SET role = 1 WHERE id = ?')->execute([$id]);
        self::assertSame(403, $this->server->get('/admin/reports')['status']);

        // Users (role 0) have no admin access at all
        $this->pdo->prepare('UPDATE users SET role = 0 WHERE id = ?')->execute([$id]);
        self::assertSame(302, $this->server->get('/admin/reports')['status']);
    }

    public function testDeletedAdminIsLoggedOut(): void
    {
        $id = TestDatabase::addUser($this->pdo, 'admin@example.com', 3);

        $this->login('admin@example.com');
        self::assertSame(200, $this->server->get('/admin/reports')['status']);

        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        self::assertSame(302, $this->server->get('/admin/reports')['status']);
    }

    public function testLoginIsThrottledPerEmail(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        for ($i = 1; $i <= 5; $i++) {
            $attempt = $this->login('admin@example.com', 'wrong-' . $i);
            self::assertSame(200, $attempt['status'], "attempt {$i}");
            self::assertStringContainsString('Login failed', $attempt['body']);
        }

        // Blocked even with the right password
        $blocked = $this->login('admin@example.com');
        self::assertSame(429, $blocked['status']);
        self::assertStringContainsString('Too many login attempts. Please try again in 15 minute(s).', $blocked['body']);
        self::assertSame(302, $this->server->get('/admin/reports')['status']);
    }

    public function testSuccessfulLoginResetsTheEmailCounter(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);

        for ($i = 1; $i <= 4; $i++) {
            $this->login('admin@example.com', 'wrong');
        }
        self::assertSame(302, $this->login('admin@example.com')['status']);
        $this->server->get('/admin/users/logout');

        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(200, $this->login('admin@example.com', 'wrong')['status']);
        }
        self::assertSame(302, $this->login('admin@example.com')['status']);
    }
}
