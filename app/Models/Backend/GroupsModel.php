<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello principale per la gestione dei Gruppi di Amministrazione e delle policy di Permesso.
 * 
 * Estende il BackendModel. Si occupa di incapsulare tutta la business logic legata alla creazione, 
 * aggiornamento ed eliminazione dei ruoli di sistema (es. Superadmin, Editor, ecc.), 
 * oltre a gestire in modo avanzato l'assegnazione massiva (Bulk Insert) dei permessi e 
 * la risoluzione delle eccezioni (override) assegnate ai singoli utenti.
 */
class GroupsModel extends BackendModel
{
    /**
     * Whitelist dei campi consentiti durante la creazione di un nuovo gruppo di ruoli.
     * Include i dati anagrafici del gruppo (nome, descrizione) e la matrice dei permessi predefiniti associati.
     *
     * @var array 
     */
    protected array $addAllowedFields = ['name', 'description', 'permissions'];

    /**
     * Whitelist dei campi consentiti durante la modifica di un gruppo esistente.
     * Estende i campi di creazione richiedendo l'identificativo numerico (ID) per puntare al record corretto.
     *
     * @var array 
     */
    protected array $editAllowedFields = ['id', 'name', 'description', 'permissions'];

    /**
     * Whitelist dei campi consentiti per l'eliminazione fisica o logica di un gruppo.
     * Restringe il payload HTTP al solo ID numerico, prevenendo cancellazioni di massa o accidentali.
     *
     * @var array 
     */
    protected array $delAllowedFields = ['id'];

    /**
     * Whitelist dei campi consentiti per la richiesta di estrazione dei dettagli di un gruppo.
     * Limita la ricezione dei parametri al solo ID, isolando le query di lettura (SELECT) da injection esterne.
     *
     * @var array 
     */
    protected array $getGroupByIdAllowedFields = ['id'];

    /**
     * Whitelist dei campi consentiti per l'assegnazione delle eccezioni sui permessi (ad personam).
     * Gestisce le chiamate che sovrascrivono i permessi ereditati dal gruppo per un singolo amministratore (tramite UUID).
     *
     * @var array 
     */
    protected array $saveExceptionsAllowedFields = ['uuid', 'permissions'];

    /**
     * Whitelist dei campi consentiti per le interrogazioni asincrone delle interfacce a comparsa (es. Dropdown o Select2).
     * Accetta unicamente la stringa di ricerca ('query') digitata dall'operatore per filtrare gli amministratori.
     *
     * @var array 
     */
    protected array $dropdownAdminsFields = ['query'];

    /**
     * Whitelist dei campi consentiti per l'estrazione in lettura dei dati di un singolo amministratore.
     * Richiede esclusivamente l'UUID, agendo come controllo preliminare prima di instradare la query.
     *
     * @var array 
     */
    protected array $getAdminByUuidFields = ['uuid'];

    /**
     * Elenco dei campi strutturali utilizzati dal motore del modello per il calcolo differenziale (Diffing).
     * Permette al sistema di capire rapidamente se le anagrafiche di base (nome e descrizione) sono state modificate, 
     * ignorando intenzionalmente proprietà relazionali complesse come le matrici dei permessi.
     *
     * @var array 
     */
    protected array $toCompare = ['name', 'description'];

    /**
     * Hook di inizializzazione nativo di CodeIgniter 4.
     * 
     * Richiama l'inizializzazione del BackendModel genitore, assicurando il corretto 
     * setup di connessioni al database, classi helper e proprietà ereditate 
     * prima di qualsiasi operazione sul modello.
     */
	protected function initModel(): void 
	{
		parent::initModel();
	}

