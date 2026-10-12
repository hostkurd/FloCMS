<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use FloCMS\Controllers\PagesController;
use FloCMS\Controllers\UsersController;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.8.0: the skeleton keeps signing in and the /admin page; the admin
 * interface comes from a package (hostkurd/flocms-admin), which registers its
 * home page in Config 'admin.home'.
 */
final class AdminAreaTest extends TestCase
{
    public function testAdminHomeAcceptsPathsUnderAdmin(): void
    {
        self::assertSame('dashboard', PagesController::adminHome('dashboard'));
        self::assertSame('panel/home', PagesController::adminHome('panel/home'));
        self::assertSame('dashboard', PagesController::adminHome(static fn (): string => 'dashboard'));
    }

    public function testAdminHomeIgnoresEverythingElse(): void
    {
        foreach ([null, '', 'https://evil.test', '//evil.test', '../pages', 'dashboard?x=1', 'Dashboard', '/dashboard', 'a//b', 42, ['dashboard']] as $value) {
            self::assertNull(PagesController::adminHome($value), var_export($value, true));
        }

        self::assertNull(PagesController::adminHome(static fn (): ?string => null), 'a package that is not set up');
        self::assertNull(PagesController::adminHome(static fn (): string => 'https://evil.test'));
    }

    public function testUsersControllerOnlySignsInAndOut(): void
    {
        $actions = array_values(array_filter(
            get_class_methods(UsersController::class),
            static fn (string $method): bool => str_starts_with($method, 'admin_') || $method === 'verify'
        ));
        sort($actions);

        self::assertSame(['admin_login', 'admin_logout'], $actions);
        self::assertFileDoesNotExist(dirname(__DIR__, 2) . '/views/users/admin_index.html');
        self::assertFileExists(dirname(__DIR__, 2) . '/views/users/admin_login.html');
    }

    public function testComposerSuggestsTheAdminPackage(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

        self::assertArrayHasKey('hostkurd/flocms-admin', $composer['suggest']);
        self::assertArrayNotHasKey('hostkurd/flocms-admin', $composer['require'], 'optional: never required');
    }
}
