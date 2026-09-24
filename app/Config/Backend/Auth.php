<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione dei parametri di autenticazione, sicurezza, crittografia e gestione delle sessioni del backend.
 */
class Auth extends BaseConfig
{
    /**
     * Chiave di cifratura utilizzata per le funzioni di hash
     * 
     * @var string 
     */
	public string $hashKey = '';

    /**
     * Chiave crittografica dedicata alla sicurezza delle sessioni
     * 
     * @var string 
     */
	public string $sessionCryptoKey = '';

    /**
     * Inizializza la configurazione di autenticazione recuperando e convertendo la chiave di cifratura dall'ambiente.
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
     * Indica se il controllo dei tentativi di accesso falliti è attivo
     * 
     * @var bool 
     */
    public bool $attempts = true;

    /**
     * Intervallo di tempo in secondi per il blocco dei tentativi di accesso
     * 
     * @var int 
     */
    public int $attemptsInterval = 600;

    /**
     * Numero massimo consentito di tentativi di accesso prima del blocco
     * 
     * @var int 
     */
    public int $attemptsLimit = 3; 

    /**
     * Indica se l'autenticazione a due fattori (2FA) è abilitata globalmente
     * 
     * @var bool 
     */
    public bool $twoFactor = true;

    /**
     * Limite massimo di tentativi errati consentiti per la verifica del codice 2FA
     * 
     * @var int 
     */
    public int $twoFactorLimit = 3;

    /**
     * Finestra temporale di validità in secondi per il codice 2FA
     * 
     * @var int 
     */
    public int $twoFactorTime = 600;

    /**
     * Nome dell'emittente associato alla generazione dei codici OTP
     * 
     * @var string 
     */
    public string $twoFactorIssuer = 'Essential';

    /**
     * Numero di cifre che compongono il codice di verifica 2FA
     * 
     * @var int 
     */
    public int $twoFactorDigits = 6;

    /**
     * Tolleranza temporale in finestre temporali per la verifica 2FA
     * 
     * @var int 
     */
    public int $twoFactorWindow = 1;

    /**
     * Tempo di scadenza in secondi per il codice 2FA inviato via email
     * 
     * @var int 
     */
    public int $twoFactorEmailExpiry = 60;

    /**
     * Indirizzo email predefinito utilizzato per l'invio dei codici 2FA
     * 
     * @var string 
     */
    public string $twoFactorEmailFrom = 'superadmin@essential.it';

    /**
     * Elenco dei metodi di autenticazione a due fattori supportati
     * 
     * @var array 
     */
    public array $twoFactorMethods = ['none', 'email', 'totp'];

    /**
     * Durata in secondi della sessione di ricordo dell'accesso (Remember Me)
     * 
     * @var int 
     */
    public int $rememberMeTime = 864000;

    /**
     * Durata massima in secondi di inattività prima della scadenza della sessione
     * 
     * @var int 
     */
    public int $sessionTime = 1200;

    /**
     * Tempo di validità in secondi per il link o codice di attivazione dell'account
     * 
     * @var int 
     */
    public int $activationTime = 43200;

    /**
     * Espressione regolare per la validazione della robustezza delle password
     * 
     * @var string 
     */
    public string $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
}