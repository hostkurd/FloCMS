<?php

class App{

    protected static $router;
    public static $db;
    /**
     * @return mixed
     */
    public static function getRouter()
    {
        return self::$router;
    }

    public static function Run($uri)
    {
        self::$router = new Router($uri);

        // DB
        $pdo = new PDO(
            'mysql:host=' . Config::get('dbHost', 'localhost') .
            ';dbname=' . Config::get('dbName') . ';charset=utf8mb4',
            Config::get('dbUser'),
            Config::get('dbPass'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        self::$db = new Database($pdo);

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
