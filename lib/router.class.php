<?php
class Router{
    protected $uri;
    protected $controller;
    protected $action;
    protected $params;
    protected $method_prefix;
    protected $language;
    protected $route;

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function getMethodPrefix()
    {
        return $this->method_prefix;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return mixed|null
     */
    public function getRoute()
    {
        return $this->route;
    }

    public function __construct($uri)
    {
       $this->uri=urldecode(trim($uri,'/'));
       //Get Defaults Data
        $routes = Config::get('routes');
        $this->route=Config::get('default_route');
        $this->method_prefix = isset($routes[$this->route]) ? $routes[$this->route] : '';
        $this->language = Config::get('default_language');
        $this->controller = Config::get('default_controller');
        $this->action = Config::get('default_action');

        $uri_parse = explode('?',$this->uri);

        // Get Path data ony like: inc/controller/action
        $path=$uri_parse[0];

        $path_parts=explode('/',$path);
        //array_shift($path_parts);
        //array_shift($path_parts);
        // For Sub domain Only, for root delete this
        if(isSubDomain == true){
            array_shift($path_parts);
        }
       


       // Check for admin and language
        if(in_array(strtolower(current($path_parts)),Config::get('languages'))){
            $this->language = strtolower(current($path_parts));
            array_shift($path_parts);
        }

        if (in_array(strtolower(current($path_parts)),array_keys($routes))){
            $this->route = strtolower(current($path_parts));
            $this->method_prefix = isset($routes[$this->route])?$routes[$this->route]:'';
            array_shift($path_parts);
        }

        // Get Controller
        if (current($path_parts)){
            $this->controller = strtolower(current($path_parts));
            array_shift($path_parts);
        }

        //Get Action
        if (current($path_parts)){
            $this->action = strtolower(current($path_parts));
            array_shift($path_parts);
        }

        // set the rest on params
        $this->params = $path_parts;
    }

    public static function redirect($location){
        header("location: $location");
    }

    public function changeLang($lang){
        $controller = self::getController();
        $action = self::getAction();
        $params = self::getParams();

       
        if ($lang == Config::get('default_language')){
            $langP = '';
        }else{
            $langP = '/'.$lang;
        }
        if (isset($controller)){
            if ($controller == 'pages' && $action == 'index'){
                $controllerP='';
            }else{
                $controllerP='/'.$controller;   
            }
               
        }else{
            $controllerP='';
        }
        if (isset($action) && $action !='index'){
            $actionP='/'.$action;
        }else{
            $actionP='';
        }
        if (isset($params)){
            $paramsP ='/'.implode('/',$params);

        }
        return $langP.$controllerP.$actionP.$paramsP;
    }
}