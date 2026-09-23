<?php declare(strict_types = 1); 

namespace App\Libraries;

/**
 * Servizio dedicato alle operazioni di basso livello sul file system per la gestione delle immagini.
 * 
 * Fornisce metodi statici per la rimozione fisica sicura dei file multimediali, occupandosi 
 * sia dell'eliminazione mirata di singoli file nelle loro diverse varianti dimensionali, 
 * sia della rimozione ricorsiva di intere directory associate a specifiche entità del database.
 */
class ImageFileSystemService
{
    /**
     * Elimina fisicamente dal file system l'intera alberatura di directory associata a uno specifico record.
     * 
     * Il metodo individua il percorso fisico esatto basandosi sul nome dell'entità e sul suo identificatore 
     * univoco (UUID), per poi invocare una procedura ricorsiva che distrugge permanentemente tutti i file 
     * e le sottocartelle (es. formati large, medium, small) in essa contenuti, operando una pulizia totale.
     *
     * @param string $entity Il nome dell'entità o del modulo di riferimento (es. 'gallery', 'users')
     * @param string $uuid L'identificatore univoco del record a cui appartiene la cartella da eliminare
     * @return void
     */
    public static function removeAllImages(string $entity, string $uuid): void
    {
        $path = rtrim(FCPATH, '/\\') . "/images/backend/{$entity}/{$uuid}";
        
        if (is_dir($path)):
            self::rrmdir($path);
        endif;
    }

    /**
     * Rimuove in modo selettivo un singolo file immagine dal server, iterando su tutte le varianti dimensionali.
     * 
     * Conoscendo l'entità, l'UUID del record e il nome originale del file, il metodo costruisce i percorsi 
     * specifici e procede alla cancellazione fisica dell'immagine all'interno delle sottocartelle di 
     * ridimensionamento standard previste dal sistema ('large', 'medium', 'small'), sopprimendo eventuali 
     * errori nel caso in cui una specifica variante non sia presente.
     *
     * @param string $entity Il nome dell'entità o del modulo di riferimento
     * @param string $uuid L'identificatore univoco del record associato
     * @param string $filename Il nome completo del file immagine da rimuovere (comprensivo di estensione)
     * @return void
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
     * Metodo interno di utilità per la rimozione ricorsiva (recursive remove directory) di una cartella.
     * 
     * Scansiona l'intero contenuto del percorso specificato, inoltrandosi progressivamente nelle eventuali 
     * sottocartelle per sbloccarne l'eliminazione. Cancella i singoli file e infine distrugge la directory 
     * radice stessa. Utilizza l'operatore di soppressione degli errori (@) per garantire che l'esecuzione 
     * non venga interrotta in caso di restrizioni impreviste sui permessi del file system.
     *
     * @param string $dir Il percorso assoluto della directory da svuotare ed eliminare
     * @return void
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