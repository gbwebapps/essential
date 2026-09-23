<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione dei parametri di autenticazione, sicurezza, crittografia e gestione delle sessioni del backend.
 */
class Auth extends BaseConfig
{
    /**
     * @var string Chiave di cifratura utilizzata per le funzioni di hash
     */
	public string $hashKey = '';

    /**
     * @var string Chiave crittografica dedicata alla sicurezza delle sessioni
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
     * @var bool Indica se il controllo dei tentativi di accesso falliti è attivo
     */
    public bool $attempts = true;

    /**
     * @var int Intervallo di tempo in secondi per il blocco dei tentativi di accesso
     */
    public int $attemptsInterval = 600;

    /**
     * @var int Numero massimo consentito di tentativi di accesso prima del blocco
     */
    public int $attemptsLimit = 3; 

    /**
     * @var bool Indica se l'autenticazione a due fattori (2FA) è abilitata globalmente
     */
    public bool $twoFactor = true;

    /**
     * @var int Limite massimo di tentativi errati consentiti per la verifica del codice 2FA
     */
    public int $twoFactorLimit = 3;

    /**
     * @var int Finestra temporale di validità in secondi per il codice 2FA
     */
    public int $twoFactorTime = 600;

    /**
     * @var string Nome dell'emittente associato alla generazione dei codici OTP
     */
    public string $twoFactorIssuer = 'Essential';

    /**
     * @var int Numero di cifre che compongono il codice di verifica 2FA
     */
    public int $twoFactorDigits = 6;

    /**
     * @var int Tolleranza temporale in finestre temporali per la verifica 2FA
     */
    public int $twoFactorWindow = 1;

    /**
     * @var int Tempo di scadenza in secondi per il codice 2FA inviato via email
     */
    public int $twoFactorEmailExpiry = 60;

    /**
     * @var string Indirizzo email predefinito utilizzato per l'invio dei codici 2FA
     */
    public string $twoFactorEmailFrom = 'superadmin@essential.it';

    /**
     * @var array Elenco dei metodi di autenticazione a due fattori supportati
     */
    public array $twoFactorMethods = ['none', 'email', 'totp'];

    /**
     * @var int Durata in secondi della sessione di ricordo dell'accesso (Remember Me)
     */
    public int $rememberMeTime = 864000;

    /**
     * @var int Durata massima in secondi di inattività prima della scadenza della sessione
     */
    public int $sessionTime = 1200;

    /**
     * @var int Tempo di validità in secondi per il link o codice di attivazione dell'account
     */
    public int $activationTime = 43200;

    /**
     * @var string Espressione regolare per la validazione della robustezza delle password
     */
    public string $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
}