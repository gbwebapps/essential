<?php declare(strict_types = 1); 

namespace App\Libraries;

class CryptoService
{
    protected string $key;
    protected string $cipher = 'aes-256-gcm';

    public function __construct(string $key)
    {
        $this->key = $key;
    }

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