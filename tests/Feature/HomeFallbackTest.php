<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.1.0: config 'home.fallback' lets a package answer / while the site
 * has no home page of its own (flocms-frontend's starter picker), and the
 * default layout says the page's language and direction.
 */
final class HomeFallbackTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    private function startWith(string $fallback): AppServer
    {
        return $this->server = AppServer::start([], static function (string $root) use ($fallback): void {
            file_put_contents($root . '/config/config.php', "\n\\FloCMS\\Core\\Config::set('home.fallback', " . $fallback . ");\n", FILE_APPEND);
        });
    }

    public function testAResponseReplacesTheWelcomePage(): void
    {
        $response = $this->startWith("static fn (\\FloCMS\\Core\\Http\\Request \$request): \\FloCMS\\Core\\Http\\Response => \\FloCMS\\Core\\Http\\Response::html('<p>Pick a starter for ' . \$request->method() . '</p>', 503)")->get('/');

        self::assertSame(503, $response['status'], $response['body']);
        self::assertSame('<p>Pick a starter for GET</p>', $response['body']);
    }

    public function testNullKeepsTheWelcomePage(): void
    {
        $response = $this->startWith('static fn (): ?string => null')->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringContainsString('class="flo-welcome"', $response['body']);
    }

    public function testAFunctionNameIsNotCalled(): void
    {
        // A string is a setting someone typed, not a hook: never called.
        $response = $this->startWith("'phpinfo'")->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringNotContainsString('PHP Version', $response['body']);
        self::assertStringContainsString('class="flo-welcome"', $response['body']);
    }

    public function testTheLayoutSaysTheLanguageAndDirection(): void
    {
        $this->server = AppServer::start();

        self::assertMatchesRegularExpression('/<html[^>]* lang="en" dir="ltr">/', $this->server->get('/en/')['body']);
        self::assertMatchesRegularExpression('/<html[^>]* lang="ar" dir="rtl">/', $this->server->get('/ar/')['body']);
        self::assertMatchesRegularExpression('/<html[^>]* lang="ku" dir="rtl">/', $this->server->get('/ku/')['body']);
    }
}
