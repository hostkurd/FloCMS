<?php

class Cookie{

    // Expiry in Hours
    public static function set($key,$value,$expiry){
        setcookie($key,$value,(time() + ($expiry * 3600)), "/");
    }

    public static function get($key){
        if (isset($_COOKIE[$key])){
            return $_COOKIE[$key];
        }
        return null;
    }

    public static function delete($key){
        if (isset($_COOKIE[$key])){
            unset ($_COOKIE[$key]);
            setcookie($key, '', time() - ((24 * 3600)),"/"); 
        }
        return null;
    }

    public static function isValid($key){
        if (isset($_COOKIE[$key])){
            return true;
        }
        return false;
    }
}