<?php
class FUNCTIONS{

    public static function getLangPath($lang){
        return $lang == Config::get('default_language')?'':'/'.$lang;
    }
}