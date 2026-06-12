<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use App\Models\Backend\AuthModel;

/**
 * Class AuthRules
 *
 * Raccolta di regole di validazione personalizzate (Custom Validation Rules)
 * dedicate alle procedure di autenticazione e verifica della sicurezza nel Backend.
 */
class AuthRules
{
    /**
     * Valida l'autenticità e la validità temporale di un token di sicurezza interrogando il modello dedicato.
     *
     * @param string $str Il token di autenticazione da verificare.
     * @return bool True se il token è valido ed esistente nel database, false in caso contrario.
     */
    public function checkTokenRule(string $str): bool
    {
        $model = new AuthModel();
        return $model->checkAuthToken($str);
    }
}