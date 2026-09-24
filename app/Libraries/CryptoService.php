<?php declare(strict_types = 1); 

namespace App\Libraries;

/**
 * Servizio crittografico dedicato alla cifratura e decifratura sicura dei dati sensibili.
 * 
 * Implementa l'algoritmo AES-256 in modalità GCM (Galois/Counter Mode), uno standard di crittografia 
 * autenticata (AEAD) che garantisce simultaneamente la confidenzialità del payload e la sua integrità 
 * contro tentativi di manipolazione.
 */
class CryptoService
{
    /**
     * Chiave segreta master (simmetrica) utilizzata per le operazioni di crittografia.
     * @var string 
     */
    protected string $key;

    /**
     * Definizione dell'algoritmo crittografico e della modalità operativa (AES a 256 bit in modalità GCM).
     * @var string 
     */
    protected string $cipher = 'aes-256-gcm';

    /**
     * Inizializza il servizio crittografico iniettando la chiave segreta necessaria all'algoritmo.
     *
     * @param string $key La stringa che funge da chiave di cifratura simmetrica.
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Esegue la cifratura di una stringa di testo in chiaro garantendone l'autenticazione.
     * 
     * Il metodo genera crittograficamente un Vettore di Inizializzazione (IV) pseudo-casuale e affida 
     * all'algoritmo GCM la creazione di un Tag di autenticazione. I tre elementi risultanti (IV, Tag e Testo Cifrato) 
     * vengono uniti in un singolo blocco contiguo e codificati in Base64 per facilitarne l'archiviazione sicura.
     *
     * @param string $plaintext La stringa di testo in chiaro (non cifrata) da proteggere
     * @return string Il payload finale protetto e codificato in Base64, pronto per l'archiviazione
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
     * Decifra e autentica un payload crittografico precedentemente elaborato.
     * 
     * Il metodo esegue il processo inverso: decodifica la stringa Base64 ed estrae in base a offset fissi 
     * il Vettore di Inizializzazione (IV) e il Tag di autenticazione originali. L'algoritmo GCM utilizza 
     * il Tag per validare crittograficamente l'integrità del dato prima di restituire il testo in chiaro, 
     * impedendo così attacchi di manipolazione (ciphertext tampering).
     *
     * @param string $ciphertextBlob Il payload crittografico codificato in Base64 (contenente IV, Tag e testo cifrato)
     * @return string|null Il testo in chiaro originale, oppure null se i dati sono corrotti o la validazione dell'integrità fallisce
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