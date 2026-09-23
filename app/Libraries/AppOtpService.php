<?php declare(strict_types=1);

namespace App\Libraries;

use OTPHP\TOTP;

/**
 * Servizio dedicato alla gestione dell'autenticazione a due fattori (2FA) basata su protocollo TOTP (Time-based One-Time Password).
 * 
 * Interfaccia la libreria crittografica sottostante per generare chiavi segrete, fornire gli URI 
 * di configurazione per le applicazioni client (es. Google Authenticator, Authy) e validare 
 * matematicamente i codici temporanei immessi dagli utenti.
 */
class AppOtpService
{
    /**
     * Costruttore del servizio.
     * Inizializza le dipendenze basilari, caricando in memoria l'helper necessario 
     * al recupero dinamico delle configurazioni del modulo di autenticazione.
     */
    public function __construct()
    {
        helper('settings');
    }

    /**
     * Genera crittograficamente un nuovo codice segreto condiviso univoco (in formato base32).
     * 
     * Questo segreto rappresenta la chiave master per l'algoritmo TOTP e deve essere associato 
     * in modo permanente e sicuro al profilo dell'amministratore all'interno del database.
     *
     * @return string La stringa alfanumerica rappresentante il nuovo segreto generato
     */
    public function generateSecret(): string
    {
        return TOTP::create()->getSecret();
    }

    /**
     * Compila e restituisce l'URI di provisioning standard richiesto per l'accoppiamento del dispositivo.
     * 
     * Legge le impostazioni globali del sistema (numero di cifre, nome dell'emittente) e struttura 
     * un URI formattato secondo le specifiche otpauth://. Questo URI viene tipicamente trasformato 
     * in un codice QR per facilitare la configurazione dell'app sullo smartphone dell'utente.
     *
     * @param string $secret La chiave segreta univoca associata all'amministratore
     * @param string $label L'etichetta identificativa da mostrare nell'applicazione client (es. l'indirizzo email)
     * @return string L'URI formattato e pronto per la generazione del codice QR
     */
    public function getProvisioningUri(string $secret, string $label): string
    {
        $config = setting('Backend\Auth');

        /* Crea l'istanza TOTP impostando la durata standard (30s), l'algoritmo e il numero di cifre */
        $totp = TOTP::create($secret, 30, 'sha1', (int) $config->twoFactorDigits);
        $totp->setLabel($label);
        $totp->setIssuer($config->twoFactorIssuer);

        return $totp->getProvisioningUri();
    }

    /**
     * Esegue la validazione algoritmica del codice temporaneo (OTP) fornito dall'amministratore.
     * 
     * Ricostruisce il validatore TOTP applicando i medesimi parametri utilizzati durante la generazione 
     * dell'URI (periodo, algoritmo, lunghezza). Integra inoltre una finestra temporale di tolleranza (window) 
     * definita nelle configurazioni, fondamentale per compensare lievi desincronizzazioni fisiologiche 
     * tra l'orologio del server e quello dello smartphone del client.
     *
     * @param string $secret La chiave segreta univoca memorizzata nel profilo dell'amministratore
     * @param string $code Il codice numerico inserito dall'utente per tentare l'accesso
     * @return bool Esito della verifica crittografica: true se il codice è valido e tempestivo, false altrimenti
     */
    public function verify(string $secret, string $code): bool
    {
        $config = setting('Backend\Auth');
        
        /* CORREZIONE: Inizializzo l'oggetto con le stesse identiche configurazioni del QR Code */
        $totp = TOTP::create($secret, 30, 'sha1', (int) $config->twoFactorDigits);
        
        /* Consente una tolleranza di X periodi prima/dopo per compensare disallineamenti di orario dello smartphone */
        $window = (int) $config->twoFactorWindow;

        return $totp->verify($code, null, $window);
    }
}