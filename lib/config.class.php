<?php
class Config{

    public static $settings = array();
    public static $db;

    public static function get($key, $default = null){
        return isset(self::$settings[$key]) ? self::$settings[$key] : $default;
    }

    public static function set($key,$value){
        self::$settings[$key]=$value;
    }

    public static function getSetting($key)
    {
        try {
            $db = App::db();
            if (!$db) return false;

            $sql = "SELECT value FROM settings WHERE param = :param AND lang = :lang LIMIT 1";
            $result = $db->query($sql, [
                'param' => $key,
                'lang'  => ACTIVE_LANG,
            ]);

            return isset($result[0]['value'])
                ? strip_tags($result[0]['value'], '<br><ul><li>')
                : false;

        } catch (\Throwable $e) {
            // table missing / db not installed yet
            return false;
        }
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