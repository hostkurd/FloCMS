<?php
    Config::set('Site_Name', 'Marjan Energy Company');
    Config::set('Site_Name_Admin', 'Marjan Admin Panel');
    Config::set('primary_email', 'info@marjanenergy.com');
    // Non Constant Variables
    Define ('isSubDomain', true);

    // Supported Languages
    Config::set('languages',array('en', 'ar', 'ku'));

    // Pathes to Bypass Default layout
    Config::set('Standalone_Pages',array('users/verify'));

    // Routes. Route name => method prefix
    Config::set('routes',array(
        'default'=>'',
        'admin'=>'admin_',
        'api'=>'api_',
        'login'=>'login_'
    ));

    // Defaults, Set default values
    Config::set('default_route','default');
    Config::set('default_language','en');
    Config::set('default_admin_lang','en');
    Config::set('default_controller','pages');
    Config::set('default_action','index');

    // if Website is in the root directory this shoud be false


    // Database paramaters
    Config::set('db.host','localhost');
    Config::set('db.user','zhyar_sarmad');
    Config::set('db.pass','3132547Kh@');
    Config::set('db.db_name','zhyar_marjan');

    Config::set('salt','Amadyxy%$25@ccx');

    // Display
    Config::set('LIMIT_PER_PAGE',25);
    Config::set('LIMIT_PER_PAGE_FRONT',13);

    Config::set('APP_NAME','FloCMS Framework'); 
    Config::set('APP_VER','1.3'); 

    // UserGroups which has access to Admin Panel
    Config::set('admin_access_roles',array('1', '2', '3'));
            
