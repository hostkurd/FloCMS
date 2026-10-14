<?php
declare(strict_types=1);
namespace HostKurd\Flocms\Tests\Unit;
use FloCMS\Core\{Config, TemplateEngine};
use PHPUnit\Framework\TestCase;

final class LoginViewHookTest extends TestCase
{
    private array $session;
    public static function setUpBeforeClass(): void
    {
        if (!defined('VIEWS_PATH')) { define('VIEWS_PATH', sys_get_temp_dir()); }
        if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }
        if (!class_exists('Session', false)) { class_alias(\FloCMS\Core\Session::class, 'Session'); }
    }
    protected function setUp(): void
    {
        $this->session = $_SESSION ?? []; $_SESSION = ['_csrf_token' => 'standalone-login'];
        Config::set('auth.login_view', null); Config::set('view.cache', false);
    }
    protected function tearDown(): void { $_SESSION = $this->session; Config::set('auth.login_view', null); Config::set('view.cache', null); }
    private function render(): string { return TemplateEngine::renderFile(dirname(__DIR__, 2) . '/views/users/admin_login.html'); }
    public function testStandaloneLoginKeepsItsFormAndCsrfWithoutAPackage(): void
    {
        $html = $this->render();
        self::assertStringContainsString('flo-wrapper', $html);
        self::assertStringContainsString('name="email"', $html);
        self::assertStringContainsString('name="password"', $html);
        self::assertStringContainsString('value="standalone-login"', $html);
        self::assertFalse(class_exists('FloCMS\\Admin\\Admin'));
    }
    public function testOptionalRendererReplacesPresentationOnly(): void
    {
        Config::set('auth.login_view', static fn (): string => '<section>Custom login</section>');
        self::assertSame('<section>Custom login</section>', trim($this->render()));
    }
    public function testDisabledOrEmptyRendererFallsBackToTheStandaloneView(): void
    {
        foreach ([null, false, static fn () => null, static fn () => ''] as $renderer) {
            Config::set('auth.login_view', $renderer); self::assertStringContainsString('flo-wrapper', $this->render());
        }
    }
}
