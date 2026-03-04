<?php
namespace HostKurd\Flocms\Lib;

final class Input
{
    public static function str(mixed $v, string $default = ''): string
    {
        if ($v === null) return $default;
        return trim((string)$v);
    }

    public static function int(mixed $v, int $default = 0): int
    {
        if ($v === null || $v === '') return $default;
        return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : $default;
    }

    public static function email(mixed $v, string $default = ''): string
    {
        $s = self::str($v, $default);
        return filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : $default;
    }

    public static function bool(mixed $v, bool $default = false): bool
    {
        $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $b === null ? $default : $b;
    }
}