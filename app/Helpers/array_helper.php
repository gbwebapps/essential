<?php declare(strict_types = 1); 

/**
 * Array Helper
 *
 * Raccolta di funzioni di utilità globale dedicate alla manipolazione,
 * pulizia e ristrutturazione delle chiavi all'interno degli array.
 */

if ( ! function_exists('removeDot')) {

    /**
     * Rimuove un prefisso specifico da tutte le chiavi di un array associativo.
     *
     * @param string $prefix Il prefisso testuale da individuare e rimuovere.
     * @param array  $array  L'array associativo originale da elaborare.
     * @return array L'array risultante con le chiavi ripulite dal prefisso.
     */
    function removeDot(string $prefix, array $array): array
    {
        $formatted = [];

        foreach ($array as $key => $value):
            $cleanKey = str_replace($prefix, '', $key);
            $formatted[$cleanKey] = $value;
        endforeach;

        return $formatted;
    }

}

if ( ! function_exists('removeDotPermissions')) {

    /**
     * Ristruttura le chiavi di un array di permessi, normalizzando e raggruppando
     * quelle che iniziano con un determinato prefisso seguito da un punto.
     *
     * @param string $prefix Il prefisso di settore da verificare e normalizzare.
     * @param array  $array  L'array dei messaggi di errore o permessi da scansionare.
     * @return array L'array risultante con le chiavi di permesso condizionate e normalizzate.
     */
    function removeDotPermissions(string $prefix, array $array): array
    {
        $formatted = [];

        foreach ($array as $key => $message):
            if (strpos($key, $prefix . '.') === 0):
                $formatted[$prefix] = $message;
            else:
                $formatted[$key] = $message;
            endif;
        endforeach;

        return $formatted;
    }
}