    /**
     * Genera il set di regole di validazione per la creazione di un nuovo Gruppo.
     * 
     * Legge dinamicamente il file di configurazione centrale dei permessi (Permissions config) 
     * ed estrae tutte le chiavi valide in modo da costruire una regola `in_list` stringente. 
     * Questo garantisce che nessun permesso inventato o malevolo possa essere salvato a database.
     *
     * @return array Array strutturato con le regole native di CodeIgniter
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
	            'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
	        ],
	        'description' => [
	            'label' => lang('backend/groups.labels.description'),
	            'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
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
     * Regole di validazione per l'estrazione di un singolo Gruppo tramite ID.
     * 
     * Assicura che l'ID fornito sia un numero intero naturale e, soprattutto, 
     * controlla (tramite `is_not_unique`) che esista effettivamente nella tabella admins_groups, 
     * bloccando richieste per record inesistenti.
     *
     * @return array Regole per la validazione dell'ID
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
     * Regole di validazione per la modifica di un Gruppo esistente.
     * 
     * Simile a addValidationRules(), ma aggiunge la protezione sull'ID da modificare e 
     * adatta la regola `is_unique` sul nome del gruppo in modo da escludere l'ID corrente, 
     * permettendo di salvare il modulo senza che il sistema segnali falsi conflitti sul nome stesso.
     *
     * @param array $posts I dati in ingresso (necessari per estrarre l'ID corrente)
     * @return array Array strutturato con le regole
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
                'rules' => ['required', "is_unique[admins_groups.name,id,{$posts['id']}]", 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
            'description' => [
                'label' => lang('backend/groups.labels.description'),
                'rules' => ['required', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
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
     * Regole di validazione per l'eliminazione fisica di un Gruppo.
     * 
     * Applica i filtri sull'ID assicurandosi che il gruppo esista a database prima 
     * di tentare la query di DELETE.
     *
     * @return array Regole per la validazione dell'ID
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
     * Regole di validazione per il salvataggio delle eccezioni utente.
     * 
     * Verifica che l'UUID dell'amministratore sia in un formato valido (tramite Regex) 
     * e compila dinamicamente la whitelist dei permessi concessi, analogamente a quanto 
     * fatto per l'inserimento dei gruppi.
     *
     * @return array Regole di validazione strutturate
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

    /**
     * Regole di validazione per la ricerca degli amministratori tramite dropdown (AJAX).
     * 
     * Filtra la stringa immessa dall'utente (query) consentendo lettere, spazi 
     * e caratteri accentati (utili per i cognomi), scartando input complessi o pericolosi.
     *
     * @return array Regole per la chiave 'query'
     */
    public function dropdownAdminsRules()
    {
        return [
            'query' => [
                'label' => lang('backend/groups.labels.query'),
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
        ];
    }

    /**
     * Regola base per validare rapidamente l'UUID di un amministratore.
     * 
     * Verifica esclusivamente che la stringa fornita rispetti l'esatto formato di un UUID versione 4, 
     * rispondendo con messaggi di errore localizzati.
     *
     * @return array Regole di validazione
     */
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
     * Estrae l'elenco completo dei Gruppi disponibili a database.
     * 
     * Query base utile per popolare selettori, filtri o liste. Restituisce semplicemente 
     * ID e nome del gruppo ordinati per data di creazione.
     *
     * @return array Array di oggetti (risultati), array vuoto in caso di errore
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
     * Estrae l'elenco dei permessi (stringhe) attualmente associati a uno specifico gruppo.
     * 
     * Appiattisce i risultati della query su una singola dimensione estraendo solo il valore 
     * della colonna 'permission', semplificando i successivi confronti logici.
     *
     * @param int $groupId L'identificativo del gruppo
     * @return array Array monodimensionale di stringhe (es. ['manage_users', 'view_logs'])
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
     * Recupera i dettagli anagrafici (ID, nome, descrizione) di un gruppo specifico.
     * 
     * Applica la whitelist al parametro ID in ingresso per prevenire injection e 
     * restituisce un singolo oggetto corrispondente al record.
     *
     * @param array $posts I dati POST filtrati (deve contenere 'id')
     * @return object|null Oggetto con i dati del gruppo, o null se non trovato
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
     * Crea un nuovo Gruppo e gli associa i permessi selezionati.
     * 
     * Esegue l'operazione all'interno di una Transazione. Scrive prima i dati base 
     * nella tabella `admins_groups`, recupera l'ID appena generato e lo utilizza per 
     * collegare tutti i permessi richiesti eseguendo una singola e performante "Bulk Insert". 
     * In caso di fallimento effettua il rollback automatico e registra tutto nei log di sistema.
     *
     * @param array $posts Dataset sanificato e validato contenente nome, descrizione e permessi
     * @return array Risposta strutturata (result, message) per il controller
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
            log_admin_activity('ADD_GROUP', 'groups', sprintf(lang('backend/groups.audits.addGroup'), esc($posts['name'])), $currentAdmin);

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
     * Aggiorna i dati anagrafici e i permessi di un Gruppo esistente.
     * 
     * Implementa un controllo "Sbarramento": se non ci sono differenze reali tra 
     * i dati inviati e quelli a database, blocca subito l'esecuzione ottimizzando il carico.
     * Se ci sono differenze, apre una transazione, aggiorna nome e descrizione, 
     * svuota (DELETE) fisicamente i vecchi permessi e inserisce i nuovi (Bulk Insert), 
     * prevenendo accavallamenti o doppioni.
     *
     * @param array $posts Dataset sanificato con ID, nome, descrizione e nuovi permessi
     * @return array Risposta strutturata per l'utente
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
            log_admin_activity('EDIT_GROUP', 'groups', sprintf(lang('backend/groups.audits.editGroup'), esc($posts['name'])), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.editSuccess')];
        } 
        catch (\Throwable $e) 
        {
            $this->db->transRollback();
            log_message('error', 'Errore aggiornamento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.editError')];
        }
    }

    /**
     * Rimuove definitivamente un Gruppo dal sistema.
     * 
     * In transazione, estrae prima il nome del gruppo per poterlo scrivere chiaramente 
     * nell'Audit Log e procede poi all'eliminazione (DELETE) fisica dalla tabella.
     * 
     * @param array $posts I dati POST filtrati (deve contenere 'id')
     * @return array Risultato dell'operazione e messaggio
     */
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
            log_admin_activity('DELETE_GROUP', 'groups', sprintf(lang('backend/groups.audits.deleteGroup'), esc($group->name)), $currentAdmin);

            return ['result' => true, 'message' => lang('backend/groups.messages.delSuccess')];

        } catch (\Throwable $e) {

            $this->db->transRollback();
            log_message('error', 'Errore aggiornamento gruppo: ' . $e);
            
            return ['result' => false, 'message' => lang('backend/groups.messages.delError')];

        }
    }

    /**
     * Controlla in modo intelligente se i dati (o i permessi) di un Gruppo sono stati alterati.
     * 
     * Prima sfrutta il metodo globale per le stringhe (hasDataChanged), dopodiché scarica 
     * i permessi attuali, li ordina in modo speculare rispetto a quelli ricevuti dal form, 
     * ed esegue un confronto assoluto. Se gli array non combaciano, rileva la modifica.
     *
     * @param array $posts Dataset aggiornato proveniente dal form
     * @param object $original Oggetto contenente lo stato originale del record a database
     * @return bool True se sono stati rilevati cambiamenti, false altrimenti
     */
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
     * Popola il menu a tendina asincrono per la ricerca degli amministratori.
     * 
     * Cerca la stringa immessa dall'utente sia sul nome che sul cognome ignorando 
     * la formattazione (trasforma tutto in minuscolo con `lower` ed elimina gli spazi). 
     * Restituisce una lista (UUID e nome completo concatenato) facile da leggere lato client.
     *
     * @param array $posts Dati POST contenenti la chiave 'query'
     * @return array Array associativo formattato per il frontend
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
                    where (lower(firstname) like ? or lower(lastname) like ?)';
            
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
     * Recupera le informazioni base di un Amministratore (e il suo Gruppo) partendo dall'UUID.
     * 
     * Sfrutta una JOIN per ricavare contemporaneamente il nome del gruppo di appartenenza. 
     * Molto utile per popolare viste o elaborare logiche in contesti legati alla profilazione.
     *
     * @param array $posts Dati POST filtrati (deve contenere 'uuid')
     * @return array|null Array associativo con i dati estratti o null/vuoto se non trovato
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
     * Metodo alias/helper per estrarre la matrice permessi di un gruppo come Array.
     * 
     * Fa esattamente il lavoro di getGroup() ma con un nome più esplicito, restituendo 
     * una lista monodimensionale pulita delle chiavi di permesso attive per l'ID richiesto.
     *
     * @param int $groupId L'ID del gruppo in esame
     * @return array Elenco dei permessi attivi
     */
    public function getGroupPermissionsArray(int $groupId): array
    {
        $sql = 'select permission from admins_groups_permissions where group_id = ?';
        $res = $this->db->query($sql, [$groupId])->getResultArray();
        return array_column($res, 'permission');
    }

    /**
     * Estrae le Eccezioni di permesso (Positive o Negative) assegnate a un utente.
     * 
     * Ricava i record dalla tabella `admins_permissions` (quindi staccati dal gruppo) e li converte 
     * in un array associativo dove la chiave è il permesso e il valore è 1 (concesso) o 0 (revocato).
     *
     * @param string $adminUuid L'identificatore utente
     * @return array Dizionario [permesso => 0/1]
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

    /**
     * Ricalcola e salva le eccezioni di permesso esclusive applicate al singolo Utente (Override).
     * 
     * Metodo denso e fondamentale. Include "Scudi Enterprise": blocca l'operazione se l'utente è un 
     * superadmin (ha già tutti i diritti per definizione) o se è stato cestinato. 
     * Ricalcola matematicamente quali permessi l'utente possiede tramite il gruppo e li confronta 
     * con quelli sottomessi dal modulo. Crea due liste (i permessi da concedere in extra e quelli 
     * ereditati da revocare), pulisce la tabella dalle vecchie eccezioni chiamando l'AdminsModel 
     * e salva le nuove differenze con un rapido Bulk Insert.
     *
     * @param array $posts Dati sanificati con 'uuid' e la nuova matrice 'permissions'
     * @return array Esito, messaggio ed eventuali errori bloccanti
     */
    public function saveExceptions(array $posts): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->saveExceptionsAllowedFields);

            $sql = 'select group_id, firstname, lastname, superadmin, deleted_at from admins where uuid = ?';
            $admin = $this->db->query($sql, [$posts['uuid']])->getRow();
            
            if ( ! $admin):
                return ['result' => false, 'message' => lang('backend/groups.messages.noAdminFound')];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($admin->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/groups.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $admin->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/groups.messages.protectedAdmin')];
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
            log_admin_activity('SAVE_EXCEPTIONS', 'groups', sprintf(lang('backend/groups.audits.saveExceptions'), esc($admin->firstname), esc($admin->lastname)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/groups.messages.saveExceptionsSuccess'), esc($admin->firstname), esc($admin->lastname))];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Errore salvataggio eccezione: ' . $e);
            return ['result' => false, 'message' => lang('backend/groups.messages.saveExceptionsError')];
        }
    }

    /**
     * Verifica se un Gruppo ha attualmente degli amministratori associati.
     * 
     * Metodo di utilità per impedire eliminazioni accidentali di Gruppi ancora in uso. 
     * Conta semplicemente gli UUID agganciati all'ID del gruppo interrogato.
     *
     * @param int $groupId L'ID del gruppo da verificare
     * @return bool True se ci sono utenti agganciati, false se è vuoto
     */
    public function hasAdminsAttached(int $groupId): bool
    {
        $sql = 'select count(uuid) as total from admins where group_id = ?';
        $result = $this->db->query($sql, [$groupId])->getRow();

        return (int) $result->total > 0;
    }
}