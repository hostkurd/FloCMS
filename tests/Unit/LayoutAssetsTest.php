<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Release 1.7.8: every layout loads its files through template_asset(), and
 * every file it asks for exists in the default theme.
 */
final class LayoutAssetsTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function layouts(): array
    {
        $root = dirname(__DIR__, 2);
        $cases = [];
        foreach (glob($root . '/templates/*/layouts/*.html') ?: [] as $file) {
            $cases[substr($file, strlen($root) + 1)] = [$file];
        }

        return $cases;
    }

    /**
     * @dataProvider layouts
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('layouts')]
    public function testLayoutAssetsExist(string $file): void
    {
        $layout = (string) file_get_contents($file);

        self::assertStringNotContainsString('/assets/', $layout, 'the skeleton has no public/assets/ folder');
        self::assertStringNotContainsString('jsPath', $layout);
        self::assertStringNotContainsString('cssPath', $layout);

        preg_match_all("/template_asset\\('([^']+)'\\)/", $layout, $matches);
        foreach ($matches[1] as $asset) {
            self::assertFileExists(dirname(__DIR__, 2) . '/public/themes/default/' . $asset, $file . ' loads ' . $asset);
        }
    }
}
