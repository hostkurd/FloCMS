<?php

class App{

    protected static $router;
    public static $db = null;
    /**
     * @return mixed
     */
    public static function getRouter()
    {
        return self::$router;
    }

        // --- NEW: Lazy DB getter ---
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
                ';dbname=' . Config::get('db.name') . ';charset=' . Config::get('db.charset', 'utf8mb4'),
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
            // Option B (recommended): throw a friendly exception for DB pages only
            throw new PDOException('Database connection failed. Check DB config.');
        }
    }

    private static function hasDbConfig(): bool
    {
        // adjust keys if your config uses different names
        return (bool) (Config::get('db.name') && Config::get('db.user'));
    }

    public static function Run($uri)
    {
        self::$router = new Router($uri);

        // Language
        Lang::load(self::$router->getLanguage());

        $layout = self::$router->getRoute();
        $hasAdminAccess = (bool) Session::get('admin_access');

        $controllerClass  = ucfirst(str_replace(' ', '', self::$router->getController())) . 'Controller';
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

        if (!method_exists($controller, $controllerMethod)) {
            throw new HttpException(404);
        }

        $viewPath = $controller->$controllerMethod();
        $content  = (new View($controller->getData(), $viewPath))->renderView();

        $layoutPath = VIEWS_PATH . DS . $layout . '.html';
        echo (new View(compact('content'), $layoutPath))->render();
    }

}
