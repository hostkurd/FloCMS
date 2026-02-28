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

    // if Website is in the root directory this shoud be false
    Config::set('salt','Amadyxy%$25@ccx');

    // Display
    Config::set('LIMIT_PER_PAGE',25);
    Config::set('LIMIT_PER_PAGE_FRONT',13);

    // UserGroups which has access to Admin Panel
    Config::set('admin_access_roles',array('1', '2', '3'));
            
