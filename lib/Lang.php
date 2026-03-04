<?php
namespace HostKurd\Flocms\Lib;

class Lang{
    protected static $data;

    public static function load($lang): void
    {
        $lang = $lang ?: 'en';

        $file = ROOT . '/lang/' . $lang . '.php';
        if (!file_exists($file)) {
            $file = ROOT . '/lang/en.php'; // fallback
        }

        $data = include $file;

        // Ensure array
        self::$data = is_array($data) ? $data : [];
    }

    public static function get($key, $default_value = '')
    {
        if (!is_array(self::$data)) {
            return $default_value;
        }

        $key = strtolower($key);
        return self::$data[$key] ?? $default_value;
    }

    public static function isRTL(): bool
    {
        // if language not loaded yet, default LTR
        if (!is_array(self::$data)) {
            return false;
        }

        $dir = self::$data['lng.dir'] ?? 'ltr';
        return strtolower($dir) === 'rtl';
    }
}