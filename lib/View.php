<?php
namespace HostKurd\Flocms\Lib;
use RuntimeException;

class View{
    protected $data;
    protected $path;
    protected $cache;

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

    public function render(): string
    {
        $tpl = new TemplateEngine();

        // load file
        $raw = file_get_contents($this->path);
        if ($raw === false) {
            throw new RuntimeException("View not found: {$this->path}");
        }

        // compile template syntax ({{ }}, @if, etc)
        $compiled = $tpl->decode($raw);

        // make $data variables available in the template
        $data = is_array($this->data) ? $this->data : [];
        extract($data, EXTR_SKIP);

        ob_start();
        
        eval('?>' . $compiled);
        return (string)ob_get_clean();
    }
}