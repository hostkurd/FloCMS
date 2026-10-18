<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use FloCMS\Uploader\Chunked\ChunkedUploader;
use FloCMS\Uploader\Uploader;
use FloCMS\Uploader\Video\VideoUploader;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.8.4: framework packages use caret ranges from a tested minimum,
 * so a package an application adds (flocms-admin) can bring the fixes it needs.
 * composer.lock still decides the exact versions: create-project installs the
 * locked set, and an existing site changes only when it runs composer update.
 */
final class DependenciesTest extends TestCase
{
    private const MINIMUMS = [
        'hostkurd/flocms-core' => '^2.4.0',
        'hostkurd/flocms-cli' => '^2.0.1',
        'hostkurd/flocms-uploader' => '^1.2.1',
        'hostkurd/flocms-captcha' => '^1.1.0',
        'hostkurd/flocms-api' => '^1.2.0',
    ];

    private static function composer(string $file): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/' . $file), true, 64, JSON_THROW_ON_ERROR);
    }

    private static function requirements(): array
    {
        return self::composer('composer.json')['require'];
    }

    public function testFrameworkPackagesUseCaretRangesFromTheirTestedMinimum(): void
    {
        $require = self::requirements();
        foreach (self::MINIMUMS as $package => $constraint) {
            self::assertSame($constraint, $require[$package] ?? null, $package);
        }
        self::assertSame('^8.1', $require['php']);
    }

    /** The lock installs the release with the Imagick resize fix (uploader 1.2.1). */
    public function testLockFileInstallsVersionsWithinTheRanges(): void
    {
        $locked = [];
        foreach (self::composer('composer.lock')['packages'] as $package) { $locked[$package['name']] = ltrim($package['version'], 'v'); }
        foreach (self::MINIMUMS as $package => $constraint) {
            $minimum = ltrim($constraint, '^');
            self::assertArrayHasKey($package, $locked);
            self::assertTrue(version_compare($locked[$package], $minimum, '>='), $package . ' ' . $locked[$package] . ' is below ' . $minimum);
            self::assertSame(explode('.', $minimum)[0], explode('.', $locked[$package])[0], $package . ' left its major version');
        }
        self::assertSame('1.2.1', $locked['hostkurd/flocms-uploader']);
    }

    public function testApiControllersAreAutoloaded(): void
    {
        $autoload = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true)['autoload']['psr-4'];

        self::assertSame('api/', $autoload['App\\Api\\']);
    }

    public function testUploaderProvidesVideoAndChunkedUploads(): void
    {
        $config = ['disks' => ['public' => ['driver' => 'local', 'root' => sys_get_temp_dir()]]];

        self::assertInstanceOf(VideoUploader::class, Uploader::video($config));
        self::assertInstanceOf(ChunkedUploader::class, Uploader::video($config)->chunked(sys_get_temp_dir() . '/flocms-chunks-test'));
    }
}
