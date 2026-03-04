<?php
namespace HostKurd\Flocms\Lib;

class Security{
    public static function secureText($text){
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    public static function HostKurd($text){
         return strip_tags($text,'<br><ul><li>');
    }
}