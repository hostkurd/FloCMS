<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Unit;

use FloCMS\Support\KeyGenerator;
use PHPUnit\Framework\TestCase;

final class KeyGeneratorTest extends TestCase
{
    private string $env;

    protected function setUp(): void
    {
        $this->env = sys_get_temp_dir() . '/flocms-env-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        @unlink($this->env);
    }

    public function testEnvExampleShipsWithoutAKey(): void
    {
        $example = (string) file_get_contents(dirname(__DIR__, 2) . '/.env.example');

        self::assertMatchesRegularExpression('/^APP_KEY=$/m', $example);
        self::assertStringNotContainsString('YFHTnSHarB6hiGIgSRtmg', $example);
    }

    public function testGeneratesA32ByteKey(): void
    {
        $key = KeyGenerator::generate();

        self::assertStringStartsWith('base64:', $key);
        self::assertSame(32, strlen((string) base64_decode(substr($key, 7), true)));
        self::assertNotSame($key, KeyGenerator::generate());
    }

    public function testFillsAnEmptyKeyOnly(): void
    {
        file_put_contents($this->env, "APP_NAME=FloCMS\nAPP_KEY=\nAPP_URL=http://localhost\n");

        $key = KeyGenerator::writeToEnv($this->env);

        self::assertNotNull($key);
        self::assertSame("APP_NAME=FloCMS\nAPP_KEY={$key}\nAPP_URL=http://localhost\n", file_get_contents($this->env));

        // Running again keeps the existing key
        self::assertNull(KeyGenerator::writeToEnv($this->env));
        self::assertStringContainsString("APP_KEY={$key}\n", (string) file_get_contents($this->env));
    }

    public function testForceReplacesTheKey(): void
    {
        file_put_contents($this->env, "APP_KEY=base64:old\n");

        $key = KeyGenerator::writeToEnv($this->env, true);

        self::assertSame("APP_KEY={$key}\n", file_get_contents($this->env));
    }

    public function testAppendsAMissingKey(): void
    {
        file_put_contents($this->env, "APP_NAME=FloCMS");

        $key = KeyGenerator::writeToEnv($this->env);

        self::assertSame("APP_NAME=FloCMS\nAPP_KEY={$key}\n", file_get_contents($this->env));
    }

    public function testFloCommandAndComposerScript(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);
        $scripts = $composer['scripts']['post-create-project-cmd'];

        // .env is created before the key is generated
        self::assertSame('@php flo key:generate', end($scripts));
        self::assertStringContainsString("copy('.env.example', '.env')", $scripts[0]);

        $root = sys_get_temp_dir() . '/flocms-flo-' . bin2hex(random_bytes(6));
        mkdir($root);
        copy(dirname(__DIR__, 2) . '/flo', $root . '/flo');
        symlink(dirname(__DIR__, 2) . '/vendor', $root . '/vendor');
        file_put_contents($root . '/.env', "APP_KEY=\n");

        try {
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/flo') . ' key:generate', $out, $code);
            self::assertSame(0, $code);
            self::assertSame(['APP_KEY set in .env.'], $out);
            self::assertMatchesRegularExpression('/^APP_KEY=base64:[A-Za-z0-9+\/]{43}=$/m', (string) file_get_contents($root . '/.env'));

            $out = [];
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/flo') . ' key:generate', $out, $code);
            self::assertSame(['APP_KEY is already set. Use --force to replace it.'], $out);
        } finally {
            array_map('unlink', [$root . '/flo', $root . '/vendor', $root . '/.env']);
            rmdir($root);
        }
    }
}
