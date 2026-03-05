<?php
// Backward-compatibility aliases so legacy (non-namespaced) code keeps working.
// You can remove these gradually once all your code uses namespaces.

$aliases = [
    'App'            => 'FloCMS\\Core\\App',
    'Config'         => 'FloCMS\\Core\\Config',
    'Controller'     => 'FloCMS\\Core\\Controller',
    'Cookie'         => 'FloCMS\\Core\\Cookie',
    'Database'       => 'FloCMS\\Core\\Database',
    'Env'            => 'FloCMS\\Core\\Env',
    'ErrorHandler'   => 'FloCMS\\Core\\ErrorHandler',
    'Functions'      => 'FloCMS\\Core\\Functions',
    'HttpException'  => 'FloCMS\\Core\\HttpException',
    'Lang'           => 'FloCMS\\Core\\Lang',
    'Model'          => 'FloCMS\\Core\\Model',
    'Router'         => 'FloCMS\\Core\\Router',
    'Security'       => 'FloCMS\\Core\\Security',
    'Session'        => 'FloCMS\\Core\\Session',
    'TemplateEngine' => 'FloCMS\\Core\\TemplateEngine',
    'Validator'      => 'FloCMS\\Core\\Validator',
    'View'           => 'FloCMS\\Core\\View',
    'Request'        => 'FloCMS\\Core\\Http\\Request',
    'Response'       => 'FloCMS\\Core\\Http\\Response',
];

foreach ($aliases as $short => $fqcn) {
    if (!class_exists($short, false) && class_exists($fqcn)) {
        class_alias($fqcn, $short);
    }
}
