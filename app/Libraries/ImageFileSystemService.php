<?php declare(strict_types = 1); 

namespace App\Libraries;

class ImageFileSystemService
{
    /**
     * Rimuove l'intera cartella dell'UUID (Quando elimini l'intero record)
     */
    public static function removeAllImages(string $entity, string $uuid): void
    {
        $path = rtrim(FCPATH, '/\\') . "/images/backend/{$entity}/{$uuid}";
        
        if (is_dir($path)):
            self::rrmdir($path);
        endif;
    }

    /**
     * Rimuove i file fisici di una singola immagine (Per la galleria asincrona)
     */
    public static function removeSingleImage(string $entity, string $uuid, string $filename): void
    {
        $base = rtrim(FCPATH, '/\\') . "/images/backend/{$entity}/{$uuid}";

        foreach (['large', 'medium', 'small'] as $size):
            $file = "{$base}/{$size}/{$filename}";
            if (is_file($file)):
                @unlink($file);
            endif;
        endforeach;
    }

    /**
     * Svuota e rimuove ricorsivamente una directory dal disco
     */
    private static function rrmdir(string $dir): void
    {
        if (is_dir($dir)):
            $objects = scandir($dir);
            foreach ($objects as $object):
                if ($object !== "." && $object !== ".."):
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && ! is_link($dir . DIRECTORY_SEPARATOR . $object)):
                        self::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else:
                        @unlink($dir . DIRECTORY_SEPARATOR . $object);
                    endif;
                endif;
            endforeach;
            @rmdir($dir);
        endif;
    }
}