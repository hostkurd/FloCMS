<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use FloCMS\Uploader\Chunked\ChunkedUploader;
use FloCMS\Uploader\Uploader;
use FloCMS\Uploader\Video\VideoUploader;
use PHPUnit\Framework\TestCase;

/**
 * Release 1.5.0: framework packages are pinned to exact versions, so every
 * site upgrades on purpose.
 */
final class DependenciesTest extends TestCase
{
    private static function requirements(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true)['require'];
    }

    public function testCoreAndUploaderArePinnedExactly(): void
    {
        $require = self::requirements();

        self::assertSame('2.2.0', $require['hostkurd/flocms-core']);
        self::assertSame('1.2.0', $require['hostkurd/flocms-uploader']);
    }

    public function testApiPackageIsPinnedExactlyAndPhpMatchesCore(): void
    {
        $require = self::requirements();

        self::assertSame('1.1.0', $require['hostkurd/flocms-api']);
        self::assertSame('^8.1', $require['php']);
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
