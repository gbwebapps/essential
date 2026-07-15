<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Class SettingsRules
 *
 * Raccolta di regole di validazione personalizzate (Custom Validation Rules)
 * dedicate alle procedure di impostazioni delle preferenze nel database.
 */
class SettingsRules
{
    /* Metodo di validazione personalizzato */
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