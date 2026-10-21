<?php declare(strict_types = 1);

namespace Modules\Backend\Brands\Models;

use App\Models\Backend\BackendModel;
use App\Interfaces\ImportValidationInterface;
use App\Interfaces\ExportValidationInterface;

/**
 * Modello principale per la gestione dei brand.
 * 
 * Estende il BackendModel. Incapsula la business logic, le query e le transazioni CRUD 
 * per l'entità "brands", inclusi caricamento immagini, stato ed eliminazioni logiche o definitive.
 */
class BrandsModel extends BackendModel implements ImportValidationInterface, ExportValidationInterface
{
    /**
     * Nome identificativo del modulo (tabella principale 'brands') usato dal motore genitore.
     * 
     * @var string 
     */
    protected ?string $module = 'brands';

    /**
     * Flag strutturale che abilita la gestione del "Cestino" (Soft Delete). 
     * Se impostato a true, le query di estrazione nasconderanno di default i record con 'deleted_at' valorizzato.
     * 
     * @var bool 
     */
    protected bool $hasSoftDelete = true;

    /**
     * Whitelist dei campi consentiti durante la visualizzazione dell'elenco brand.
     * Include i parametri strutturali per la paginazione, l'ordinamento e la gestione della vista cestino.
     *
     * @var array
     */
    protected array $showAllAllowedFields = ['id', 'column', 'order', 'page', 'rows', 'searchFields', 'searchDates', 'trash_filter'];

    /**
     * Whitelist dei campi consentiti per la creazione di un brand.
     *
     * @var array
     */
    protected array $addAllowedFields = ['brand', 'note', 'status', 'images'];

    /**
     * Whitelist dei campi consentiti per la modifica di un brand esistente.
     *
     * @var array
     */
    protected array $editAllowedFields = ['uuid', 'brand', 'note', 'status', 'images'];

    /**
     * Whitelist dei campi consentiti per l'eliminazione di un brand.
     * Restringe il payload al solo UUID per prevenire cancellazioni multiple o accidentali.
     *
     * @var array
     */
    protected array $delAllowedFields = ['uuid'];

    /**
     * Whitelist dei campi consentiti per l'operazione di cambio stato rapido (es. da attivo a sospeso).
     *
     * @var array
     */
    protected array $changeStatusAllowedFields = ['uuid'];

    /**
     * Whitelist delle colonne anagrafiche e di contatto su cui è attiva la ricerca testuale (LIKE).
     * Limita il motore di ricerca ai soli campi pertinenti per tutelare le performance e la sicurezza.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['brand', 'status']; 

    /**
     * Whitelist delle colonne temporali su cui è possibile effettuare filtri per intervallo di date (Da - A).
     *
     * @var array
     */
    protected array $showAllSearchAllowedDates = ['created_at', 'updated_at'];

    /**
     * Array combinato che indica quali colonne sono esposte all'ordinamento  
     * e alla ricerca testuale o temporale, validando rigorosamente l'input dell'operatore.
     * 
     * @var array 
     */
    protected array $allowedOrderColumns = ['id', 'brand', 'status']; 

    /**
     * Elenco dei campi anagrafici monitorati per rilevare cambiamenti effettivi 
     * prima di lanciare una query di UPDATE a database.
     * 
     * @var array 
     */
    protected array $toCompare = ['brand', 'note', 'status'];

    /**
     * Query principale per il caricamento della griglia brand. 
     * Include sub-query ottimizzate per calcolare dinamicamente il numero di immagini associate 
     * e recuperare l'eventuale immagine di copertina.
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select id, uuid, brand, status, created_at, updated_at, deleted_at, restored_at,
                                        (select images.filename from images where images.entity_uuid = brands.uuid and images.entity = 'brands' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = brands.uuid and images.entity = 'brands') as images_num 
                                        from brands where 1 = 1";

    /**
     * Query utilizzata per estrarre un singolo brand tramite UUID.
     * 
     * @var string 
     */
    protected ?string $getUUIDQuery = "select 
                                            id, 
                                            uuid, 
                                            brand, 
                                            status, 
                                            note, 
                                            created_at, 
                                            updated_at, 
                                            deleted_at,
                                            restored_at
                                        from brands 
                                        where uuid = ? limit 1";

