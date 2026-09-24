<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello principale per la gestione del profilo personale dell'amministratore (Account).
 * 
 * Estende il BackendModel fornendo l'accesso ai dati e incapsulando la business logic per l'interfaccia 
 * "Il mio Account". Gestisce transazioni atomiche per l'aggiornamento dell'anagrafica, la gestione sicura 
 * delle credenziali (reset password), il tracciamento e la revoca selettiva delle sessioni attive, 
 * nonché la configurazione dei metodi di autenticazione a due fattori (2FA).
 */
class AccountModel extends BackendModel
{
	/**
	 * Identificativo del modulo corrente, impiegato per la risoluzione dinamica delle viste (es. template e-mail) e dei percorsi.
	 * 
	 * @var string|null 
	 */
	protected ?string $module = 'account';

	/**
	 * Whitelist dei campi consentiti durante l'aggiornamento del profilo.
	 * Previene vulnerabilità di Mass Assignment filtrando severamente l'array $posts in ingresso.
	 * 
	 * @var array 
	 */
	protected array $editAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'note'];

	/**
	 * Whitelist dei campi ammessi per l'operazione di revoca (eliminazione) di un token di sessione.
	 * 
	 * @var array 
	 */
	protected array $deleteTokenAllowedFields = ['id'];

	/**
	 * Elenco delle chiavi da confrontare per verificare l'effettiva mutazione dei dati 
	 * prima di innescare una query di UPDATE a database (ottimizzazione delle performance).
	 * 
	 * @var array 
	 */
    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'note'];

    /**
     * Hook nativo di CodeIgniter 4 per l'inizializzazione del modello.
     * 
     * Richiama il costruttore del parent (BackendModel) per garantire il corretto setup 
     * delle dipendenze di base (connessione DB, helper).
     */
	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
	 * Genera il set di regole di validazione nativo di CodeIgniter per l'aggiornamento dell'anagrafica.
	 * 
	 * Applica controlli rigorosi tramite espressioni regolari (nomi, numeri di telefono internazionali) e 
	 * verifica l'univocità dell'indirizzo e-mail. La regola is_unique è configurata per ignorare l'UUID 
	 * dell'amministratore corrente, consentendo il salvataggio senza generare falsi positivi sulla propria e-mail.
	 *
	 * @param string $adminUuid L'identificativo univoco dell'amministratore per escluderlo dal controllo is_unique
	 * @return array Struttura associativa contenente etichette, regole di filtro/validazione ed eventuali messaggi d'errore custom
	 */
	public function editValidationRules(string $adminUuid): array
	{
	    return [
	        'firstname' => [
	            'label' => lang('backend/account.labels.firstname'),
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
	        ],
	        'lastname' => [
	            'label' => lang('backend/account.labels.lastname'),
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
	        ],
	        'email' => [
	            'label' => lang('backend/account.labels.email'),
	            'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', "is_unique[admins.email,uuid,{$adminUuid}]"],
	        ],
	        'phone' => [
	            'label' => lang('backend/account.labels.phone'),
	            'rules' => ['required', 'trim', 'regex_match[/^\+?[0-9]{9,15}$/]'],
	        ],
	        'note' => [
	            'label' => lang('backend/account.labels.note'),
	            'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
	            'errors' => [
	                'safeText' => 'Caratteri non ammessi.'
	            ]
	        ],
	    ];
	}

	/**
	 * Genera le regole di validazione per la richiesta di revoca di un token di sessione.
	 * 
	 * Assicura che l'identificativo del record (ID) fornito in POST sia un intero naturale valido 
	 * maggiore di zero, prevenendo query malformate o injection.
	 *
	 * @return array Regole di validazione per il campo 'id'
	 */
	public function deleteTokenValidationRules(): array
	{
	    return [
	        'id' => [
	            'label' => lang('backend/account.labels.id'),
	            'rules' => ['required', 'is_natural_no_zero'],
	            'errors' => [
	                'required' => lang('backend/account.errors.id'), 
	                'is_natural_no_zero' => lang('backend/account.errors.id') 
	            ]
	        ],
	    ];
	}

	/**
	 * Estrae l'elenco dei permessi (ACL) ereditati dal gruppo di appartenenza.
	 * 
	 * Interroga la tabella di raggruppamento (admins_groups_permissions) e appiattisce il set di risultati 
	 * restituendo un array monodimensionale di sole stringhe, facilitando la successiva fusione con le eccezioni utente.
	 *
	 * @param int $groupId L'ID numerico del gruppo associato all'amministratore
	 * @return array Array sequenziale contenente i nomi dei permessi (es. ['manage_users', 'view_logs'])
	 */
	public function getGroupPermissions(int $groupId): array
	{
	    $sql = "select permission from admins_groups_permissions where group_id = ?";
	    $result = $this->db->query($sql, [$groupId])->getResultObject();

	    if ( ! $result):
	        return [];
	    endif;

	    /* Appiattisco l'array di oggetti in un array di stringhe */
	    return array_map(function($row) {
	        return $row->permission;
	    }, $result);
	}

	/**
	 * Recupera le eccezioni specifiche (override dei permessi) applicate direttamente sul singolo amministratore.
	 * 
	 * Genera un dizionario chiave-valore dove la chiave è il nome del permesso e il valore è l'intero 
	 * che ne definisce lo stato (1 per concessione/allow, 0 per revoca/deny rispetto al gruppo).
	 *
	 * @param string $uuid L'identificatore univoco dell'amministratore
	 * @return array Array associativo [nome_permesso => stato_allow]
	 */
	public function getAdminExceptions(string $uuid): array
	{
	    $sql = "select permission, allow from admins_permissions where admin_uuid = ?";
	    $result = $this->db->query($sql, [$uuid])->getResultObject();

	    if ( ! $result):
	        return [];
	    endif;

	    $exceptions = [];
	    foreach ($result as $row):
	        /* Mappo il nome del permesso come chiave e il valore di allow (0 o 1) come stato dell'eccezione */
	        $exceptions[$row->permission] = (int) $row->allow;
	    endforeach;

	    return $exceptions;
	}

	/**
	 * Recupera l'intero registro dei token di accesso (sessioni web, cookie remember-me, reset password) associati all'utente.
	 *
	 * @param string $uuid L'identificatore univoco dell'amministratore
	 * @return array Elenco dei record estratti dalla tabella admins_tokens
	 */
	public function getTokens(string $uuid): array
	{
	    /* Estrazione log dei tokens di sessione o reset */
	    $sql = "select * from admins_tokens where admin_uuid = ?";
	    return $this->db->query($sql, [$uuid])->getResult();
	}

	/**
	 * Esegue l'aggiornamento transazionale dei dati anagrafici dell'amministratore.
	 * 
	 * Il metodo applica preliminarmente il filtro sui campi consentiti (Whitelist) e verifica se vi è stata 
	 * un'effettiva modifica (hasDataChanged). Gestisce in modo sicuro le stringhe vuote (es. trasformando 
	 * una textarea 'note' svuotata in un reale NULL sul DB). L'operazione di UPDATE è racchiusa in una 
	 * transazione DB: in caso di successo, ricarica forzatamente l'identità in sessione (refresh) per 
	 * riflettere immediatamente le modifiche nell'interfaccia e registra l'evento nell'audit trail.
	 *
	 * @param array $posts I dati sanificati provenienti dal form HTTP POST
	 * @param \stdClass $currentAdmin L'oggetto rappresentante l'identità attuale in sessione
	 * @return array Esito strutturato contenente il flag di result, il messaggio localizzato e l'oggetto amministratore aggiornato
	 */
	public function edit(array $posts, \stdClass $currentAdmin): array 
	{
	    try {

	        $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

	        /* Se non è stato effettuato alcun cambio sui dati gestiti, interrompiamo subito */
	        if ( ! $this->hasDataChanged($posts, $currentAdmin)):
	            return ['result' => false, 'message' => lang('backend/account.messages.noDataChanged')];
	        endif;

	        $updated_at = date('Y-m-d H:i:s');
	        
	        /* Gestione pulita della stringa vuota in NULL per la textarea note */
	        $note = isset($posts['note']) ? trim($posts['note']) : '';
	        $noteValue = ($note === '') ? null : $note;

	        $this->db->transBegin();

	        $sql = 'update admins set firstname = ?, lastname = ?, email = ?, phone = ?, note = ?, updated_at = ? where uuid = ?';
	        $this->db->query($sql, [$posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $noteValue, $updated_at, $currentAdmin->uuid]);

	        if ($this->db->transStatus() === false):
	            $this->db->transRollback();
	            log_message('error', lang('backend/account.messages.editError'));
	            return ['result' => false, 'message' => lang('backend/account.messages.editError')];
	        endif;

	        $this->db->transCommit();

	        /* Ricarichiamo l'istanza dell'admin aggiornata per passarla al controller */
	        $currentAdmin = service('authorization')->refresh()->currentAdmin();
	        log_admin_activity('UPDATE_DATA', 'account', sprintf(lang('backend/account.audits.updateData'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

	        return [
	            'result' => true, 
	            'message' => sprintf(lang('backend/account.messages.editSuccess'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), 
	            'currentAdmin' => $currentAdmin
	        ];

	    } catch(\Throwable $e) {
	        $this->db->transRollback();
	        log_message('error', lang('backend/account.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
	        return ['result' => false, 'message' => lang('backend/account.messages.editError')];
	    }
	}

	/**
	 * Identifica l'ID a database del token corrispondente all'attuale sessione di navigazione.
	 * 
	 * Recupera il valore esadecimale dalla sessione nativa, ne calcola l'hash HMAC-SHA256 tramite 
	 * l'algoritmo condiviso e interroga la tabella admins_tokens per trovare la corrispondenza esatta, 
	 * restituendone l'ID primario. Fondamentale per i controlli di auto-esclusione.
	 *
	 * @return int|null L'ID del token di sessione attuale, o null se non individuato
	 */
    public function getCurrentTokenId(): ?int
    {
        if (session()->has('backendSession')):
            
            $sessionValue = session()->get('backendSession');
            $token = new \App\Libraries\Token($sessionValue);
            $tokenHash = $token->getHash(config('Backend/Auth')->hashKey);

            $sql = "select id from admins_tokens where token_hash = ? and token_type = 'session'";
            $row = $this->db->query($sql, [$tokenHash])->getRow();

            return $row ? (int) $row->id : null;
            
        endif;

        return null;
    }

    /**
     * Revoca e distrugge fisicamente un token (sessione, cookie o attivazione) dal database.
     * 
     * Integra un blocco di sicurezza (Sbarramento) che impedisce all'operatore di "suicidare" 
     * accidentalmente la propria sessione HTTP in corso. Se il token da eliminare è legato a un accesso 
     * effettivo (cookie o session), il metodo aggiorna preventivamente i log applicativi (admins_logs) 
     * registrando il timestamp di logout e causale 'deleted', prima di procedere con la cancellazione fisica 
     * del record e il tracciamento nell'audit.
     *
     * @param array $posts I dati provenienti dalla richiesta (contenenti l'ID del token)
     * @param \stdClass $currentAdmin L'identità amministrativa che richiede la cancellazione
     * @param int|null $currentTokenId L'ID della sessione attuale (usato per il controllo di sicurezza)
     * @return array Esito dell'operazione con relativo messaggio localizzato
     */
	public function deleteToken(array $posts, \stdClass $currentAdmin, ?int $currentTokenId = null): array
    {
        /* Match dei posts con i campi consentiti */
        $posts = $this->checkAllowedFields($posts, $this->deleteTokenAllowedFields);

        /* SBARRAMENTO DI SICUREZZA: Impedisce l'eliminazione esclusiva del token di sessione in uso */
        if ($currentTokenId !== null && (int) $posts['id'] === $currentTokenId):
            log_message('warning', 'Tentativo bloccato: l\'utente ha tentato di eliminare il token della sessione in uso.');
            return ['result' => false, 'message' => lang('backend/account.messages.cannotDeleteCurrentToken')];
        endif;

        try {

            /* 1. Recupero il token per leggere last_activity */
            $tokenSql = "select id, last_activity, token_type from admins_tokens where admin_uuid = ? and id = ?";
            $tokenRow = $this->db->query($tokenSql, [$currentAdmin->uuid, $posts['id']])->getRow();

            if ($tokenRow):
                /* 2. Aggiorno il log se è una sessione o cookie */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'deleted' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;
            endif;

            /* 3. Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$currentAdmin->uuid, $posts['id']]);

            if($this->db->affectedRows() > 0):
                log_admin_activity('DELETE_TOKEN', 'account', sprintf(lang('backend/account.audits.deleteToken'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);
                return ['result' => true, 'message' => sprintf(lang('backend/account.messages.deleteTokenSuccess'), esc($currentAdmin->firstname), esc($currentAdmin->lastname))];
            endif;

            return ['result' => false, 'message' => lang('backend/account.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/account.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/account.messages.deleteTokenError')];

        }
    }

    /**
     * Esegue la procedura transazionale per la generazione di una richiesta di ripristino/attivazione password.
     * 
     * Disabilita ogni eventuale token di attivazione precedente e genera un nuovo token crittograficamente sicuro, 
     * calcolandone l'hash e la scadenza in base ai parametri globali. Salva il record nel database aggiornando 
     * il campo resetted_at, raccoglie l'impronta del client (User Agent, IP) e invia l'e-mail transazionale.
     * Qualora il server SMTP fallisca ma l'inserimento a DB vada a buon fine, il sistema non effettua il rollback 
     * (il token resta valido) ma restituisce lo stato speciale 'db_committed_no_email' per avvisare l'operatore.
     *
     * @param \stdClass $currentAdmin L'identità amministrativa che subisce/richiede il reset
     * @param \CodeIgniter\HTTP\IncomingRequest $request L'oggetto richiesta per l'estrazione dell'IP e dello User Agent
     * @return array Esito differenziato ('true', 'false', o 'db_committed_no_email') con messaggio associato
     */
	public function resetPassword(\stdClass $currentAdmin, \CodeIgniter\HTTP\IncomingRequest $request): array
	{
	    try 
	    {
	        $userAgent = $request->getUserAgent()->getAgentString();
	        $ip_address = $request->getIPAddress();

	        /* Generazione token di attivazione */
	        $token = new \App\Libraries\Token();
	        $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

	        /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
	        $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

	        $this->db->transBegin();

	        /* Scrittura Data di Reset nella tabella admins */
	        $sql = "update admins set resetted_at = ? where uuid = ?";
	        $this->db->query($sql, [date('Y-m-d H:i:s'), $currentAdmin->uuid]);

	        /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
	        $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
	        $this->db->query($sql, [$currentAdmin->uuid, 'activation']);

	        /* Scrittura del token di attivazione */
	        $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
	        $this->db->query($sql, [$currentAdmin->uuid, $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d_H-i-s')]);

	        if ($this->db->transStatus() === false):

	            $this->db->transRollback();
	            log_message('error', lang('backend/account.messages.resetPasswordError'));

	            return ['result' => false, 'message' => lang('backend/account.messages.resetPasswordError')];
	        endif;

	        $this->db->transCommit();

	        log_admin_activity('RESET_PASSWORD', 'account', sprintf(lang('backend/account.audits.resetPassword'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

	    } catch (\Throwable $e) {

	        $this->db->transRollback();

	        log_message('error', lang('backend/account.messages.resetPasswordError') . ' - ' . $e);
	        return ['result' => false, 'message' => lang('backend/account.messages.resetPasswordError')];

	    }

	    /* Istanzio il servizio email dedicato e tento l'invio */
	    $emailService = new \App\Libraries\Backend\EmailService();

	    /* Configuro i parametri dinamici per questa specifica chiamata */
	    $module = $this->module;
	    $template = 'emailResetPasswordAdminPartial';
	    $subjectLangKey = 'backend/email.account.resetPassword.subjectResetPasswordEmail';

	    /* Chiamata al metodo con i nuovi parametri separati */
	    if ( ! $emailService->sendActivationEmail($currentAdmin, $token->getValue(), $module, $template, $subjectLangKey)):

	        $message = sprintf(lang('backend/account.messages.resetPasswordSuccessNoEmail'), esc($currentAdmin->firstname), esc($currentAdmin->lastname));
	        return ['result' => 'db_committed_no_email', 'message' => $message];
	        
	    else:
	        
	        $message = sprintf(lang('backend/account.messages.resetPasswordSuccess'), esc($currentAdmin->firstname), esc($currentAdmin->lastname));
	        return ['result' => true, 'message' => $message];
	        
	    endif;
	}

	/**
	 * Recupera e formatta la data di scadenza del token di reset/attivazione più recente per l'utente.
	 * 
	 * Estrae l'ultimo record di tipo 'activation' e ne confronta la data di scadenza con il timestamp attuale. 
	 * Ritorna una stringa HTML formattata condizionalmente: testo verde normale se il token è ancora operativo, 
	 * testo rosso barrato se la finestra temporale è ormai scaduta.
	 *
	 * @param \stdClass $currentAdmin L'identità amministrativa di riferimento
	 * @return string Markup HTML formattato contenente la data conversazionale o stringa vuota se nessun token esiste
	 */
	public function getExpiringDate(\stdClass $currentAdmin): string
	{
	    $expiringDate = '';

	    $sql = 'select token_expire from admins_tokens where admin_uuid = ? and token_type = ? order by token_expire desc limit 1';
	    
	    if($token = $this->db->query($sql, [$currentAdmin->uuid, 'activation'])->getRow()):

		    if (date('Y-m-d H:i:s') < $token->token_expire):
		        $expiringDate = '<span class="text-success">' . convertDate($token->token_expire, 'conversational') . '</span>';
		    else:
		        $expiringDate = '<span class="text-danger"><s>' . convertDate($token->token_expire, 'conversational') . '</s></span>';
		    endif;

		endif;

	    return $expiringDate;
	}

	/**
	 * Recupera l'attuale metodo di Autenticazione a Due Fattori (2FA) abilitato per l'utente.
	 *
	 * @param string $adminUuid L'identificatore univoco dell'amministratore
	 * @return string Il metodo attivo (es. 'email', 'totp') o 'none' se nessuna protezione aggiuntiva è abilitata
	 */
	public function getActiveMethod(string $adminUuid): string
	{
	    $sql = "select method from admins_2fa where admin_uuid = ? and enabled = 1 limit 1";
	    $row = $this->db->query($sql, [$adminUuid])->getRow();

	    if ( ! $row):
	        return 'none';
	    endif;

	    return $row->method;
	}

	/**
	 * Imposta o disattiva il metodo di Autenticazione a Due Fattori (2FA) di base (Nessuno o E-mail).
	 * 
	 * L'operazione, eseguita all'interno di una transazione, disabilita preventivamente qualsiasi metodo 
	 * configurato per l'utente impostando enabled = 0. Successivamente, se il metodo richiesto è 'email', 
	 * esegue un "Upsert" (Insert or Update) per abilitare specificamente tale protezione. 
	 * Conclude registrando l'azione nell'audit log.
	 *
	 * @param \stdClass $currentAdmin L'identità amministrativa per cui modificare la sicurezza
	 * @param string $method Il metodo richiesto ('none' per disabilitare, 'email' per abilitare OTP via e-mail)
	 * @return bool True se la transazione e l'aggiornamento vanno a buon fine, false in caso di eccezione
	 */
	public function setBasicMethod(\stdClass $currentAdmin, string $method,): bool
    {
        try {
            $this->db->transBegin();

            /* Disattivo tutti i metodi esistenti per l'utente */
            $sqlDisable = "update admins_2fa set enabled = 0 where admin_uuid = ?";
            $this->db->query($sqlDisable, [$currentAdmin->uuid]);

            /* Se il metodo è email, eseguo l'upsert per attivarlo */
            if ($method === 'email') :
                $sqlUpsert = "insert into admins_2fa (admin_uuid, method, enabled) values (?, 'email', 1) on duplicate key update enabled = 1";
                $this->db->query($sqlUpsert, [$currentAdmin->uuid]);
            endif;

            $this->db->transCommit();

            log_admin_activity('ACTIVATE_' . strtoupper($method), 'account', sprintf(lang('backend/account.audits.activateMethod'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Errore nel salvataggio del metodo 2FA base: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Salva temporaneamente il segreto crittografico TOTP generato (es. via Google Authenticator).
     * 
     * Esegue un comando di Upsert per inserire o aggiornare la chiave segreta (secret) nella tabella 
     * admins_2fa, forzando rigorosamente il campo 'enabled' a 0. Il metodo TOTP diventerà operativo 
     * solo dopo che l'utente avrà dimostrato di possedere l'app superando una prima validazione.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
     * @param string $secret La chiave segreta alfanumerica di base32
     * @return bool True in caso di salvataggio riuscito, false in caso di errore
     */
    public function saveTemporarySecret(string $adminUuid, string $secret): bool
    {
        try {

            $sql = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'totp', ?, 0) on duplicate key update secret = ?, enabled = 0";
            
            $this->db->query($sql, [$adminUuid, $secret, $secret]);
            return true;

        } catch (\Throwable $e) {
            log_message('error', 'Errore nel salvataggio del secret TOTP temporaneo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recupera la chiave segreta TOTP (temporanea e non ancora validata) associata all'utente.
     * 
     * Utilizzato durante il processo di onboarding del 2FA tramite App: estrae il segreto salvato 
     * in precedenza (con enabled = 0) per permetterne il confronto con l'OTP digitato dall'utente.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
     * @return string|null La chiave segreta se presente, null in caso contrario
     */
    public function getTemporarySecret(string $adminUuid): ?string
    {
        $sql = "select secret from admins_2fa where admin_uuid = ? and method = 'totp' and enabled = 0 limit 1";
        $row = $this->db->query($sql, [$adminUuid])->getRow();

        if ( ! $row) :
            return null;
        endif;

        return (string) $row->secret;
    }

    /**
     * Convalida e attiva definitivamente il metodo TOTP (Time-based One-Time Password) per l'utente.
     * 
     * Questa transazione rappresenta la fase conclusiva dell'onboarding 2FA: disattiva esplicitamente 
     * l'eventuale metodo via E-mail preesistente e converte in stato attivo (enabled = 1) il record 
     * TOTP precedentemente inserito in modalità temporanea. L'evento viene protocollato nell'audit log.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
     * @param \stdClass $currentAdmin L'oggetto identità utilizzato per la compilazione del log di audit
     * @return bool True se la transazione e l'attivazione hanno successo, false in caso di errore
     */
    public function activateTotpMethod(string $adminUuid, \stdClass $currentAdmin): bool
    {
        try {
        	
            $this->db->transBegin();

            /* Disattivo l'eventuale metodo email attivo */
            $sqlDisableEmail = "update admins_2fa set enabled = 0 where admin_uuid = ? and method = 'email'";
            $this->db->query($sqlDisableEmail, [$adminUuid]);

            /* Attivo definitivamente il metodo TOTP esistente */
            $sqlActivateTotp = "update admins_2fa set enabled = 1 where admin_uuid = ? and method = 'totp'";
            $this->db->query($sqlActivateTotp, [$adminUuid]);

            $this->db->transCommit();

            log_admin_activity('ACTIVATE_TOTP', 'account', sprintf(lang('backend/account.audits.activateTotp'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Errore durante l\'attivazione definitiva del TOTP: ' . $e->getMessage());
            return false;
        }
    }
}