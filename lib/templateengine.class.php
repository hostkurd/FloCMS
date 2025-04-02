<?php
class TemplateEngine{

  

    public function __construct(){
       
    }

    protected static function Generate($path){

        $contentss = file_get_contents($path);
        echo '<br>'.$contentss.'<br>';
        return;
        // Engine Rules
        $content = preg_replace('/{{\s*(.+?)\s*}}/','<?=$1; ?>',$contentss);
        $content = preg_replace('/@if\(\s*(.+?)\s*\)/','<?php if($1): ?>',$contentss);
        $content = str_replace('@endif','<?php endif; ?>',$contentss);
        $content = preg_replace('/@foreach\{{\s*(.+?)\s*\}}/','<?php foreach($1): ?>',$contentss);
        $content = str_replace('@endforeach','<?php endforeach; ?>',$contentss);
        $content = preg_replace('/@config\(\s*(.+?)\s*\)/','<?=Config::get($1);?>',$contentss);

        //echo $result;
        return $contentss;
    }

    public static function CreateView($path, $data = []){
        $router = App::getRouter();
        $controller_dir = $router->getController();
        $template_name = $router->getController().'_'.$router->getMethodPrefix().$router->getAction();//.'.html';

        extract($data);

        $cacheFile = VIEWS_PATH.DS.'cache'.DS.$template_name.'.php';
        $contents = self::Generate($path);

        file_put_contents($cacheFile, $contents);
        return $cacheFile;

    }
}