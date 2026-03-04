<?php
namespace HostKurd\Flocms\Lib;
use HostKurd\Flocms\Lib\Http\Request;

use PDO;
use PDOException;

class App
{
    protected static ?Router $router = null;
    public static ?Database $db = null;
    public static ?Logger $logger = null;
    /**
     * Get the current router instance (must be initialized by Run()).
     */
    public static function getRouter(): Router
    {
        if (!self::$router instanceof Router) {
            throw new \RuntimeException('Router not initialized. Call App::Run() first.');
        }
        return self::$router;
    }

    public static function logger(): Logger
    {
        if (!self::$logger) {
            self::$logger = new Logger(ROOT . '/storage/logs/app.log');
        }
        return self::$logger;
    }
    /**
     * Optional: check if router is initialized without throwing.
     */
    public static function hasRouter(): bool
    {
        return self::$router instanceof Router;
    }

    // --- Lazy DB getter ---
    public static function db(): ?Database
    {
        if (self::$db instanceof Database) {
            return self::$db;
        }

        // If framework not installed / no DB config, just return null
        if (!self::hasDbConfig()) {
            return null;
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . Config::get('db.host', 'localhost') .
                ';dbname=' . Config::get('db.name') .
                ';charset=' . Config::get('db.charset', 'utf8mb4'),
                Config::get('db.user'),
                Config::get('db.pass'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            self::$db = new Database($pdo);
            return self::$db;

        } catch (PDOException $e) {
            // Keep original exception for debugging (previous)
            throw new \RuntimeException('Database connection failed. Check DB config.', 0, $e);
        }
    }

    private static function hasDbConfig(): bool
    {
        return (bool)(Config::get('db.name') && Config::get('db.user'));
    }

    /** 
     * Single source of truth for router: App creates it once.
     * Do NOT create another Router in index.php.
     */
    public static function Run(string $uri): void
    {
        self::$router = new Router($uri);
        $request = Request::fromGlobals();

        // Global Configuration
        require ROOT . '/public/conf_global.php';

        // Language
        Lang::load(self::$router->getLanguage());

        if ($request->isStateChanging()) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            if (!Csrf::validate($token)) {
                throw new HttpException(419); // add a 419 error page if you like
            }
            // Csrf::rotate(); // optional
        }

        $layout         = self::$router->getRoute();
        $hasAdminAccess = (bool) Session::get('admin_access');

        $controllerName  = self::$router->getController();
        // basic hardening: controller names must be alnum/_ only
        if (!preg_match('/^[a-z0-9_]+$/i', (string)$controllerName)) {
            throw new HttpException(404);
        }

        $controllerClass  = 'HostKurd\\Flocms\\Controllers\\' .
            ucfirst(str_replace(' ', '', (string)$controllerName)) . 'Controller';

        $controllerMethod = strtolower(self::$router->getMethodPrefix() . self::$router->getAction());

        // Admin auth gate
        if ($layout === 'admin' && !$hasAdminAccess && $controllerMethod !== 'admin_login') {
            Router::redirect(SITE_URI . DS . 'admin/users/login/');
            return;
        }
        if ($layout === 'admin' && $hasAdminAccess && $controllerMethod === 'admin_login') {
            Router::redirect(SITE_URI . DS . 'admin/');
            return;
        }

        // Maintenance mode (DB-backed)
        if (!$hasAdminAccess && $layout !== 'admin' && Config::getSetting('offline_mode') === '1') {
            $offlinePath = VIEWS_PATH . DS . 'offline.html';
            echo (new View(null, $offlinePath))->render();
            return;
        }

        // Controller dispatch
        if (!class_exists($controllerClass)) {
            throw new HttpException(404);
        }

        $controller = new $controllerClass;
        $controller->setRequest($request);

        if (!method_exists($controller, $controllerMethod)) {
            throw new HttpException(404);
        }

        $viewPath = $controller->$controllerMethod();
        $content  = (new View($controller->getData(), $viewPath))->render();

        $layoutPath = VIEWS_PATH . DS . $layout . '.html';
        echo (new View(compact('content'), $layoutPath))->render();
    }
}