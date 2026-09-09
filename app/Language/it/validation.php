<?php

declare(strict_types=1);

/*
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

/* Validation language settings */
return [
    /* Core Messages */
    'noRuleSets'      => 'Nessun set di regole specificato nella configurazione di Validazione.',
    'ruleNotFound'    => '"{0}" non è una regola valida.',
    'groupNotFound'   => '"{0}" non è un gruppo di regole di validazione.',
    'groupNotArray'   => 'Il gruppo di regole "{0}" deve essere un array.',
    'invalidTemplate' => '"{0}" non è un template di Validazione valido.',

    /* Rule Messages */
    'alpha'                 => 'Il campo {field} può contenere solo caratteri alfabetici.',
    'alpha_dash'            => 'Il campo {field} può contenere solo caratteri alfanumerici, trattini bassi e trattini.',
    'alpha_numeric'         => 'Il campo {field} può contenere solo caratteri alfanumerici.',
    'alpha_numeric_punct'   => 'Il campo {field} può contenere solo caratteri alfanumerici, spazi e i caratteri ~ ! # $ % & * - _ + = | : .',
    'alpha_numeric_space'   => 'Il campo {field} può contenere solo caratteri alfanumerici e spazi.',
    'alpha_space'           => 'Il campo {field} può contenere solo caratteri alfabetici e spazi.',
    'decimal'               => 'Il campo {field} deve contenere un numero decimale.',
    'differs'               => 'Il campo {field} deve essere diverso dal campo {param}.',
    'equals'                => 'Il campo {field} deve essere esattamente: {param}.',
    'exact_length'          => 'Il campo {field} deve essere lungo esattamente {param} caratteri.',
    'field_exists'          => 'Il campo {field} deve esistere.',
    'greater_than'          => 'Il campo {field} deve contenere un numero maggiore di {param}.',
    'greater_than_equal_to' => 'Il campo {field} deve contenere un numero maggiore o uguale a {param}.',
    'hex'                   => 'Il campo {field} può contenere solo caratteri esadecimali.',
    'in_list'               => 'Il campo {field} deve essere uno tra: {param}.',
    'integer'               => 'Il campo {field} deve contenere un numero intero.',
    'is_natural'            => 'Il campo {field} deve contenere solo cifre.',
    'is_natural_no_zero'    => 'Il campo {field} deve contenere solo cifre e deve essere maggiore di zero.',
    'is_not_unique'         => 'Il campo {field} deve contenere un valore già esistente nel database.',
    'is_unique'             => 'Il campo {field} deve contenere un valore univoco.',
    'less_than'             => 'Il campo {field} deve contenere un numero minore di {param}.',
    'less_than_equal_to'    => 'Il campo {field} deve contenere un numero minore o uguale a {param}.',
    'matches'               => 'Il campo {field} non corrisponde al campo {param}.',
    'max_length'            => 'Il campo {field} non può superare i {param} caratteri di lunghezza.',
    'min_length'            => 'Il campo {field} deve essere lungo almeno {param} caratteri.',
    'not_equals'            => 'Il campo {field} non può essere: {param}.',
    'not_in_list'           => 'Il campo {field} non deve essere uno tra: {param}.',
    'numeric'               => 'Il campo {field} deve contenere solo numeri.',
    'regex_match'           => 'Il campo {field} non è nel formato corretto.',
    'required'              => 'Il campo {field} è obbligatorio.',
    'required_with'         => 'Il campo {field} è obbligatorio quando è presente {param}.',
    'required_without'      => 'Il campo {field} è obbligatorio quando non è presente {param}.',
    'string'                => 'Il campo {field} deve essere una stringa valida.',
    'timezone'              => 'Il campo {field} deve essere un fuso orario valido.',
    'valid_base64'          => 'Il campo {field} deve essere una stringa base64 valida.',
    'valid_email'           => 'Il campo {field} deve contenere un indirizzo email valido.',
    'valid_emails'          => 'Il campo {field} deve contenere tutti indirizzi email validi.',
    'valid_ip'              => 'Il campo {field} deve contenere un IP valido.',
    'valid_url'             => 'Il campo {field} deve contenere un URL valido.',
    'valid_url_strict'      => 'Il campo {field} deve contenere un URL valido.',
    'valid_date'            => 'Il campo {field} deve contenere una data valida.',
    'valid_json'            => 'Il campo {field} deve contenere un JSON valido.',

    /* Credit Cards */
    'valid_cc_number' => 'Il campo {field} non sembra essere un numero di carta di credito valido.',

    /* Files */
    'uploaded' => 'Il campo {field} non è un file caricato valido.',
    'max_size' => 'Il file nel campo {field} è troppo grande.',
    'is_image' => 'Il campo {field} non è un file immagine caricato valido.',
    'mime_in'  => 'Il campo {field} non ha un tipo mime valido.',
    'ext_in'   => 'Il campo {field} non ha un\'estensione file valida.',
    'max_dims' => 'Il campo {field} non è un\'immagine, oppure è troppo larga o troppo alta.',
    'min_dims' => 'Il campo {field} non è un\'immagine, oppure non è abbastanza larga o alta.',
];