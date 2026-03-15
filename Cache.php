<?php

class Cache
{
    private static array $data = [];

    public static function set(string $key, mixed $value, int $expire = 3600): void
    {

        self::$data[$key] = [
            'value' => $value,
            'expire' => time() + $expire
        ];

    }

    public static function get(string $key, mixed $default = null): mixed
    {

        if (isset(self::$data[$key])) 
        {

            if (self::$data[$key]['expire'] > time()) 
            {

                return self::$data[$key]['value'];

            } 
            else 
            {

                unset(self::$data[$key]);

            }
        }

        return $default;

    }

    public static function has(string $key): bool
    {

        return isset(self::$data[$key]) && self::$data[$key]['expire'] > time();

    }

    public static function remove(string $key): void
    {
        
        unset(self::$data[$key]);

    }

    public static function clear(): void
    {

        self::$data = [];

    }

    public static function flash(string $key, mixed $value = null, int $expire = 60): mixed
    {

        if ($value !== null) 
        {

            self::set('_flash_' . $key, $value, $expire);

        } 
        else 
        {

            $flashKey = '_flash_' . $key;

            $message = self::get($flashKey);

            self::remove($flashKey);

            return $message;

        }

    }

}

?>