<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

/** Il valore restituito da lang() resta una stringa, anche negli attributi e nelle validazioni. */
class InterfaceLanguage extends \CodeIgniter\Language\Language
{
    public function getLine(string $line, array $args = [])
    {
        $text = parent::getLine($line, $args);
        if (is_string($text)):
            InterfaceTranslations::remember($text, $line, $args);
        endif;

        return $text;
    }
}
