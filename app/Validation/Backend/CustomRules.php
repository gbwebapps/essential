<?php declare(strict_types = 1);

namespace App\Validation\Backend;

class CustomRules
{
    public function valid_email(string $str): bool
    {
        /* Se l'email contiene il suffisso di soft delete seguito da timestamp */
        if (preg_match('/^(.*?)\.deleted\.\d+$/', $str, $matches)):
            $baseEmail = $matches[1];
            return (bool) filter_var($baseEmail, FILTER_VALIDATE_EMAIL);
        endif;

        /* Altrimenti esegue la normale validazione email */
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }
}