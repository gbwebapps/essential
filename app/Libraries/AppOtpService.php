<?php declare(strict_types=1);

namespace App\Libraries;

use OTPHP\TOTP;

class AppOtpService
{
    public function __construct()
    {
        helper('settings');
    }

    /**
     * Genera una chiave segreta (secret) univoca in formato Base32 per l'utente.
     * Come funziona:
     * -> Crea una stringa casuale protetta che verrà salvata sul database dell'utente.
     * @return string La chiave segreta generata.
     */
    public function generateSecret(): string
    {
        return TOTP::create()->getSecret();
    }

    /**
     * Genera l'indirizzo URI standard da convertire in codice QR per le App di autenticazione.
     * Come funziona:
     * -> Prende la chiave segreta dell'utente e imposta il nome del sito e dell'utente.
     * -> Restituisce il testo che diventerà il QR Code da inquadrare con lo smartphone.
     * @param string $secret La chiave segreta Base32 dell'amministratore.
     * @param string $label L'identificativo visibile nell'app (es. l'email dell'utente).
     * @return string L'indirizzo URI di provisioning.
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
     * Verifica se il codice numerico inserito corrisponde alla chiave segreta dell'utente.
     * Come funziona:
     * -> Controlla il codice attuale basandosi sul tempo reale del server.
     * -> Accetta una finestra di tolleranza impostata nelle configurazioni per evitare problemi di fuso orario.
     * @param string $secret La chiave segreta Base32 salvata sul database.
     * @param string $code Il codice di 6 cifre inserito a schermo dall'utente.
     * @return bool True se il codice è valido, altrimenti False.
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