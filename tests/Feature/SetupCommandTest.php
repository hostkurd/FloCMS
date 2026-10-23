<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use App\Commands\SetupCommand;
use FloCMS\CLI\Console\Testing\ApplicationTester;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.0.0: `php flo setup` (run by composer create-project) sets the
 * project up as one of five setups. Composer and the package installers are
 * replaced by a recorder; everything else runs on a copy of the skeleton.
 */
final class SetupCommandTest extends TestCase
{
    private string $root;

    /** @var list<string> */
    private array $ran = [];

    protected function setUp(): void
    {
        $this->root = (realpath(sys_get_temp_dir()) ?: sys_get_temp_dir()) . '/flocms-setup-' . bin2hex(random_bytes(5));
        $source = dirname(__DIR__, 2);
        mkdir($this->root, 0777, true);
        foreach (['composer.json', 'flo', '.env.example'] as $file) {
            copy($source . '/' . $file, $this->root . '/' . $file);
        }
        copy($source . '/.env.example', $this->root . '/.env');
        foreach (['config', 'views', 'templates', 'public/themes/dark', 'commands'] as $dir) {
            self::copyDirectory($source . '/' . $dir, $this->root . '/' . $dir);
        }
        symlink($source . '/vendor', $this->root . '/vendor');

        $this->ran = [];
        SetupCommand::$terminal = static fn (): bool => true;
        SetupCommand::$runner = function (array $command, string $cwd): int {
            $line = self::describe($command);
            $this->ran[] = $line;
            if (str_starts_with($line, 'composer require ')) {
                $composer = json_decode((string) file_get_contents($cwd . '/composer.json'), true);
                foreach (array_diff(array_slice(explode(' ', $line), 2), ['--no-interaction']) as $package) {
                    [$name, $version] = explode(':', $package, 2);
                    $composer['require'][$name] = $version;
                }
                file_put_contents($cwd . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
            if (str_starts_with($line, 'php flo admin:install') && !is_file($cwd . '/config/admin.php')) {
                // What flocms-admin 0.39.3's config stub says
                file_put_contents($cwd . '/config/admin.php', "<?php\n\nreturn [\n    'path' => 'admin',\n    'site_link' => true,\n];\n");
            }
            if (str_starts_with($line, 'php flo api:install')) {
                file_put_contents($cwd . '/public/api.php', "<?php\n");
            }

            return 0;
        };
    }

    protected function tearDown(): void
    {
        SetupCommand::$runner = null;
        SetupCommand::$terminal = null;
        self::delete($this->root);
    }

    /** "php /path/flo admin:install" -> "php flo admin:install", "/usr/bin/php composer.phar require" -> "composer require" */
    private static function describe(array $command): string
    {
        $words = [];
        foreach ($command as $index => $part) {
            if ($index === 0 && $part === PHP_BINARY) {
                $words[] = 'php';
                continue;
            }
            $base = basename(str_replace('\\', '/', $part));
            $words[] = in_array($base, ['flo', 'composer', 'composer.phar'], true) ? $base : $part;
        }
        $line = implode(' ', $words);

        return (string) preg_replace('/^(php composer(\.phar)?|cmd \/c composer) /', 'composer ', $line);
    }

    private function runSetup(array $arguments, array $inputs = [], bool $interactive = true): ApplicationTester
    {
        $tester = ApplicationTester::forProject($this->root);
        $tester->run(['setup', '--no-ansi', ...$arguments], $inputs, ['interactive' => $interactive]);

        return $tester;
    }

    private function config(): string
    {
        return (string) file_get_contents($this->root . '/config/config.php');
    }

    public function testTheExampleFingerprintsMatchTheSkeleton(): void
    {
        foreach (SetupCommand::EXAMPLES as $file => $hash) {
            self::assertSame($hash, SetupCommand::fingerprint(dirname(__DIR__, 2) . '/' . $file), $file . ' changed: update SetupCommand::EXAMPLES');
        }
        self::assertStringContainsString("Config::set('home', 'site');", (string) file_get_contents(dirname(__DIR__, 2) . '/config/config.php'));
    }

    public function testWithoutATerminalNothingChanges(): void
    {
        SetupCommand::$terminal = static fn (): bool => false;
        $before = $this->config();

        $tester = $this->runSetup([]);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('stays as it is (website only)', $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame([], $this->ran);
        self::assertSame($before, $this->config());
        self::assertFileExists($this->root . '/views/pages/index.html');

        // --no-interaction too (composer create-project -n)
        SetupCommand::$terminal = static fn (): bool => true;
        self::assertSame(0, $this->runSetup(['--no-interaction'], [], false)->getStatusCode());
        self::assertSame([], $this->ran);
    }

    public function testTheQuestionDefaultsToWebsiteAndAdmin(): void
    {
        $tester = $this->runSetup([], ['', 'y']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertStringContainsString('What are you building?', $tester->getErrorOutput());
        self::assertSame(['composer require hostkurd/flocms-admin:^0.39.3', 'php flo admin:install'], $this->ran);
        self::assertStringContainsString("Config::set('home', 'site');", $this->config());
        self::assertFileExists($this->root . '/views/pages/index.html');
        self::assertStringContainsString("'site_link' => true", (string) file_get_contents($this->root . '/config/admin.php'));
        self::assertStringContainsString('php flo user:create', $tester->getDisplay());
    }

    public function testAnsweringNoChangesNothing(): void
    {
        $tester = $this->runSetup([], ['3', 'n']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Admin panel only:', $tester->getDisplay());
        self::assertStringContainsString('Nothing changed.', $tester->getDisplay());
        self::assertSame([], $this->ran);
        self::assertFileExists($this->root . '/views/pages/index.html');
    }

    public function testWebsiteOnlyHasNothingToDo(): void
    {
        $tester = $this->runSetup(['--as=site']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('nothing to change', $tester->getDisplay());
        self::assertSame([], $this->ran);
    }

    public function testAdminOnly(): void
    {
        $tester = $this->runSetup(['--as=admin'], ['y']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame(['composer require hostkurd/flocms-admin:^0.39.3', 'php flo admin:install'], $this->ran);
        self::assertStringContainsString("Config::set('home', 'admin');", $this->config());
        self::assertStringContainsString("'site_link' => false", (string) file_get_contents($this->root . '/config/admin.php'));
        self::assertFileDoesNotExist($this->root . '/views/pages/index.html');
        self::assertDirectoryDoesNotExist($this->root . '/templates/dark');
        self::assertDirectoryDoesNotExist($this->root . '/public/themes/dark');
        self::assertFileExists($this->root . '/views/pages/admin_index.html', 'the skeleton /admin page stays');
        self::assertDirectoryExists($this->root . '/templates/default', 'the login page uses it');
    }

    public function testApiAndAdmin(): void
    {
        $tester = $this->runSetup(['--as=api-admin'], ['y']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame([
            'composer require hostkurd/flocms-admin:^0.39.3 hostkurd/flocms-api:^1.3',
            'php flo admin:install',
            'php flo api:install',
        ], $this->ran);
        self::assertStringContainsString("Config::set('home', 'admin');", $this->config());
        self::assertStringContainsString("'site_link' => false", (string) file_get_contents($this->root . '/config/admin.php'));
        self::assertFileDoesNotExist($this->root . '/views/pages/index.html');
    }

    public function testApiOnly(): void
    {
        $tester = $this->runSetup(['--as=api'], ['y']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame(['composer require hostkurd/flocms-api:^1.3', 'php flo api:install'], $this->ran);
        self::assertStringContainsString("Config::set('home', 'api');", $this->config());
        self::assertFileDoesNotExist($this->root . '/config/admin.php');
        self::assertFileDoesNotExist($this->root . '/views/pages/index.html');
    }

    public function testScriptsPassAsWithoutATerminal(): void
    {
        SetupCommand::$terminal = static fn (): bool => false;

        $tester = $this->runSetup(['--as=api', '--no-interaction'], [], false);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        // Without questions, the commands it starts don't ask either.
        self::assertSame(['composer require hostkurd/flocms-api:^1.3 --no-interaction', 'php flo api:install --no-interaction'], $this->ran);
        self::assertStringContainsString("Config::set('home', 'api');", $this->config());
    }

    public function testDryRunOnlyPrintsThePlan(): void
    {
        $before = $this->config();

        $tester = $this->runSetup(['--as=api-admin', '--dry-run']);

        self::assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        foreach ([
            'composer require hostkurd/flocms-admin:^0.39.3 hostkurd/flocms-api:^1.3',
            'php flo admin:install',
            'php flo api:install',
            'remove views/pages/index.html, templates/dark/layouts/default.html, public/themes/dark/css/style.css',
            'config/config.php: home = admin',
            'config/admin.php: site_link = false',
        ] as $step) {
            self::assertStringContainsString($step, $display);
        }
        self::assertSame([], $this->ran);
        self::assertSame($before, $this->config());
        self::assertFileExists($this->root . '/views/pages/index.html');
    }

    public function testAChangedExampleFileIsKept(): void
    {
        file_put_contents($this->root . '/views/pages/index.html', '<h1>My own home page</h1>');

        $tester = $this->runSetup(['--as=admin'], ['y']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('keep views/pages/index.html (changed since the skeleton)', $tester->getDisplay());
        self::assertSame('<h1>My own home page</h1>', file_get_contents($this->root . '/views/pages/index.html'));
        self::assertDirectoryDoesNotExist($this->root . '/templates/dark');
    }

    public function testRunningAgainOnlyAdds(): void
    {
        $this->runSetup(['--as=admin'], ['y']);
        $this->ran = [];

        // Admin -> API + admin: only the API is added.
        $tester = $this->runSetup(['--as=api-admin'], ['y']);
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame(['composer require hostkurd/flocms-api:^1.3', 'php flo admin:install', 'php flo api:install'], $this->ran);
        $this->ran = [];

        // API + admin -> website only: nothing is removed, the steps are printed.
        $tester = $this->runSetup(['--as=site'], ['y']);
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        $display = $tester->getDisplay() . $tester->getErrorOutput();
        self::assertStringContainsString('hostkurd/flocms-admin is installed but not part of this setup', $display);
        self::assertStringContainsString('composer remove hostkurd/flocms-api', $display);
        self::assertSame([], $this->ran);
        self::assertStringContainsString("Config::set('home', 'site');", $this->config());
        // The welcome page was removed by the admin setup: the website gets a home page again.
        self::assertStringContainsString('write views/pages/index.html (a simple home page)', $display);
        self::assertStringContainsString('Your website starts here', (string) file_get_contents($this->root . '/views/pages/index.html'));
        self::assertArrayHasKey('hostkurd/flocms-admin', json_decode((string) file_get_contents($this->root . '/composer.json'), true)['require']);
    }

    public function testAFileReachedThroughALinkIsKept(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('symlinks need extra rights on Windows');
        }
        // templates/dark shared with another site
        $shared = $this->root . '-shared-dark';
        rename($this->root . '/templates/dark', $shared);
        symlink($shared, $this->root . '/templates/dark');

        $tester = $this->runSetup(['--as=admin'], ['y']);

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay() . $tester->getErrorOutput());
        self::assertFileExists($shared . '/layouts/default.html');
        self::assertStringContainsString('Kept templates/dark/layouts/default.html', $tester->getDisplay() . $tester->getErrorOutput());
        self::assertFileDoesNotExist($this->root . '/views/pages/index.html');
        self::delete($shared);
    }

    public function testAFailingCommandStopsWithWhatToDo(): void
    {
        SetupCommand::$runner = function (array $command): int {
            $this->ran[] = self::describe($command);

            return 2;
        };

        $tester = $this->runSetup(['--as=admin'], ['y']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('run `php flo setup` again', $tester->getDisplay() . $tester->getErrorOutput());
        self::assertSame(['composer require hostkurd/flocms-admin:^0.39.3'], $this->ran, 'stops at the first failure');
        self::assertFileExists($this->root . '/views/pages/index.html');
    }

    public function testAnUnknownSetupIsInvalidUsage(): void
    {
        $tester = $this->runSetup(['--as=blog']);

        self::assertSame(2, $tester->getStatusCode());
        self::assertStringContainsString('site-admin, site, admin, api-admin, api', $tester->getDisplay() . $tester->getErrorOutput());
    }

    private static function copyDirectory(string $from, string $to): void
    {
        mkdir($to, 0777, true);
        foreach (scandir($from) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            is_dir($from . '/' . $entry) ? self::copyDirectory($from . '/' . $entry, $to . '/' . $entry) : copy($from . '/' . $entry, $to . '/' . $entry);
        }
    }

    private static function delete(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::delete($path . '/' . $entry);
            }
        }
        @rmdir($path);
    }
}
