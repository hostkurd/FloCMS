<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.1.0: the session cookie is called flocms_session (like
 * CodeIgniter's ci_session), and public/captcha.php uses the same session as
 * the site, so the form sees the captcha's code.
 */
final class SessionCookieTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testTheSiteNamesItsSessionCookie(): void
    {
        $this->server = AppServer::start();

        $response = $this->server->get('/');

        self::assertSame(200, $response['status'], $response['body']);
        self::assertStringStartsWith('flocms_session=', $response['headers']['set-cookie'] ?? '');
        self::assertStringContainsString('HttpOnly', $response['headers']['set-cookie']);
        self::assertStringContainsString('SameSite=Lax', $response['headers']['set-cookie']);
        self::assertStringNotContainsString('PHPSESSID', $this->cookies());
    }

    public function testTheCaptchaSharesTheSitesSession(): void
    {
        $this->server = AppServer::start();

        $captcha = $this->server->get('/captcha.php');
        self::assertSame(200, $captcha['status'], $captcha['body']);
        self::assertStringStartsWith('flocms_session=', $captcha['headers']['set-cookie'] ?? '');

        // The site reads the captcha's session: no new cookie, no second session
        $page = $this->server->get('/');
        self::assertSame(200, $page['status'], $page['body']);
        self::assertArrayNotHasKey('set-cookie', $page['headers']);
        self::assertStringNotContainsString('PHPSESSID', $this->cookies());
    }

    public function testTheNameIsSetInOnePlace(): void
    {
        $root = dirname(__DIR__, 2);
        $session = (string) file_get_contents($root . '/includes/session.php');

        self::assertStringContainsString("define('FLO_SESSION_NAME', 'flocms_session')", $session);
        foreach (['public/index.php', 'public/captcha.php'] as $file) {
            $code = (string) file_get_contents($root . '/' . $file);
            self::assertStringContainsString('/includes/session.php', $code, $file);
            self::assertStringContainsString('flo_session_start();', $code, $file);
            self::assertDoesNotMatchRegularExpression('/\bsession_start\(/', $code, $file . ' starts the session through flo_session_start()');
        }
    }

    private function cookies(): string
    {
        $jar = $this->server->root() . '.cookies';

        return is_file($jar) ? (string) file_get_contents($jar) : '';
    }
}
