<?php

require 'Logger.php'; 


class File
{

    private static string $uploadDir = 'uploads/';

    private static array $allowedTypes = ['jpg','jpeg','png','gif','pdf','txt'];

    private static int $maxSize = 5 * 1024 * 1024; // 5MB 

    public static function setUploadDir(string $dir): void
    {

        self::$uploadDir = rtrim($dir, '/') . '/';

        if (!is_dir(self::$uploadDir)) 
        {

            mkdir(self::$uploadDir, 0755, true);

        }

    }

    public static function setAllowedTypes(array $types): void
    {

        self::$allowedTypes = array_map('strtolower', $types);

    }

    public static function setMaxSize(int $bytes): void
    {

        self::$maxSize = $bytes;

    }

    public static function upload(array $files): array
    {

        $uploadedFiles = [];

        if (isset($files['name']) && is_array($files['name'])) 
        {

            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) 
            {

                $fileData = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i]
                ];

                $result = self::uploadSingle($fileData);

                $uploadedFiles[] = $result;

            }

        } 
        else 
        {

            $uploadedFiles[] = self::uploadSingle($files);

        }

        return $uploadedFiles;

    }

    private static function uploadSingle(array $file): array
    {

        $filename = basename($file['name']);

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $result = ['success' => false, 'file' => $filename, 'error' => ''];


        if ($file['error'] !== UPLOAD_ERR_OK) 
        {

            $result['error'] = "Erreur lors du téléchargement";

            Logger::error($result['error'] . " - " . $filename);

            return $result;

        }

        if (!in_array($ext, self::$allowedTypes)) 
        {

            $result['error'] = "Type de fichier non autorisé: .$ext";

            Logger::warning($result['error']);

            return $result;

        }

        if ($file['size'] > self::$maxSize) 
        {

            $result['error'] = "Le fichier dépasse la taille maximale autorisée";

            Logger::warning($result['error']);

            return $result;

        }

        $newName = uniqid() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], self::$uploadDir . $newName)) 
        {

            $result['success'] = true;

            $result['file'] = $newName;

            Logger::info("Fichier téléchargé avec succès: $newName");

        } 
        else 
        {

            $result['error'] = "Impossible de déplacer le fichier";

            Logger::error($result['error'] . " - " . $filename);

        }

        return $result;
    }

    public static function delete(string $filename): bool
    {

        $path = self::$uploadDir . $filename;

        if (file_exists($path)) 
        {

            return unlink($path);

        }

        return false;

    }

    public static function read(string $filename): ?string
    {

        $path = self::$uploadDir . $filename;

        if (file_exists($path))
        {

            return file_get_contents($path);

        }

        return null;

    }

    public static function listFiles(): array
    {

        if (!is_dir(self::$uploadDir)) return [];

        return array_diff(scandir(self::$uploadDir), ['.','..']);

    }

}

?>