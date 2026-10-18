<?php

namespace FloCMS\Controllers;

use FloCMS\Core\Config;
use FloCMS\Core\Auth;
use FloCMS\Core\Controller;
use FloCMS\Core\Router;
use FloCMS\Api\Security\ClientIpResolver;
use FloCMS\Core\Lang;
use FloCMS\Core\Session;
use FloCMS\Models\UsersModel;
use FloCMS\Support\LoginThrottle;

/**
 * Signing in and out of the admin area (/admin/users/login, /admin/users/logout).
 *
 * User management is not part of the skeleton: install hostkurd/flocms-admin
 * (/admin/accounts), or add your own admin_ actions here; see the
 * "Build your own admin" guide in the documentation.
 */
class UsersController extends Controller
{
    // Roles with access to the admin panel when neither Config 'admin_access_roles'
    // nor a roles loader (core 2.4+, e.g. flocms-admin's Roles page) says otherwise.
    protected array $admin_access_roles = ['1', '2', '3'];

    // Permission required per action (checked by flocms-core before the action runs).
    // Actions you add here need users.manage unless listed with null.
    protected array $actionPermissions = [
        '*'            => 'users.manage',
        'admin_login'  => null,
        'admin_logout' => null,
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->model = new UsersModel();
    }

    public function admin_login(): void
    {
        if (Session::get('isloggedin')) {
            if (!Session::get('admin_access')) {
                Session::setFlash(
                    '<strong>You are already logged in.</strong><br>Your account does not have permission to access this area. Please <a href="' . SITE_URI . '/admin/users/logout">logout</a> first.',
                    'warning'
                );
            } else {
                Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/');
                return;
            }

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');

        if ($email === '' || $pass === '') {
            Session::setFlash('Email and password are required.', 'danger');
            return;
        }

        // Brute-force protection: checked before the password is looked at
        $throttle = LoginThrottle::fromConfig();
        $wait = $throttle->attempt($this->clientIp(), $email);

        if ($wait > 0) {
            http_response_code(429);
            Session::setFlash(
                Lang::get(
                    'auth.throttled',
                    'Too many login attempts. Please try again in :minutes minute(s).',
                    ['minutes' => (int) ceil($wait / 60)]
                ),
                'danger'
            );
            return;
        }

        $user = $this->model->getByEmail($email);

        if (!$user) {
            Session::setFlash('Login failed. Email or password is incorrect.', 'danger');
            return;
        }

        $hash = (string) ($user['password'] ?? '');

        if ($hash === '' || !$this->model->verifyPassword($pass, $hash)) {
            Session::setFlash('Login failed. Email or password is incorrect.', 'danger');
            return;
        }

        // Rehash password if needed
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $this->model->rehashPassword((int) $user['id'], $newHash);
            $user['password'] = $newHash;
        }

        $status = (int) ($user['status'] ?? 0);

        switch ($status) {
            case 0:
                Session::setFlash('Login failed.<br>Your account has not been activated.', 'warning');
                return;

            case 1:
                $throttle->clear($email);
                session_regenerate_id(true);

                Session::set('admin_access', false);
                Session::set('user_id', (int) $user['id']);
                Session::set('role', $user['role'] ?? null);
                Session::set('username', $user['login'] ?? null);
                Session::set('email', $user['email'] ?? null);
                Session::set('fullname', $user['fullname'] ?? '');
                Session::set('isloggedin', true);

                $role = $user['role'] ?? null;
                $access = Auth::hasAdminAccess(is_scalar($role) ? $role : null)
                    ?? in_array((string) $role, $this->admin_access_roles, true);

                if ($access) {
                    Session::set('admin_access', true);
                }

                Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/');
                return;

            case 2:
                Session::setFlash(
                    '<strong>Login failed.</strong><br>Your account is pending verification. Please verify your email address.',
                    'info'
                );
                return;

            case 3:
                Session::setFlash(
                    '<strong>Login failed.</strong><br>Your account has been <strong>suspended</strong>. Please contact the website administrator for more information.',
                    'danger'
                );
                return;

            default:
                Session::setFlash('Login failed.<br>Unknown account status.', 'danger');
                return;
        }
    }

    /**
     * Client IP for login throttling. Behind a reverse proxy, list it in
     * Config 'trusted_proxies' (TRUSTED_PROXIES in .env) so X-Forwarded-For is used.
     */
    private function clientIp(): string
    {
        $proxies = array_values(array_filter(array_map('trim', (array) Config::get('trusted_proxies', []))));

        return (new ClientIpResolver($proxies))->resolve($this->request);
    }

    public function admin_logout(): void
    {
        Session::destroy();
        Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/');
    }
}
