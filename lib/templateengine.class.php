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
     * Create View cache file
     *
     * @param  mixed $path
     * @return string
     */
    public static function CreateView($path)
    {
        if (!file_exists($path)) {
            throw new Exception("View file '$path' not found.");
        }

        $router = App::getRouter();
        $template_name = $router->getController().'_'.$router->getMethodPrefix().$router->getAction();

        $cacheFile = VIEWS_PATH.DS.'cache'.DS.$template_name.'.php';

        // If cache doesn't exist or template changed
        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($path)) {
            $content = file_get_contents($path);
            $content = self::Decode($content);

            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

            file_put_contents($cacheFile, $content);
        }

        return $cacheFile;
    }

    /**
     * Decode
     *
     * @param  mixed $data
     * @return string
     */
    public static function Decode($data){

        // Raw PHP block
        $data = str_replace('@php', '<?php', $data);
        $data = str_replace('@endphp', '?>', $data);

        // Escaped HTML (safe)
        $data = preg_replace('/{{\s*(.+?)\s*}}/', '<?=htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $data);

        // // Raw HTML (unescaped)
        $data = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?=$1; ?>', $data);

        // Conditionals
        $data = preg_replace('/@if\(\s*(.+?)\s*\)/', '<?php if($1): ?>', $data);
        $data = preg_replace('/@elseif\(\s*(.+?)\s*\)/', '<?php elseif($1): ?>', $data);
        $data = preg_replace('/@else\b/', '<?php else: ?>', $data);
        $data = str_replace('@endif', '<?php endif; ?>', $data);

        // Loops
        $data = preg_replace('/@foreach\(\s*(.+?)\s*\)/', '<?php foreach($1): ?>', $data);
        $data = str_replace('@endforeach', '<?php endforeach; ?>', $data);

        $data = preg_replace('/@for\(\s*(.+?)\s*\)/', '<?php for($1): ?>', $data);
        $data = str_replace('@endfor', '<?php endfor; ?>', $data);

        $data = preg_replace('/@while\(\s*(.+?)\s*\)/', '<?php while($1): ?>', $data);
        $data = str_replace('@endwhile', '<?php endwhile; ?>', $data);

        // Forelse / Empty loop (like Laravel Blade)
        $data = preg_replace('/@forelse\(\s*(.+?)\s*\)/', '<?php if(!empty($1)): foreach($1 as $key => $value): ?>', $data);
        $data = preg_replace('/@empty\b/', '<?php endforeach; else: ?>', $data);
        $data = str_replace('@endforelse', '<?php endif; ?>', $data);

        // Config and Lang
        $data = preg_replace('/@config\(\s*(.+?)\s*\)/', '<?=Config::get($1); ?>', $data);
        $data = preg_replace('/@lang\(\s*(.+?)\s*\)/', '<?=__($1); ?>', $data);

        // Environment Variables
        $data = preg_replace('/@env\(\s*(.+?)\s*\)/', '<?=Env::get($1);?>', $data);

        return $data;
    }
}