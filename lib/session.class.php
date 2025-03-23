<?php

class Session{
    protected static $flash_message;
    protected static $message_type;

    // Message Types : info, success, danger, warning
    public static function setFlash($message, $type='info'){
        self::$flash_message = $message;
        self::$message_type = $type;
    }

    public static function hasFlash(){
        return !is_null(self::$flash_message);
    }

    public static function Flash(){
        echo self::$flash_message;
        self::$flash_message = null;
    }

    public static function flashType(){
        echo self::$message_type;
            self::$message_type = null;
    }

    public static function set($key,$value){
      $_SESSION[$key]=$value;
    }

    public static function get($key){
        if (isset($_SESSION[$key])){
            return $_SESSION[$key];
        }
        return null;
    }

    public static function delete($key){
        if (isset($_SESSION[$key])){
            unset ($_SESSION[$key]);
        }
        return null;
    }

    public static function destroy(){
        session_destroy();
    }
}