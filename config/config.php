<?php

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
    Config::set('default_controller','pages');
    Config::set('default_action','index');
    Config::set('languages', explode(',', Env::get('LANGUAGES')));
    Config::set('default_language',Env::get('DEFAULT_LANG'));

    // Database Parameters
    Config::set('db.host', Env::get('DB_HOST', 'localhost'));
    Config::set('db.name', Env::get('DB_NAME', null));
    Config::set('db.user', Env::get('DB_USERNAME', null));
    Config::set('db.pass', Env::get('DB_PASSWORD', null));
    Config::set('db.charset', Env::get('DB_CHARSET', 'utf8mb4'));

    // Display
    Config::set('LIMIT_PER_PAGE',25);
    Config::set('LIMIT_PER_PAGE_FRONT',13);

    // UserGroups which has access to Admin Panel
    Config::set('admin_access_roles',array('1', '2', '3'));
            
