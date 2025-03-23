<?php
    //session_set_cookie_params(0, '/', '', true, true);

    include(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'public/conf_global.php');


    //Define('ArabicPath',$router->changeLang('ar'));
    //Define('KurdishPath',$router->changeLang('ku'));
    //require_once('conf_global.php');
    require_once (ROOT.DS.'lib'.DS.'init.php');
    $router = new Router($_SERVER['REQUEST_URI']);

    Define('ACTIVE_LANG',$router->getLanguage());
    Define('EnglishPath',$router->changeLang('en'));
    // Detecting Main Index Page
    Define('CurControl', $router->getController());
    Define('CurAction', $router->getAction());
    Define('isIndexPage',(CurControl == 'pages' && CurAction=='index' ? true : false));

    Define('LANG_SUFFIX', ACTIVE_LANG != 'en'?'_'.ACTIVE_LANG:'');
    
    //Active Language Definition
    IF ($router->getLanguage() == Config::get('default_language')){
        Define('ACTIVE_LANG_PATH','');
    }else{
        Define('ACTIVE_LANG_PATH',ACTIVE_LANG.'/');
    }

    // Check if is Admin or not
    if ($router->getMethodPrefix() =='admin_'){
        Define('IS_ADMIN',true);
    }else{
        Define('IS_ADMIN',false);
    }
    

    session_start();
    
    App::Run($_SERVER['REQUEST_URI']);
    