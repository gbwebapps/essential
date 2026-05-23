<?php declare(strict_types = 1); 

namespace App\Libraries;

class Token
{
    protected string $token;

    public function __construct(?string $token = null)
    {
        if ($token === null):
            $this->token = bin2hex(random_bytes(16));
        else:
            $this->token = $token;
        endif;
    }

    public function getValue(): string
    {
        return $this->token;
    }

    /* Riceve la chiave dall'esterno per garantire la massima riutilizzabilità e testabilità */
    public function getHash(string $hashKey): string
    {
        return hash_hmac('sha256', $this->token, $hashKey);
    }
}