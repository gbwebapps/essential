<?php

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    /**
     * Indirizzo email predefinito del mittente per le comunicazioni in uscita.
     *
     * @var string
     */
    public string $fromEmail  = 'essential@essential.com';

    /**
     * Nome predefinito del mittente visualizzato nelle comunicazioni in uscita.
     *
     * @var string
     */
    public string $fromName   = 'Essential';

    /**
     * Indirizzi email dei destinatari predefiniti (separati da virgola), se necessario.
     *
     * @var string
     */
    public string $recipients = '';

    /**
     * L'"user agent" inviato nelle intestazioni dell'email.
     *
     * @var string
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * Il protocollo di invio delle email: mail, sendmail, smtp.
     *
     * @var string
     */
    public string $protocol = 'smtp';

    /**
     * Il percorso del server per l'eseguibile Sendmail.
     *
     * @var string
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * Nome dell'host del server SMTP.
     *
     * @var string
     */
    public string $SMTPHost = 'sandbox.smtp.mailtrap.io';

    /**
     * Metodo di autenticazione SMTP da utilizzare: login, plain.
     *
     * @var string
     */
    public string $SMTPAuthMethod = 'LOGIN';

    /**
     * Nome utente (Username) per l'autenticazione SMTP.
     *
     * @var string
     */
    public string $SMTPUser = '2136f4b6976b87';

    /**
     * Password per l'autenticazione SMTP.
     *
     * @var string
     */
    public string $SMTPPass = 'e866a6da928c72';

    /**
     * Porta di connessione al server SMTP.
     *
     * @var int
     */
    public int $SMTPPort = 2525;

    /**
     * Tempo massimo di attesa (in secondi) per le connessioni SMTP.
     *
     * @var int
     */
    public int $SMTPTimeout = 5;

    /**
     * Abilita le connessioni SMTP persistenti.
     *
     * @var bool
     */
    public bool $SMTPKeepAlive = false;

    /**
     * Crittografia SMTP.
     * 
     * Può essere '', 'tls' o 'ssl'. 
     * 'tls' invierà un comando STARTTLS al server. 'ssl' indica una connessione SSL implicita. 
     * Per connessioni sulla porta 465, questo valore dovrebbe essere impostato su ''.
     *
     * @var string
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Abilita l'a capo automatico delle parole (word-wrap).
     *
     * @var bool
     */
    public bool $wordWrap = true;

    /**
     * Numero di caratteri a cui forzare l'a capo automatico.
     *
     * @var int
     */
    public int $wrapChars = 76;

    /**
     * Tipo di formato per le email, può essere 'text' (testo normale) o 'html'.
     *
     * @var string
     */
    public string $mailType = 'html';

    /**
     * Set di caratteri utilizzato (utf-8, iso-8859-1, ecc.).
     *
     * @var string
     */
    public string $charset = 'UTF-8';

    /**
     * Determina se convalidare formalmente l'indirizzo email prima dell'invio.
     *
     * @var bool
     */
    public bool $validate = false;

    /**
     * Priorità dell'email. 1 = massima, 5 = minima, 3 = normale.
     *
     * @var int
     */
    public int $priority = 3;

    /**
     * Carattere di ritorno a capo. (Usa "\r\n" per rispettare lo standard RFC 822).
     *
     * @var string
     */
    public string $CRLF = "\r\n";

    /**
     * Carattere di nuova riga. (Usa "\r\n" per rispettare lo standard RFC 822).
     *
     * @var string
     */
    public string $newline = "\r\n";

    /**
     * Abilita la modalità a blocchi (batch) per i destinatari in copia nascosta (BCC).
     *
     * @var bool
     */
    public bool $BCCBatchMode = false;

    /**
     * Numero di email incluse in ogni blocco BCC.
     *
     * @var int
     */
    public int $BCCBatchSize = 200;

    /**
     * Abilita i messaggi di notifica di stato della consegna (DSN) dal server.
     *
     * @var bool
     */
    public bool $DSN = false;
}