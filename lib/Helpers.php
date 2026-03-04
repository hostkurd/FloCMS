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