<?php

if (! function_exists('removeDot')) {
    /**
     * Rimuove un prefisso dalle chiavi di un array.
     *
     * @param string $prefix La stringa da rimuovere (es. 'searchFields.')
     * @param array  $array  L'array originale
     * @return array
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