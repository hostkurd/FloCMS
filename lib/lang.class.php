<?php

class Lang{
    protected static $data;

    public static function load($lang_code){
        $lang_file_path = ROOT.DS.'lang'.DS.strtolower($lang_code).'.php';

        if (file_exists($lang_file_path)){
            self::$data = include ($lang_file_path);
        }else{
            echo'Language file not found.';
        }
    }

    public static function get($key,$default_value=''){
        return isset(self::$data[strtolower($key)]) ? self::$data[strtolower($key)] : $default_value;
    }

    public static function isRTL(){
        $dir = self::$data['lng.dir'];
        return ($dir=='rtl') ? true : false;;
    }
}