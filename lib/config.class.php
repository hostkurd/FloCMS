<?php
class Config{

    public static $settings = array();
    public static $db;

    public static function get($key){
        return isset(self::$settings[$key]) ? self::$settings[$key] : null ;
    }

    public static function set($key,$value){
        self::$settings[$key]=$value;
    }

    public static function getSetting($key){
        self::$db = App::$db;
        $sql = "select settings.value from settings where param = '{$key}' and lang='".ACTIVE_LANG."' limit 1";
        $result = self::$db->query($sql);
        return isset($result[0])?strip_tags($result[0]['value'],'<br><ul><li>'):false;
    }
    public static function getThemeSetting($key){
        self::$db = App::$db;
        $sql = "select value from theme_settings where param = '{$key}' limit 1";
        $result = self::$db->query($sql);
        return isset($result[0])?strip_tags($result[0]['value'],'<br><ul><li>'):false;
    }
    public static function getPubSetting($key){
        self::$db = App::$db;
        $sql = "select settings.value from settings where param = '{$key}' and lang='ge' limit 1";
        $result = self::$db->query($sql);
        return isset($result[0]) ? strip_tags($result[0]['value'],'<br><ul><li>') : false;
    }

}