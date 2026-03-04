<?php
namespace HostKurd\Flocms\Lib;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    // Accept token from form field or header
    public static function validate(?string $token): bool
    {
        $sessionToken = Session::get(self::SESSION_KEY);
        if (!$sessionToken || !$token) return false;
        return hash_equals($sessionToken, $token);
    }

    // Optional: rotate after successful validation (reduces replay risk)
    public static function rotate(): void
    {
        Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"_token\" value=\"{$t}\">";
    }
}