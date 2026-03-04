<?php
namespace HostKurd\Flocms\Lib;

class Functions{

    public static function getLangPath($lang){
        return $lang == Env::get('DEFAULT_LANG')?'':'/'.$lang;
    }

    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}