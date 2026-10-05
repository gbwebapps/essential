<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Classe dedicata alle regole di validazione personalizzate per il caricamento delle immagini (Upload).
 * 
 * Fornisce un motore di controllo avanzato per la validazione di array di file multimediali, 
 * colmando le limitazioni native del framework nell'elaborazione di upload multipli (multi-file) 
 * e integrandosi direttamente con le impostazioni globali configurate a database.
 */
class ImagesRules
{
    /**
     * Valida dinamicamente un lotto di immagini (array) caricate tramite form HTTP.
     * 
     * L'algoritmo opera secondo un principio di configurazione a cascata (Fallback):
     * 1. Estrae i limiti globali di sicurezza salvati nel database (peso, estensioni, dimensioni in pixel).
     * 2. Se la regola di validazione nel Model definisce dei parametri espliciti (es. checkImages[size:2048,ext:jpg]), 
     *    questi sovrascrivono temporaneamente i limiti globali per la singola operazione.
     * 3. Analizza i file uno ad uno tramite il FileLocator di sistema e le funzioni GD (getimagesize).
     * 
     * NOTA TECNICA SUL RITORNO: Il metodo restituisce deliberatamente sempre 'true'. Se ritornasse 'false', 
     * CodeIgniter genererebbe un singolo messaggio d'errore generico per l'intero array di input. 
     * Per garantire precisione, il metodo inietta invece i messaggi d'errore localizzati direttamente nell'istanza 
     * del Validatore, agganciandoli alla chiave esatta del file problematico (es. 'images.0', 'images.1').
     *
     * @param mixed ...$args Parametri variadici passati nativamente dal motore CI4 (array dei file, stringa parametri, dati POST)
     * @return bool True (forzato) per demandare la segnalazione degli errori al livello dei singoli file
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
            'width' => isset($globalUploadSettings->maxImageX) && (int) $globalUploadSettings->maxImageX > 0 ? (int) $globalUploadSettings->maxImageX : null,
            'height' => isset($globalUploadSettings->maxImageY) && (int) $globalUploadSettings->maxImageY > 0 ? (int) $globalUploadSettings->maxImageY : null,
            'ext' => isset($globalUploadSettings->allowedExtensions) ? array_map('strtolower', explode('|', $globalUploadSettings->allowedExtensions)) : []
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
                    $config['ext'] = array_map('strtolower', explode('|', $value));
                elseif (array_key_exists($key, $config)) :
                    $numericValue = (int) $value;
                    $config[$key] = in_array($key, ['width', 'height'], true) && $numericValue <= 0 ? null : $numericValue;
                endif;
            endforeach;
        endif;

        $validator = \Config\Services::validation();

        /* 3. Ciclo di validazione sulle immagini reali basato sulla configurazione unificata */
        foreach ($files as $jsKey => $file) :
            if ( ! $file->isValid()) :
                if ($file->getError() !== UPLOAD_ERR_NO_FILE) :
                    $validator->setError("images.{$jsKey}", $file->getErrorString());
                endif;

                continue;
            endif;

            /* Controllo Peso (KB) */
            if ($config['size'] !== null && $file->getSizeByUnit('kb') > $config['size']) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.maxSize', [$config['size']]));
                continue;
            endif;

            /* Verifica che il contenuto sia realmente un'immagine */
            $dimensions = @getimagesize($file->getTempName());

            if ($dimensions === false) :
                $validator->setError("images.{$jsKey}", lang('Validation.is_image', ["images.{$jsKey}"]));
                continue;
            endif;

            [$width, $height] = $dimensions;

            /* Controllo dell'estensione determinata dal MIME reale */
            $extension = strtolower($file->guessExtension());

            if ( ! empty($config['ext']) && ! in_array($extension, $config['ext'], true)) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.extIn', [implode(', ', $config['ext'])]));
                continue;
            endif;

            /* Controllo Dimensioni in Pixel (Larghezza / Altezza) */
            if ($config['width'] !== null && $width > $config['width']) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.maxWidth', [$config['width']]));
                continue;
            endif;

            if ($config['height'] !== null && $height > $config['height']) :
                $validator->setError("images.{$jsKey}", lang('backend/upload.maxHeight', [$config['height']]));
            endif;
        endforeach;

        /* Ritorniamo sempre true per non far scattare l'errore generico sulla chiave 'images' */
        return true;
    }
}
