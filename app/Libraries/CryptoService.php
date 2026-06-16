<?php declare(strict_types = 1); 

namespace App\Libraries;

/**
 * Class CryptoService
 *
 * Servizio centrale per la cifratura e decifratura simmetrica bidirezionale dei dati,
 * con utilizzo dell'algoritmo sicuro AES-256-GCM per garantire la riservatezza e l'integrità del payload.
 */
class CryptoService
{
    /**
     * Chiave crittografica segreta utilizzata per le operazioni di cifratura e decifratura.
     * 
     * @var string 
     */
    protected string $key;

    /**
     * Metodo e modalità dell'algoritmo crittografico impostato (predefinito: 'aes-256-gcm').
     * 
     * @var string 
     */
    protected string $cipher = 'aes-256-gcm';

    /**
     * Costruttore della classe.
     * Inizializza il servizio crittografico memorizzando la chiave segreta iniettata dall'esterno.
     *
     * @param string $key La chiave crittografica da associare al servizio.
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Cifra una stringa di testo in chiaro generando un payload sicuro, integro e autenticato.
     *
     * @param string $plaintext Il testo in chiaro da sottoporre a cifratura.
     * @return string Stringa finale codificata in Base64 contenente l'unione sequenziale di IV, Tag e testo cifrato.
     */
    public function encrypt(string $plaintext): string
    {
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLen);

        /* AES-256-GCM richiede una variabile di riferimento per memorizzare il tag di autenticazione */
        $tag = ''; 

        $encrypted = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        /* Il pacchetto finale unisce IV (12 byte), Tag di autenticazione (16 byte) e il testo cifrato */
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decifra un payload protetto estraendone i componenti e verificandone l'autenticità.
     *
     * @param string $ciphertextBlob Il blocco di dati cifrati codificato in Base64 da elaborare.
     * @return string|null Il testo originale decifrato in chiaro, oppure null se il payload è alterato o corrotto.
     */
    public function decrypt(string $ciphertextBlob): ?string
    {
        $raw = base64_decode($ciphertextBlob);
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $tagLen = 16; /* Lunghezza standard per il tag GCM */

        /* Verifica che la stringa contenga almeno lo spazio per IV e Tag */
        if ($raw === false || strlen($raw) < ($ivLen + $tagLen)):
            return null;
        endif;

        /* Estrazione dei segmenti tramite le lunghezze fisse */
        $iv = substr($raw, 0, $ivLen);
        $tag = substr($raw, $ivLen, $tagLen);
        $ciphertext = substr($raw, $ivLen + $tagLen);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $decrypted === false ? null : $decrypted;
    }
}