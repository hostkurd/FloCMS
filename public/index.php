<?php

    Define('ROOT',dirname(dirname(__FILE__)));
    Define('DS',DIRECTORY_SEPARATOR);
    Define('vendor_autoload',ROOT."/vendor/autoload.php");

    if(file_exists(vendor_autoload)){require_once(vendor_autoload);}
    require_once (ROOT.'/lib/init.php');
    

    $router = new Router($_SERVER['REQUEST_URI']);

    //Load Global Configuration
    include(dirname(dirname(__FILE__)).'\public\conf_global.php');
    
    session_start();
    
    App::Run($_SERVER['REQUEST_URI']);