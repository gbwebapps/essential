<?php declare(strict_types = 1); 

namespace App\Libraries;

/**
 * Libreria per la generazione e la gestione sicura di token temporanei.
 * 
 * Fornisce una struttura orientata agli oggetti per creare stringhe casuali crittograficamente sicure 
 * (utilizzate ad esempio per reset password, meccanismi "remember me" o validazioni a singolo uso) 
 * e per calcolarne l'hash. Questo pattern assicura che nel database venga archiviata solo l'impronta 
 * crittografica, impedendo l'esposizione del token originale in caso di compromissione dei dati.
 */
class Token
{
    /**
     * La stringa alfanumerica rappresentante il token nel suo formato in chiaro.
     * @var string 
     */
    protected string $token;

    /**
     * Inizializza l'oggetto Token.
     * 
     * Se non viene passato alcun parametro, il costruttore genera automaticamente un nuovo token 
     * sfruttando il generatore di numeri pseudo-casuali (CSPRNG) del sistema (16 byte convertiti 
     * in una stringa esadecimale di 32 caratteri). Se viene fornito un token esistente (ad esempio 
     * estrapolato da una richiesta HTTP), l'oggetto viene istanziato per consentirne il calcolo dell'hash.
     *
     * @param string|null $token (Opzionale) Un token in chiaro preesistente. Se omesso, ne verrà generato uno nuovo in automatico
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
     * Restituisce il valore in chiaro del token.
     * 
     * Questo dato è destinato esclusivamente alla trasmissione verso il client (es. iniettato in un link 
     * inviato via e-mail o salvato all'interno di un cookie sul browser) e non deve mai essere 
     * memorizzato direttamente nel database per ragioni di sicurezza.
     *
     * @return string La stringa esadecimale del token non cifrato
     */
    public function getValue(): string
    {
        return $this->token;
    }

    /**
     * Genera l'impronta crittografica del token utilizzando l'algoritmo HMAC-SHA256.
     * 
     * Applica una funzione di hashing unidirezionale combinando il valore in chiaro del token 
     * con una chiave segreta di sistema (hashKey). Il valore risultante da questa operazione 
     * è l'unica stringa che deve essere materialmente salvata e confrontata nel database, 
     * impedendo l'uso malevolo dei dati in caso di accesso non autorizzato ai record.
     *
     * @param string $hashKey La chiave segreta di sistema impiegata per la firma HMAC
     * @return string La stringa risultante dall'operazione di hashing, pronta per la persistenza su database
     */
    public function getHash(string $hashKey): string
    {
        return hash_hmac('sha256', $this->token, $hashKey);
    }
}