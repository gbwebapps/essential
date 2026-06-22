<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class GroupsModel extends BackendModel
{
	/**
	 * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
	 *
	 * @var string|null
	 */
	protected ?string $module = 'groups';

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
                    'in_list' => lang('Backend/admins.errors.permission')
                ]
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
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
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
     * Estrae tutti i gruppi presenti nel database per il primo livello dell'accordion.
     *
     * @return array Elenco dei gruppi trovati.
     */
    public function getGroups(): array
    {
        try 
        {
            $sql = 'select id, name, description from admins_groups order by created_at desc';
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
    public function getGroupById(int $id): ?object
    {
        try 
        {
            $sql = "select id, name, description from admins_groups where id = ? limit 1";
            $query = $this->db->query($sql, [$id]);
            
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

            /* Inserimento permessi associati (se selezionati) */
            if ( ! empty($posts['permissions']) && is_array($posts['permissions'])):

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

            endif;

            /* Verifichiamo lo stato prima di consolidare i dati */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.addError')];
            endif;

            $this->db->transCommit();

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

            /* Rimozione totale dei permessi */
            $sql = "delete from admins_groups_permissions where group_id = ?";
            $this->db->query($sql, [$posts['id']]);

            /* Rimozione del gruppo */
            $sql = "delete from admins_groups where id = ?";
            $this->db->query($sql, [$posts['id']]);

            /* Verifichiamo lo stato prima di consolidare i dati */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/groups.messages.delError')];
            endif;

            $this->db->transCommit();

            return ['result' => true, 'message' => lang('backend/groups.messages.delSuccess')];

        } catch (\Throwable $e) {

            $this->db->transRollback();
            log_message('error', 'Errore aggiornamento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.delError')];

        }
    }
}