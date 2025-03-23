<?php
class Security{
    public static function secureText($text){
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    public static function secureTags($text){
         strip_tags($text,'<br><ul><li>');
    }
}