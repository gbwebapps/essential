<?php declare(strict_types=1);

namespace App\Libraries;

class EmailOtpService
{
    /**
     * Database connection instance.
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;

    /**
     * Constructor initialization.
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Genera un codice OTP, lo salva sul database e invia l'e-mail all'amministratore.
     * Come funziona:
     * -> Crea un numero casuale di 6 cifre e imposta la scadenza leggendo la configurazione.
     * -> Salva il record nella tabella `admins_2fa_codes`.
     * -> Recupera i dati anagrafici dell'amministratore.
     * -> Prepara e invia l'e-mail utilizzando il servizio nativo di CodeIgniter.
     * @param string $adminUuid L'identificativo univoco dell'amministratore.
     * @return bool True se l'e-mail è stata inviata con successo, altrimenti False.
     */
    public function send(string $adminUuid): bool
    {
        $config = config(\Config\Backend\Auth::class);
        
        /* Genera un codice numerico casuale della lunghezza configurata */
        $code = str_pad((string)random_int(0, 999999), $config->twoFactorDigits, '0', STR_PAD_LEFT);
        
        /* Calcola la data di scadenza per il formato DATETIME */
        $expiresAt = date('Y-m-d H:i:s', time() + (int)$config->twoFactorEmailExpiry);

        try {
            $sql = "insert into admins_2fa_codes (admin_uuid, code, expires_at) values (?, ?, ?)";
            $this->db->query($sql, [$adminUuid, $code, $expiresAt]);
        } 
        catch (\Throwable $e) {
            log_message('error', 'Errore salvataggio OTP email: ' . $e->getMessage());
            return false;
        }

        /* Recupera l'e-mail e i dati dell'utente per la spedizione */
        $sql = "select email, firstname, lastname from admins where uuid = ? and status = 1 limit 1";
        $admin = $this->db->query($sql, [$adminUuid])->getRow();

        if ( ! $admin):
            return false;
        endif;

        /* Configurazione e invio tramite il servizio Email nativo di CodeIgniter */
        $email = \Config\Services::email();
        $email->setFrom($config->twoFactorEmailFrom, $config->twoFactorIssuer);
        $email->setTo($admin->email);
        $email->setSubject(lang('backend/email.auth.2fa.subjectVerifyCodeEmail'));

        /* Calcola i minuti di scadenza direttamente nel servizio */
        $expiryMinutes = (int) round($config->twoFactorEmailExpiry / 60);
        
        /* Carica la vista parziale passando i dati necessari */
        $htmlMessage = view('backend/auth/partials/email/email2faPartial', [
            'firstname'     => $admin->firstname,
            'lastname'      => $admin->lastname,
            'code'          => $code,
            'expiryMinutes' => $expiryMinutes /* Passato come argomento */
        ]);
        
        $email->setMessage($htmlMessage);

        if ( ! $email->send()):
            log_message('error', sprintf('Invio OTP fallito per %s %s (%s)', $admin->firstname, $admin->lastname, $admin->email));
            return false;
        else:
            return true;
        endif;
    }

    /**
     * Verifica se il codice OTP inserito dall'utente è valido e non è ancora scaduto.
     * Come funziona:
     * -> Cerca nella tabella un record che corrisponda all'utente e al codice digitato.
     * -> Controlla che la data di scadenza sia maggiore o uguale al momento attuale.
     * @param string $adminUuid L'identificativo univoco dell'amministratore.
     * @param string $code Il codice OTP digitato dall'utente.
     * @return bool True se il codice esiste ed è valido, altrimenti False.
     */
    public function verify(string $adminUuid, string $code): bool
    {
        $now = date('Y-m-d H:i:s');

        $sql = 'select id from admins_2fa_codes where admin_uuid = ? and code = ? and expires_at >= ? limit 1';
        $row = $this->db->query($sql, [$adminUuid, $code, $now])->getRow();

        if ( ! $row):
            return false;
        endif;

        return true;
    }
}