<?php

class Session
{

    public static function start()
    {

        if(session_status() === PHP_SESSION_NONE)
        {

            session_start();

        }

    }

    public static function set($key, $value)
    {

        self::start();

        $_SESSION[$key] = $value;

    }

    public static function get($key, $default = null)
    {

        self::start();

        return $_SESSION[$key] ?? $default;

    }

    public static function has($key)
    {

        self::start();

        return isset($_SESSION[$key]);

    }

    public static function remove($key)
    {

        self::start();

        unset($_SESSION[$key]);
        
    }

    public static function destroy()
    {

        self::start();

        session_destroy();

    }

    public static function flash($key, $value = null)
    {

        self::start();

        if($value !== null)
        {

            $_SESSION['_flash'][$key] = $value;

        } 
        else 
        {

            $message = $_SESSION['_flash'][$key] ?? null;

            unset($_SESSION['_flash'][$key]);

            return $message;

        }

    }

}


?>