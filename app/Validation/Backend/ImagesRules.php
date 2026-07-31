<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Class ImagesRules
 *
 * Raccolta di regole di validazione personalizzate (Custom Validation Rules)
 * dedicate alle validazioni di allegati.
 */
class ImagesRules
{
    /**
     * Valida un array associativo di immagini con etichette flessibili (chiave:valore).
     * Sintassi: checkImages[size:2048,width:1920,height:1080,ext:png|jpg|webp]
     */
    public function checkImages(...$args): bool
    {
        /* Recuperiamo i parametri passati dinamicamente da CodeIgniter */
        $files = $args[0] ?? [];
        $params = $args[1] ?? null;
        $data = $args[2] ?? [];

        if (empty($files)) :
            return true;
        endif;

        /* 1. Recupero dei limiti dinamici unificati (DB + Config) tramite l'helper */
        $globalUploadSettings = setting('Backend\Upload');

        /* Mappiamo i valori predefiniti presi dall'helper */
        $config = [
            'size' => isset($globalUploadSettings->maxFileSize) ? (int) $globalUploadSettings->maxFileSize : null,
            'width' => isset($globalUploadSettings->maxImageX) ? (int) $globalUploadSettings->maxImageX : null,
            'height' => isset($globalUploadSettings->maxImageY) ? (int) $globalUploadSettings->maxImageY : null,
            'ext' => isset($globalUploadSettings->allowedExtensions) ? explode('|', $globalUploadSettings->allowedExtensions) : []
        ];

        /* 2. Parsing e sovrascrittura condizionale tramite gli argomenti espliciti della regola (se presenti) */
        if ( ! empty($params)) :
            $pairs = explode(',', $params);
            foreach ($pairs as $pair) :

                if (strpos($pair, ':') === false) :
                    continue;
                endif;
                
                [$key, $value] = explode(':', $pair, 2);
                $key = trim($key);
                $value = trim($value);

                if ($key === 'ext') :
                    $config['ext'] = explode('|', $value);
                elseif (array_key_exists($key, $config)) :
                    $config[$key] = (int) $value;
                endif;
            endforeach;
        endif;

        $validator = \Config\Services::validation();
        $hasErrors = false;

        /* 3. Ciclo di validazione sulle immagini reali basato sulla configurazione unificata */
        foreach ($files as $jsKey => $file) :
            if ( ! $file->isValid()) :
                continue;
            endif;

            /* Controllo Peso (KB) */
            if ($config['size'] !== null && $file->getSizeByUnit('kb') > $config['size']) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.maxSize', [$config['size']]));
                $hasErrors = true;
            endif;

            /* Controllo Estensioni */
            if ( ! empty($config['ext']) && ! in_array($file->getClientExtension(), $config['ext'], true)) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.extIn', [implode(', ', $config['ext'])]));
                $hasErrors = true;
            endif;

            /* Controllo Dimensioni in Pixel (Larghezza / Altezza) */
            if ($config['width'] !== null || $config['height'] !== null) :
                [$width, $height] = getimagesize($file->getTempName());

                if ($config['width'] !== null && $width > $config['width']) :
                    $validator->setError("images.{$jsKey}", lang('backend/upload.maxWidth', [$config['width']]));
                    $hasErrors = true;
                endif;

                if ($config['height'] !== null && $height > $config['height']) :
                    $validator->setError("images.{$jsKey}", lang('backend/upload.maxHeight', [$config['height']]));
                    $hasErrors = true;
                endif;
            endif;
        endforeach;

        /* Ritorniamo sempre true per non far scattare l'errore generico sulla chiave 'images' */
        return true;
    }
}