<?php
/**
   * TemplateEngine
   * 
   * 
   * @package    FloCMS
   * @subpackage Library
   * @author     HostKurd <info@flocms.com>
   */
class TemplateEngine{

    public function __construct(){
       
    }
 
    /**
     * CreateView
     *
     * @param  mixed $path
     * @param  mixed $data
     * @return string
     */
    public static function CreateView($path, $data = []){
        $router = App::getRouter();
        $controller_dir = $router->getController();
        $template_name = $router->getController().'_'.$router->getMethodPrefix().$router->getAction();//.'.html';

        // Get Content of the view file
        $content = file_get_contents($path);
        // 
        $content = self::Decode($content);

        //Cache File Path
        $cacheFile = VIEWS_PATH.DS.'cache'.DS.$template_name.'.php';
        // Save Data to Cache File
        file_put_contents($cacheFile, $content);
        // Return Cache file
        return $cacheFile;
    }
    
    /**
     * Decode
     *
     * @param  mixed $data
     * @return string
     */
    public static function Decode($data){
        // Engine Rules
        $data = preg_replace('/{{\s*(.+?)\s*}}/','<?=$1; ?>',$data);
        $data = preg_replace('/@if\(\s*(.+?)\s*\)/','<?php if($1): ?>',$data);
        $data = str_replace('@endif','<?php endif; ?>',$data);
        $data = preg_replace('/@foreach\(\s*(.+?)\s*\)/','<?php foreach($1): ?>',$data);
        $data = str_replace('@endforeach','<?php endforeach; ?>',$data);
        $data = preg_replace('/@config\(\s*(.+?)\s*\)/','<?=Config::get($1);?>',$data);
        $data = preg_replace('/@lang\(\s*(.+?)\s*\)/','<?=__($1); ?>',$data);
        
        return $data;
    }
}