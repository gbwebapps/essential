<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello principale per la gestione degli Amministratori di sistema.
 * 
 * Estende il BackendModel. Incapsula la business logic, le query e le transazioni CRUD 
 * per l'entità "admins". Gestisce operazioni critiche come la creazione degli utenti, 
 * l'assegnazione ai gruppi, l'override dei permessi (eccezioni), i blocchi di sicurezza 
 * (es. protezione del Superadmin), le sospensioni temporanee e le eliminazioni (sia fisiche che logiche).
 */
class AdminsModel extends BackendModel
{
    /**
     * Nome identificativo del modulo (tabella principale 'admins') usato dal motore genitore.
     * 
     * @var string 
     */
    protected ?string $module = 'admins';

    /**
     * Flag strutturale che abilita la gestione del "Cestino" (Soft Delete). 
     * Se impostato a true, le query di estrazione nasconderanno di default i record con 'deleted_at' valorizzato.
     * 
     * @var bool 
     */
    protected bool $hasSoftDelete = true;

    /**
     * La colonna predefinita per l'ordinamento dei dati (es. 'id').
     * 
     * @var string 
     */
    protected ?string $defaultColumn = 'id';

    /**
     * Whitelist dei campi consentiti durante la richiesta di visualizzazione dell'elenco amministratori.
     * Include i parametri strutturali per la paginazione, l'ordinamento e la gestione della vista cestino.
     *
     * @var array
     */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields', 'trash_filter'];

