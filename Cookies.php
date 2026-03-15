<?php

class Cookie
{

    public static function set($name, $value, $expire = 3600, $path = '/', $domain = '', $secure = false, $httponly = true)
    {
        setcookie($name, $value, time() + $expire, $path, $domain, $secure, $httponly);

        $_COOKIE[$name] = $value; 

    }

    public static function get($name, $default = null)
    {

        return $_COOKIE[$name] ?? $default;

    }

    public static function has($name)
    {

        return isset($_COOKIE[$name]);

    }

    public static function remove($name, $path = '/', $domain = '')
    {

        setcookie($name, '', time() - 3600, $path, $domain);

        unset($_COOKIE[$name]);

    }

    public static function flash($name, $value = null, $expire = 60)
    {

        if ($value !== null) 
        {

            self::set('_flash_' . $name, $value, $expire);

        } 
        else 
        {

            $cookieName = '_flash_' . $name;

            $message = self::get($cookieName);

            self::remove($cookieName);

            return $message;

        }

    }

}

?>