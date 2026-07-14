<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello di gestione e persistenza delle anagrafiche e dei privilegi degli amministratori.
 *
 * Questa classe estende le funzionalità base del backend model per governare il ciclo di vita completo
 * (CRUD) degli utenti amministrativi. Gestisce la mappatura dei campi autorizzati per ogni singola
 * operazione, isola i criteri di ricerca e ordinamento per le visualizzazioni tabellari, orchestra
 * le sotto-query per il recupero degli asset polimorfi (immagini e documenti) e centralizza le query
 * native per l'estrazione dei record al netto delle eccezioni di sicurezza.
 */
class AdminsModel extends BackendModel
{
    /**
     * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
     *
     * @var string|null
     */
    protected ?string $module = 'admins';

    protected ?string $entity = 'admins';

    /**
     * Colonna di ordinamento predefinita utilizzata nelle query di estrazione se non specificata.
     *
     * @var string|null
     */
    protected ?string $defaultColumn = 'created_at';

    /**
     * Elenco dei parametri di input autorizzati per il filtraggio e l'impaginazione della vista tabellare globale.
     *
     * @var array
     */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    /**
     * Elenco dei campi anagrafici e relazionali consentiti durante la fase di inserimento di un nuovo amministratore.
     *
     * @var array
     */
    protected array $addAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'images'];

    /**
     * Elenco dei campi consentiti per la persistenza dei dati durante la fase di aggiornamento di un profilo esistente.
     *
     * @var array
     */
    protected array $editAllowedFields = ['uuid', 'firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'permissions', 'images'];

    /**
     * Campi di input autorizzati per l'identificazione e l'esecuzione della procedura di cancellazione.
     *
     * @var array
     */
    protected array $delAllowedFields = ['uuid'];

    /**
     * Campi consentiti per l'invocazione del flusso di ripristino e generazione del token di reset password.
     *
     * @var array
     */
    protected array $resetPasswordAllowedFields = ['uuid'];

    /**
     * Campi autorizzati per la ricezione dell'istruzione di commutazione dello stato attivo o inattivo.
     *
     * @var array
     */
    protected array $changeStatusAllowedFields = ['uuid'];

    /**
     * Campi di input consentiti per la modifica rapida e isolata di un singolo privilegio utente.
     *
     * @var array
     */
    protected array $changePermissionAllowedFields = ['uuid', 'permission'];

    /**
     * Campi consentiti per l'identificazione e la revoca forzata di un token memorizzato.
     *
     * @var array
     */
    protected array $deleteTokenAllowedFields = ['id', 'uuid'];

    /**
     * Corrispondenza rigida tra gli indici dell'interfaccia utente e le colonne reali della tabella per l'ordinamento.
     *
     * @var array
     */
    protected array $allowedOrderColumns = ['firstname', 'lastname', 'email', 'phone', 'status']; 

    /**
     * Elenco dei campi su cui è consentita l'applicazione dei filtri di ricerca testuale nella vista globale.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['firstname', 'lastname', 'email', 'phone']; 

    /**
     * Elenco delle proprietà anagrafiche utilizzate per la comparazione dei dati storici o per il tracciamento dei log.
     *
     * @var array
     */
    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'status', 'group_id', 'note'];

    /**
     * Stringa SQL per l'estrazione massiva degli amministratori con inclusione dei conteggi per immagini e documenti.
     *
     * @var string|null
     */
    protected ?string $getDataQuery = "select uuid, firstname, lastname, email, phone, status, created_at, updated_at, resetted_at, suspended_at,
                                        (select images.filename from images where images.entity_uuid = admins.uuid and images.entity = 'admins' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = admins.uuid and images.entity = 'admins') as images_num 
                                        from admins where master <> ? and uuid <> ?";

    /**
     * Stringa SQL per il recupero puntuale dei dettagli anagrafici e di stato di un singolo amministratore tramite UUID.
     *
     * @var string|null
     */
    protected ?string $getUUIDQuery = "select 
                                            admins_groups.name as groupName, 
                                            uuid, 
                                            firstname, 
                                            lastname, 
                                            email, 
                                            phone, 
                                            status, 
                                            master, 
                                            group_id, 
                                            note, 
                                            admins.created_at, 
                                            admins.updated_at, 
                                            suspended_at, 
                                            resetted_at 
                                        from admins 
                                        join admins_groups 
                                        on admins.group_id = admins_groups.id 
                                        where admins.uuid = ? limit 1";

    /**
     * Stringa SQL ottimizzata per il conteggio totale dei record presenti, utile al calcolo dell'impaginazione.
     *
     * @var string|null
     */
    protected ?string $getNumRowsQuery = 'select count(*) as num from admins where master <> ? and uuid <> ?';

    /**
     * Inizializza il modello eseguendo le configurazioni di base ereditate dalla classe madre.
     *
     * Sincronizza lo stato del modello impostando le dipendenze native e i driver di connessione
     * necessari al funzionamento del modulo amministratori.
     *
     * @return void
     */
    protected function initModel(): void 
    {
        parent::initModel();
    }

    /**
     * Definisce i vincoli di ordinamento e paginazione per la griglia tabellare.
     *
     * Restituisce le regole di validazione necessarie a blindare i parametri della richiesta DataTables,
     * verificando l'integrità della colonna bersaglio, il verso di ordinamento e la naturalità degli indici di pagina.
     *
     * @return array Mappa dei criteri di validazione per i flussi di paginazione.
     */
    public function showAllValidationRules(): array
    {
        return [
            'column' => [
                'rules' => ['required', 'alpha_dash'] 
            ],
            'order' => [
                'rules' => ['required', 'in_list[asc,desc]'] 
            ],
            'page' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
            'rows' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
        ];
    }

    /**
     * Valida i criteri di ricerca applicati ai singoli campi della visualizzazione massiva.
     *
     * Applica espressioni regolari specifiche e mappate per intercettare pattern testuali non conformi
     * su nomi, cognomi, stringhe email e formati telefonici inviati tramite array nidificato.
     *
     * @return array Regole di Whitelisting per la sanitizzazione dei filtri di ricerca.
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.firstname' => [
                'label' => lang('backend/admins.labels.firstname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.lastname' => [
                'label' => lang('backend/admins.labels.lastname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.email' => [
                'label' => lang('backend/admins.labels.email'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'], 
            ],
            'searchFields.phone' => [
                'label' => lang('backend/admins.labels.phone'), 
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'], 
            ],
        ];
    }

    /**
     * Stabilisce i criteri di validazione per la registrazione iniziale di un nuovo amministratore.
     *
     * Blinda i moduli di inserimento verificando l'univocità di email e telefono sul database,
     * la congruenza del set di permessi e l'assenza di payload nocivi nelle note tramite la regola safeText.
     *
     * @return array Mappa di validazione per la creazione delle entità.
     */
    public function addValidationRules(): array
    {
        return [
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', 'is_unique[admins.email]'],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'is_unique[admins.phone]', 'regex_match[/^\+?[0-9]{9,15}$/]'],
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'Caratteri non ammessi.'
                ]
            ],
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'images' => [
                'label' => lang('backend/admins.labels.images'),
                'rules' => ['permit_empty', 'checkImages']
            ]
        ];
    }

    /**
     * Configura i vincoli di convalida per l'aggiornamento dei profili esistenti.
     *
     * Riceve i dati correnti per isolare le regole di univocità (is_unique) condizionate tramite l'UUID,
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
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', "is_unique[admins.uuid,uuid,{$posts['uuid']}]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', "is_unique[admins.email,uuid,{$posts['uuid']}]"],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', "is_unique[admins.phone,uuid,{$posts['uuid']}]", 'regex_match[/^\+?[0-9]{9,15}$/]'],
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'Caratteri non ammessi.'
                ]
            ],
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            /* Validazione di ogni singolo elemento contenuto nell'array delle eccezioni */
            'permissions.*' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . $inListString . ']'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.permission')
                ]
            ],
            'images' => [
                'label' => lang('backend/admins.labels.images'),
                'rules' => ['permit_empty', 'checkImages[size:2048,ext:png|jpg|jpeg|webp]']
            ]
        ];
    }

    /**
     * Restituisce le regole di validazione per il cambio dinamico del gruppo via AJAX.
     *
     * Definisce i vincoli di integrità per l'ID del gruppo (che deve esistere nella
     * tabella `admins_groups`) e per l'UUID dell'amministratore su cui si sta operando.
     *
     * @return array Le regole di validazione strutturate per il Validator di CodeIgniter 4.
     */
    public function changeGroupValidationRules(): array
    {
        return [
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ]
        ];
    }

    /**
     * Valida i parametri per l'invocazione della procedura di cancellazione sicura.
     *
     * Assicura che l'UUID fornito per l'eliminazione dell'amministratore sia presente e conforme
     * allo standard formale delle espressioni regolari per gli identificativi a 128 bit.
     *
     * @return array Criteri di validazione per la revoca e rimozione del record.
     */
    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Controlla i requisiti di input per l'inoltro della richiesta di rigenerazione credenziali.
     *
     * Verifica la correttezza formale dell'UUID dell'operatore designato per l'invio del link
     * di ripristino password.
     *
     * @return array Vincoli per l'attivazione della pipeline di reset.
     */
    public function resetPasswordValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Valida i parametri per il cambio di stato operativo (attivo/sospeso) di un profilo.
     *
     * Verifica la correttezza dell'identificativo dell'amministratore e la conformità del contesto
     * di provenienza dell'azione per preservare l'integrità dei flussi AJAX dell'interfaccia.
     *
     * @return array Regole per la commutazione di stato.
     */
    public function changeStatusValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.context'), 
                ]
            ],
        ];
    }

    /**
     * Controlla i criteri di richiesta per il recupero dei token attivi legati a un utente.
     *
     * Valida la stringa UUID necessaria all'interrogazione mirata dei dispositivi e delle sessioni
     * collegate all'amministratore in esame.
     *
     * @return array Regole di accesso alla griglia dei token.
     */
    public function getTokensValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
        ]; 
    }

    /**
     * Valida le richieste di accesso ai dati anagrafici di base in modalità lettura.
     *
     * Controlla la presenza di un UUID valido e la coerenza del contesto operativo, limitando
     * l'interazione esclusivamente alle azioni esplicite di visualizzazione (show) o modifica (edit).
     *
     * @return array Regole per l'estrazione sicura dei dati anagrafici.
     */
    public function generalDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'), 
                    'in_list' => lang('Backend/admins.errors.context') 
                ]
            ],
        ];
    }

    /**
     * Valida i requisiti per l'estrazione dei metadati di tracciamento e storicizzazione.
     *
     * Fornisce i criteri per isolare i record di audit log basandosi sull'identificativo univoco dell'amministratore.
     *
     * @return array Criteri per il recupero dei metadati.
     */
    public function metaDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
        ];
    }

    /**
     * Configura i parametri di convalida per l'estrazione della griglia dei permessi assegnati.
     *
     * Garantisce l'integrità dell'ispezione visiva dei privilegi incrociando l'UUID dell'operatore
     * con le autorizzazioni di contesto previste per la scheda utente.
     *
     * @return array Vincoli di richiesta per l'albero dei privilegi.
     */
    public function getPermissionsValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'), 
                    'in_list' => lang('Backend/admins.errors.context') 
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
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'id' => [
                'label' => lang('backend/admins.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.id'), 
                    'is_natural_no_zero' => lang('Backend/admins.errors.id') 
                ]
            ],
        ];
    }

    /**
     * Valida l'assegnazione o la revoca immediata di un singolo privilegio in tempo reale (on the fly).
     *
     * Estrae l'albero complessivo delle autorizzazioni applicative dalle configurazioni, ne mappa le chiavi
     * in una lista lineare e compila dinamicamente la regola in_list per bloccare l'inserimento di permessi
     * orfani o non censiti nel file di configurazione core.
     *
     * @return array Regole di validazione dinamica e restrittiva per i singoli permessi applicativi.
     */
    public function changePermissionValidationRules(): array 
    {
        /* Recupero l'array multidimensionale dalla configurazione */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        /* Estraggo solo le chiavi (es. 'users_index') ciclando i gruppi */
        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        /* Implodo l'array piatto ottenuto per formare la stringa richiesta da in_list */
        $inListString = implode(',', $validKeys);

        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'permission' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['required', 'in_list[' . $inListString . ']'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.permission'), 
                    'in_list' => lang('Backend/admins.errors.permission') 
                ]
            ]
        ];
    }

    /**
     * Estrae l'elenco completo dei singoli privilegi espliciti assegnati all'amministratore.
     *
     * Interroga la tabella delle autorizzazioni per recuperare tutte le righe associate all'identificativo
     * univoco fornito, permettendo l'analisi puntuale delle eccezioni al ruolo base.
     *
     * @param string $uuid Identificativo univoco dell'amministratore.
     * @return array Lista dei record contenenti i permessi espliciti.
     */
    public function getPermissions(string $uuid): array
    {
        /* Estrazione permessi assegnati all'admin */
        $sql = "select * from admins_permissions where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
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

    /**
     * Estrae il registro cronologico dei tentativi di autenticazione falliti legati all'anagrafica.
     *
     * Raccoglie i metadati di audit relativi agli errori di login standard, utili alla diagnostica di sicurezza
     * e al calcolo dei blocchi temporanei per la mitigazione degli attacchi Brute Force.
     *
     * @param string $uuid Identificativo univoco dell'amministratore.
     * @return array Storico dei record di errore memorizzati per l'accesso base.
     */
    public function getAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso standard */
        $sql = "select * from admins_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Recupera il registro cronologico dei tentativi falliti durante la fase di verifica del secondo fattore (2FA).
     *
     * Isola i record di audit specifici per gli errori nell'inserimento dei codici OTP o delle chiavi di sicurezza,
     * garantendo il monitoraggio separato rispetto alla pipeline di autenticazione primaria.
     *
     * @param string $uuid Identificativo univoco dell'amministratore.
     * @return array Storico dei record di errore memorizzati per il secondo fattore.
     */
    public function getTwoFaAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso 2FA */
        $sql = "select * from admins_2fa_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Estrae l'elenco dei codici di backup e ripristino per l'autenticazione a due fattori.
     *
     * Restituisce sia le chiavi monouso ancora attive sia quelle già consumate dall'operatore,
     * consentendo la verifica dello stato di saturazione dei sistemi di recovery del profilo.
     *
     * @param string $uuid Identificativo univoco dell'amministratore.
     * @return array Elenco dei codici di backup mappati sul database.
     */
    public function getTwoFaCodes(string $uuid): array
    {
        /* Estrazione codici di backup 2FA attivi o consumati */
        $sql = "select * from admins_2fa_codes where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Recupera la configurazione e lo stato corrente del modulo di autenticazione a due fattori dell'utente.
     *
     * Estrae il record singolo contenente le impostazioni core del sistema 2FA, inclusi lo stato di abilitazione,
     * il metodo prescelto (es. email, app) e i relativi segreti crittografici di sincronizzazione.
     *
     * @param string $uuid Identificativo univoco dell'amministratore.
     * @return object|null Oggetto contenente i parametri di configurazione 2FA, o null se non configurato.
     */
    public function getTwoFa(string $uuid): ?object
    {
        /* Estrazione configurazione principale 2FA (record singolo, uso getRow) */
        $sql = "select * from admins_2fa where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getRow();
    }

    /**
     * Recupera l'elenco completo dei gruppi amministrativi disponibili nel sistema.
     *
     * Il metodo esegue una query diretta sulla tabella `admins_groups` per prelevare tutti
     * i record dei ruoli censiti. Viene utilizzato principalmente nei moduli di gestione
     * degli amministratori (es. maschere di inserimento e modifica) per popolare i componenti
     * di selezione (select) dell'interfaccia utente.
     *
     * @return array Elenco di oggetti rappresentanti le righe della tabella dei gruppi.
     */
    public function getGroups(): array
    {
        $sql = "select * from admins_groups";
        return $this->db->query($sql)->getResult();
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
     * Gestisce la logica di business e la transazione per l'inserimento di un nuovo amministratore.
     *
     * Il metodo esegue la pulizia dei dati in ingresso tramite `checkAllowedFields` e avvia una 
     * transazione database. Registra l'utente nella tabella `admins` associandolo al gruppo specificato, 
     * scrive il token di attivazione in `admins_tokens`, configura il metodo 2FA predefinito via email 
     * in `admins_2fa` e, in caso di successo complessivo, delega a `EmailService` l'invio dell'email 
     * di attivazione. Gestisce il rollback automatico in caso di anomalie o fallimenti SQL.
     *
     * @param array $posts I dati provenienti dal form di inserimento.
     * @param \CodeIgniter\HTTP\IncomingRequest $request L'oggetto della richiesta HTTP corrente.
     * @return array Esito dell'operazione con flag 'result' e stringa informativa 'message'.
     */
    public function add(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            /* Filtro campi post ammessi */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);

            /* Genero uuid */
            $uuid = $this->generateUUID();

            /* Istanzio la classe request per ricavare User Agent e IP */
            $request = service('request');
            $userAgent = $request->getUserAgent()->getAgentString();
            $ip = $request->getIPAddress();

            /* 1. Avvio la transazione PRIMA di eseguire qualsiasi query */
            $this->db->transBegin();

            /* Inserimento dati nella tabella principale con l'aggiunta di group_id */
            $sql = "insert into admins (uuid, firstname, lastname, email, phone, status, group_id, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['group_id'], (trim($posts['note']) !== '' ? $posts['note'] : null), date('Y-m-d H:i:s')]);

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip]);

            /* Metodo email di default */
            $sql = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'email', NULL, 1)";
            $this->db->query($sql, [$uuid]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                $uploadService = new \App\Libraries\Backend\Upload();
                $filenames = $uploadService->doUpload($posts['images'], $this->entity, $uuid);
                
                if ($filenames):
                    $this->insertImages($filenames, $uuid, $this->entity, 'add');
                endif;
            endif;

            /* 3. Verifico eventuali errori SQL prima di fare il commit */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();

                log_message('error', lang('backend/admins.messages.addError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
            endif;

            /* Se le query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            /* Recupero dati utente appena inseriti */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $module = $this->module;
        $template = 'emailCreateAdminPartial';
        $subjectLangKey = 'backend/email.admins.add.subjectCreateAdminEmail';

        /* Chiamata al metodo con i parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.addSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.addSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    /**
     * Gestisce l'aggiornamento dei dati anagrafici, del gruppo e delle eccezioni sui permessi di un amministratore.
     *
     * Il metodo avvia una transazione database. Aggiorna la tabella principale `admins` inserendo il nuovo `group_id`
     * e, successivamente, esegue il calcolo differenziale dei permessi. Rimuove le vecchie eccezioni e inserisce in
     * `admins_permissions` solo i record relativi a revoche esplicite (permessi del gruppo deselezionati, `allow = 0`)
     * o concessioni extra (permessi fuori dal gruppo selezionati, `allow = 1`). In caso di anomalie effettua il rollback.
     *
     * @param array $posts I dati provenienti dal form di modifica.
     * @return array Esito dell'operazione con il flag 'result', la stringa 'message' e l'oggetto 'row' aggiornato.
     */
    public function edit(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti (ricordati di inserire group_id ed eliminare permissions in $editAllowedFields) */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

            /* Recupero i dati dell'utente prima dell'aggiornamento */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            /* Se non è stato effettuato alcun cambio sui dati gestiti, interrompiamo subito */
            if( ! $this->hasAdminChanged($posts, $data['row'])):
                return ['result' => false, 'message' => lang('backend/admins.messages.noDataChanged')];
            endif;

            $updated_at = date('Y-m-d H:i:s');

            $this->db->transBegin();

            /* Aggiorno la tabella principale dell'utente includendo il group_id */
            $sql = 'update admins set firstname = ?, lastname = ?, email = ?, phone = ?, status = ?, group_id = ?, note = ?, updated_at = ? where uuid = ?';
            $this->db->query($sql, [$posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['group_id'], (trim($posts['note']) !== '' ? $posts['note'] : null), $updated_at, $posts['uuid']]);

            /* Eliminazione incondizionata delle vecchie eccezioni dell'utente */
            $this->deletePermissions($posts['uuid']);

            /* Recupero i permessi nativi del gruppo appena assegnato per calcolare le eccezioni */
            $groupPermissions = $this->getGroupPermissions((int)$posts['group_id']);
            $submittedPermissions = $posts['permissions'] ?? [];

            /* 1. Calcolo eccezioni positive (Permessi extra): presenti nel form MA non nel gruppo */
            $extraPermissions = array_diff($submittedPermissions, $groupPermissions);

            /* 2. Calcolo eccezioni negative (Revoche): presenti nel gruppo MA non nel form */
            $revokedPermissions = array_diff($groupPermissions, $submittedPermissions);

            /* Scrittura delle eccezioni positive (allow = 1) */
            if ( ! empty($extraPermissions)):
                foreach ($extraPermissions as $perm):
                    $sqlInsert = "insert into admins_permissions (permission, admin_uuid, allow) values (?, ?, 1)";
                    $this->db->query($sqlInsert, [$perm, $posts['uuid']]);
                endforeach;
            endif;

            /* Scrittura delle eccezioni negative (allow = 0) */
            if ( ! empty($revokedPermissions)):
                foreach ($revokedPermissions as $perm):
                    $sqlInsert = "insert into admins_permissions (permission, admin_uuid, allow) values (?, ?, 0)";
                    $this->db->query($sqlInsert, [$perm, $posts['uuid']]);
                endforeach;
            endif;

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                $uploadService = new \App\Libraries\Backend\Upload();
                $filenames = $uploadService->doUpload($posts['images'], $this->entity, $posts['uuid']);
                
                if ($filenames):
                    $this->insertImages($filenames, $posts['uuid'], $this->entity, 'edit');
                endif;
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.editError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dell'oggetto in memoria da restituire alla vista */
            $data['row']->firstname  = $posts['firstname'];
            $data['row']->lastname   = $posts['lastname'];
            $data['row']->email      = $posts['email'];
            $data['row']->phone      = $posts['phone'];
            $data['row']->status     = $posts['status'];
            $data['row']->group_id   = $posts['group_id'];
            $data['row']->note       = $posts['note'];
            $data['row']->updated_at = $updated_at;

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/admins.messages.editSuccess'), esc($posts['firstname']), esc($posts['lastname'])), 
                'row'     => $data['row']
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/admins.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
            return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
        }
    }

    /**
     * Verifica se i dati inviati dal form (campi base o matrice dei permessi)
     * differiscono da quelli attualmente memorizzati nel database.
     *
     * @param array $posts I dati ricevuti dal form POST.
     * @param object $original L'oggetto record originale dell'amministratore prima della modifica.
     * @return bool True se c'è stata almeno una modifica, false altrimenti.
     */
    public function hasAdminChanged(array $posts, object $original): bool
    {
        /* 1. Controlla i campi base (incluso group_id presente in $toCompare) e i file */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        /* 2. Recupero i permessi ereditati dal gruppo originale dell'utente */
        $groupPerms = $this->getGroupPermissions((int) $original->group_id);

        /* 3. Recupero le eccezioni attuali dell'utente dal database */
        $userExceptions = $this->getAdminExceptions($original->uuid);

        /* 4. Calcolo la lista reale e attiva dei permessi attuali dell'utente */
        $oldPermissions = [];
        
        /* Prendo la configurazione globale dei permessi atomici per ciclare tutti i permessi possibili */
        $globalPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        foreach ($globalPermissions as $group):
            foreach ($group['perms'] as $code => $title):
                /* Se esiste un'eccezione esplicita nel DB, comanda lei */
                if (array_key_exists($code, $userExceptions)):
                    if ($userExceptions[$code] === 1):
                        $oldPermissions[] = $code;
                    endif;
                else:
                    /* Altrimenti l'utente eredita lo stato del suo gruppo */
                    if (in_array($code, $groupPerms)):
                        $oldPermissions[] = $code;
                    endif;
                endif;
            endforeach;
        endforeach;

        /* 5. Preparo l'array dei nuovi permessi inviati dal form */
        $newPermissions = $posts['permissions'] ?? [];

        /* 6. Ordino entrambi gli array per garantire un confronto coerente */
        sort($newPermissions);
        sort($oldPermissions);

        /* 7. Confronto finale tra lo stato reale precedente e quello nuovo inviato */
        if ($newPermissions !== $oldPermissions):
            return true;
        endif;

        return false;
    }

    /**
     * Esegue la rimozione fisica e definitiva (hard delete) del record dell'amministratore.
     *
     * Filtra l'input mediante whitelisting dei campi e recupera l'anagrafica storica per conservare
     * i riferimenti nominali utili alla messaggistica di successo. Successivamente, apre una transazione
     * database atomica per eseguire l'istruzione di cancellazione sulla tabella principale, affidando i vincoli
     * di integrità referenziale sulle tabelle correlate (es. permessi, token) alle regole ON DELETE CASCADE
     * del motore relazionale. Intercetta eventuali anomalie o eccezioni d'esecuzione forzando il rollback.
     *
     * @param array $posts Dataset contenente l'identificativo univoco del profilo da rimuovere.
     * @return array Esito dell'operazione corredato dal messaggio localizzato di avvenuta cancellazione.
     */
    public function del(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $this->db->transBegin();

            /* Eliminazione utente */
            $sql = "delete from admins where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            /* Eliminazione immagini dal database */
            $sql = "delete from images where entity_uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.delError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.delError')];
            endif;

            $this->db->transCommit();

            \App\Libraries\ImageFileSystemService::removeAllImages('admins', $posts['uuid']);

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.delSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.delError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.delError')];

        }
    }

    /**
     * Gestisce la generazione transazionale di un token di ripristino credenziali (procedura di reset).
     *
     * Filtra l'input tramite whitelisting, verifica l'esistenza dell'account e acquisisce i metadati 
     * ambientali del client. Genera un oggetto token calcolandone l'hash di sicurezza e la scadenza temporale. 
     * Avvia una transazione database integrando due operazioni atomiche: l'aggiornamento della colonna `resetted_at` 
     * sulla tabella dell'anagrafica principale (per tracciare la storicizzazione della richiesta) e l'inserimento 
     * del nuovo token di tipo 'activation' nella tabella delle sessioni. In caso di anomalie, esegue il rollback 
     * automatico dello stato.
     *
     * @param array $posts Dataset contenente l'identificativo univoco del profilo per cui generare il reset.
     * @param \CodeIgniter\HTTP\IncomingRequest $request Oggetto della richiesta HTTP per l'estrazione di IP e User Agent.
     * @return array Esito logico dell'operazione e relativo messaggio di stato o di errore.
     */
    public function resetPassword(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->resetPasswordAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $userAgent = $request->getUserAgent()->getAgentString();
            $ip = $request->getIPAddress();

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

            $this->db->transBegin();

            /* Scrittura Data di Reset nella tabella admins */
            $sql = "update admins set resetted_at = ? where uuid = ?";
            $this->db->query($sql, [date('Y-m-d H:i:s'), $posts['uuid']]);

            /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$posts['uuid'], 'activation']);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$posts['uuid'], $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.resetPasswordError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];
            endif;

            $this->db->transCommit();

        } catch (\Throwable $e) {

            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.resetPasswordError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];

        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $module = $this->module;
        $template = 'emailResetPasswordAdminPartial';
        $subjectLangKey = 'backend/email.admins.resetPassword.subjectResetPasswordEmail';

        /* Chiamata al metodo con i nuovi parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    /**
     * Gestisce la commutazione transazionale dello stato operativo (attivo/inattivo) di un profilo.
     *
     * Filtra i parametri in ingresso tramite whitelisting ed estrae l'entità storica per valutarne lo stato corrente.
     * Implementa una logica condizionale binaria: se l'utente è disattivato (0), ne forza l'attivazione (1) azzerando
     * il flag di sospensione; se è attivo (1), ne esegue la disattivazione (0) storicizzando il timestamp corrente
     * nella colonna `suspended_at`. Alimenta infine una transazione atomica per aggiornare permanentemente il record 
     * sul database e riallinea l'oggetto entità in memoria prima della restituzione.
     *
     * @param array $posts Dataset contenente l'identificativo univoco dell'amministratore da variare.
     * @return array Matrice di risposta contenente l'esito logico, il messaggio localizzato e l'istanza aggiornata.
     */
    public function changeStatus(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $currentStatus = (int) $data['row']->status;

            /* Converte il risultato in un intero (0 o 1) per MySQL */
            if($currentStatus === 0):

                $newStatus = 1;
                $suspendedAt = null;
                $data['row']->status = 1;
                $data['row']->suspended_at = null;

            elseif($currentStatus === 1):

                $newStatus = 0;
                $suspendedAt = date('Y-m-d H:i:s');
                $data['row']->status = 0;
                $data['row']->suspended_at = $suspendedAt;

            endif;

            $updatedAt = date('Y-m-d H:i:s');
            $data['row']->updated_at = $updatedAt;

            $this->db->transBegin();

            /* cambio status utente */
            $sql = "update admins set status = ?, updated_at = ?, suspended_at = ? where uuid = ?";
            $this->db->query($sql, [$newStatus, $updatedAt, $suspendedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.changeStatusError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.changeStatusSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];

        }
    }

    /**
     * Esegue la commutazione asincrona e transazionale (toggle) di un singolo privilegio utente.
     *
     * Filtra i dati in ingresso tramite whitelisting e verifica l'esistenza del profilo amministrativo. 
     * Interroga la tabella dei permessi per intercettare la presenza del privilegio specificato: se il record 
     * esiste, ne esegue la revoca immediata (delete); se non è presente, ne valida l'assegnazione (insert). 
     * Alimenta infine una transazione atomica che include l'aggiornamento del timestamp `updated_at` sulla tabella 
     * dell'anagrafica principale e riallinea l'istanza in memoria prima della risposta.
     *
     * @param array $posts Dataset contenente l'UUID dell'amministratore e la chiave testuale del permesso da variare.
     * @return array Matrice di risposta contenente l'esito logico, il messaggio localizzato e l'istanza aggiornata.
     */
    /**
     * Modifica lo stato di un singolo permesso per l'utente, inserendo, aggiornando
     * o rimuovendo un record dalla tabella delle eccezioni (admins_permissions).
     */
    public function changePermission(array $posts): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->changePermissionAllowedFields);

            $data = $this->getByUUID($posts['uuid']);

            if ($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $admin = $data['row'];
            $permissionCode = $posts['permission'];

            /* 1. Recuperiamo lo stato nativo del gruppo e le eccezioni attuali */
            $groupPerms = $this->getGroupPermissions((int) $admin->group_id);
            $userExceptions = $this->getAdminExceptions($admin->uuid);

            $isBelongingToGroup = in_array($permissionCode, $groupPerms);
            $hasException = array_key_exists($permissionCode, $userExceptions);

            $this->db->transBegin();

            if ($isBelongingToGroup):
                /* Il permesso appartiene al gruppo */
                if ($hasException):
                    /* C'era un'eccezione (era a 0 per bloccarlo), cliccando lo ripristiniamo al gruppo (elimina eccezione) */
                    $sql = "delete from admins_permissions where admin_uuid = ? and permission = ?";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                else:
                    /* Non c'era eccezione (era attivo da gruppo), cliccando creiamo un'eccezione negativa (allow = 0) */
                    $sql = "insert into admins_permissions (admin_uuid, permission, allow) values (?, ?, 0)";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                endif;
            else:
                /* Il permesso NON appartiene al gruppo */
                if ($hasException):
                    /* C'era un'eccezione (era a 1 per sbloccarlo), cliccando lo ripristiniamo al gruppo (elimina eccezione) */
                    $sql = "delete from admins_permissions where admin_uuid = ? and permission = ?";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                else:
                    /* Non c'era eccezione (era spento da gruppo), cliccando creiamo un'eccezione positiva (allow = 1) */
                    $sql = "insert into admins_permissions (admin_uuid, permission, allow) values (?, ?, 1)";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                endif;
            endif;

            /* Aggiorno nella tabella admins il campo updated_at */
            $updatedAt = date('Y-m-d H:i:s');
            $sql = 'update admins set updated_at = ? where uuid = ?';
            $this->db->query($sql, [$updatedAt, $admin->uuid]);

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.changePermissionError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];
            endif;

            $this->db->transCommit();

            $admin->updated_at = $updatedAt;

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/admins.messages.changePermissionSuccess'), esc($admin->firstname), esc($admin->lastname)), 
                'admin'   => $admin
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/admins.messages.changePermissionError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];
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
    public function deleteToken(array $posts): array
    {
        /* Match dei posts con i campi consentiti */
        $posts = $this->checkAllowedFields($posts, $this->deleteTokenAllowedFields);

        try {

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il master */
            if ((int) $data['row']->master === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            /* Query per eliminare il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$posts['uuid'], $posts['id']]);

            if($this->db->affectedRows() > 0):
                return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/admins.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        }
    }

    /**
     * Rimuove incondizionatamente tutte le associazioni ai permessi espliciti legati all'utente.
     *
     * Svuota integralmente le righe di privilegio memorizzate nella tabella relazionale per l'UUID fornito.
     * Questo metodo agisce come operazione distruttiva preliminare sia durante la fase di cancellazione (hard delete)
     * dell'account, sia durante le routine di aggiornamento anagrafico per il successivo riallineamento dei dati.
     *
     * @param string $admin_uuid Identificativo univoco dell'amministratore da ripulire.
     * @return void
     */
    public function deletePermissions($admin_uuid)
    {
        $sql = "delete from admins_permissions where admin_uuid = ?";
        $this->db->query($sql, [$admin_uuid]);
    }
}