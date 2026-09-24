<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use App\Models\Backend\AuthModel;

/**
 * Classe dedicata alle regole di validazione personalizzate per il modulo di Autenticazione (Auth).
 * 
 * Estende le capacità del motore di validazione nativo di CodeIgniter 4, fornendo 
 * metodi specifici per validare dati complessi (come i token crittografici) delegando 
 * il controllo effettivo ai modelli di riferimento.
 */
class AuthRules
{
    /**
     * Regola di validazione per la verifica di un token di sicurezza (es. attivazione o reset password).
     * 
     * Il metodo istanzia dinamicamente l'AuthModel e gli passa la stringa ricevuta dal form 
     * per eseguire un controllo approfondito a database. L'AuthModel si occuperà di calcolare 
     * l'hash e verificare che il token esista e non sia scaduto.
     *
     * @param string $str La stringa raw del token inviata dal form (solitamente tramite un campo nascosto)
     * @return bool True se il token è autentico e ancora valido, false se è manipolato, inesistente o scaduto
     */
    public function checkTokenRule(string $str): bool
    {
        $model = new AuthModel();
        return $model->checkAuthToken($str);
    }
}