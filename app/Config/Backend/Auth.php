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
     * Chiave binaria utilizzata per le operazioni di hashing e validazione dei dati.
     * 
     * @var string 
     */
	public string $hashKey = '';

    /**
     * Chiave binaria utilizzata per la cifratura e protezione della sessione.
     * 
     * @var string 
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
     * Abilita o disabilita il controllo sul limite dei tentativi di login falliti.
     * 
     * @var bool 
     */
    public bool $attempts = true;

    /**
     * Intervallo di tempo (in secondi) in cui monitorare e vincolare i tentativi di accesso.
     * 
     * @var int 
     */
    public int $attemptsInterval = 600;

    /**
     * Numero massimo di tentativi di login errati consentiti prima del blocco temporaneo.
     * 
     * @var int 
     */
    public int $attemptsLimit = 3; 

    /**
     * Stato di attivazione globale per l'autenticazione a due fattori (2FA).
     * @var bool 
     */
    public bool $twoFactor = true;

    /**
     * Numero massimo di tentativi errati di inserimento OTP prima del blocco temporaneo.
     * @var int
     */
    public int $twoFactorLimit = 3;

    /**
     * Finestra temporale di monitoraggio (in secondi) per il calcolo del brute-force.
     * @var int
     */
    public int $twoFactorTime = 600;

    /**
     * Nome dell'applicazione che apparirà all'utente all'interno dell'app di autenticazione.
     * @var string
     */
    public string $twoFactorIssuer = 'Essential';

    /**
     * Numero di cifre che compongono il codice OTP generato.
     * @var int
     */
    public int $twoFactorDigits = 6;

    /**
     * Finestra di tolleranza temporale per i codici TOTP per compensare disallineamenti di orologio.
     * @var int
     */
    public int $twoFactorWindow = 1;

    /**
     * Durata di validità (in secondi) del codice OTP inviato tramite e-mail.
     * @var int
     */
    public int $twoFactorEmailExpiry = 60;

    /**
     * Indirizzo e-mail del mittente utilizzato per l'invio dei codici di verifica.
     * @var string
     */
    public string $twoFactorEmailFrom = 'master@essential.it';

    /**
     * Elenco dei metodi di autenticazione a due fattori supportati dal sistema.
     * @var array
     */
    public array $twoFactorMethods = ['none', 'email', 'totp'];

    /**
     * Durata di validità (in secondi) della persistenza dell'accesso tramite cookie "Remember Me".
     * 
     * @var int 
     */
    public int $rememberMeTime = 86400;

    /**
     * Durata massima di inattività (in secondi) prima della scadenza della sessione di backend.
     * 
     * @var int 
     */
    public int $sessionTime = 1200;

    /**
     * Finestra temporale di validità (in secondi) dei token di attivazione o ripristino account.
     * 
     * @var int 
     */
    public int $activationTime = 21600;

    /**
     * Espressione regolare (Regex) utilizzata per validare i requisiti minimi di sicurezza delle password.
     * 
     * @var string 
     */
    public string $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
}