<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AccountModel extends BackendModel
{
	/**
	 * Identificativo testuale del modulo associato al profilo del profilo corrente.
	 *
	 * @var string|null
	 */
	protected ?string $module = 'account';

	/**
	 * Elenco dei campi consentiti per la persistenza dei dati durante la fase di aggiornamento del profilo corrente.
	 *
	 * @var array
	 */
	protected array $editAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'note'];

	/**
	 * Campi consentiti per l'identificazione e la revoca forzata di un token memorizzato.
	 *
	 * @var array
	 */
	protected array $deleteTokenAllowedFields = ['id'];

	/**
     * Elenco delle proprietà anagrafiche utilizzate per la comparazione dei dati storici o per il tracciamento dei log.
     *
     * @var array
     */
    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'note'];

	protected function initModel(): void 
	{
		parent::initModel();
	}

	public function editValidationRules(string $adminUuid): array
	{
	    return [
	        'firstname' => [
	            'label' => lang('backend/account.labels.firstname'),
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
	        ],
	        'lastname' => [
	            'label' => lang('backend/account.labels.lastname'),
	            'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
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
	 * Controlla i parametri necessari alla revoca immediata di un token dal database.
	 *
	 * Richiede obbligatoriamente l'UUID dell'utente e l'indice intero sequenziale (id) del record token
	 * per l'esecuzione della cancellazione atomica.
	 *
	 * @return array Criteri per l'eliminazione mirata delle sessioni.
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
	 * Recupera l'elenco piatto dei permessi associati a un determinato gruppo.
	 *
	 * Interroga la tabella `admins_groups_permissions` per estrarre tutti i codici
	 * di permesso assegnati al gruppo specificato. Il risultato viene appiattito
	 * in un array di stringhe per facilitare la comparazione con i permessi dell'utente.
	 *
	 * @param int $groupId L'ID del gruppo amministrativo.
	 * @return array Un array piatto contenente i codici dei permessi (es. ['users_index', 'users_show']).
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
	 * Recupera le eccezioni sui permessi specifiche per un determinato amministratore.
	 *
	 * Interroga la tabella `admins_permissions` per raccogliere le personalizzazioni
	 * introdotte sull'utente (permessi extra concessi o permessi del gruppo revocati).
	 * Il risultato viene strutturato come array associativo per ottimizzare le performance di lettura.
	 *
	 * @param string $uuid L'UUID dell'amministratore.
	 * @return array Array associativo dove la chiave è il codice permesso e il valore è lo stato 'allow' (0 o 1).
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
	 * Recupera lo storico e lo stato dei token di sessione, persistenza o attivazione emessi per l'utente.
	 *
	 * Esegue un'estrazione mirata sulla tabella dei token per raccogliere i dati di tracciamento ambientali
	 * quali gli indirizzi IP, gli User Agent e i relativi formati DATETIME di creazione e scadenza.
	 *
	 * @param string $uuid Identificativo univoco dell'amministratore.
	 * @return array Lista dei token associati all'anagrafica.
	 */
	public function getTokens(string $uuid): array
	{
	    /* Estrazione log dei tokens di sessione o reset */
	    $sql = "select * from admins_tokens where admin_uuid = ?";
	    return $this->db->query($sql, [$uuid])->getResult();
	}

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
	        log_admin_activity('UPDATE_DATA', 'account', sprintf('Aggiornamento dati generali %s %s', esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

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
	 * Revoca ed elimina permanentemente un singolo token identificativo (sessione o persistenza) dal database.
	 *
	 * Filtra i dati in ingresso tramite whitelisting ed esegue la verifica preventiva sull'esistenza dell'account.
	 * Interroga la tabella dei token per cancellare il record corrispondente all'UUID dell'amministratore e all'ID 
	 * incrementale fornito. Valida l'esito dell'operazione basandosi sul conteggio delle righe effettivamente coinvolte 
	 * dalla query (`affectedRows`), confermando l'avvenuta disconnessione forzata del dispositivo associato.
	 *
	 * @param array $posts Dataset contenente l'UUID dell'amministratore e l'ID sequenziale del token da revocare.
	 * @return array Matrice di risposta contenente l'esito logico dell'epurazione e il messaggio per l'interfaccia.
	 */
	public function deleteToken(array $posts, \stdClass $currentAdmin): array
	{
	    /* Match dei posts con i campi consentiti */
	    $posts = $this->checkAllowedFields($posts, $this->deleteTokenAllowedFields);

	    try {

	        /* Query per eliminare il token */
	        $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
	        $this->db->query($sql, [$currentAdmin->uuid, $posts['id']]);

	        if($this->db->affectedRows() > 0):
	        	
	        	log_admin_activity('DELETE_TOKEN', 'account', sprintf('Eliminazione token %s %s', esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

	            return ['result' => true, 'message' => sprintf(lang('backend/account.messages.deleteTokenSuccess'), esc($currentAdmin->firstname), esc($currentAdmin->lastname))];
	        endif;

	        return ['result' => false, 'message' => lang('backend/account.messages.deleteTokenError')];

	    } catch(\Throwable $e) {

	        log_message('error', lang('backend/account.messages.deleteTokenError') . ' - ' . $e);
	        return ['result' => false, 'message' => lang('backend/account.messages.deleteTokenError')];

	    }
	}

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

	        log_admin_activity('RESET_PASSWORD', 'account', sprintf('Reset password %s %s', esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

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

	public function getExpiringDate(\stdClass $currentAdmin): string
	{
	    $expiringDate = '';

	    $sql = 'select token_expire from admins_tokens where admin_uuid = ? and token_type = ? order by token_expire desc limit 1';
	    
	    if($token = $this->db->query($sql, [$currentAdmin->uuid, 'activation'])->getRow()):

		    if (date('Y-m-d H:i:s') < $token->token_expire):
		        $expiringDate = '<span class="text-success">' . convertDate($token->token_expire) . '</span>';
		    else:
		        $expiringDate = '<span class="text-danger"><s>' . convertDate($token->token_expire) . '</s></span>';
		    endif;

		endif;

	    return $expiringDate;
	}

	/**
	 * Recupera il metodo 2FA attualmente attivo per l'amministratore corrente.
	 *
	 * @param string $adminUuid L'UUID dell'amministratore corrente.
	 * @return string Il nome del metodo attivo ('none', 'email', 'totp').
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

            log_admin_activity('ACTIVATE_' . strtoupper($method), 'account', sprintf('Impostazione metodo 2Fa %s %s', esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Errore nel salvataggio del metodo 2FA base: ' . $e->getMessage());
            return false;
        }
    }

	/**
     * Salva nel database il secret TOTP temporaneo in stato disattivato.
     *
     * @param string $adminUuid L'UUID dell'amministratore.
     * @param string $secret Il codice segreto generato per il TOTP.
     * @return bool True in caso di successo, false altrimenti.
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
     * Recupera il secret TOTP temporaneo non ancora attivato per la verifica.
     *
     * @param string $adminUuid L'UUID dell'amministratore.
     * @return string|null Il codice segreto se trovato, altrimenti null.
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
     * Attiva definitivamente il metodo TOTP e disattiva gli altri canali 2FA.
     *
     * @param string $adminUuid L'UUID dell'amministratore.
     * @return bool True in caso di successo, false altrimenti.
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

            log_admin_activity('ACTIVATE_TOTP', 'account', sprintf('Impostazione metodo 2Fa %s %s', esc($currentAdmin->firstname), esc($currentAdmin->lastname)), $currentAdmin);

            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Errore durante l\'attivazione definitiva del TOTP: ' . $e->getMessage());
            return false;
        }
    }
}