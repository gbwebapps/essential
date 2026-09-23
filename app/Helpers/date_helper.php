<?php declare(strict_types = 1);

/**
 * Helper globale per la manipolazione e la formattazione delle date.
 * 
 * Centralizza la logica di conversione temporale dell'applicativo, garantendo che 
 * tutte le date esposte nell'interfaccia utente rispettino in modo uniforme 
 * il fuso orario (timezone) e le regole di localizzazione (locale) configurati nel sistema.
 */

if ( ! function_exists('convertDate')):

    /**
     * Converte e formatta una stringa temporale, applicando il fuso orario e la lingua correnti.
     * 
     * Il metodo elabora la data in ingresso (tipicamente in formato UTC proveniente dal database), 
     * ne esegue il parsing sfruttando le librerie native I18n di CodeIgniter e restituisce 
     * una rappresentazione localizzata. Supporta l'iniezione di formati personalizzati, 
     * l'uso di un formato testuale 'conversational' e gestisce eventuali eccezioni di parsing 
     * restituendo il dato grezzo (graceful degradation).
     *
     * @param string|null $date La stringa temporale originale da processare (es. '2026-09-23 18:50:00')
     * @param string|null $format Il pattern di formattazione ICU desiderato o la keyword speciale 'conversational'
     * @return string La stringa formattata e localizzata, un'istanza vuota se l'input manca, o la stringa originale in caso di errore
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