    /**
     * Whitelist dei campi consentiti per la creazione di un nuovo amministratore.
     * Include i dati anagrafici, di contatto, l'assegnazione al gruppo principale e i media (immagini).
     *
     * @var array
     */
    protected array $addAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'images'];

    /**
     * Whitelist dei campi consentiti per la modifica di un amministratore esistente.
     * Estende i campi di creazione aggiungendo l'obbligatorietà dell'UUID e la gestione della matrice permessi ad personam.
     *
     * @var array
     */
    protected array $editAllowedFields = ['uuid', 'firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'permissions', 'images'];

    /**
     * Whitelist dei campi consentiti per l'eliminazione (Soft Delete) di un amministratore.
     * Restringe il payload al solo UUID per prevenire cancellazioni multiple o accidentali.
     *
     * @var array
     */
    protected array $delAllowedFields = ['uuid'];

    /**
     * Whitelist dei campi consentiti per forzare il ripristino della password dal pannello di controllo.
     * Richiede unicamente l'UUID dell'amministratore bersaglio per innescare la generazione del token.
     *
     * @var array
     */
    protected array $resetPasswordAllowedFields = ['uuid'];

    /**
     * Whitelist dei campi consentiti per l'operazione di cambio stato rapido (es. da attivo a sospeso).
     *
     * @var array
     */
    protected array $changeStatusAllowedFields = ['uuid'];

    /**
     * Whitelist dei campi consentiti per l'aggiornamento asincrono di un singolo permesso (toggle).
     * Richiede l'identificativo dell'amministratore e la chiave testuale del permesso da invertire.
     *
     * @var array
     */
    protected array $changePermissionAllowedFields = ['uuid', 'permission'];

    /**
     * Whitelist dei campi consentiti per invalidare una sessione o un token di accesso remoto.
     * Necessita dell'ID univoco del token e dell'UUID dell'amministratore per confermare la proprietà.
     *
     * @var array
     */
    protected array $deleteTokenAllowedFields = ['id', 'uuid'];

    /**
     * Whitelist delle colonne anagrafiche e di contatto su cui è attiva la ricerca testuale (LIKE).
     * Limita il motore di ricerca ai soli campi pertinenti per tutelare le performance e la sicurezza.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['firstname', 'lastname', 'email', 'phone']; 

    /**
     * Whitelist delle colonne temporali su cui è possibile effettuare filtri per intervallo di date (Da - A).
     *
     * @var array
     */
    protected array $showAllSearchAllowedDates = ['created_at', 'updated_at'];

    /**
     * Array combinato che indica quali colonne sono esposte all'ordinamento (DataTables) 
     * e alla ricerca testuale o temporale, validando rigorosamente l'input dell'operatore.
     * 
     * @var array 
     */
    protected array $allowedOrderColumns = ['firstname', 'lastname', 'email', 'phone', 'status']; 

    /**
     * Elenco dei campi anagrafici monitorati per rilevare cambiamenti effettivi 
     * prima di lanciare una query di UPDATE a database.
     * 
     * @var array 
     */
    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'status', 'group_id', 'note'];

    /**
     * Query principale per il caricamento della griglia utenti. 
     * Include sub-query ottimizzate per calcolare dinamicamente il numero di immagini associate 
     * e recuperare l'eventuale immagine di copertina (avatar) dell'utente.
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select uuid, firstname, lastname, email, phone, status, superadmin, created_at, updated_at, resetted_at, suspended_at, deleted_at,
                                        (select images.filename from images where images.entity_uuid = admins.uuid and images.entity = 'admins' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = admins.uuid and images.entity = 'admins') as images_num 
                                        from admins where 1 = 1";

    /**
     * Query utilizzata per estrarre il profilo completo di un singolo amministratore tramite UUID. 
     * Esegue una JOIN con `admins_groups` per recuperare il nome del ruolo assegnato.
     * 
     * @var string 
     */
    protected ?string $getUUIDQuery = "select 
                                            admins_groups.name as groupName, 
                                            uuid, 
                                            firstname, 
                                            lastname, 
                                            email, 
                                            phone, 
                                            status, 
                                            superadmin, 
                                            group_id, 
                                            note, 
                                            admins.created_at, 
                                            admins.updated_at, 
                                            suspended_at, 
                                            resetted_at, 
                                            admins.deleted_at 
                                        from admins 
                                        join admins_groups 
                                        on admins.group_id = admins_groups.id 
                                        where admins.uuid = ? limit 1";

    /**
     * Query SQL essenziale per calcolare il numero totale degli amministratori, 
     * necessaria al frontend per creare i bottoni della paginazione.
     *
     * @var string 
     */
    protected ?string $getNumRowsQuery = 'select count(*) as count from admins where 1 = 1';

    /**
     * Hook nativo di CodeIgniter 4.
     * 
     * Inizializza il modello richiamando il costruttore della classe padre, predisponendo 
     * le connessioni al DB e gli helper condivisi.
     */
    protected function initModel(): void 
    {
        parent::initModel();
    }

    /**
     * Regole di validazione per la griglia dati (paginazione, ordinamento e filtro cestino).
     *
     * @return array Regole native per i parametri strutturali
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
            'trash_filter' => [
                'rules' => ['in_list[active,trashed,all]'] 
            ],
        ];
    }

    /**
     * Regole di validazione per i filtri di ricerca degli amministratori.
     * 
     * Verifica che i campi di testo (nome, cognome, email, telefono) e le date (Da/A) 
     * rispettino pattern sicuri, prevenendo injection direttamente sui parametri di ricerca.
     *
     * @return array Regole per i filtri
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.firstname' => [
                'label' => lang('backend/admins.labels.firstname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
            'searchFields.lastname' => [
                'label' => lang('backend/admins.labels.lastname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
            'searchFields.email' => [
                'label' => lang('backend/admins.labels.email'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'], 
            ],
            'searchFields.phone' => [
                'label' => lang('backend/admins.labels.phone'), 
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/admins.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/admins.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /**
     * Regole di validazione per la creazione di un nuovo Amministratore (Add).
     * 
     * Verifica la correttezza formale dei dati anagrafici, impone l'univocità assoluta dell'indirizzo email 
     * (`is_unique[admins.email]`) e valida l'ID del gruppo assegnato. Include anche il controllo sulle immagini caricate.
     *
     * @return array Regole strutturate
     */
    public function addValidationRules(): array
    {
        return [
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', 'is_unique[admins.email]'],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'regex_match[/^\+[0-9]{9,15}$/]'], 
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
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Regole di validazione per la modifica di un Amministratore esistente (Edit).
     * 
     * Estrae dinamicamente la lista dei permessi validi per popolare la regola `in_list`. 
     * Aggiorna i controlli di univocità (UUID e Email) configurandoli in modo da ignorare il record in corso di modifica, 
     * evitando così falsi conflitti con i dati dell'utente stesso.
     *
     * @param array $posts I dati inviati dal form (serve a recuperare l'UUID corrente)
     * @return array Regole strutturate
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
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', "is_unique[admins.email,uuid,{$posts['uuid']}]"],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'regex_match[/^\+[0-9]{9,15}$/]'], 
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
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Regole di validazione per il cambio rapido del Gruppo (Ruolo) di un utente.
     *
     * @return array Regole per uuid e group_id
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
     * Regole di validazione per le richieste di eliminazione (Soft/Hard Delete).
     *
     * @return array Regole per il campo uuid
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
     * Regole di validazione per la richiesta di Reset Password.
     *
     * @return array Regole per il campo uuid
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
     * Regole di validazione per il cambio di stato (Attivo/Sospeso).
     * 
     * Oltre all'UUID, valida il campo 'context' che definisce da quale vista 
     * è partita la richiesta (es. vista di dettaglio 'show').
     *
     * @return array Regole per uuid e context
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
     * Regole di validazione per la richiesta di estrazione dei token di sicurezza (es. sessioni).
     * 
     * Assicura che l'identificativo fornito sia un UUID valido, impedendo query malformate 
     * o tentativi di estrazione dati arbitrari.
     *
     * @return array Regole per la validazione dell'UUID
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
     * Regole di validazione generiche per l'estrazione parziale dei dati anagrafici (General Data).
     * 
     * Verifica la correttezza dell'UUID e valida il parametro 'context', assicurandosi che la richiesta 
     * provenga da una vista autorizzata a mostrare ('show') o modificare ('edit') tali informazioni.
     *
     * @return array Regole per UUID e context
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
     * Regole di validazione per l'estrazione dei metadati di sistema dell'utente (Metadata).
     * 
     * Metodo essenziale che filtra la richiesta limitandosi a confermare l'integrità formale 
     * dell'UUID (tramite Regex) prima di procedere con l'estrazione dei dati temporali (creazione, modifica, ecc.).
     *
     * @return array Regole per la validazione dell'UUID
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
     * Regole di validazione per l'estrazione della matrice dei permessi (nativi ed eccezioni).
     * 
     * Valida rigorosamente sia l'UUID dell'utente bersaglio sia il contesto ('show' o 'edit') 
     * dal quale parte la richiesta, garantendo che le informazioni sensibili sulle autorizzazioni 
     * vengano caricate solo in scenari previsti dall'architettura.
     *
     * @return array Regole per UUID e context
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
     * Regole di validazione per la revoca forzata di un token (es. disconnessione remota).
     * 
     * Verifica che la richiesta fornisca sia un UUID valido (l'utente proprietario del token) 
     * sia un ID numerico positivo (il token stesso), bloccando manipolazioni del payload 
     * finalizzate a eliminare sessioni di altri amministratori.
     *
     * @return array Regole incrociate per UUID e ID del token
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
     * Regole di validazione per l'impostazione o la rimozione di una singola eccezione di permesso (Switch).
     * 
     * Carica dinamicamente il dizionario globale dei permessi e lo compila in una stringa piatta 
     * per alimentare la regola `in_list`. Questo garantisce che nessun permesso fittizio, inesistente 
     * o deprecato possa essere iniettato nel database alterando l'integrità del sistema ACL.
     *
     * @return array Regole per UUID e chiave del permesso
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
     * Restituisce i permessi (eccezioni) assegnati in modo diretto allo specifico amministratore.
     *
     * @param string $uuid Identificativo univoco
     * @return array Array di oggetti contenente i permessi personalizzati
     */
    public function getPermissions(string $uuid): array
    {
        /* Estrazione permessi assegnati all'admin */
        $sql = "select * from admins_permissions where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Estrae lo storico dei token (login, sessioni, attivazioni) attivi o scaduti dell'utente.
     *
     * @param string $uuid Identificativo univoco
     * @return array Array di oggetti (log dei token)
     */
    public function getTokens(string $uuid): array
    {
        /* Estrazione log dei tokens di sessione o reset */
        $sql = "select * from admins_tokens where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Estrae lo storico dei log relativi ai tentativi di accesso standard.
     *
     * @param string $uuid Identificativo univoco
     * @return array Array di oggetti
     */
    public function getAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso standard */
        $sql = "select * from admins_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Estrae lo storico dei log relativi ai tentativi di accesso protetti da 2FA (Autenticazione a Due Fattori).
     *
     * @param string $uuid Identificativo univoco
     * @return array Array di oggetti
     */
    public function getTwoFaAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso 2FA */
        $sql = "select * from admins_2fa_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Estrae i codici di backup (recovery codes) 2FA associati all'utente.
     *
     * @param string $uuid Identificativo univoco
     * @return array Array di oggetti (codici attivi o già utilizzati)
     */
    public function getTwoFaCodes(string $uuid): array
    {
        /* Estrazione codici di backup 2FA attivi o consumati */
        $sql = "select * from admins_2fa_codes where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    /**
     * Recupera l'impostazione principale e il metodo 2FA attualmente configurato per l'utente.
     *
     * @param string $uuid Identificativo univoco
     * @return object|null Oggetto con le impostazioni 2FA o null se non presente
     */
    public function getTwoFa(string $uuid): ?object
    {
        /* Estrazione configurazione principale 2FA (record singolo, uso getRow) */
        $sql = "select * from admins_2fa where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getRow();
    }

    /**
     * Recupera l'elenco di tutti i Gruppi (Ruoli) amministrativi disponibili a sistema.
     *
     * @return array Elenco dei gruppi
     */
    public function getGroups(): array
    {
        $sql = "select * from admins_groups";
        return $this->db->query($sql)->getResult();
    }

    /**
     * Estrae i permessi base associati a un Gruppo convertendoli in un array piatto di stringhe.
     *
     * @param int $groupId L'ID del gruppo
     * @return array Elenco testuale dei permessi (es. ['users_index', 'logs_view'])
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
     * Recupera le eccezioni di permesso dell'utente (override) sotto forma di array associativo.
     *
     * @param string $uuid Identificativo univoco
     * @return array Dizionario [permesso => 0 o 1] (0 per revoca, 1 per concessione)
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
     * Crea un nuovo Amministratore all'interno del sistema (Transazionale).
     * 
     * Gestisce l'intera catena di provisioning: genera l'UUID, inserisce l'anagrafica, 
     * crea un token crittografico di attivazione account a scadenza, imposta il metodo 2FA di base (email), 
     * gestisce l'eventuale upload dell'immagine del profilo e invia l'email di benvenuto. 
     * In caso di fallimento in uno di questi passaggi, il Rollback annulla ogni operazione sul DB.
     *
     * @param array $posts Dataset anagrafico filtrato dalla request
     * @param \CodeIgniter\HTTP\IncomingRequest $request Oggetto per estrarre IP e User Agent ai fini del token
     * @return array Esito strutturato dell'operazione
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
            $ip_address = $request->getIPAddress();

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
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d H:i:s')]);

            /* Metodo email di default */
            $sql = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'email', NULL, 1)";
            $this->db->query($sql, [$uuid]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                $uploadService = new \App\Libraries\Backend\UploadClass();
                $filenames = $uploadService->doUpload($posts['images'], 'admins', $uuid);
                
                if ($filenames):
                    $this->insertImages($filenames, $uuid, 'admins', 'add');
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

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('ADD_ADMIN', 'admins', sprintf(lang('backend/admins.audits.addAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            /* STAMPIAMO L'ERRORE E LA RIGA ESATTA */
            var_dump("ERRORE: " . $e->getMessage() . " | RIGA: " . $e->getLine());

            log_message('error', lang('backend/admins.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $template = 'emailCreateAdminPartial';
        $subjectLangKey = 'backend/email.admins.add.subjectCreateAdminEmail';

        /* Chiamata al metodo con i parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $this->module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.addSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.addSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    /**
     * Aggiorna i dati anagrafici e i permessi di un Amministratore esistente (Transazionale).
     * 
     * Implementa Scudi di Sicurezza (blocca modifiche su superadmin o record cestinati) e uno 
     * Sbarramento prestazionale (hasAdminChanged). In transazione: aggiorna l'anagrafica, svuota 
     * le vecchie eccezioni sui permessi, ricalcola matematicamente le nuove differenze confrontando 
     * i permessi sottomessi con quelli nativi del nuovo gruppo assegnato (generando eccezioni positive a 1 
     * o negative a 0), e gestisce le eventuali nuove immagini.
     *
     * @param array $posts Dataset aggiornato filtrato dalla request
     * @return array Esito strutturato con il record aggiornato
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
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
                $uploadService = new \App\Libraries\Backend\UploadClass();
                $filenames = $uploadService->doUpload($posts['images'], 'admins', $posts['uuid']);
                
                if ($filenames):
                    $this->insertImages($filenames, $posts['uuid'], 'admins', 'edit');
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

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EDIT_ADMIN', 'admins', sprintf(lang('backend/admins.audits.editAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

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
     * Verifica in modo euristico se l'anagrafica o i permessi dell'utente hanno subito reali mutazioni.
     * 
     * Oltre a chiamare il metodo base (hasDataChanged), ricalcola la matrice dei permessi attuali 
     * dell'utente fondendo le regole del suo gruppo con le sue eccezioni a database. 
     * Ordina e confronta questa matrice con i permessi inviati tramite form: restituisce true al primo disallineamento.
     *
     * @param array $posts Dati provenienti dal form
     * @param object $original L'oggetto rappresentativo dello stato attuale nel database
     * @return bool True se è necessaria la query di UPDATE, false altrimenti
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
     * Elimina definitivamente e irrevocabilmente l'Amministratore dal sistema (Transazionale).
     * 
     * Scudo di Sicurezza: vieta sempre e comunque la cancellazione del record designato come Superadmin. 
     * Rimuove i record dalla tabella `admins` e `images`, fa il commit, e solo a quel punto invoca 
     * il file system per distruggere fisicamente la cartella con le foto profilo collegate a quell'UUID.
     *
     * @param array $posts Dataset contenente l'UUID dell'utente da eliminare
     * @return array Esito dell'operazione
     */
    public function hardDelete(array $posts): array
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

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
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
                log_message('error', lang('backend/admins.messages.hardDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.hardDeleteError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('HARD_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.hardDeleteAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            \App\Libraries\ImageFileSystemService::removeAllImages('admins', $posts['uuid']);

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.hardDeleteSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.hardDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.hardDeleteError')];

        }
    }

    /**
     * Cestina logicamente l'Amministratore sospendendone istantaneamente gli accessi (Transazionale).
     * 
     * Scudo di Sicurezza: protetto il Superadmin. L'operazione inserisce il timestamp `deleted_at`, 
     * e per evitare che l'email dell'utente cestinato entri in conflitto UNIQUE bloccando registrazioni future, 
     * la offusca concatenandola con un marcatore temporale (es. '.deleted.16912345'). 
     * Elimina inoltre tutti i token di sessione e i codici 2FA per chiudere immediatamente gli accessi aperti.
     *
     * @param array $posts Dataset contenente l'UUID
     * @return array Esito dell'operazione
     */
    public function softDelete(array $posts): array
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $this->db->transBegin();

            /* Generazione marcatore per offuscare l'email ed evitare conflitti UNIQUE */
            $deletedMarker = '.deleted.' . time();

            /* Cestinamento e offuscamento email */
            $sql = "update admins set email = CONCAT(email, ?), deleted_at = NOW() where uuid = ?";
            $this->db->query($sql, [$deletedMarker, $posts['uuid']]);

            /* Revoca immediata degli accessi attivi (disconnessione forzata) */
            $this->db->query("delete from admins_tokens where admin_uuid = ?", [$posts['uuid']]);
            $this->db->query("delete from admins_2fa_codes where admin_uuid = ?", [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.softDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.softDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('SOFT_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.softDeleteAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            /* Nota: usa una stringa di lingua dedicata come softDelSuccess se l'hai creata */
            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.softDeleteSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.softDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.softDeleteError')];

        }
    }

    /**
     * Ripristina un Amministratore precedentemente spostato nel cestino (Transazionale).
     * 
     * Tenta di rimuovere il marcatore '.deleted.' dall'email originale. Controlla a database 
     * se, durante il periodo di cestinamento, un altro utente attivo si è registrato con quella stessa email. 
     * - Se NON c'è conflitto: ripristina l'email originale e azzera il `deleted_at`.
     * - Se c'è CONFLITTO: assegna all'utente un'email fittizia (es. '16912345@temp.local'), 
     *   lo ripristina bloccandolo (`status = 0`) e avvisa l'operatore tramite un messaggio specifico.
     *
     * @param array $posts Dataset contenente l'UUID
     * @return array Esito dell'operazione e messaggio contestuale
     */
    public function restoreDelete(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            /* Recupero diretto dal DB per aggirare eventuali filtri sui record attivi */
            $sql = "select * from admins where uuid = ?";
            $row = $this->db->query($sql, [$posts['uuid']])->getRow();

            if (empty($row)):
                return ['result' => false, 'message' => lang('backend/admins.messages.notFound')];
            endif;

            /* Ripulisco l'email dal marcatore generato durante il soft delete */
            if (strpos($row->email, '.deleted.') !== false):
                $cleanEmail = explode('.deleted.', $row->email)[0];
            else:
                $cleanEmail = $row->email;
            endif;

            /* Scudo di sicurezza: verifico se nel frattempo l'email è stata presa da un utente attivo */
            $sqlCheck = "select uuid from admins where email = ? and deleted_at IS NULL";
            $emailExists = $this->db->query($sqlCheck, [$cleanEmail])->getRow();

            $this->db->transBegin();

            if ( ! empty($emailExists)):
                
                /* CONFLITTO: Ripristino forzando a inattivo e mantenendo la mail offuscata */
                $tempEmail = time() . '@temp.local';
                $sqlUpdate = "update admins set email = ?, status = 0, deleted_at = NULL where uuid = ?";
                $this->db->query($sqlUpdate, [$tempEmail, $posts['uuid']]);
                
                $message = lang('backend/admins.messages.restoreDeleteConflict'); 
                
            else:
                
                /* NESSUN CONFLITTO: Ripristino dell'utente e della sua email originale */
                $sqlUpdate = "update admins set email = ?, deleted_at = NULL where uuid = ?";
                $this->db->query($sqlUpdate, [$cleanEmail, $posts['uuid']]);
                
                $message = sprintf(lang('backend/admins.messages.restoreDeleteSuccess'), esc($row->firstname), esc($row->lastname));
                
            endif;

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.restoreDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.restoreDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESTORE_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.restoreDeleteAdmin'), esc($row->firstname), esc($row->lastname)), $currentAdmin);

            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.restoreDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.restoreDeleteError')];

        }
    }

    /**
     * Avvia la procedura per forzare il recupero credenziali dell'Amministratore (Transazionale).
     * 
     * Scudi attivi: bloccato su cestinati e superadmin. Azzera i vecchi token di attivazione, 
     * genera un nuovo token crittografico, lo salva a database e invia in tempo reale l'email 
     * con il link di ripristino per impostare la nuova password.
     *
     * @param array $posts Dataset contenente l'UUID
     * @param \CodeIgniter\HTTP\IncomingRequest $request Richiesta HTTP per logging IP/Agent
     * @return array Esito dell'operazione (o avviso in caso di invio email fallito)
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

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
            $this->db->query($sql, [date('Y-m-d H:i:s'), $posts['uuid']]);

            /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$posts['uuid'], 'activation']);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$posts['uuid'], $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d_H-i-s')]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.resetPasswordError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESET_PASSWORD_ADMIN', 'admins', sprintf(lang('backend/admins.audits.resetPasswordAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

        } catch (\Throwable $e) {

            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.resetPasswordError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];

        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $template = 'emailResetPasswordAdminPartial';
        $subjectLangKey = 'backend/email.admins.resetPassword.subjectResetPasswordEmail';

        /* Chiamata al metodo con i nuovi parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $this->module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    /**
     * Alterna lo stato di attivazione dell'Amministratore (Attivo <-> Sospeso).
     * 
     * Scudi attivi: bloccato su cestinati e superadmin. La logica matematica legge lo stato corrente 
     * e lo inverte (da 0 a 1, o da 1 a 0). Se lo stato passa a Sospeso (0), valorizza la colonna 
     * `suspended_at` con il timestamp attuale; altrimenti la resetta a NULL. L'operazione è transazionale.
     *
     * @param array $posts Dataset contenente l'UUID
     * @return array Esito con il record utente aggiornato
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
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

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_STATUS_ADMIN', 'admins', sprintf(lang('backend/admins.audits.changeStatusAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.changeStatusSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];

        }
    }

    /**
     * Abilita o revoca un singolo permesso in modalità "switch" direttamente dalla scheda utente (Transazionale).
     * 
     * L'algoritmo valuta se il permesso richiesto è nativo del gruppo dell'utente:
     * - Se SÌ: un click lo disabilita creando un'eccezione negativa (`allow = 0`). Un secondo click rimuove l'eccezione ripristinando il default.
     * - Se NO: un click lo abilita creando un'eccezione positiva (`allow = 1`). Un secondo click rimuove l'eccezione ripristinando il blocco.
     * Questo sistema mantiene la tabella delle eccezioni pulita, salvando solo le deviazioni reali dal ruolo assegnato.
     *
     * @param array $posts Dataset contenente UUID e il codice stringa del permesso
     * @return array Esito con i dati dell'amministratore per aggiornare l'interfaccia
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
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

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_PERMISSION_ADMIN', 'admins', sprintf(lang('backend/admins.audits.changePermissionAdmin'), esc($admin->firstname), esc($admin->lastname)), $currentAdmin);

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
     * Forza la revoca e l'eliminazione di un singolo token di sessione dell'Amministratore.
     * 
     * Scudi attivi: bloccato su cestinati e superadmin. Se il token corrisponde a una sessione o cookie (login), 
     * registra l'interruzione nel log impostando la causale a 'banned'. Successivamente rimuove 
     * la riga dalla tabella `admins_tokens` disconnettendo all'istante l'utente da quel dispositivo.
     *
     * @param array $posts Dataset contenente ID del token e UUID dell'utente
     * @return array Esito dell'operazione
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            /* 1. Recupero il token per leggere last_activity */
            $tokenSql = "select id, last_activity, token_type from admins_tokens where admin_uuid = ? and id = ?";
            $tokenRow = $this->db->query($tokenSql, [$posts['uuid'], $posts['id']])->getRow();

            if ($tokenRow):
                /* 2. Aggiorno il log registrando la forzatura (banned) */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;
            endif;

            /* 3. Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$posts['uuid'], $posts['id']]);

            if($this->db->affectedRows() > 0):

                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN_ADMIN', 'admins', sprintf(lang('backend/admins.audits.deleteTokenAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/admins.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        }
    }

    /**
     * Metodo di utilità per la rimozione totale di tutte le eccezioni di permesso associate a un utente.
     * 
     * Utilizzato principalmente durante l'operazione di `edit` (modifica) per ripulire la matrice utente 
     * prima di inserire il nuovo set di eccezioni calcolato rispetto al gruppo.
     *
     * @param string $admin_uuid L'identificativo univoco
     */
    public function deletePermissions($admin_uuid)
    {
        $sql = "delete from admins_permissions where admin_uuid = ?";
        $this->db->query($sql, [$admin_uuid]);
    }
}