<?php

class Env
{
    public static function load(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(".env file not found: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            $line = trim($line);

            // Skip comments
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Split KEY=value
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove surrounding quotes only
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Do not override real environment variables
            if (array_key_exists($key, $_ENV) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key]
            ?? $_SERVER[$key]
            ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return self::castValue($value);
    }

    private static function castValue(string $value): mixed
    {
        $lower = strtolower($value);

        if ($lower === 'null') return null;
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;

        if (is_numeric($value)) {
            return str_contains($value, '.')
                ? (float) $value
                : (int) $value;
        }

        return $value;
    }
}
