<?php declare(strict_types = 1);

/**
 * Date Helper
 *
 * Raccolta di funzioni di utilità globale dedicate alla manipolazione,
 * formattazione e localizzazione (i18n) delle date nel sistema.
 */

/* Evita la ridefinizione della funzione se il file helper viene caricato più volte */
/* Evita la ridefinizione della funzione se il file helper viene caricato più volte */
if (! function_exists('convertDate')):

    /**
     * Converte una stringa data nel formato localizzato e nel fuso orario dell'utente.
     *
     * Gestisce la conversione di timezones (dal fuso orario di default dell'applicazione
     * a quello scelto dall'utente) e traduce nomi di mesi/giorni in base al locale attuale.
     *
     * @param string|null $date   La data originaria da elaborare.
     * @param string|null $format Il pattern desiderato ('conversational' o pattern custom).
     *
     * @return string La data formattata e localizzata, o la stringa originale in caso di errore di parsing.
     */
    function convertDate(?string $date, ?string $format = null): string
    {
        /* Verifica preliminare della validità dell'input */
        if (empty($date) || trim($date) === ''):
            return '';
        endif;

        try {
            /* Recupero delle configurazioni di sistema e delle preferenze utente */
            $appTimezone   = config('App')->appTimezone;
            $userTimezone  = setting('Backend\General', 'timezone') ?? $appTimezone;
            
            /* Recupero della lingua attuale impostata nel framework */
            $currentLocale = setting('Backend\General', 'language');
            
            /* Determinazione del pattern di formattazione */
            if ($format === 'conversational'):
                $userFormat = lang('backend/global.formats.conversationalDate');
            else:
                /* Formato di fallback 'EEEE' aggiunge il giorno della settimana per esteso */
                $userFormat = $format ?? setting('Backend\General', 'dateFormat') ?? 'EEEE d MMMM yyyy HH:mm:ss';
            endif;

            /* Istanziazione dell'oggetto Time passando la lingua corrente come TERZO parametro */
            $timeObject = \CodeIgniter\I18n\Time::parse($date, $appTimezone, $currentLocale);
            
            /* Conversione del fuso orario */
            $timeObject = $timeObject->setTimezone($userTimezone);

            /* Output formattato: la lingua è già stata iniettata nell'oggetto */
            return $timeObject->toLocalizedString($userFormat);

        } catch (\Throwable $e) {
            /* Graceful degradation: in caso di eccezione restituisce l'input non processato */
            return $date;
        }
    }

endif;