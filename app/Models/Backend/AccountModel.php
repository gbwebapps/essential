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
	 * Contenitore per i parametri di configurazione globali del modulo Auth 
	 * (es. limiti di tentativi, durata sessioni, espressioni regolari per le password).
	 * 
	 * @var object 
	 */
	private object $config;
	
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

    protected function initModel(): void 
    {
    	parent::initModel();

    	$this->config = setting('Backend\Auth');
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
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
	        ],
	        'lastname' => [
	            'label' => lang('backend/account.labels.lastname'),
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
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
	                'safeText' => lang('backend/account.errors.safeText')
	            ]
	        ],
	    ];
	}

	/**
	 * Genera le regole di validazione per la conferma della password corrente.
	 *
	 * @return array Regole di validazione per il campo currentPassword
	 */
	public function currentPasswordValidationRules(): array
	{
		return [
			'currentPassword' => [
				'label' => lang('backend/account.labels.currentPassword'),
				'rules' => ['required', "regex_match[{$this->config->passwordRegex}]"],
				'errors' => [
					'required' => lang('backend/account.errors.currentPasswordRequired'),
				],
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
	 * @return array Array sequenziale contenente i nomi dei permessi (es. ['brands_show', 'view_logs'])
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
	 * Recupera l'intero registro dei token di accesso (sessioni web, cookie remember-me e attivazione password) associati all'utente.
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
		$transactionStarted = false;

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

	        if ( ! $this->db->transBegin()):
	            throw new \RuntimeException('Impossibile avviare la transazione di aggiornamento account.');
	        endif;

	        $transactionStarted = true;

	        $sql = 'update admins set firstname = ?, lastname = ?, email = ?, phone = ?, note = ?, updated_at = ? where uuid = ?';
	        $this->db->query($sql, [$posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $noteValue, $updated_at, $currentAdmin->uuid]);

	        if ($this->db->transStatus() === false):
	            throw new \RuntimeException('Errore durante l\'aggiornamento dell\'account.');
	        endif;

	        if ( ! $this->db->transCommit()):
	            throw new \RuntimeException('Commit dell\'aggiornamento account non riuscito.');
	        endif;

	        $transactionStarted = false;

	        /* Ricarichiamo l'istanza dell'admin aggiornata per passarla al controller */
	        $currentAdmin = service('authorization')->refresh()->currentAdmin();
	        log_admin_activity('UPDATE_DATA', 'account', ui_message('backend/account.audits.updateData', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.audits.updateData')), $currentAdmin);

	        return [
	            'result' => true, 
	            'message' => ui_message('backend/account.messages.editSuccess', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.messages.editSuccess')), 
	            'currentAdmin' => $currentAdmin
	        ];

	    } catch(\Throwable $e) {
	        if ($transactionStarted):
	            $this->db->transRollback();
	        endif;

	        log_message('error', lang('backend/account.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
	        return ['result' => false, 'message' => lang('backend/account.messages.editError')];
	    }
	}

	/**
	 * Verifica che la password fornita corrisponda a quella dell'amministratore autenticato.
	 *
	 * @param string $adminUuid Identificatore univoco dell'amministratore
	 * @param string $currentPassword Password corrente in chiaro ricevuta dal form
	 * @return bool True se la password è corretta, false altrimenti
	 */
	public function verifyCurrentPassword(string $adminUuid, string $currentPassword): bool
	{
		$sql = "select password_hash from admins where uuid = ? and status = 1 and suspended_at is null and deleted_at is null limit 1";
		$row = $this->db->query($sql, [$adminUuid])->getRow();

		return $row !== null
			&& ! empty($row->password_hash)
			&& password_verify($currentPassword, (string) $row->password_hash);
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
            $tokenHash = $token->getHash(config(\Config\Backend\Auth::class)->hashKey);

            $sql = "select id from admins_tokens where token_hash = ? and token_type = 'session'";
            $row = $this->db->query($sql, [$tokenHash])->getRow();

            return $row ? (int) $row->id : null;
            
        endif;

        return null;
    }

    public function getGroupName(int $id): ?object
    {
        try 
        {
            $sql = "select name from admins_groups where id = ? limit 1";
            $query = $this->db->query($sql, [$id]);
            
            return $query->getRow() ?: null;
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero nome gruppo: ' . $e);
            return null;
        }
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

        $transactionStarted = false;

        try {

			if ( ! $this->db->transBegin()):
				throw new \RuntimeException('Impossibile avviare la transazione di revoca token.');
			endif;

			$transactionStarted = true;

            /* 1. Recupero il token per leggere last_activity */
			$tokenSql = "select id, last_activity, token_type from admins_tokens where admin_uuid = ? and id = ? for update";
            $tokenRow = $this->db->query($tokenSql, [$currentAdmin->uuid, $posts['id']])->getRow();

			if ( ! $tokenRow):
				throw new \RuntimeException('Token non trovato o non appartenente all\'account corrente.');
            endif;

			if ($currentTokenId !== null && (int) $tokenRow->id === $currentTokenId):
				$this->db->transRollback();
				$transactionStarted = false;
				return ['result' => false, 'message' => lang('backend/account.messages.cannotDeleteCurrentToken')];
			endif;

			/* 2. Aggiorno il log se è una sessione o cookie */
			if (in_array($tokenRow->token_type, ['cookie', 'session'], true)):
				$logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
				$logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'deleted' where token_id = ? and logout is null";
				$this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
			endif;

            /* 3. Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$currentAdmin->uuid, $posts['id']]);

			if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false):
				throw new \RuntimeException('Revoca token non riuscita.');
            endif;

			if ( ! $this->db->transCommit()):
				throw new \RuntimeException('Commit della revoca token non riuscito.');
			endif;

			$transactionStarted = false;

			log_admin_activity('DELETE_TOKEN', 'account', ui_message('backend/account.audits.deleteToken', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.audits.deleteToken')), $currentAdmin);
			return ['result' => true, 'message' => ui_message('backend/account.messages.deleteTokenSuccess', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.messages.deleteTokenSuccess'))];

        } catch(\Throwable $e) {
			if ($transactionStarted):
				$this->db->transRollback();
			endif;

            log_message('error', lang('backend/account.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/account.messages.deleteTokenError')];

        }
    }

    /**
	 * Avvia la procedura transazionale per la richiesta di impostazione di una nuova password.
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
		$transactionStarted = false;

	    try 
	    {
	        $userAgent = $request->getUserAgent()->getAgentString();
	        $ip_address = $request->getIPAddress();

	        /* Generazione token di attivazione */
	        $token = new \App\Libraries\Token();
	        $tokenHash = $token->getHash(config(\Config\Backend\Auth::class)->hashKey);

	        /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
	        $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

	        if ( ! $this->db->transBegin()):
	            throw new \RuntimeException('Impossibile avviare la transazione di reset password.');
	        endif;

	        $transactionStarted = true;

	        /* Scrittura Data di Reset nella tabella admins */
	        $sql = "update admins set resetted_at = ? where uuid = ?";
	        $this->db->query($sql, [date('Y-m-d H:i:s'), $currentAdmin->uuid]);

	        /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
	        $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
	        $this->db->query($sql, [$currentAdmin->uuid, 'activation']);

	        /* Scrittura del token di attivazione */
			$sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, last_activity, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$this->db->query($sql, [$currentAdmin->uuid, $tokenHash, date('Y-m-d H:i:s'), null, $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d H:i:s')]);

	        if ($this->db->transStatus() === false):

	            throw new \RuntimeException('Errore durante la registrazione del reset password.');
	        endif;

	        if ( ! $this->db->transCommit()):
	            throw new \RuntimeException('Commit del reset password non riuscito.');
	        endif;

	        $transactionStarted = false;

	        log_admin_activity('RESET_PASSWORD', 'account', ui_message('backend/account.audits.resetPassword', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.audits.resetPassword')), $currentAdmin);

	    } catch (\Throwable $e) {

	        if ($transactionStarted):
	            $this->db->transRollback();
	        endif;

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

	        $message = ui_message('backend/account.messages.resetPasswordSuccessNoEmail', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.messages.resetPasswordSuccessNoEmail'));
	        return ['result' => 'db_committed_no_email', 'message' => $message];
	        
	    else:
	        
	        $message = ui_message('backend/account.messages.resetPasswordSuccess', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.messages.resetPasswordSuccess'));
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
		        $expiringDate = '<span class="app-text-success">' . convertDate($token->token_expire, 'conversational') . '</span>';
		    else:
		        $expiringDate = '<span class="app-text-danger"><s>' . convertDate($token->token_expire, 'conversational') . '</s></span>';
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
	public function setBasicMethod(\stdClass $currentAdmin, string $method): bool
    {
		$transactionStarted = false;

        try {
			if ( ! $this->db->transBegin()):
				throw new \RuntimeException('Impossibile avviare la transazione 2FA.');
			endif;

			$transactionStarted = true;

            /* Disattivo tutti i metodi esistenti per l'utente */
            $sqlDisable = "update admins_2fa set enabled = 0 where admin_uuid = ?";
            $this->db->query($sqlDisable, [$currentAdmin->uuid]);

            /* Se il metodo è email, eseguo l'upsert per attivarlo */
            if ($method === 'email') :
                $sqlUpsert = "insert into admins_2fa (admin_uuid, method, enabled) values (?, 'email', 1) on duplicate key update enabled = 1";
                $this->db->query($sqlUpsert, [$currentAdmin->uuid]);
            endif;

			if ($this->db->transStatus() === false):
				throw new \RuntimeException('Aggiornamento del metodo 2FA non riuscito.');
			endif;

			if ( ! $this->db->transCommit()):
				throw new \RuntimeException('Commit del metodo 2FA non riuscito.');
			endif;

			$transactionStarted = false;
			$this->clearTemporarySecret();

            log_admin_activity('ACTIVATE_' . strtoupper($method), 'account', ui_message('backend/account.audits.activateMethod', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.audits.activateMethod')), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
			if ($transactionStarted):
				$this->db->transRollback();
			endif;

            log_message('error', 'Errore nel salvataggio del metodo 2FA base: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Salva in sessione, cifrato e con scadenza, il segreto TOTP ancora da confermare.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
     * @param string $secret La chiave segreta alfanumerica di base32
     * @return bool True in caso di salvataggio riuscito, false in caso di errore
     */
    public function saveTemporarySecret(string $adminUuid, string $secret): bool
    {
        try {

			$protectedSecret = (new \App\Libraries\AppOtpService())->protectSecret($secret);

			session()->set('account_totp_setup', [
				'admin_uuid' => $adminUuid,
				'secret' => $protectedSecret,
				'created_at' => time(),
			]);

            return true;

        } catch (\Throwable $e) {
            log_message('error', 'Errore nel salvataggio del secret TOTP temporaneo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recupera e decifra il segreto TOTP temporaneo dalla sessione corrente.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
     * @return string|null La chiave segreta se presente, null in caso contrario
     */
    public function getTemporarySecret(string $adminUuid): ?string
    {
		$pending = session()->get('account_totp_setup');
		$settings = setting('Backend\Auth');
		$expiresIn = (int) ($settings->twoFactorTime ?? config(\Config\Backend\Auth::class)->twoFactorTime);

		if ( ! is_array($pending)
			|| ! isset($pending['admin_uuid'], $pending['secret'], $pending['created_at'])
			|| ! hash_equals($adminUuid, (string) $pending['admin_uuid'])
			|| (time() - (int) $pending['created_at']) > $expiresIn):
			$this->clearTemporarySecret();
			return null;
		endif;

		$secret = (new \App\Libraries\AppOtpService())->revealSecret((string) $pending['secret']);

		if ($secret === null):
			$this->clearTemporarySecret();
		endif;

		return $secret;
    }

	/**
	 * Elimina dalla sessione l'eventuale configurazione TOTP ancora in sospeso.
	 *
	 * @return void
	 */
	public function clearTemporarySecret(): void
	{
		session()->remove('account_totp_setup');
	}

    /**
     * Convalida e attiva definitivamente il metodo TOTP (Time-based One-Time Password) per l'utente.
     * 
     * Questa transazione disattiva gli altri metodi e salva il nuovo segreto TOTP cifrato
     * soltanto dopo che il primo codice è stato validato.
     *
     * @param string $adminUuid L'identificatore univoco dell'amministratore
	 * @param string $secret La chiave TOTP in chiaro già validata
     * @param \stdClass $currentAdmin L'oggetto identità utilizzato per la compilazione del log di audit
     * @return bool True se la transazione e l'attivazione hanno successo, false in caso di errore
     */
	public function activateTotpMethod(string $adminUuid, string $secret, \stdClass $currentAdmin): bool
    {
		$transactionStarted = false;

        try {
			if ( ! $this->db->transBegin()):
				throw new \RuntimeException('Impossibile avviare la transazione TOTP.');
			endif;

			$transactionStarted = true;

			/* Disattivo tutti i metodi prima di attivare esclusivamente TOTP */
			$sqlDisable = "update admins_2fa set enabled = 0 where admin_uuid = ?";
			$this->db->query($sqlDisable, [$adminUuid]);

			$protectedSecret = (new \App\Libraries\AppOtpService())->protectSecret($secret);
			$sqlActivateTotp = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'totp', ?, 1) on duplicate key update secret = values(secret), enabled = 1";
			$this->db->query($sqlActivateTotp, [$adminUuid, $protectedSecret]);

			if ($this->db->transStatus() === false):
				throw new \RuntimeException('Attivazione TOTP non riuscita.');
			endif;

			if ( ! $this->db->transCommit()):
				throw new \RuntimeException('Commit dell\'attivazione TOTP non riuscito.');
			endif;

			$transactionStarted = false;
			$this->clearTemporarySecret();

            log_admin_activity('ACTIVATE_TOTP', 'account', ui_message('backend/account.audits.activateTotp', [esc($currentAdmin->firstname), esc($currentAdmin->lastname)], lang('backend/account.audits.activateTotp')), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
			if ($transactionStarted):
				$this->db->transRollback();
			endif;

            log_message('error', 'Errore durante l\'attivazione definitiva del TOTP: ' . $e->getMessage());
            return false;
        }
    }
}
