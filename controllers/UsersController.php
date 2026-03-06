<?php

namespace FloCMS\Controllers;

use FloCMS\Core\Config;
use FloCMS\Core\Controller;
use FloCMS\Core\Env;
use FloCMS\Core\Router;
use FloCMS\Core\Session;
use FloCMS\Models\UsersModel;

class UsersController extends Controller
{
    protected int $limit_per_page = 10;

    protected array $user_statuses = [
        1 => 'Active',
        2 => 'Pending',
        3 => 'Suspended',
    ];

    protected array $user_roles = [
        0 => 'User',
        1 => 'Editor',
        2 => 'Admin',
        3 => 'Super Admin',
    ];

    protected array $status_colors = [
        1 => 'success',
        2 => 'purple',
        3 => 'danger',
    ];

    protected array $verify_types = [
        0 => 'Not Verified',
        1 => 'Email',
        2 => 'Manual',
    ];

    // User groups which have access to admin panel
    protected array $admin_access_roles = ['1', '2', '3'];

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
                session_regenerate_id(true);

                Session::set('admin_access', false);
                Session::set('role', $user['role'] ?? null);
                Session::set('username', $user['login'] ?? null);
                Session::set('email', $user['email'] ?? null);
                Session::set('fullname', $user['fullname'] ?? '');
                Session::set('isloggedin', true);

                if (in_array((string) ($user['role'] ?? ''), $this->admin_access_roles, true)) {
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

    public function admin_logout(): void
    {
        Session::destroy();
        Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/');
    }

    public function admin_profile(): void
    {
        $this->data['fullName'] = Session::get('fullname');
        $this->data['userName'] = Session::get('username');
        $this->data['userMail'] = Session::get('email');
        $this->data['userRole'] = Session::get('role');
        $this->data['lang'] = 'en';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $email = Session::get('email');

        if ($email && $this->model->updateUser($_POST, $email)) {
            Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/');
            return;
        }

        Session::setFlash('Error updating profile. Unknown error.', 'danger');
    }

    public function admin_add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = bin2hex(random_bytes(30));

        if (!$this->model->save($_POST, $token)) {
            Session::setFlash('Error adding user. Unknown error.', 'danger');
            return;
        }

        $to = (string) ($_POST['email'] ?? '');
        $fullName = htmlspecialchars((string) ($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
        $siteName = (string) Config::get('Site_Name');
        $verifyUrl = SITE_URI . '/users/verify?token=' . urlencode($token);

        $subject = 'Verify your account on ' . $siteName;
        $message = '<html>
            <head>
                <title>Email Verification</title>
            </head>
            <body>
                <div style="width: 100%; text-align: center; font-family: Arial, Helvetica, sans-serif;">
                    <img src="' . imgPath . '/logo.png" alt="Logo">
                    <h2>Verify your email address</h2>
                    <p style="max-width: 450px; margin: 20px auto; color: rgb(106,106,109); line-height: 22px; font-size: 16px;">
                        Hello ' . $fullName . ', welcome to ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '.
                        For your security, we will not activate your account until you verify your email address.
                    </p>
                    <a href="' . $verifyUrl . '" style="text-decoration: none; font-weight: bold; font-size: 18px; color: rgb(63,120,224); display: block; margin: 40px 0;">
                        Verify your email ›
                    </a>
                    <p>
                        If the link above does not work, copy and paste this link into your browser:<br>
                        ' . $verifyUrl . '
                    </p>
                </div>
            </body>
        </html>';

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . Env::get('PRIMARY_EMAIL') . "\r\n";

        if (!mail($to, $subject, $message, $headers)) {
            Session::setFlash('User created, but verification email could not be sent.', 'warning');
        }

        Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/users');
    }

    public function admin_index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keyword'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                $this->model->listUsers(
                    Config::get('LIMIT_PER_PAGE'),
                    1,
                    (string) $_POST['keyword']
                )
            );
            exit;
        }

        $this->data['users'] = $this->model->listUsers($this->limit_per_page, 1);
        $this->data['pagination'] = $this->model->paginationData(
            1,
            Config::get('LIMIT_PER_PAGE'),
            $this->model->getTotal(),
            'users'
        );
        $this->data['pageID'] = 1;
        $this->data['userStatus'] = $this->user_statuses;
        $this->data['statusColors'] = $this->status_colors;
        $this->data['verifyTypes'] = $this->verify_types;
        $this->data['userRole'] = $this->user_roles;
    }

    public function admin_edit(): void
    {
        if (!isset($this->params[0])) {
            Session::setFlash('Invalid user ID.', 'danger');
            return;
        }

        $userId = $this->params[0];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->model->save($_POST, null, $userId)) {
                Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/users');
                return;
            }

            Session::setFlash('Error updating user. Unknown error.', 'danger');
            return;
        }

        $this->data = $this->model->getByID($userId);
    }

    public function admin_delete(): void
    {
        if (!isset($this->params[0])) {
            Session::setFlash('Invalid ID.', 'danger');
            return;
        }

        if ($this->model->delete($this->params[0])) {
            Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/users/');
            return;
        }

        Session::setFlash('Invalid ID.', 'danger');
    }

    public function verify(): void
    {
        $token = (string) ($_GET['token'] ?? '');

        if ($token === '') {
            Session::setFlash(
                '<strong>No token provided.</strong><br>Please supply a valid verification token.',
                'danger'
            );
            $this->data['status'] = 'notoken';
            return;
        }

        if (!$this->model->isUserTokenExist($token)) {
            Session::setFlash(
                '<strong>Verification failed.</strong><br>Invalid verification token, or the token has already been used.',
                'danger'
            );
            $this->data['status'] = 'invalid';
            return;
        }

        if ($this->model->verifyUser($token)) {
            Session::setFlash(
                '<strong>Verification succeeded.</strong><br>Your account has been verified. Please <a href="' . SITE_URI . '/admin/">login here</a>.',
                'success'
            );
            $this->data['status'] = 'verified';
            return;
        }

        Session::setFlash(
            '<strong>Verification failed.</strong><br>We could not verify your account due to an unknown error.',
            'danger'
        );
        $this->data['status'] = 'error';
    }

    public function admin_verify(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'This page is restricted.',
            ]);
            exit;
        }

        $token = (string) ($_POST['token'] ?? '');

        if ($token === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'No token was provided.',
            ]);
            exit;
        }

        if (!$this->model->isUserTokenExist($token)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid token. Invalid verification token has been applied.',
            ]);
            exit;
        }

        if ($this->model->verifyUser($token, 'manual')) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Verification succeeded. The account has been verified.',
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown error has occurred.',
        ]);
        exit;
    }

    public function admin_suspend(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'This page is restricted.',
            ]);
            exit;
        }

        $id = $_POST['userid'] ?? null;
        $action = $_POST['action'] ?? null;

        if (!$id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No user ID was supplied.',
            ]);
            exit;
        }

        if (!$this->model->isUserExist($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No such user exists in the system.',
            ]);
            exit;
        }

        if (!$action) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No action has been specified.',
            ]);
            exit;
        }

        switch ($action) {
            case 'suspend':
                if ($this->model->suspendUser($id)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'User has been suspended.',
                    ]);
                    exit;
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown error has occurred.',
                ]);
                exit;

            case 'unsuspend':
                if ($this->model->unsuspendUser($id)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'The user has been unblocked successfully.',
                    ]);
                    exit;
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown error has occurred.',
                ]);
                exit;

            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid action.',
                ]);
                exit;
        }
    }
}