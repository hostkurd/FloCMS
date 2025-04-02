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

    public static function Run($uri){
        //echo self::$router->getRoute();
        self::$router = new Router($uri);
        self::$db = new DB(getenv('DB_HOST'),getenv('DB_USERNAME'),getenv('DB_PASSWORD'),getenv('DB_DATABASE'));

        Lang::load(self::$router->getLanguage());

        $controller_class=ucfirst(str_replace(' ', '', self::$router->getController())).'Controller';
        $controller_method=strtolower(self::$router->getMethodPrefix().self::$router->getAction());

        $layout = self::$router->getRoute();
        $user_role = Session::get('role');
        $has_admin_access = Session::get('admin_access');


        // Check if Admin is not logged in
        if ($layout=='admin' && $has_admin_access == false){
            // Redirect to login page
            if ($controller_method !='admin_login'){
                Router::redirect(SITE_URI.DS.'admin/users/login/');
            }
        }else{
            // If logged in don't show login page
            if ($controller_method =='admin_login'){
                Router::redirect(SITE_URI.DS.'admin/');
            }
        }

        // Check for controller whether it exists or not
        if (class_exists($controller_class)){
            //Calling Function
            $controller_object = new $controller_class;
            
            // Check for method inside controller if exist or not
            if (method_exists($controller_object, $controller_method)){
                $view_path = $controller_object->$controller_method();
                $view_object = new View($controller_object->getData(),$view_path);
                $content = $view_object->render();
                //Render The Page
                $layout_path = VIEWS_PATH.DS.$layout.'.html';
                $layout_view_object = new View(compact('content'),$layout_path);
                
                echo $layout_view_object->renderFinal();
                
            }else{
                //Display 404 Error Page
                $layout_path = VIEWS_PATH.DS.'404.html';
                //echo"Method Not Found!";
                $layout_view_object = new View(NULL,$layout_path);
                //$layout_view_object = new View(compact('content'),$layout_path);
                echo $layout_view_object->render();
            }
        }else{
            // Display 404 Page if class not exist
            $layout_path = VIEWS_PATH.DS.'404.html';
            //echo"Class Not Found!";
            $layout_view_object = new View(NULL,$layout_path);
            //$layout_view_object = new View(compact('content'),$layout_path);
            echo $layout_view_object->render();
        }

    }
}
