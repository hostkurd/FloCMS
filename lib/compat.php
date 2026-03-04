<?php
// Backward-compatibility aliases so legacy (non-namespaced) code keeps working.
// You can remove these gradually once all your code uses namespaces.

$aliases = [
    'App'            => 'HostKurd\\Flocms\\Lib\\App',
    'Config'         => 'HostKurd\\Flocms\\Lib\\Config',
    'Controller'     => 'HostKurd\\Flocms\\Lib\\Controller',
    'Cookie'         => 'HostKurd\\Flocms\\Lib\\Cookie',
    'Database'       => 'HostKurd\\Flocms\\Lib\\Database',
    'Env'            => 'HostKurd\\Flocms\\Lib\\Env',
    'ErrorHandler'   => 'HostKurd\\Flocms\\Lib\\ErrorHandler',
    'Functions'      => 'HostKurd\\Flocms\\Lib\\Functions',
    'HttpException'  => 'HostKurd\\Flocms\\Lib\\HttpException',
    'Lang'           => 'HostKurd\\Flocms\\Lib\\Lang',
    'Model'          => 'HostKurd\\Flocms\\Lib\\Model',
    'Router'         => 'HostKurd\\Flocms\\Lib\\Router',
    'Security'       => 'HostKurd\\Flocms\\Lib\\Security',
    'Session'        => 'HostKurd\\Flocms\\Lib\\Session',
    'TemplateEngine' => 'HostKurd\\Flocms\\Lib\\TemplateEngine',
    'Validator'      => 'HostKurd\\Flocms\\Lib\\Validator',
    'View'           => 'HostKurd\\Flocms\\Lib\\View',

    // Controllers
    'PagesController'=> 'HostKurd\\Flocms\\Controllers\\PagesController',
    'UsersController'=> 'HostKurd\\Flocms\\Controllers\\UsersController',

    // Models
    'PagesModel'     => 'HostKurd\\Flocms\\Models\\PagesModel',
    'UsersModel'     => 'HostKurd\\Flocms\\Models\\UsersModel',
];

foreach ($aliases as $short => $fqcn) {
    if (!class_exists($short, false) && class_exists($fqcn)) {
        class_alias($fqcn, $short);
    }
}
