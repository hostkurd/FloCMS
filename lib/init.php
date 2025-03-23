<?php

    // AutoLoad Classes function
    spl_autoload_register(function($class_name){
        $lib_file =ROOT.DS."lib".DS.strtolower($class_name).".class.php";
        $controller_file=ROOT.DS."controllers".DS.str_replace('controller','',strtolower($class_name)).".controller.php";
        $model_file=ROOT.DS."models".DS.str_replace('model','',strtolower($class_name)).".model.php";

        if (file_exists($lib_file)) {
            require_once($lib_file);
        }elseif(file_exists($controller_file)){
            require_once($controller_file);
        }elseif(file_exists($model_file)){
            require_once($model_file);
        }else{
            //Router::redirect(SITE_URI.DS.ACTIVE_LANG_PATH);
            // Die("The Controller: <strong>{$class_name}</strong> Could not be found");

            // show 404 Page
           //$layout_path = VIEWS_PATH.DS.'404.html';
           //$layout_view_object = new View(NULL,$layout_path);
           //echo $layout_view_object->render();
        }
    });

    // Importing Config Files
    require_once (ROOT.DS."config".DS."config.php");

    function __($key,$default_value=''){
        return Lang::get($key,$default_value);
    }

 