    /**
     * Query SQL essenziale per calcolare il numero totale dei brand, 
     * necessaria al frontend per creare i bottoni della paginazione.
     *
     * @var string 
     */
    protected ?string $getNumRowsQuery = 'select count(*) as count from brands where 1 = 1';

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
     * Rimuove i file creati durante un upload che non ha raggiunto il commit.
     *
     * @param array $filenames Nomi dei file caricati nella transazione corrente
     * @param string $uuid Identificativo del proprietario delle immagini
     */
    protected function cleanupUploadedImages(array $filenames, string $uuid): void
    {
        foreach ($filenames as $filename):
            \App\Libraries\ImageFileSystemService::removeSingleImage('brands', $uuid, $filename);
        endforeach;
    }

    /**
     * Verifica se il brand è associato ad almeno un prodotto.
     *
     * @param string $uuid Identificativo del brand
     * @return bool True quando esiste almeno un'associazione
     */
    protected function hasProducts(string $uuid): bool
    {
        $sql = "select 1
                from brands_to_products
                inner join brands on brands.id = brands_to_products.brand_id
                where brands.uuid = ?
                limit 1";

        return $this->db->query($sql, [$uuid])->getRow() !== null;
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
     * Regole di validazione per i filtri di ricerca dei brand.
     * 
     * Verifica che brand, stato e intervalli temporali rispettino i formati previsti.
     *
     * @return array Regole per i filtri
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.brand' => [
                'label' => lang('backend/brands.labels.brand'), 
                'rules' => ['permit_empty', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'], 
            ],
            'searchFields.status' => [
                'label' => lang('backend/brands.labels.status'), 
                'rules' => ['permit_empty', 'in_list[0,1]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/brands.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/brands.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.updated_at-from' => [
                'label' => lang('backend/brands.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.updated_at-to' => [
                'label' => lang('backend/brands.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /**
     * Regole di validazione per la creazione di un brand.
     * 
     * Verifica la correttezza formale del brand, ne impone l'univocità
     * e include il controllo sulle immagini caricate.
     *
     * @return array Regole strutturate
     */
    public function addValidationRules(): array
    {
        return [
            'brand' => [
                'label' => lang('backend/brands.labels.brand'),
                'rules' => ['required', 'trim', 'min_length[2]', 'is_unique[brands.brand]', 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/brands.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/brands.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/brands.errors.safeText')
                ]
            ],
            'images' => [
                'label' => lang('backend/brands.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Regole di validazione per la modifica di un brand.
     * 
     * Aggiorna i controlli di univocità configurandoli per ignorare il record corrente.
     *
     * @param array $posts I dati inviati dal form (serve a recuperare l'UUID corrente)
     * @return array Regole strutturate
     */
    public function editValidationRules(array $posts): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', "is_unique[brands.uuid,uuid,{$posts['uuid']}]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'brand' => [
                'label' => lang('backend/brands.labels.brand'),
                'rules' => ['required', 'trim', 'min_length[2]', "is_unique[brands.brand,uuid,{$posts['uuid']}]", 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/brands.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/brands.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/brands.errors.safeText')
                ]
            ],
            'images' => [
                'label' => lang('backend/brands.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Whitelist dei campi fisici importabili dal CRUD brand.
     * Esclude esplicitamente colonne di sicurezza e di stato gestite dal backend.
     *
     * @return list<string>
     */
    public function importAllowedFields(): array
    {
        return ['uuid', 'brand', 'status', 'note'];
    }

    /**
     * Whitelist dei campi esportabili dal CRUD brand.
     *
     * @return list<string>
     */
    public function exportAllowedFields(): array
    {
        return ['uuid', 'brand', 'status', 'note'];
    }

    /**
     * Regole applicative dedicate all'inserimento di brand tramite ImportModel.
     *
     * Restano separate dalle regole del form CRUD perché l'importazione non gestisce
     * immagini, permessi o altri dati di interfaccia. Le regole vengono sovrapposte
     * al fallback strutturale generato dallo schema della tabella.
     *
     * @return array Regole specifiche per i record brands pianificati come insert
     */
    public function importAddValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['permit_empty', 'is_unique[brands.uuid]', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'brand' => [
                'label' => lang('backend/brands.labels.brand'),
                'rules' => ['required', 'trim', 'min_length[2]', 'is_unique[brands.brand]', 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/brands.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/brands.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/brands.errors.safeText')
                ]
            ],
        ];
    }

    /**
     * Regole applicative dedicate alla modifica di brand tramite ImportModel.
     *
     * ImportModel applica queste regole solo alle colonne presenti nel CSV, permettendo
     * aggiornamenti parziali. L'UUID resta il riferimento del record e le regole specifiche
     * sostituiscono, per i campi interessati, il fallback derivato dallo schema SQL.
     *
     * @param array $data Dati di contesto del record, incluso l'UUID corrente
     * @return array Regole specifiche per i record brands pianificati come update
     */
    public function importEditValidationRules(array $data): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'brand' => [
                'label' => lang('backend/brands.labels.brand'),
                'rules' => ['required', 'trim', 'min_length[2]', "is_unique[brands.brand,uuid,{$data['uuid']}]", 'max_length[30]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/brands.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/brands.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/brands.errors.safeText')
                ]
            ],
        ];
    }

    /**
     * Vincoli di dominio aggiuntivi per l'import CRUD dei brand.
     *
     * L'importazione mantiene la protezione dei record cestinati.
     *
     * @return list<string>
     */
    public function importBusinessValidationErrors(string $action, array $data, ?array $currentRow): array
    {
        if ($action !== 'update' || $currentRow === null):
            return [];
        endif;

        if (($currentRow['deleted_at'] ?? null) !== null):
            return [lang('backend/brands.messages.cannotModifyDeleted')];
        endif;

        return [];
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
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/brands.errors.uuid'),
                    'regex_match' => lang('backend/brands.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Regole di validazione per il cambio di stato (Attivo/Inattivo).
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
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/brands.errors.uuid'),
                    'regex_match' => lang('backend/brands.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/brands.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
                'errors' => [
                    'in_list' => lang('backend/brands.errors.context'),
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
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/brands.errors.uuid'),
                    'regex_match' => lang('backend/brands.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/brands.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('backend/brands.errors.context'),
                    'in_list' => lang('backend/brands.errors.context')
                ]
            ],
        ];
    }

    /**
     * Regole di validazione per l'estrazione dei metadati del brand.
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
                'label' => lang('backend/brands.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('backend/brands.errors.uuid'),
                    'regex_match' => lang('backend/brands.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Crea un nuovo brand in modo transazionale.
     * 
     * Genera l'UUID, inserisce i dati e gestisce le eventuali immagini.
     * In caso di fallimento il rollback annulla le operazioni sul database.
     *
     * @param array $posts Dataset anagrafico filtrato dalla request
     * @return array Esito strutturato dell'operazione
     */
    public function add(array $posts): array
    {
        $uuid = '';

        try 
        {
            /* Filtro campi post ammessi */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);

            /* Genero uuid */
            $uuid = $this->generateUUID();

			/* 1. Avvio la transazione PRIMA di eseguire qualsiasi query */
            $this->db->transBegin();

            /* Inserimento dati nella tabella principale */
            $sql = "insert into brands (uuid, brand, status, note, created_at) values (?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $posts['brand'], $posts['status'], (trim($posts['note']) !== '' ? $posts['note'] : null), date('Y-m-d H:i:s')]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                
                $uploadService = $this->getUploadService();
                    
                $filenames = $uploadService->doUpload($posts['images'], 'brands', $uuid);
                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Brands non completato.');
                endif;

                $this->insertImages($filenames, $uuid, 'brands', 'add');
            endif;

            /* 3. Verifico eventuali errori SQL prima di fare il commit */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                \App\Libraries\ImageFileSystemService::removeAllImages('brands', $uuid);

                log_message('error', lang('backend/brands.messages.addError'));
                return ['result' => false, 'message' => lang('backend/brands.messages.addError')];
            endif;

            /* Recupero dati del brand appena inserito */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                $this->db->transRollback();
                \App\Libraries\ImageFileSystemService::removeAllImages('brands', $uuid);
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Se le query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('ADD_BRANDS', 'brands', sprintf(lang('backend/brands.audits.addBrand'), esc($data['row']->brand)), $currentAdmin);

            $message = sprintf(lang('backend/brands.messages.addSuccess'), esc($data['row']->brand));
            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            if ($uuid !== ''):
                \App\Libraries\ImageFileSystemService::removeAllImages('brands', $uuid);
            endif;

			log_message('error', lang('backend/brands.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/brands.messages.addError')];
        }
    }

    /**
     * Aggiorna un brand esistente in modo transazionale.
     * 
     * Blocca i record cestinati, evita aggiornamenti inutili e gestisce le eventuali nuove immagini.
     *
     * @param array $posts Dataset aggiornato filtrato dalla request
     * @return array Esito strutturato con il record aggiornato
     */
    public function edit(array $posts): array
    {
        $uploadedFilenames = [];
        $uploadUuid = '';

        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

            $uploadUuid = $posts['uuid'];

            /* Recupero i dati del brand prima dell'aggiornamento */
            $brand = $this->getByUUID($posts['uuid']);

            if($brand['result'] === false):
                return ['result' => false, 'message' => $brand['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($brand['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/brands.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Se non è stato effettuato alcun cambio sui dati gestiti, interrompiamo subito */
            if( ! $this->hasBrandChanged($posts, $brand['row'])):
                return ['result' => false, 'message' => lang('backend/brands.messages.noDataChanged')];
            endif;

            $updated_at = date('Y-m-d H:i:s');

            $this->db->transBegin();

            /* Aggiorno la tabella principale */
            $sql = 'update brands set brand = ?, status = ?, note = ?, updated_at = ? where uuid = ?';
            $this->db->query($sql, [$posts['brand'], $posts['status'], (trim($posts['note']) !== '' ? $posts['note'] : null), $updated_at, $posts['uuid']]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):

                $uploadService = $this->getUploadService();

                $filenames = $uploadService->doUpload($posts['images'], 'brands', $posts['uuid']);
                $uploadedFilenames = $filenames ?: [];

                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Brands non completato.');
                endif;

                $this->insertImages($filenames, $posts['uuid'], 'brands', 'edit');
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                $this->cleanupUploadedImages($uploadedFilenames, $uploadUuid);
                log_message('error', lang('backend/brands.messages.editError'));
                return ['result' => false, 'message' => lang('backend/brands.messages.editError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dell'oggetto in memoria da restituire alla vista */
            $brand['row']->brand = $posts['brand'];
            $brand['row']->status = $posts['status'];
            $brand['row']->note = $posts['note'];
            $brand['row']->updated_at = $updated_at;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EDIT_BRANDS', 'brands', sprintf(lang('backend/brands.audits.editBrand'), esc($brand['row']->brand)), $currentAdmin);

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/brands.messages.editSuccess'), esc($posts['brand'])), 
                'row'     => $brand['row']
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uploadUuid);
            log_message('error', lang('backend/brands.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
            return ['result' => false, 'message' => lang('backend/brands.messages.editError')];
        }
    }

    /**
     * Verifica se i dati del brand o le immagini hanno subito modifiche.
     *
     * @param array $posts Dati provenienti dal form
     * @param object $original L'oggetto rappresentativo dello stato attuale nel database
     * @return bool True se è necessaria la query di UPDATE, false altrimenti
     */
    public function hasBrandChanged(array $posts, object $original): bool
    {
        /* Controlla i campi base e i file */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        return false;
    }

    /**
     * Elimina definitivamente un brand non associato a prodotti.
     * 
     * Rimuove i record da `brands` e `images`, quindi elimina la relativa cartella immagini.
     *
     * @param array $posts Dataset contenente l'UUID del brand da eliminare
     * @return array Esito dell'operazione
     */
    public function hardDelete(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            /* Recupero i dati del brand prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

			if ($data['row']->deleted_at === null):
				return ['result' => false, 'message' => lang('backend/brands.messages.hardDeleteRequiresTrash')];
			endif;

            if ($this->hasProducts($posts['uuid'])):
                return ['result' => false, 'message' => lang('backend/brands.messages.deleteAssociated')];
            endif;

            $this->db->transBegin();

            /* Eliminazione brand */
            $sql = "delete from brands where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            /* Eliminazione immagini dal database */
            $sql = "delete from images where entity = 'brands' and entity_uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/brands.messages.hardDeleteError'));

                return ['result' => false, 'message' => lang('backend/brands.messages.hardDeleteError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('HARD_DELETE_BRANDS', 'brands', sprintf(lang('backend/brands.audits.hardDeleteBrand'), esc($data['row']->brand)), $currentAdmin);

			if ( ! \App\Libraries\ImageFileSystemService::removeAllImages('brands', $posts['uuid'])):
				log_message('error', lang('backend/brands.messages.hardDeleteImagesError'));
				return [
					'result' => true,
					'cleanup' => false,
					'message' => sprintf(lang('backend/brands.messages.hardDeleteSuccess'), esc($data['row']->brand)) . ' ' . lang('backend/brands.messages.hardDeleteImagesError')
				];
			endif;

			return ['result' => true, 'cleanup' => true, 'message' => sprintf(lang('backend/brands.messages.hardDeleteSuccess'), esc($data['row']->brand))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/brands.messages.hardDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/brands.messages.hardDeleteError')];

        }
    }

    /**
     * Cestina logicamente un brand non associato a prodotti.
     * 
     * L'operazione valorizza `deleted_at` senza eliminare fisicamente il record.
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

            /* Recupero i dati del brand prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/brands.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            if ($this->hasProducts($posts['uuid'])):
                return ['result' => false, 'message' => lang('backend/brands.messages.deleteAssociated')];
            endif;

            $this->db->transBegin();

            /* Cestinamento */
            $sql = "update brands set deleted_at = now() where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/brands.messages.softDeleteError'));

                return ['result' => false, 'message' => lang('backend/brands.messages.softDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('SOFT_DELETE_BRANDS', 'brands', sprintf(lang('backend/brands.audits.softDeleteBrand'), esc($data['row']->brand)), $currentAdmin);

            /* Nota: usa una stringa di lingua dedicata come softDelSuccess se l'hai creata */
            return ['result' => true, 'message' => sprintf(lang('backend/brands.messages.softDeleteSuccess'), esc($data['row']->brand))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/brands.messages.softDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/brands.messages.softDeleteError')];

        }
    }

    /**
     * Ripristina un brand precedentemente spostato nel cestino.
     * 
     * Azzera `deleted_at` e valorizza `restored_at`.
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
            $sql = "select * from brands where uuid = ?";
            $row = $this->db->query($sql, [$posts['uuid']])->getRow();

			if (empty($row)):
				return ['result' => false, 'message' => lang('backend/brands.messages.notFound')];
			endif;

			if ($row->deleted_at === null):
				return ['result' => false, 'message' => lang('backend/brands.messages.restoreRequiresTrash')];
			endif;

            $this->db->transBegin();

            /* Ripristino del brand */
            $sqlUpdate = "update brands set deleted_at = null, restored_at = ? where uuid = ?";
            $this->db->query($sqlUpdate, [date('Y-m-d H:i:s'), $posts['uuid']]);
            
            $message = sprintf(lang('backend/brands.messages.restoreDeleteSuccess'), esc($row->brand));

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/brands.messages.restoreDeleteError'));

                return ['result' => false, 'message' => lang('backend/brands.messages.restoreDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESTORE_DELETE_BRANDS', 'brands', sprintf(lang('backend/brands.audits.restoreDeleteBrand'), esc($row->brand)), $currentAdmin);

            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/brands.messages.restoreDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/brands.messages.restoreDeleteError')];

        }
    }

    /**
     * Alterna lo stato del brand.
     * 
     * Il cambio è bloccato sui record cestinati. La logica legge lo stato corrente,
     * lo inverte e aggiorna il timestamp di modifica in modo transazionale.
     *
     * @param array $posts Dataset contenente l'UUID
     * @return array Esito con il brand aggiornato
     */
    public function changeStatus(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);

            /* Recupero i dati del brand */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/brands.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            $currentStatus = (int) $data['row']->status;

            $newStatus = null;

            /* Converte il risultato in un intero (0 o 1) per MySQL */
            if($currentStatus === 0):

                $newStatus = 1;
                $data['row']->status = 1;

            elseif($currentStatus === 1):

                $newStatus = 0;
                $data['row']->status = 0;

            endif;

            $updatedAt = date('Y-m-d H:i:s');
            $data['row']->updated_at = $updatedAt;

            $this->db->transBegin();

            /* Cambio stato brand */
            $sql = "update brands set status = ?, updated_at = ? where uuid = ?";
            $this->db->query($sql, [$newStatus, $updatedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/brands.messages.changeStatusError'));

                return ['result' => false, 'message' => lang('backend/brands.messages.changeStatusError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_STATUS_BRANDS', 'brands', sprintf(lang('backend/brands.audits.changeStatusBrand'), esc($data['row']->brand)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/brands.messages.changeStatusSuccess'), esc($data['row']->brand)), 'brand' => $data['row']];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/brands.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/brands.messages.changeStatusError')];

        }
    }
}
