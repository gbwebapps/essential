<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Class Auth
 *
 * Configurazione centrale per le politiche di sicurezza, gestione delle sessioni,
 * tentativi di accesso e chiavi crittografiche per l'ambiente di Backend.
 */
class Auth extends BaseConfig
{
    /**
     * @var string Chiave binaria utilizzata per le operazioni di hashing e validazione dei dati.
     */
	public string $hashKey = '';

    /**
     * @var string Chiave binaria utilizzata per la cifratura e protezione della sessione.
     */
	public string $sessionCryptoKey = '';

    /**
     * Costruttore della classe.
     * Recupera la chiave di cifratura dal file di ambiente (.env), gestisce l'eventuale
     * conversione da esadecimale e inizializza le chiavi crittografiche.
     */
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

    /**
     * @var bool Abilita o disabilita il controllo sul limite dei tentativi di login falliti.
     */
    public bool $attempts = true;

    /**
     * @var int Intervallo di tempo (in secondi) in cui monitorare e vincolare i tentativi di accesso.
     */
    public int $attemptsInterval = 600;

    /**
     * @var int Numero massimo di tentativi di login errati consentiti prima del blocco temporaneo.
     */
    public int $attemptsLimit = 3; 

    /**
     * @var bool Stato di attivazione globale per l'autenticazione a due fattori (2FA).
     */
    public bool $twoFactor = false;

    /**
     * @var int Durata di validità (in secondi) della persistenza dell'accesso tramite cookie "Remember Me".
     */
    public int $rememberMeTime = 86400;

    /**
     * @var int Durata massima di inattività (in secondi) prima della scadenza della sessione di backend.
     */
    public int $sessionTime = 1200;

    /**
     * @var int Finestra temporale di validità (in secondi) dei token di attivazione o ripristino account.
     */
    public int $activationTime = 21600;

    /**
     * @var string Espressione regolare (Regex) utilizzata per validare i requisiti minimi di sicurezza delle password.
     */
    public string $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
}