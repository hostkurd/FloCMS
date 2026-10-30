<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use App\Commands\SetupCommand;
use PHPUnit\Framework\TestCase;

/**
 * Release 2.0.2: on Windows `php flo setup` ran "composer" (quoted) through
 * cmd.exe. A batch file started by a quoted name found on PATH gets the
 * current folder as %~dp0, so Composer-Setup's composer.bat looked for
 * composer.phar in the project: "Could not open input file:
 * ...\composer.phar".
 */
final class SetupWindowsComposerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = (realpath(sys_get_temp_dir()) ?: sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'flocms-composer-' . bin2hex(random_bytes(5));
        mkdir($this->dir . DIRECTORY_SEPARATOR . 'bin', 0777, true);
        mkdir($this->dir . DIRECTORY_SEPARATOR . 'my project', 0777, true);
    }

    protected function tearDown(): void
    {
        self::delete($this->dir);
    }

    public function testOnlyPartsWithSpecialCharactersAreQuoted(): void
    {
        self::assertSame(
            'composer require "hostkurd/flocms-admin:^0.39.3" --no-interaction',
            SetupCommand::windowsCommandLine(['composer', 'require', 'hostkurd/flocms-admin:^0.39.3', '--no-interaction']),
        );
        self::assertSame(
            'C:\\xampp\\php\\php.exe "D:\\Claude\\Fectum Hellas\\site\\flo" admin:install',
            SetupCommand::windowsCommandLine(['C:\\xampp\\php\\php.exe', 'D:\\Claude\\Fectum Hellas\\site\\flo', 'admin:install']),
        );
        self::assertSame('"C:\\Program Files (x86)\\php.exe" "a&b" ""', SetupCommand::windowsCommandLine(['C:\\Program Files (x86)\\php.exe', 'a&b', '']));
    }

    public function testComposerBatWithThePharBesideItRunsThePhar(): void
    {
        $bin = $this->dir . DIRECTORY_SEPARATOR . 'bin';
        touch($bin . DIRECTORY_SEPARATOR . 'composer.bat');
        touch($bin . DIRECTORY_SEPARATOR . 'composer.phar');

        self::assertSame(
            [PHP_BINARY, $bin . DIRECTORY_SEPARATOR . 'composer.phar'],
            SetupCommand::windowsComposer('C:\\nowhere;"' . $bin . '\\";', ';'),
        );
    }

    public function testAComposerBatAloneIsCalledByItsFullPath(): void
    {
        $bin = $this->dir . DIRECTORY_SEPARATOR . 'bin';
        touch($bin . DIRECTORY_SEPARATOR . 'composer.cmd');

        self::assertSame([$bin . DIRECTORY_SEPARATOR . 'composer.cmd'], SetupCommand::windowsComposer($bin, ';'));
    }

    public function testNoComposerOnPathFallsBackToTheBareName(): void
    {
        self::assertSame(['composer'], SetupCommand::windowsComposer($this->dir . ';;', ';'));
    }

    /** The real thing: Composer-Setup's composer.bat, started from a project folder with a space. */
    public function testComposerBatOnPathRunsFromTheProjectFolder(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            self::markTestSkipped('cmd.exe only.');
        }
        $bin = $this->dir . DIRECTORY_SEPARATOR . 'bin';
        $out = $this->dir . DIRECTORY_SEPARATOR . 'args.json';
        // What Composer-Setup installs, with this PHP instead of "php"
        file_put_contents($bin . DIRECTORY_SEPARATOR . 'composer.bat', "@echo OFF\r\nsetlocal DISABLEDELAYEDEXPANSION\r\n\"" . PHP_BINARY . "\" \"%~dp0composer.phar\" %*\r\n");
        file_put_contents($bin . DIRECTORY_SEPARATOR . 'composer.phar', '<?php file_put_contents(' . var_export($out, true) . ', json_encode(array_slice($argv, 1)));');

        $path = (string) getenv('PATH');
        $binary = getenv('COMPOSER_BINARY');
        putenv('COMPOSER_BINARY');
        putenv('PATH=' . $bin . ';' . $path);
        try {
            $execute = new \ReflectionMethod(SetupCommand::class, 'execute');
            $execute->setAccessible(true);
            $composer = new \ReflectionMethod(SetupCommand::class, 'composer');
            $composer->setAccessible(true);
            $project = $this->dir . DIRECTORY_SEPARATOR . 'my project';

            // The bare name through cmd.exe, as when Composer is not found by its path
            $code = $execute->invoke(null, ['composer', 'require', 'hostkurd/flocms-admin:^0.39.3'], $project);
            self::assertSame(0, $code, 'composer.bat found composer.phar');
            self::assertSame(['require', 'hostkurd/flocms-admin:^0.39.3'], json_decode((string) file_get_contents($out), true));
            unlink($out);

            // What setup runs: the phar beside composer.bat, with this PHP
            $command = $composer->invoke(null);
            self::assertSame([PHP_BINARY, $bin . DIRECTORY_SEPARATOR . 'composer.phar'], $command);
            self::assertSame(0, $execute->invoke(null, [...$command, 'require', 'hostkurd/flocms-admin:^0.39.3'], $project));
            self::assertSame(['require', 'hostkurd/flocms-admin:^0.39.3'], json_decode((string) file_get_contents($out), true));
        } finally {
            putenv('PATH=' . $path);
            if (is_string($binary)) {
                putenv('COMPOSER_BINARY=' . $binary);
            }
        }
    }

    private static function delete(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);

            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::delete($path . DIRECTORY_SEPARATOR . $entry);
            }
        }
        @rmdir($path);
    }
}
