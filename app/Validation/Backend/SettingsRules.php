<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Classe dedicata alle regole di validazione personalizzate per le Impostazioni di sistema (Settings).
 * 
 * Fornisce logiche condizionali avanzate, estendendo il motore nativo di CodeIgniter 4 per gestire 
 * dipendenze incrociate tra i campi dei form (es. rendere un parametro obbligatorio solo 
 * in base al valore assunto da un altro input).
 */
class SettingsRules
{
    /**
     * Regola di validazione condizionale (Obbligatorio se...).
     * 
     * Rende il campo corrente strettamente obbligatorio esclusivamente se un altro campo specifico 
     * all'interno dello stesso form assume un determinato valore. Risulta fondamentale per i moduli 
     * di configurazione dinamici (es. rendere obbligatori i campi host e porta solo se il selettore 
     * del protocollo email è impostato su "smtp").
     * 
     * L'argomento $fields deve essere formattato inserendo il nome del campo di controllo 
     * e il valore atteso separati da una virgola (es. "protocol,smtp").
     *
     * @param string $str Il valore immesso dall'utente nel campo attualmente sotto validazione
     * @param string $fields Parametri della regola formattati in stringa ("campo_di_controllo,valore_atteso")
     * @param array $data L'intero payload associativo dei dati in arrivo (POST array)
     * @return bool True se il campo è opzionale (condizione non verificata) o se è compilato, false se è richiesto ma risulta vuoto
     */
    public function required_if_field(string $str, string $fields, array $data): bool
    {
        /* Esplodiamo i parametri passati nella regola, es: "protocol,smtp" */
        list($field, $value) = explode(',', $fields);

        /* Se il campo di controllo corrisponde al valore atteso, questo campo diventa obbligatorio */
        if (isset($data[$field]) && $data[$field] === $value):
            return $str !== '';
        endif;

        return true;
    }
}