<?php
declare(strict_types=1);

/*
 * The session cookie, shared by every entry point that uses the session
 * (public/index.php, public/captcha.php), so they all read the same session.
 *
 * The cookie is called "flocms_session", the way CodeIgniter uses
 * "ci_session" and CakePHP "CAKEPHP". Tools such as Wappalyzer recognise
 * FloCMS sites by this name. To use another name, change FLO_SESSION_NAME
 * below (letters, digits and underscores, with at least one letter). Changing
 * it signs everyone out once, because the old cookie is no longer read.
 *
 * This runs before .env is read, so the name can't come from .env.
 */

if (!defined('FLO_SESSION_NAME')) {
    define('FLO_SESSION_NAME', 'flocms_session');
}

if (!function_exists('flo_session_start')) {
    function flo_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        );

        session_name(FLO_SESSION_NAME);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        session_start();
    }
}
