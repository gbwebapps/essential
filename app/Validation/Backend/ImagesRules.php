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