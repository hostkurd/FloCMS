<?php

    // Pathes to Bypass Default layout
    Config::set('Standalone_Pages',array('users/verify'));

    // Routes. Route name => method prefix
    $routes = array(
        'default'=>'',
        'admin'=>'admin_',
    );
    // Legacy /api/<controller>/<action> route (api_ methods). It skips CSRF checks
    // and has no authentication, so it is off unless LEGACY_API=true in .env.
    // New APIs belong in api/routes.php (served under /api/v1 by public/api.php).
    if (Env::get('LEGACY_API') === true) {
        $routes['api'] = 'api_';
    }
    $routes['login'] = 'login_';
    Config::set('routes', $routes);
    unset($routes);

    // Defaults, Set default values
    Config::set('default_route','default');
    Config::set('default_controller','pages');
    Config::set('default_action','index');
    Config::set('languages', explode(',', Env::get('LANGUAGES')));
    Config::set('default_language',Env::get('DEFAULT_LANG'));

    // Database Parameters
    Config::set('db.host', Env::get('DB_HOST', 'localhost'));
    Config::set('db.port', Env::get('DB_PORT', 3306));
    Config::set('db.name', Env::get('DB_NAME', null));
    Config::set('db.user', Env::get('DB_USERNAME', null));
    Config::set('db.pass', Env::get('DB_PASSWORD', null));
    Config::set('db.charset', Env::get('DB_CHARSET', 'utf8mb4'));

    // Display
    Config::set('LIMIT_PER_PAGE',25);
    Config::set('LIMIT_PER_PAGE_FRONT',13);

    // UserGroups which has access to Admin Panel
    Config::set('admin_access_roles',array('1', '2', '3'));
            

    // Role => permissions (requires flocms-core 2.1+). Supports 'name', 'prefix.*' and '*'.
    // Roles: 0 User, 1 Editor, 2 Admin, 3 Super Admin. Roles not listed get no permissions.
    Config::set('permissions', array(
        0 => array(),
        1 => array('content.*'),
        2 => array('content.*', 'users.manage', 'users.assign_role', 'settings.*'),
        3 => array('*'),
    ));

    // Reload role/status from the database on every admin request (requires flocms-core 2.2+),
    // so suspended or demoted users lose access immediately.
    Config::set('auth.user_loader', static fn (int $id) => (new \FloCMS\Models\UsersModel())->getByID($id));

    // Admin login brute-force protection (attempts per 15 minutes)
    Config::set('login_throttle', array(
        'max_per_ip'    => 20,
        'max_per_email' => 5,
        'window'        => 900,
    ));

    // Reverse proxies whose X-Forwarded-For header is trusted (IPs or CIDR ranges)
    Config::set('trusted_proxies', array_filter(array_map('trim', explode(',', (string) Env::get('TRUSTED_PROXIES', '')))));
