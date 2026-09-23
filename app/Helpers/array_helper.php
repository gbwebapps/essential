<?php declare(strict_types = 1); 

/**
 * Helper contenente funzioni di utilità per la manipolazione e la trasformazione degli array.
 * 
 * Fornisce strumenti specifici per la normalizzazione e la pulizia delle chiavi, 
 * risultando particolarmente utile per la formattazione degli errori di validazione complessi 
 * (es. input multidimensionali o array di campi) prima del loro invio all'interfaccia client.
 */

if ( ! function_exists('removeDot')) {

    /**
     * Rimuove una stringa di prefisso specifica dalle chiavi di un array associativo.
     * 
     * Utilizzato tipicamente per ripulire le chiavi degli errori di validazione nidificati 
     * (es. trasformando 'searchFields.nome' in 'nome'). Questo processo semplifica il parsing 
     * e la successiva iniezione dei messaggi di errore nel DOM da parte del JavaScript.
     *
     * @param string $prefix La stringa esatta da rimuovere dalle chiavi (es. 'searchFields.')
     * @param array $array L'array associativo originale da elaborare
     * @return array Un nuovo array contenente i medesimi valori, ma con le chiavi ripulite dal prefisso
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
     * Raggruppa e normalizza gli errori di validazione appartenenti a un array di input (es. checkbox multiple).
     * 
     * Quando il validatore di sistema analizza un array di dati, genera errori con notazione a punti 
     * (es. 'permissions.0', 'permissions.1'). Questa funzione intercetta tali chiavi dinamiche 
     * e le riconduce alla singola chiave radice (es. 'permissions'), un passaggio fondamentale 
     * affinché lo script lato client riesca ad agganciare il messaggio di errore al contenitore corretto.
     *
     * @param string $prefix Il nome della chiave radice da isolare e ripristinare (es. 'permissions')
     * @param array $array L'array grezzo degli errori restituito dal validatore
     * @return array Un nuovo array in cui le chiavi indicizzate sono state unificate sotto il prefisso radice
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
