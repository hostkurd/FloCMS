<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Release 1.7.0: the root `flo` file is a thin launcher for flocms-cli's
 * kernel. All commands (core, api:*, key:generate, the app's commands/)
 * come from packages, so `composer update` delivers them.
 */
final class LauncherTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @param list<string> $args
     * @return array{code: int, stdout: string, stderr: string}
     */
    private static function flo(array $args, ?string $cwd = null, string $launcher = 'flo'): array
    {
        $process = proc_open(
            [PHP_BINARY, $launcher, ...$args],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $cwd ?? self::root()
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['code' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    public function testLauncherOnlyBootsTheKernel(): void
    {
        $code = (string) file_get_contents(self::root() . '/flo');

        self::assertStringContainsString('FloCMS\\CLI\\Kernel::handle(__DIR__, $argv)', $code);
        self::assertLessThanOrEqual(5, count(array_filter(explode("\n", $code))));
    }

    public function testListShowsEveryCommand(): void
    {
        $result = self::flo(['list', '--no-ansi']);

        self::assertSame(0, $result['code'], $result['stderr']);
        foreach (['key:generate', 'make:controller', 'migrate', 'doctor', 'api:key:create', 'api:install-schema'] as $command) {
            self::assertStringContainsString($command, $result['stdout']);
        }
    }

    public function testApiCommandsNeedNoForwarding(): void
    {
        $result = self::flo(['api:list', '--no-ansi']);

        self::assertSame(0, $result['code'], $result['stderr']);
        self::assertStringContainsString('api:token:create', $result['stdout']);
    }

    public function testUnknownCommandsFailWithASuggestion(): void
    {
        $result = self::flo(['api:create:schema']);

        self::assertSame(1, $result['code']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('api:install-schema', $result['stderr']);
    }

    public function testKeyGenerateComesFromTheCli(): void
    {
        $result = self::flo(['key:generate', '--show']);

        self::assertSame(0, $result['code'], $result['stderr']);
        self::assertMatchesRegularExpression('/^base64:[A-Za-z0-9+\/=]{44}$/', trim($result['stdout']));
    }

    public function testRunsFromASubfolder(): void
    {
        $result = self::flo(['-V'], self::root() . DIRECTORY_SEPARATOR . 'views', '..' . DIRECTORY_SEPARATOR . 'flo');

        self::assertSame(0, $result['code'], $result['stderr']);
        self::assertMatchesRegularExpression('/^FloCMS CLI \S+ \(core 2\.2\.0, api \S+, uploader 1\.2\.0(, app \S+)?\)$/', trim($result['stdout']));
    }
}
