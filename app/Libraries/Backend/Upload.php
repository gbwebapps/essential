<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\HTTP\Files\UploadedFile;

class Upload
{
    private BaseConfig $config;

    public function __construct()
    {
        $this->config = config(\Config\Backend\Upload::class);
    }

    /**
     * Esegue l'upload e il ridimensionamento multilivello delle immagini.
     * Accetta un array di oggetti UploadedFile di CodeIgniter 4.
     */
    public function doUpload(array $files, string $entity, string $uuid): array|false
    {
        $uploaded = [];
        
        /* NUOVO PERCORSO: Inserito 'backend' nel path pubblico */
        $baseImg = rtrim(FCPATH, '/\\') . '/images/backend/' . $entity . '/' . $uuid;

        /* Generazione cartelle multilivello */
        foreach (['large', 'medium', 'small'] as $sz):
            $dir = $baseImg . '/' . $sz;
            if ( ! is_dir($dir)): 
                @mkdir($dir, 0775, true); 
            endif;
        endforeach;

        foreach ($files as $file):
            if ( ! $file instanceof UploadedFile || ! $file->isValid() || $file->hasMoved()):
                continue;
            endif;

            $tmpPath  = $file->getTempName();
            $imgInfo  = @getimagesize($tmpPath);
            
            if ( ! $imgInfo):
                continue;
            endif;

            $ext   = $file->getClientExtension();
            $fname = pathinfo($file->getClientName(), PATHINFO_FILENAME);

            /* Configurazione: Rinomina */
            if ((bool) $this->config->renameImages):
                $base = pathinfo($file->getRandomName(), PATHINFO_FILENAME);
            else:
                $base = str_replace(' ', '-', $fname);
                $base = preg_replace('/[^A-Za-z0-9\-\_]/', '', $base);
                $base = preg_replace('/-+/', '-', $base);
            endif;

            $filename = $base . ($ext ? '.' . $ext : '');
            $path     = $baseImg . '/large';
            $destination = $path . '/' . $filename;

            /* Configurazione: Sovrascrittura */ 
            if ( ! (bool) $this->config->overwriteImages):
                $i = 1;
                while (file_exists($destination)):
                    $filename = $base . "($i)" . ($ext ? '.' . $ext : '');
                    $destination = $path . '/' . $filename;
                    $i++;
                endwhile;
            endif;

            try {
                $file->move($path, $filename, true); 
                $uploaded[] = $filename;

                /* Generazione Medium e Small */ 
                $folders = [
                    'medium' => [(int) $this->config->resizeMediumX, (int) $this->config->resizeMediumY],
                    'small'  => [(int) $this->config->resizeSmallX,  (int) $this->config->resizeSmallY],
                ];

                foreach ($folders as $k => $v):
                    $dest = $baseImg . '/' . $k . '/' . $filename;
                    if ((bool) $this->config->cropCenter):
                        $this->cropCenter($destination, $dest, $v[0], $v[1]);
                    else:
                        $this->scaleImage($imgInfo, $destination, $dest, $v[0], $v[1]);
                    endif;
                endforeach;

            } catch (\Exception $e) {
                log_message('error', 'Errore elaborazione immagine: ' . $e->getMessage());
                continue;
            }
        endforeach;

        return $uploaded ?: false;
    }

    /* ridimensionamento proporzionale */
    protected function scaleImage(array $imgInfo, string $srcPath, string $destPath, int $targetX, int $targetY): bool
    {
        [$width, $height, $type] = $imgInfo;

        switch ($type):
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:  $src = imagecreatefrompng($srcPath);  break;
            case IMAGETYPE_GIF:  $src = imagecreatefromgif($srcPath);  break;
            case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($srcPath) : false; break;
            case IMAGETYPE_BMP:  $src = function_exists('imagecreatefrombmp') ? imagecreatefrombmp($srcPath) : false; break;
            case IMAGETYPE_AVIF: $src = function_exists('imagecreatefromavif') ? imagecreatefromavif($srcPath) : false; break;
            default: return false;
        endswitch;

        if ( ! $src): 
            return false; 
        endif;

        $ratio     = min($targetX / $width, $targetY / $height);
        $newWidth  = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_AVIF])):
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        endif;

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $result = false;
        switch ($type):
            case IMAGETYPE_JPEG: $result = imagejpeg($dst, $destPath, 90); break;
            case IMAGETYPE_PNG:  $result = imagepng($dst, $destPath); break;
            case IMAGETYPE_GIF:  $result = imagegif($dst, $destPath); break;
            case IMAGETYPE_WEBP: $result = function_exists('imagewebp') ? imagewebp($dst, $destPath) : false; break;
            case IMAGETYPE_BMP:  $result = function_exists('imagebmp')  ? imagebmp($dst, $destPath)  : false; break;
            case IMAGETYPE_AVIF: $result = function_exists('imageavif') ? imageavif($dst, $destPath) : false; break;
        endswitch;

        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $result;
    }

    /* crop centrale con rapporto forzato */
    protected function cropCenter(string $srcPath, string $destPath, int $targetX, int $targetY): bool
    {
        [$width, $height, $type] = getimagesize($srcPath);

        switch ($type):
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:  $src = imagecreatefrompng($srcPath);  break;
            case IMAGETYPE_GIF:  $src = imagecreatefromgif($srcPath);  break;
            case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($srcPath) : false; break;
            case IMAGETYPE_BMP:  $src = function_exists('imagecreatefrombmp') ? imagecreatefrombmp($srcPath) : false; break;
            case IMAGETYPE_AVIF: $src = function_exists('imagecreatefromavif') ? imagecreatefromavif($srcPath) : false; break;
            default: return false;
        endswitch;

        if ( ! $src): 
            return false; 
        endif;

        $srcRatio    = $width / $height;
        $targetRatio = $targetX / $targetY;

        if ($srcRatio > $targetRatio):
            $newWidth  = (int) ($height * $targetRatio);
            $newHeight = $height;
            $srcX      = (int) (($width - $newWidth) / 2);
            $srcY      = 0;
        else:
            $newWidth  = $width;
            $newHeight = (int) ($width / $targetRatio);
            $srcX      = 0;
            $srcY      = (int) (($height - $newHeight) / 2);
        endif;

        $dst = imagecreatetruecolor($targetX, $targetY);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_AVIF])):
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        endif;

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetX, $targetY, $newWidth, $newHeight);

        $result = false;
        switch ($type):
            case IMAGETYPE_JPEG: $result = imagejpeg($dst, $destPath, 90); break;
            case IMAGETYPE_PNG:  $result = imagepng($dst, $destPath); break;
            case IMAGETYPE_GIF:  $result = imagegif($dst, $destPath); break;
            case IMAGETYPE_WEBP: $result = function_exists('imagewebp') ? imagewebp($dst, $destPath) : false; break;
            case IMAGETYPE_BMP:  $result = function_exists('imagebmp')  ? imagebmp($dst, $destPath)  : false; break;
            case IMAGETYPE_AVIF: $result = function_exists('imageavif') ? imageavif($dst, $destPath) : false; break;
        endswitch;

        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $result;
    }
}