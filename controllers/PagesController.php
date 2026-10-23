<?php
namespace FloCMS\Controllers;

use FloCMS\Core\Auth;
use FloCMS\Core\App;
use FloCMS\Core\Controller;
use FloCMS\Models\PagesModel;
use FloCMS\Core\AppUrlValidator;
use FloCMS\Core\Env;
use FloCMS\Core\Http\Response;
use FloCMS\Core\Router;
use FloCMS\Core\Config;
use FloCMS\Core\Session;

class PagesController extends Controller{

    /**
     * Created on first use, so pages that never query the database (like the
     * welcome page) work before a database is set up.
     */
    protected function model(): PagesModel
    {
        return $this->model ??= new PagesModel();
    }

    public function index(){ 
        // An admin-only or API-only project has no website (config 'home').
        $home = self::home(Config::get('home', 'site'));
        if ($home === 'admin') {
            return Response::make('', 302)->header('Location', rtrim((string) SITE_URI, '/') . '/' . ACTIVE_LANG . '/admin');
        }
        if ($home === 'api') {
            return Response::json(['name' => (string) Env::get('APP_NAME', 'FloCMS'), 'api' => rtrim((string) SITE_URI, '/') . '/api/v1']);
        }

        // The home page chosen in the admin panel (Site -> Pages), when there is one.
        if (function_exists('home_page') && ($page = home_page()) !== null) {
            return $page->response();
        }

        if ($this->request->isMethod('POST') && $this->request->input('action') === 'fix_app_url') {
            $this->fixAppUrl();
        }

        $this->data['appUrlWarning'] = null;
        $this->data['suggestedAppUrl'] = null;
        $this->data['canFixAppUrl'] = $this->canFixAppUrl();
        $mismatch = AppUrlValidator::detectStrictMismatch(
            (string) Env::get('APP_URL'),
            $_SERVER
        );
        if ($mismatch !== null) {
            $this->data['appUrlWarning'] = $mismatch['message'];
            $this->data['suggestedAppUrl'] = AppUrlValidator::getSuggestedUrl($_SERVER);
        }

        // Shown as a setup card on the welcome page; the driver detail only in debug mode
        $this->data['dbStatus'] = App::dbStatus();
        $this->data['showDbDetail'] = Env::get('APP_DEBUG') === true;
        
        // $name = \HostKurd\Flocms\Lib\Input::str($this->request->input('name'));
        // $age  = \HostKurd\Flocms\Lib\Input::int($this->request->input('age'));
        $this->data['title'] = 'The Most lightweight PHP Framework';
        $this->data['test']  = 'This is Test Parameter';
    }

    /**
     * The config 'home' value: 'site', 'admin' or 'api'. Anything else is
     * 'site', so a typo never hides the website.
     */
    public static function home(mixed $home): string
    {
        return is_string($home) && in_array($home, ['site', 'admin', 'api'], true) ? $home : 'site';
    }

    /**
     * The one-click APP_URL fix writes to .env, so it is only offered on a
     * local install viewed from the same machine.
     */
    private function canFixAppUrl(): bool
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return Env::get('APP_ENV') === 'local'
            && in_array($remote, ['127.0.0.1', '::1'], true)
            && is_writable(ROOT . DS . '.env');
    }

    private function fixAppUrl(): void
    {
        $url = AppUrlValidator::getSuggestedUrl($_SERVER);

        if (!$this->canFixAppUrl()
            || !filter_var($url, FILTER_VALIDATE_URL)
            || preg_match('/[\s"\'#]/', $url)) {
            return;
        }

        $file = ROOT . DS . '.env';
        $env  = (string) file_get_contents($file);
        $line = 'APP_URL=' . $url;
        $env  = preg_match('/^APP_URL=.*$/m', $env)
            ? (string) preg_replace('/^APP_URL=.*$/m', $line, $env, 1)
            : rtrim($env) . PHP_EOL . $line . PHP_EOL;

        file_put_contents($file, $env, LOCK_EX);

        \FloCMS\Core\Router::redirect($url);
    }

    /**
     * Admin home (/admin/). Login and logout redirect here, so it has to exist.
     * Access is checked by flocms-core: only roles with admin access get here.
     *
     * An admin package (such as hostkurd/flocms-admin) can register its own home
     * page in Config 'admin.home': a path under /admin ('dashboard'), or a
     * non-string callable returning one or null. Set it in config/config.php
     * to choose another page, or to null to keep this one.
     */
    public function admin_index(): void
    {
        $home = self::adminHome(Config::get('admin.home'));
        if ($home !== null) {
            Router::redirect(SITE_URI . DS . ACTIVE_LANG . DS . 'admin/' . $home);
            return;
        }

        $roles = Auth::roles();
        $role  = (int) Session::get('role');

        $this->data['fullName'] = (string) (Session::get('fullname') ?: Session::get('email'));
        $this->data['email']    = (string) Session::get('email');
        $this->data['roleName'] = (string) ($roles[$role] ?? 'Role ' . $role);
    }

    /**
     * The admin.home setting as a safe path under /admin, or null. Only lower
     * case letters, digits, '-' and '/' are accepted, so the setting can never
     * send visitors to another site or outside /admin. Strings are paths, even
     * when a PHP function has the same name. Login and this page are excluded
     * because they would redirect an authenticated user back here.
     */
    public static function adminHome(mixed $home): ?string
    {
        if (!is_string($home) && is_callable($home)) {
            $home = $home();
        }

        if (!is_string($home) || preg_match('#^[a-z0-9]+(?:[/-][a-z0-9]+)*$#', $home) !== 1) {
            return null;
        }

        $segments = explode('/', $home);
        $controller = str_replace('-', '', $segments[0]);
        $action = $segments[1] ?? (string) Config::get('default_action', 'index');

        if ($action === 'login' || ($controller === 'pages' && $action === 'index')) {
            return null;
        }

        return $home;
    }
}
