<?php

declare(strict_types=1);

/* Validation language settings */
return [
    /* Core Messages */
    'noRuleSets'      => 'Keine Regelsätze in der Validierungskonfiguration angegeben.',
    'ruleNotFound'    => '"{0}" ist keine gültige Regel.',
    'groupNotFound'   => '"{0}" ist keine gültige Validierungsregelgruppe.',
    'groupNotArray'   => 'Die Regelgruppe "{0}" muss ein Array sein.',
    'invalidTemplate' => '"{0}" ist kein gültiges Validierungstemplate.',

    /* Rule Messages */
    'alpha'                 => 'Das Feld {field} darf nur alphabetische Zeichen enthalten.',
    'alpha_dash'            => 'Das Feld {field} darf nur alphanumerische Zeichen, Unterstriche und Bindestriche enthalten.',
    'alpha_numeric'         => 'Das Feld {field} darf nur alphanumerische Zeichen enthalten.',
    'alpha_numeric_punct'   => 'Das Feld {field} darf nur alphanumerische Zeichen, Leerzeichen und die Zeichen ~ ! # $ % & * - _ + = | : . enthalten.',
    'alpha_numeric_space'   => 'Das Feld {field} darf nur alphanumerische Zeichen und Leerzeichen enthalten.',
    'alpha_space'           => 'Das Feld {field} darf nur alphabetische Zeichen und Leerzeichen enthalten.',
    'decimal'               => 'Das Feld {field} muss eine Dezimalzahl enthalten.',
    'differs'               => 'Das Feld {field} muss sich vom Feld {param} unterscheiden.',
    'equals'                => 'Das Feld {field} muss genau {param} entsprechen.',
    'exact_length'          => 'Das Feld {field} muss genau {param} Zeichen lang sein.',
    'field_exists'          => 'Das Feld {field} muss existieren.',
    'greater_than'          => 'Das Feld {field} muss eine Zahl größer als {param} enthalten.',
    'greater_than_equal_to' => 'Das Feld {field} muss eine Zahl größer oder gleich {param} enthalten.',
    'hex'                   => 'Das Feld {field} darf nur hexadezimale Zeichen enthalten.',
    'in_list'               => 'Das Feld {field} muss eines der folgenden sein: {param}.',
    'integer'               => 'Das Feld {field} muss eine ganze Zahl enthalten.',
    'is_natural'            => 'Das Feld {field} darf nur Ziffern enthalten.',
    'is_natural_no_zero'    => 'Das Feld {field} darf nur Ziffern enthalten und muss größer als Null sein.',
    'is_not_unique'         => 'Das Feld {field} muss einen bereits in der Datenbank vorhandenen Wert enthalten.',
    'is_unique'             => 'Das Feld {field} muss einen eindeutigen Wert enthalten.',
    'less_than'             => 'Das Feld {field} muss eine Zahl kleiner als {param} enthalten.',
    'less_than_equal_to'    => 'Das Feld {field} muss eine Zahl kleiner oder gleich {param} enthalten.',
    'matches'               => 'Das Feld {field} stimmt nicht mit dem Feld {param} überein.',
    'max_length'            => 'Das Feld {field} darf eine Länge von {param} Zeichen nicht überschreiten.',
    'min_length'            => 'Das Feld {field} muss mindestens {param} Zeichen lang sein.',
    'not_equals'            => 'Das Feld {field} darf nicht {param} sein.',
    'not_in_list'           => 'Das Feld {field} darf keines der folgenden sein: {param}.',
    'numeric'               => 'Das Feld {field} darf nur Zahlen enthalten.',
    'regex_match'           => 'Das Feld {field} hat kein korrektes Format.',
    'required'              => 'Das Feld {field} ist ein Pflichtfeld.',
    'required_with'         => 'Das Feld {field} ist erforderlich, wenn {param} vorhanden ist.',
    'required_without'      => 'Das Feld {field} ist erforderlich, wenn {param} nicht vorhanden ist.',
    'string'                => 'Das Feld {field} muss eine gültige Zeichenkette sein.',
    'timezone'              => 'Das Feld {field} muss eine gültige Zeitzone sein.',
    'valid_base64'          => 'Das Feld {field} muss eine gültige Base64-Zeichenkette sein.',
    'valid_email'           => 'Das Feld {field} muss eine gültige E-Mail-Adresse enthalten.',
    'valid_emails'          => 'Das Feld {field} muss ausschließlich gültige E-Mail-Adressen enthalten.',
    'valid_ip'              => 'Das Feld {field} muss eine gültige IP-Adresse enthalten.',
    'valid_url'             => 'Das Feld {field} muss eine gültige URL enthalten.',
    'valid_url_strict'      => 'Das Feld {field} muss eine gültige URL enthalten.',
    'valid_date'            => 'Das Feld {field} muss ein gültiges Datum enthalten.',
    'valid_json'            => 'Das Feld {field} muss ein gültiges JSON enthalten.',

    /* Credit Cards */
    'valid_cc_number' => 'Das Feld {field} scheint keine gültige Kreditkartennummer zu sein.',

    /* Files */
    'uploaded' => 'Das Feld {field} ist keine gültige hochgeladene Datei.',
    'max_size' => 'Die Datei im Feld {field} ist zu groß.',
    'is_image' => 'Das Feld {field} ist keine gültige hochgeladene Bilddatei.',
    'mime_in'  => 'Das Feld {field} hat keinen gültigen Mime-Typ.',
    'ext_in'   => 'Das Feld {field} hat keine gültige Dateiendung.',
    'max_dims' => 'Das Feld {field} ist entweder kein Bild oder zu breit oder zu hoch.',
    'min_dims' => 'Das Feld {field} ist entweder kein Bild oder nicht breit oder hoch genug.',
];
