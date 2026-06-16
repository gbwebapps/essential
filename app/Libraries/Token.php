<?php declare(strict_types = 1); 

namespace App\Libraries;

/**
 * Class Token
 *
 * Libreria di uso generale per la generazione, gestione e hashing 
 * crittograficamente sicuro di token univoci (es. per reset password o attivazioni).
 */
class Token
{
    /**
     * Il valore stringa del token grezzo.
     * 
     * @var string 
     */
    protected string $token;

    /**
     * Costruttore della classe.
     * Inizializza la libreria memorizzando il token fornito o generandone uno nuovo
     * in modo crittograficamente sicuro tramite 16 byte casuali convertiti in esadecimale.
     *
     * @param string|null $token Stringa del token esistente, oppure null per richiederne la generazione automatica.
     */
    public function __construct(?string $token = null)
    {
        if ($token === null):
            $this->token = bin2hex(random_bytes(16));
        else:
            $this->token = $token;
        endif;
    }

    /**
     * Restituisce il valore alfanumerico del token grezzo memorizzato nell'oggetto.
     *
     * @return string Il token corrente.
     */
    public function getValue(): string
    {
        return $this->token;
    }

    /**
     * Calcola e restituisce l'hash HMAC-SHA256 del token utilizzando una chiave crittografica iniettata.
     * Garantisce l'integrità e l'univocità del token per il salvataggio o la verifica nel database.
     *
     * @param string $hashKey La chiave crittografica segreta da utilizzare per l'algoritmo HMAC.
     * @return string La stringa dell'hash generato.
     */
    public function getHash(string $hashKey): string
    {
        return hash_hmac('sha256', $this->token, $hashKey);
    }
}