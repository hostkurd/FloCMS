<?php

Class View{
    protected $data;
    protected $path;

    public static function getDefaultViewPath(){
        $router = App::getRouter();
        if (!$router){
            return false;
        }
        $controller_dir = $router->getController();
        $template_name = $router->getMethodPrefix().$router->getAction().'.html';
        return VIEWS_PATH.DS.$controller_dir.DS.$template_name;
    }

    public function __construct($data=array(), $path=null){
        if(!$path){
            $path = self::getDefaultViewPath();
        }
        if (!file_exists($path)){
            echo 'View file does not exist! '.$path;
        }
        $this->cache = VIEWS_PATH.DS.'cache'.DS;
        $this->path = $path;
        $this->data = $data;
    }


    public function render(){
        $data = $this->data;

        ob_start();
        include $this->path;
        $content = ob_get_clean();

        return $content;

        //return TemplateEngine::CreateView($this->path, $this->data);
    }

    public function renderFinal(){
        $data = TemplateEngine::GenerateTemplate($this->data);

        ob_start();
        include $this->path;
        $content = ob_get_clean();

        return $content;

        //return TemplateEngine::CreateView($this->path, $this->data);
    }



}