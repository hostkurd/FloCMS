<?php
declare(strict_types=1);

namespace HostKurd\Flocms\Tests\Feature;

use HostKurd\Flocms\Tests\Support\AppServer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Backlog #6: every POST form carries the CSRF token; AJAX sends X-CSRF-TOKEN.
 */
final class CsrfTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function templates(): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];

        foreach (['views', 'templates'] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'html') {
                    $relative = substr($file->getPathname(), strlen($root) + 1);
                    $files[$relative] = [$file->getPathname()];
                }
            }
        }

        return $files;
    }

    #[DataProvider('templates')]
    public function testPostFormsIncludeTheCsrfField(string $file): void
    {
        $html = (string) file_get_contents($file);

        preg_match_all('#<form\b([^>]*)>(.*?)</form>#is', $html, $forms, PREG_SET_ORDER);

        foreach ($forms as [$form, $attributes, $body]) {
            if (!preg_match('/method\s*=\s*["\']?post/i', $attributes)) {
                continue;
            }

            self::assertMatchesRegularExpression(
                '/@csrf\b|Csrf::field\(\)/',
                $body,
                "POST form without CSRF field in {$file}:\n{$form}"
            );
        }

        $this->addToAssertionCount(1);
    }

    public function testAdminLayoutExposesTokenForAjax(): void
    {
        $layout = (string) file_get_contents(dirname(__DIR__, 2) . '/templates/default/layouts/admin.html');

        self::assertStringContainsString('<meta name="csrf-token"', $layout);
        self::assertStringContainsString("template_asset('js/csrf.js')", $layout);
        self::assertFileExists(dirname(__DIR__, 2) . '/public/themes/default/js/csrf.js');
    }

    public function testCsrfIsEnforcedForFormsAndAcceptedFromHeader(): void
    {
        $server = AppServer::start();

        try {
            $form = $server->get('/admin/users/login');
            self::assertSame(200, $form['status'], $form['body']);
            self::assertSame(1, preg_match('/<meta name="csrf-token" content="([a-f0-9]{64})">/', $form['body'], $meta));
            self::assertStringContainsString('name="_token" value="' . $meta[1] . '"', $form['body']);
            self::assertStringContainsString('/themes/default/js/csrf.js', $form['body']);

            // No token: rejected before the controller runs
            $missing = $server->request('POST', '/admin/users/login', ['email' => 'a@example.com', 'password' => 'x']);
            self::assertSame(419, $missing['status']);

            // AJAX style: token in the X-CSRF-TOKEN header (empty fields, so no database needed)
            $ajax = $server->request('POST', '/admin/users/login', ['email' => '', 'password' => ''], ['X-CSRF-TOKEN' => $meta[1]]);
            self::assertSame(200, $ajax['status'], $ajax['body']);
            self::assertStringContainsString('Email and password are required.', $ajax['body']);
        } finally {
            $server->stop();
        }
    }
}
