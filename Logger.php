<?php


class Logger
{

    private static string $file = 'app.log';  

    
    public static function setFile(string $filename): void
    {

        self::$file = $filename;
        
    }


    public static function info(string $message): void
    {

        self::write('INFO', $message);

    }


    public static function warning(string $message): void
    {

        self::write('WARNING', $message);

    }


    public static function error(string $message): void
    {

        self::write('ERROR', $message);

    }


    private static function write(string $level, string $message): void
    {

        $date = date('Y-m-d H:i:s');

        $line = "[$date] [$level] $message" . PHP_EOL;

        file_put_contents(self::$file, $line, FILE_APPEND);

    }


    public static function clear(): void
    {

        file_put_contents(self::$file, '');

    }


}


?>