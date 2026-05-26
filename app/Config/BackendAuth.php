<?php declare(strict_types = 1); 

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BackendAuth extends BaseConfig
{
	public string $hashKey = '';
	public string $sessionCryptoKey = '';

	public function __construct()
    {
        parent::__construct();

        /* Recupera la stringa dall'env */
        $rawKey = env('encryption.key', '');

        /* Se contiene il prefisso hex2bin:, estrae la parte esadecimale e la converte in binario */
        if (str_starts_with($rawKey, 'hex2bin:')):
            $hex = substr($rawKey, 8);
            $binaryKey = hex2bin($hex);
        else:
            $binaryKey = $rawKey;
        endif;

        /* Assegna la chiave binaria pronta per le funzioni crittografiche */
        $this->hashKey = $binaryKey;
        $this->sessionCryptoKey = $binaryKey;
    }

    public bool $attempts = true;

    public int $attemptsInterval = 600;

    public int $attemptsLimit = 3; 

    public bool $twoFactor = false;

    public int $rememberMeTime = 86400;

    public int $sessionTime = 1200;

    public int $activationTime = 21600;
}