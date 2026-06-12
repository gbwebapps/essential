<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use App\Models\Backend\AdminsModel;

/**
 * Class AdminsRules
 *
 * Raccolta di regole di validazione personalizzate (Custom Validation Rules)
 * estese e utilizzate dal motore di validazione di CodeIgniter per il modulo Admins.
 */
class AdminsRules
{
    /**
     * Verifica che la stringa fornita sia priva di caratteri speciali potenzialmente nocivi (<, >, `).
     * Utilizzata come misura di sicurezza preliminare contro attacchi di tipo XSS o iniezioni di codice.
     *
     * @param string $str La stringa di testo da sottoporre a validazione.
     * @return bool True se il testo è privo dei caratteri vietati, false in caso di corrispondenza rilevata.
     */
    public function safeText(string $str): bool
    {
        return ! preg_match('/[<>\x60]/', $str);
    }
}