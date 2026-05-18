<?php declare(strict_types = 1); 

if (! function_exists('convertDate')):
    /*
     * Converte una stringa data in un formato localizzato (i18n) basato sulla lingua dell'applicazione.
     * Utilizza la classe nativa CodeIgniter\I18n\Time per la gestione della localizzazione.
     */
    function convertDate(?string $date, string $format = 'd MMMM yyyy HH:mm'): string
    {
        if (empty($date)):
            return '';
        endif;

        try {
            return \CodeIgniter\I18n\Time::parse($date)->toLocalizedString($format);
        } catch (\Exception $e) {
            /* In caso di errore nel parsing, restituisce la stringa originale per evitare crash della vista */
            return $date;
        }
    }
endif; 