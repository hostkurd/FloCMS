<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use FloCMS\Controllers\PagesController;
use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.0.0: config 'home' decides what / shows (`php flo setup` sets it).
 */
final class HomeSettingTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    private function startWithHome(string $home): AppServer
    {
        return $this->server = AppServer::start([], static function (string $root) use ($home): void {
            $file = $root . '/config/config.php';
            $config = (string) file_get_contents($file);
            file_put_contents($file, str_replace("Config::set('home', 'site');", "Config::set('home', '" . $home . "');", $config));
        });
    }

    public function testSiteShowsTheWelcomePage(): void
    {
        $response = $this->startWithHome('site')->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringContainsString('text/html', $response['headers']['content-type'] ?? '');
    }

    public function testAdminGoesToTheAdminArea(): void
    {
        $response = $this->startWithHome('admin')->get('/');

        self::assertSame(302, $response['status'], $response['body']);
        self::assertMatchesRegularExpression('#/[a-z]{2}/admin$#', $response['headers']['location'] ?? '');
        self::assertSame('', $response['body']);
    }

    public function testApiAnswersAJsonWelcome(): void
    {
        $response = $this->startWithHome('api')->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringStartsWith('application/json', $response['headers']['content-type'] ?? '');
        $body = json_decode($response['body'], true);
        self::assertIsString($body['name'] ?? null);
        self::assertStringEndsWith('/api/v1', $body['api'] ?? '');
    }

    public function testAnUnknownValueKeepsTheWebsite(): void
    {
        self::assertSame('site', PagesController::home('blog'));
        self::assertSame('site', PagesController::home(null));
        self::assertSame('admin', PagesController::home('admin'));
        self::assertSame('api', PagesController::home('api'));

        $response = $this->startWithHome('Admin')->get('/');
        self::assertSame(200, $response['status']);
    }
}
