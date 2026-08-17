<?php declare(strict_types = 1); 

/**
 * Date Helper
 *
 * Raccolta di funzioni di utilità globale dedicate alla manipolazione,
 * formattazione e localizzazione (i18n) delle date nel sistema.
 */

if ( ! function_exists('convertDate')):
    
    /**
     * Converte una stringa data in un formato localizzato (i18n) basato sulla lingua impostata nell'applicazione.
     *
     * @param string|null $date   La stringa della data o timestamp da convertire.
     * @param string      $format Il formato ICU di destinazione per la localizzazione.
     * @return string La stringa della data formattata, oppure vuota/originale in caso di errore o valore nullo.
     */
    function convertDate(?string $date, ?string $format = null): string
    {
        if (empty($date)):
            return '';
        endif;

        try {
            $userTimezone = setting('Backend\General', 'timezone') ?? config('App')->appTimezone;
            $appTimezone  = config('App')->appTimezone;
            
            /* 1. Usa il formato passato come parametro, altrimenti 
               2. Cerca nelle impostazioni Generali, altrimenti 
               3. Usa un fallback statico */
            $userFormat = $format ?? setting('Backend\General', 'dateFormat') ?? 'd MMMM yyyy HH:mm:ss';

            return \CodeIgniter\I18n\Time::parse($date, $appTimezone)->setTimezone($userTimezone)->toLocalizedString($userFormat);

        } catch (\Throwable $e) {
            /* In caso di errore nel parsing, restituisce la stringa originale */
            return $date;
        }
    }
endif; 