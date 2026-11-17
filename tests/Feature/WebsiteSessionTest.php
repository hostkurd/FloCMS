<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use HostKurd\Flocms\Tests\Support\MySqlConfig;
use HostKurd\Flocms\Tests\Support\TestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.1.0: a visitor signed in on the website (e.g. a flocms-frontend
 * account) can still open the admin sign-in form, and signing in there
 * replaces the website's session. Needs MySQL/MariaDB (FLO_TEST_MYSQL_HOST).
 */
final class WebsiteSessionTest extends TestCase
{
    private ?AppServer $server = null;
    private PDO $pdo;
    private int $visitor;

    protected function setUp(): void
    {
        $db = MySqlConfig::require();
        $this->pdo = TestDatabase::connect($db);
        TestDatabase::resetUsers($this->pdo);
        $this->visitor = TestDatabase::addUser($this->pdo, 'visitor@example.com', 0);
        $this->server = AppServer::start(MySqlConfig::env($db), static function (string $root): void {
            // A website sign-in, as a site's own account pages would do it
            file_put_contents($root . '/controllers/SitesessionController.php', <<<'CONTROLLER'
<?php
namespace FloCMS\Controllers;

use FloCMS\Core\Controller;
use FloCMS\Core\Session;

class SitesessionController extends Controller
{
    public function index(): void
    {
        Session::set('user_id', (int) ($_GET['id'] ?? 0));
        Session::set('role', '0');
        Session::set('email', 'visitor@example.com');
        Session::set('website_cart', 'three items');
        Session::set('isloggedin', true);
    }

    public function cart(): void
    {
    }
}
CONTROLLER);
            mkdir($root . '/views/sitesession');
            file_put_contents($root . '/views/sitesession/index.html', '<p>Signed in on the website</p>');
            file_put_contents($root . '/views/sitesession/cart.html', '<p>Cart: <?= htmlspecialchars((string) (\\FloCMS\\Core\\Session::get(\'website_cart\') ?? \'empty\')) ?></p>');
            file_put_contents($root . '/config/config.php', "\nrequire_once ROOT . '/controllers/SitesessionController.php';\n", FILE_APPEND);
        });
    }

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    private function signInOnTheWebsite(): void
    {
        $page = $this->server->get('/en/sitesession?id=' . $this->visitor);
        self::assertSame(200, $page['status'], $page['body']);
        self::assertStringContainsString('Cart: three items', $this->server->get('/en/sitesession/cart')['body']);
    }

    public function testTheFormIsShownWithANote(): void
    {
        $this->signInOnTheWebsite();

        $form = $this->server->get('/admin/users/login');

        self::assertSame(200, $form['status'], $form['body']);
        self::assertStringContainsString('You are signed in on the website', $form['body']);
        self::assertStringContainsString('name="password"', $form['body']);
        self::assertStringNotContainsString('logout</a> first', $form['body']);
    }

    public function testSigningInReplacesTheWebsiteSession(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);
        $this->signInOnTheWebsite();

        $form = $this->server->get('/admin/users/login');
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m), $form['body']);
        $login = $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => 'admin@example.com',
            'password' => TestDatabase::PASSWORD,
        ]);

        self::assertSame(302, $login['status'], $login['body']);
        self::assertStringEndsWith('/admin/', $login['headers']['location'] ?? '');
        $home = $this->server->get('/admin/');
        self::assertSame(200, $home['status'], $home['body']);
        self::assertStringContainsString('admin@example.com', $home['body']);
        self::assertStringNotContainsString('visitor@example.com', $home['body']);
        // Nothing of the website's session is left.
        self::assertStringContainsString('Cart: empty', $this->server->get('/en/sitesession/cart')['body']);
    }

    public function testAWrongPasswordKeepsTheWebsiteSession(): void
    {
        TestDatabase::addUser($this->pdo, 'admin@example.com', 2);
        $this->signInOnTheWebsite();

        $form = $this->server->get('/admin/users/login');
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m));
        $login = $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => 'admin@example.com',
            'password' => 'wrong password',
        ]);

        self::assertSame(200, $login['status'], $login['body']);
        self::assertStringContainsString('Login failed', $login['body']);
        // Still signed in on the website: the form still says so.
        self::assertStringContainsString('You are signed in on the website', $this->server->get('/admin/users/login')['body']);
    }

    public function testAnAccountWithoutPanelAccessIsToldSo(): void
    {
        $form = $this->server->get('/admin/users/login');
        self::assertSame(1, preg_match('/name="_token" value="([a-f0-9]+)"/', $form['body'], $m));
        $login = $this->server->request('POST', '/admin/users/login', [
            '_token' => $m[1],
            'email' => 'visitor@example.com',
            'password' => TestDatabase::PASSWORD,
        ]);
        self::assertSame(302, $login['status'], $login['body']);

        $panel = $this->server->get('/admin/');
        self::assertSame(302, $panel['status']);
        $page = $this->server->get('/admin/users/login');
        self::assertStringContainsString('This account cannot use the admin panel', $page['body']);
        self::assertStringNotContainsString('You are signed in on the website', $page['body']);
    }
}
