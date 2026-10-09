<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\TestCase;

/**
 * Backlog #5: views, layouts and partials are compiled to views/cache and included.
 */
final class TemplateCacheTest extends TestCase
{
    private ?AppServer $server = null;

    protected function tearDown(): void
    {
        $this->server?->stop();
        $this->server = null;
    }

    public function testWelcomePageIsServedFromCompiledTemplates(): void
    {
        $this->server = AppServer::start();
        $cache = $this->server->root() . '/views/cache';

        $first = $this->server->get('/');
        self::assertSame(200, $first['status']);

        $compiled = array_map('basename', glob($cache . '/*.php') ?: []);
        sort($compiled);
        self::assertCount(3, $compiled, implode(', ', $compiled));
        self::assertMatchesRegularExpression('/^default_/', $compiled[0]);   // layout
        self::assertMatchesRegularExpression('/^header_/', $compiled[1]);    // partial
        self::assertMatchesRegularExpression('/^index_/', $compiled[2]);     // view

        $second = $this->server->get('/');
        self::assertSame($first['body'], $second['body']);
        self::assertCount(3, glob($cache . '/*.php') ?: []);
    }

    public function testEditedViewIsRecompiled(): void
    {
        $this->server = AppServer::start();
        $view = $this->server->root() . '/views/pages/index.html';

        $this->server->get('/');
        file_put_contents($view, (string) file_get_contents($view) . '<p id="edited">Edited view</p>');
        touch($view, time() + 5);

        self::assertStringContainsString('Edited view', $this->server->get('/')['body']);
        self::assertCount(3, glob($this->server->root() . '/views/cache/*.php') ?: []);
    }

    public function testCacheDirectoryIsKeptButIgnored(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileExists($root . '/views/cache/.gitignore');
        self::assertFileDoesNotExist($root . '/views/cache/pages_index.php');
    }
}
