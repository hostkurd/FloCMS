<?php
// lib/helpers.php

use HostKurd\Flocms\Lib\Lang;

if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $default = null): string
    {
        // Safe fallback if Lang isn't initialized:
        try {
            return Lang::get($key, $replace, $default);
        } catch (\Throwable $e) {
            return $default ?? $key;
        }
    }
}


if (!function_exists('render_static_page')) {
    /**
     * Render a static HTML template with {{placeholders}} and exit.
     *
     * $data keys:
     * - template (string) : full path to template
     * - vars (array)      : placeholder values
     * - status (?int)     : optional HTTP status (if null, don't touch it)
     * - contentType (string) : optional content type
     */
    function render_static_page(array $data): void
    {
        $template    = (string)($data['template'] ?? '');
        $vars        = (array)($data['vars'] ?? []);
        $status      = $data['status'] ?? null;          // can be null
        $contentType = (string)($data['contentType'] ?? 'text/html; charset=UTF-8');

        if (!headers_sent()) {
            if ($status !== null) {
                http_response_code((int)$status);
            }
            header('Content-Type: ' . $contentType);
        }

        $html = ($template !== '' && is_file($template))
            ? (string)file_get_contents($template)
            : '<h1>Page</h1>';

        foreach ($vars as $key => $value) {
            $safe = htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = str_replace('{{' . $key . '}}', $safe, $html);
        }

        echo $html;
        exit;
    }
}