<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AccountModel extends BackendModel
{
	/**
	 * Elenco dei campi consentiti per la persistenza dei dati durante la fase di aggiornamento del profilo corrente.
	 *
	 * @var array
	 */
	protected array $editAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'note'];

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
	            'rules' => ['required', "is_unique[admins.phone,uuid,{$adminUuid}]", 'regex_match[/^\+?[0-9]{9,15}$/]'],
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
	        $updatedAdmin = service('authorization')->refresh()->currentAdmin();

	        return [
	            'result' => true, 
	            'message' => sprintf(lang('backend/account.messages.editSuccess'), esc($updatedAdmin->firstname), esc($updatedAdmin->lastname)), 
	            'currentAdmin' => $updatedAdmin
	        ];

	    } catch(\Throwable $e) {
	        $this->db->transRollback();
	        log_message('error', lang('backend/account.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
	        return ['result' => false, 'message' => lang('backend/account.messages.editError')];
	    }
	}
}