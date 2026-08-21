<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class GroupsModel extends BackendModel
{
	/**
     * Elenco dei campi anagrafici e relazionali consentiti durante la fase di inserimento di un nuovo gruppo.
     *
     * @var array
     */
    protected array $addAllowedFields = ['name', 'description', 'permissions'];

    /**
     * Elenco dei campi consentiti per la persistenza dei dati durante la fase di aggiornamento di un gruppo esistente.
     *
     * @var array
     */
    protected array $editAllowedFields = ['id', 'name', 'description', 'permissions'];

    /**
     * Campi di input autorizzati per l'identificazione e l'esecuzione della procedura di cancellazione.
     *
     * @var array
     */
    protected array $delAllowedFields = ['id'];

    /**
     * Campi di input autorizzati per il recupero di un gruppo.
     *
     * @var array
     */
    protected array $getGroupByIdAllowedFields = ['id'];

    /**
     * Campi di input autorizzati per il salvataggio delle eccezioni.
     *
     * @var array
     */
    protected array $saveExceptionsAllowedFields = ['uuid', 'permissions'];

    /**
     * Campi di input autorizzati per la ricerca di un admin.
     *
     * @var array
     */
    protected array $dropdownAdminsFields = ['query'];

    /**
     * Campi di input autorizzati per l'identificazione e l'esecuzione della procedura di cancellazione.
     *
     * @var array
     */
    protected array $getAdminByUuidFields = ['uuid'];

    /**
     * Elenco delle proprietà principali utilizzate per la comparazione dei dati storici o per il tracciamento dei log.
     *
     * @var array
     */
    protected array $toCompare = ['name', 'description'];

    /**
	 * Inizializza il modello eseguendo le configurazioni di base ereditate dalla classe madre.
	 *
	 * Sincronizza lo stato del modello impostando le dipendenze native e i driver di connessione
	 * necessari al funzionamento del modulo gruppi.
	 *
	 * @return void
	 */
	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
	 * Stabilisce i criteri di validazione per la registrazione iniziale di un nuovo gruppo.
	 *
	 * Blinda i moduli di inserimento verificando l'univocità del nome del gruppo nel database.
	 *
	 * @return array Mappa di validazione per la creazione delle entità.
	 */
	public function addValidationRules(): array
	{
        /* Recuperiamo l'array multidimensionale dalla configurazione per estrarre le chiavi valide */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        $inListString = implode(',', $validKeys);

	    return [
	        'name' => [
	            'label' => lang('backend/groups.labels.name'),
	            'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
	        ],
	        'description' => [
	            'label' => lang('backend/groups.labels.description'),
	            'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
	        ],
            /* Validazione di ogni singolo elemento contenuto nell'array delle eccezioni */
            'permissions.*' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . $inListString . ']'],
                'errors' => [
                    'in_list' => lang('backend/groups.errors.permission')
                ]
            ],
	    ];
	}

    /**
     * Valida i parametri per il recupero del gruppo.
     *
     * Assicura che l'id fornito per il recupero del gruppo sia presente.
     *
     * @return array Criteri di validazione per il recupero del gruppo.
     */
    public function getGroupByIdValidationRules(): array
    {
        return [
            'id' => [
                'label' => lang('backend/groups.labels.id'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
        ];
    }

	/**
     * Configura i vincoli di convalida per l'aggiornamento dei gruppi esistenti.
     *
     * Riceve i dati correnti per isolare le regole di univocità (is_unique) condizionate tramite l'id,
     * blinda il formato dell'identificativo e controlla la formattazione dei dati modificati.
     *
     * @param array $posts Dataset dei parametri inviati dal modulo di modifica.
     * @return array Set di regole contestuali basate sullo stato dell'entità corrente.
     */
    public function editValidationRules(array $posts): array
    {
        /* Recuperiamo l'array multidimensionale dalla configurazione per estrarre le chiavi valide */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        $inListString = implode(',', $validKeys);

        return [
            'id' => [
                'label' => lang('backend/groups.labels.id'),
                'rules' => ['is_not_unique[admins_groups.id]'],
            ],
            'name' => [
                'label' => lang('backend/groups.labels.name'),
                'rules' => ['required', "is_unique[admins_groups.name,id,{$posts['id']}]", 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'description' => [
                'label' => lang('backend/groups.labels.description'),
                'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            /* Validazione di ciascun permesso inviato nell'array del gruppo */
            'permissions.*' => [
                'label' => lang('backend/groups.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . $inListString . ']'],
                'errors' => [
                    'in_list' => lang('backend/groups.errors.permission')
                ]
            ],
        ];
    }

    /**
     * Valida i parametri per l'invocazione della procedura di cancellazione sicura.
     *
     * Assicura che l'id fornito per l'eliminazione dell'amministratore sia presente.
     *
     * @return array Criteri di validazione per la revoca e rimozione del record.
     */
    public function delValidationRules(): array
    {
        return [
            'id' => [
                'label' => lang('backend/groups.labels.id'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
        ];
    }

    /**
     * Valida i parametri per il salvataggio delle eccezioni.
     *
     * Assicura che l'id fornito per l'eliminazione dell'amministratore sia presente.
     *
     * @return array Criteri di validazione per la revoca e rimozione del record.
     */
    public function saveExceptionsValidationRules(): array
    {
        /* Recuperiamo l'array multidimensionale dalla configurazione per estrarre le chiavi valide */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        $inListString = implode(',', $validKeys);

        return [
            'uuid' => [
                'label' => lang('backend/groups.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/groups.errors.wrongUUID'), 
                    'regex_match' => lang('backend/groups.errors.wrongUUID') 
                ]
            ],
            /* Validazione di ogni singolo elemento contenuto nell'array delle eccezioni */
            'permissions.*' => [
                'label' => lang('backend/groups.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . $inListString . ']'],
                'errors' => [
                    'in_list' => lang('backend/groups.errors.permission')
                ]
            ],
        ];
    }

    public function dropdownAdminsRules()
    {
        return [
            'query' => [
                'label' => lang('backend/groups.labels.query'),
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
        ];
    }

    public function adminPermissionsValidationRules()
    {
        return [
            'uuid' => [
                'label' => lang('backend/groups.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/groups.errors.wrongUUID'), 
                    'regex_match' => lang('backend/groups.errors.wrongUUID') 
                ]
            ],
        ];
    }

    /**
     * Estrae tutti i gruppi presenti nel database per il primo livello dell'accordion.
     *
     * @return array Elenco dei gruppi trovati.
     */
    public function getGroups(): array
    {
        try 
        {
            $sql = 'select id, name from admins_groups order by created_at desc';
            $query = $this->db->query($sql);

            return $query->getResult();
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero gruppi: ' . $e);
            return [];
        }
    }

    /**
     * Estrae i codici dei permessi associati a un determinato gruppo.
     *
     * @param int $groupId ID del gruppo.
     * @return array Array piatto dei codici permesso (es. ['users_index', 'users_create']).
     */
    public function getGroup(int $groupId): array
    {
        try 
        {
            $sql = "select permission from admins_groups_permissions where group_id = ?";
            $query = $this->db->query($sql, [$groupId]);
            
            $result = $query->getResultArray();
            return array_column($result, 'permission');
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero permessi gruppo: ' . $e);
            return [];
        }
    }

    /**
     * Recupera i dettagli di un singolo gruppo tramite il suo ID.
     *
     * @param int $id ID del gruppo.
     * @return object|null I dati del gruppo o null se non trovato.
     */
    public function getGroupById(array $posts): ?object
    {
        try 
        {
            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->getGroupByIdAllowedFields);

            $sql = "select id, name, description from admins_groups where id = ? limit 1";
            $query = $this->db->query($sql, [$posts['id']]);
            
            return $query->getRow() ?: null;
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero dettagli gruppo: ' . $e);
            return null;
        }
    }

    /**
     * Registra un nuovo gruppo di amministratori e associa i relativi permessi.
     *
     * @param array $posts Dataset dei parametri validati.
     * @return array Esito dell'operazione per la risposta JSON.
     */
    public function add(array $posts): array
    {
        try 
        {
            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);

            $this->db->transBegin();

            /* Inserimento anagrafica del gruppo */
            $sqlGroup = "insert into admins_groups (name, description, created_at) values (?, ?, ?)";
            $this->db->query($sqlGroup, [$posts['name'], $posts['description'], date('Y-m-d H:i:s')]);

            $groupId = $this->db->insertID();

            /* Inserimento permessi associati tramite un'unica Bulk Insert */
            if ( ! empty($posts['permissions']) && is_array($posts['permissions'])):

                $queriesValues = [];
                $binds = [];

                foreach ($posts['permissions'] as $permission):
                    $queriesValues[] = "(?, ?)";
                    $binds[] = $groupId;
                    $binds[] = $permission;
                endforeach;

                /* Uniamo i segnaposto con la virgola: (?, ?), (?, ?), (?, ?) */
                $sqlPerm = "insert into admins_groups_permissions (group_id, permission) values " . implode(', ', $queriesValues);
                
                /* Eseguiamo una sola query passando tutti i bind accumulati */
                $this->db->query($sqlPerm, $binds);

            endif;

            /* Verifichiamo lo stato prima di consolidare i dati */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.addError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('ADD_GROUP', 'groups', sprintf('Aggiunta gruppo %s ', esc($posts['name'])), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.addSuccess')];
        } 
        catch (\Throwable $e) 
        {
            $this->db->transRollback();
            log_message('error', 'Errore inserimento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.addError')];
        }
    }

    /**
     * Aggiorna l'anagrafica di un gruppo esistente e ne risincronizza i permessi associati.
     *
     * @param array $posts Dataset dei parametri inviati dal modulo di modifica.
     * @return array Esito dell'operazione per la risposta JSON.
     */
    public function edit(array $posts): array
    {
        try 
        {
            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

            /* 1. Recupero il record originale del gruppo dal DB per il confronto */
            $originalGroup = $this->getGroupById($posts);
            if ( ! $originalGroup):
                return ['result' => false, 'message' => lang('backend/groups.messages.noGroupFound')];
            endif;

            /* 2. Controllo di sbarramento: se non è cambiato nulla, interrompo subito */
            if ( ! $this->hasGroupChanged($posts, $originalGroup)):
                return ['result' => false, 'message' => lang('backend/groups.messages.noDataChanged')];
            endif;

            $this->db->transBegin();

            /* 1. Aggiornamento anagrafica del gruppo */
            $sqlGroup = "update admins_groups set name = ?, description = ? where id = ?";
            $this->db->query($sqlGroup, [$posts['name'], $posts['description'], $posts['id']]);

            /* 2. Rimozione totale dei vecchi permessi per evitare duplicati o disallineamenti */
            $sqlDeletePerms = "delete from admins_groups_permissions where group_id = ?";
            $this->db->query($sqlDeletePerms, [$posts['id']]);

            /* 3. Inserimento nuovi permessi associati tramite un'unica Bulk Insert (se selezionati) */
            if ( ! empty($posts['permissions']) && is_array($posts['permissions'])):

                $queriesValues = [];
                $binds = [];

                foreach ($posts['permissions'] as $permission):
                    $queriesValues[] = "(?, ?)";
                    $binds[] = $posts['id'];
                    $binds[] = $permission;
                endforeach;

                /* Uniamo i segnaposto con la virgola: (?, ?), (?, ?) */
                $sqlPerm = "insert into admins_groups_permissions (group_id, permission) values " . implode(', ', $queriesValues);
                
                /* Eseguiamo una sola query passando tutti i bind accumulati */
                $this->db->query($sqlPerm, $binds);

            endif;

            /* Verifichiamo lo stato prima di consolidare i dati */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.editError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EDIT_GROUP', 'groups', sprintf('Aggiornamento gruppo %s ', esc($posts['name'])), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.editSuccess')];
        } 
        catch (\Throwable $e) 
        {
            $this->db->transRollback();
            log_message('error', 'Errore aggiornamento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.editError')];
        }
    }

    /* Elimina un gruppo */
    public function del(array $posts): array
    {
        try {

            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            $this->db->transBegin();

            /* Recupero nome del gruppo */
            $sql = 'select name from admins_groups where id = ?';
            $group = $this->db->query($sql, [$posts['id']])->getRow();

            /* Rimozione del gruppo */
            $sql = "delete from admins_groups where id = ?";
            $this->db->query($sql, [$posts['id']]);

            /* Verifichiamo lo stato prima di consolidare i dati */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.delError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('DELETE_GROUP', 'groups', sprintf('Eliminazione gruppo %s ', esc($group->name)), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.delSuccess')];

        } catch (\Throwable $e) {

            $this->db->transRollback();
            log_message('error', 'Errore aggiornamento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.delError')];

        }
    }

    private function hasGroupChanged(array $posts, object $original): bool
    {
        /* 1. Controllo i campi base della tabella admins_groups (name, description) via metodo globale */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        /* 2. Recupero i permessi attualmente salvati nel DB per questo gruppo */
        $oldPermissions = $this->getGroup((int) $original->id);

        /* 3. Preparo i nuovi permessi inviati dal form (se vuoti, array vuoto) */
        $newPermissions = $posts['permissions'] ?? [];

        /* 4. Ordino entrambi gli array per evitare falsi positivi dovuti all'ordine di selezione */
        sort($oldPermissions);
        sort($newPermissions);

        /* 5. Confronto finale: se gli array differiscono, qualcosa è cambiato */
        if ($newPermissions !== $oldPermissions):
            return true;
        endif;

        return false;
    }

    /**
     * Recupera l'elenco degli amministratori filtrati per nome/username
     * per il dropdown delle eccezioni permessi.
     */
    public function getDropdownAdmins(array $posts): array
    {
        try 
        {
            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->dropdownAdminsFields);

            /* Rimuoviamo eventuali spazi vuoti iniziali o finali e convertiamo in minuscolo */
            $cleanQuery = trim(strtolower($posts['query']));
            $bindValue = '%' . $cleanQuery . '%';

            /* Utilizziamo lower() per rendere la ricerca totalmente case-insensitive 
               e cambiamo il separatore del concat usando le funzioni standard SQL 
            */
            $sql = 'select uuid, concat(firstname, " ", lastname) as identity  
                    from admins 
                    where (lower(firstname) like ? or lower(lastname) like ?) and master <> 1';
            
            $query = $this->db->query($sql, [$bindValue, $bindValue]);

            return $query->getResultArray();
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero amministratori: ' . $e);
            return [];
        }
    }

    /**
     * Recupera i dati di base di un amministratore tramite il suo UUID.
     */
    public function getAdminByUuid(array $posts): ?array
    {
        try {

            /* Filtro campi post ammessi tramite metodo centralizzato */
            $posts = $this->checkAllowedFields($posts, $this->getAdminByUuidFields);

            $sql = 'select uuid, group_id, name 
                    from admins 
                    join admins_groups 
                    on admins_groups.id = admins.group_id 
                    where admins.uuid = ?';
                    
            return $this->db->query($sql, [$posts['uuid']])->getRowArray();

        } catch(\Throwable $e) {
            log_message('error', 'Errore recupero amministratore: ' . $e);
            return [];
        }
    }

    /**
     * Restituisce un array piatto contenente solo le stringhe dei permessi del gruppo.
     */
    public function getGroupPermissionsArray(int $groupId): array
    {
        $sql = 'select permission from admins_groups_permissions where group_id = ?';
        $res = $this->db->query($sql, [$groupId])->getResultArray();
        return array_column($res, 'permission');
    }

    /**
     * Restituisce un array associativo [permission => allow] delle eccezioni dell'admin.
     */
    public function getAdminExceptionsArray(string $adminUuid): array
    {
        $sql = 'select permission, allow from admins_permissions where admin_uuid = ?';
        $res = $this->db->query($sql, [$adminUuid])->getResultArray();
        
        $exceptions = [];
        foreach ($res as $row):
            $exceptions[$row['permission']] = (int)$row['allow'];
        endforeach;
        
        return $exceptions;
    }

    public function saveExceptions(array $posts): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->saveExceptionsAllowedFields);

            $sql = 'select group_id, firstname, lastname from admins where uuid = ?';
            $admin = $this->db->query($sql, [$posts['uuid']])->getRow();
            
            if ( ! $admin):
                return ['result' => false, 'message' => lang('backend/groups.messages.noAdminFound')];
            endif;

            /* 1. Recuperiamo la situazione ATTUALE sul database prima di fare modifiche */
            $groupPermissions = $this->getGroupPermissionsArray((int) $admin->group_id);
            
            /* getAdminExceptionsArray deve restituire la visualizzazione reale (es: array associativo o lista di permessi attivi) */
            $currentExceptions = $this->getAdminExceptionsArray($posts['uuid']); 
            $submittedPermissions = $posts['permissions'] ?? [];

            /* 2. Calcoliamo la matrice dei permessi attualmente attivi per l'utente sul DB */
            $currentActivePermissions = $groupPermissions;
            foreach ($currentExceptions as $perm => $allow):
                if ($allow === 1 && ! in_array($perm, $currentActivePermissions)):
                    $currentActivePermissions[] = $perm;
                elseif ($allow === 0):
                    $currentActivePermissions = array_diff($currentActivePermissions, [$perm]);
                endif;
            endforeach;
            $currentActivePermissions = array_values($currentActivePermissions);

            /* 3. CONTROLLO DI SBARRAMENTO
                Se i permessi inviati sono identici (sia in quantità che in contenuto) a quelli già attivi, 
                allora non è stato cambiato nulla. 
            */
            sort($submittedPermissions);
            sort($currentActivePermissions);
            
            if ($submittedPermissions === $currentActivePermissions):
                return ['result' => false, 'message' => lang('backend/groups.messages.noDataChanged')];
            endif;

            /* --- Da qui in poi eseguiamo le modifiche perché qualcosa è cambiato --- */
            $this->db->transBegin();

            /* Istanziamo AdminsModel al volo e richiamiamo il suo metodo nativo */
            model(\App\Models\Backend\AdminsModel::class)->deletePermissions($posts['uuid']);

            $extraPermissions = array_diff($submittedPermissions, $groupPermissions);
            $revokedPermissions = array_diff($groupPermissions, $submittedPermissions);

            /* Scrittura delle eccezioni positive (allow = 1) */
            $bulkData = [];
            $valuesSql = [];

            /* Raccogliamo le eccezioni positive */
            foreach ($extraPermissions as $perm):
                $valuesSql[] = "(?, ?, 1)";
                array_push($bulkData, $perm, $posts['uuid']);
            endforeach;

            /* Raccogliamo le eccezioni negative */
            foreach ($revokedPermissions as $perm):
                $valuesSql[] = "(?, ?, 0)";
                array_push($bulkData, $perm, $posts['uuid']);
            endforeach;

            /* Se ci sono dati da scrivere, eseguiamo un'unica query massiva */
            if ( ! empty($valuesSql)):
                $sqlInsert = "insert into admins_permissions (permission, admin_uuid, allow) values " . implode(', ', $valuesSql);
                $this->db->query($sqlInsert, $bulkData);
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.saveExceptionsError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('SAVE_EXCEPTIONS', 'groups', sprintf('Inserimento eccezione %s %s ', esc($admin->firstname), esc($admin->lastname)), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.saveExceptionsSuccess')];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', $e);
            return ['result' => false, 'message' => lang('backend/groups.messages.saveExceptionsError')];
        }
    }
}