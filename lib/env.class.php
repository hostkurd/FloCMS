<?php

class Env {
    
    public static function load($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found: $filePath");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Split "KEY=value"
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove quotes if any
            $value = trim($value, "\"'");

            // Store in global environment
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    public static function get($key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return self::castValue($value);
    }


    private static function castValue($value)
    {
        $lower = strtolower($value);

        // null
        if ($lower === 'null') return null;
        // boolean true
        if ($lower === 'true' || $value === '1') return true;
        // boolean false
        if ($lower === 'false' || $value === '0') return false;

        // numeric (int or float)
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // default: raw string
        return $value;
    }
}