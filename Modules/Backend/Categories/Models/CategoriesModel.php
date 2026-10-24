<?php declare(strict_types = 1);

namespace Modules\Backend\Categories\Models;

use App\Models\Backend\BackendModel;
use App\Interfaces\ImportValidationInterface;
use App\Interfaces\ExportValidationInterface;

/**
 * Modello principale per la gestione dei category.
 * 
 * Estende il BackendModel. Incapsula la business logic, le query e le transazioni CRUD 
 * per l'entità "categories", inclusi caricamento immagini, stato ed eliminazioni logiche o definitive.
 */
class CategoriesModel extends BackendModel implements ImportValidationInterface, ExportValidationInterface
{
    /**
     * Nome identificativo del modulo (tabella principale 'categories') usato dal motore genitore.
     * 
     * @var string 
     */
    protected ?string $module = 'categories';

    /**
     * Flag strutturale che abilita la gestione del "Cestino" (Soft Delete). 
     * Se impostato a true, le query di estrazione nasconderanno di default i record con 'deleted_at' valorizzato.
     * 
     * @var bool 
     */
    protected bool $hasSoftDelete = true;

    protected bool $hasStatusFilter = true;

    /**
     * Whitelist dei campi consentiti durante la visualizzazione dell'elenco category.
     * Include i parametri strutturali per la paginazione, l'ordinamento e la gestione della vista cestino.
     *
     * @var array
     */
    protected array $showAllAllowedFields = ['id', 'column', 'order', 'page', 'rows', 'searchFields', 'searchDates', 'trash_filter'];

    /**
     * Whitelist dei campi consentiti per la creazione di un category.
     *
     * @var array
     */
    protected array $addAllowedFields = ['parent_id', 'category', 'note', 'status', 'images'];

    /**
     * Whitelist dei campi consentiti per la modifica di un category esistente.
     *
     * @var array
     */
    protected array $editAllowedFields = ['uuid', 'parent_id', 'category', 'note', 'status', 'images'];

    /**
     * Whitelist dei campi consentiti per l'eliminazione di un category.
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
    protected array $showAllSearchAllowedFields = ['category', 'status']; 

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
    protected array $allowedOrderColumns = ['id', 'category', 'status']; 

    /**
     * Elenco dei campi anagrafici monitorati per rilevare cambiamenti effettivi 
     * prima di lanciare una query di UPDATE a database.
     * 
     * @var array 
     */
    protected array $toCompare = ['parent_id', 'category', 'note', 'status'];

    /**
     * Query principale per il caricamento della griglia category. 
     * Include sub-query ottimizzate per calcolare dinamicamente il numero di immagini associate 
     * e recuperare l'eventuale immagine di copertina.
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select id, uuid, parent_id, category, status, created_at, updated_at, deleted_at, restored_at,
                                        (select parent.category from categories as parent where parent.id = categories.parent_id limit 1) as parent_category,
                                        (select images.filename from images where images.entity_uuid = categories.uuid and images.entity = 'categories' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = categories.uuid and images.entity = 'categories') as images_num 
                                        from categories where 1 = 1";

    /**
     * Query utilizzata per estrarre un singolo category tramite UUID.
     * 
     * @var string 
     */
    protected ?string $getUUIDQuery = "select 
                                            id, 
                                            uuid, 
                                            parent_id,
                                            category, 
                                            (select parent.category from categories as parent where parent.id = categories.parent_id limit 1) as parent_category,
                                            status, 
                                            note, 
                                            created_at, 
                                            updated_at, 
                                            deleted_at,
                                            restored_at
                                        from categories 
                                        where uuid = ? limit 1";

    /**
     * Query SQL essenziale per calcolare il numero totale dei category, 
     * necessaria al frontend per creare i bottoni della paginazione.
     *
     * @var string 
     */
    protected ?string $getNumRowsQuery = 'select count(*) as count from categories where 1 = 1';

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
     * Conta attivi, sospesi, eliminati e totale, senza ricerca o paginazione.
     *
     * @return array{active: int, suspended: int, trashed: int, all: int}
     */
    public function getTrashCounts(): array
    {
        $query = $this->db->query('select count(*) as total,
                                        count(case when deleted_at is null and status = 1 then 1 end) as active,
                                        count(case when deleted_at is not null then 1 end) as trashed,
                                        count(case when deleted_at is null and status = 0 then 1 end) as suspended
                                 from categories');

        if ($query === false || ($counts = $query->getRowArray()) === null):
            throw new \RuntimeException(lang('backend/global.messages.getDataError'));
        endif;

        $total = (int) $counts['total'];
        $trashed = (int) $counts['trashed'];
        $suspended = (int) $counts['suspended'];

        return ['active' => (int) $counts['active'], 'suspended' => $suspended, 'trashed' => $trashed, 'all' => $total];
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
            \App\Libraries\ImageFileSystemService::removeSingleImage('categories', $uuid, $filename);
        endforeach;
    }

    /**
     * Verifica se il category è associato ad almeno un prodotto.
     *
     * @param string $uuid Identificativo del category
     * @return bool True quando esiste almeno un'associazione
     */
    protected function hasProducts(string $uuid, bool $lockRows = false): bool
    {
        $sql = "select 1
                from categories_to_products
                inner join categories on categories.id = categories_to_products.category_id
                where categories.uuid = ?
                limit 1" . ($lockRows ? ' for update' : '');

        return $this->db->query($sql, [$uuid])->getRow() !== null;
    }

    /**
     * Verifica se la categoria contiene sottocategorie.
     */
    protected function hasChildren(string $uuid, bool $lockRows = false): bool
    {
        $sql = "select 1
                from categories as child
                inner join categories as parent on parent.id = child.parent_id
                where parent.uuid = ?
                limit 1" . ($lockRows ? ' for update' : '');

        return $this->db->query($sql, [$uuid])->getRow() !== null;
    }

    /** La sospensione richiede una categoria priva di qualsiasi contenuto, anche cestinato. */
    protected function suspensionError(string $uuid, bool $lockRows = false): ?string
    {
        if ($this->hasProducts($uuid, $lockRows)):
            return lang('backend/categories.messages.suspendAssociated');
        endif;

        return $this->hasChildren($uuid, $lockRows) ? lang('backend/categories.messages.suspendHasChildren') : null;
    }

    /**
     * Valida la categoria padre e impedisce cicli o strutture miste prodotti/sottocategorie.
     */
    protected function parentAssignmentError(?int $parentId, ?string $currentUuid = null, bool $lockRows = false): ?string
    {
        if ($parentId === null):
            return null;
        endif;

        $parentSql = "select id, parent_id, status, deleted_at from categories where id = ? limit 1" . ($lockRows ? ' for update' : '');
        $parent = $this->db->query($parentSql, [$parentId])->getRow();

        if ($parent === null || $parent->deleted_at !== null):
            return lang('backend/categories.messages.invalidParent');
        endif;

        if ((int) $parent->status !== 1):
            return lang('backend/categories.messages.parentSuspended');
        endif;

        $productsSql = "select 1 from categories_to_products where category_id = ? limit 1" . ($lockRows ? ' for update' : '');
        if ($this->db->query($productsSql, [$parentId])->getRow() !== null):
            return lang('backend/categories.messages.parentHasProducts');
        endif;

        if ($currentUuid === null):
            return null;
        endif;

        $currentSql = "select id from categories where uuid = ? limit 1" . ($lockRows ? ' for update' : '');
        $current = $this->db->query($currentSql, [$currentUuid])->getRow();
        if ($current === null):
            return lang('backend/categories.messages.notFound');
        endif;

        $currentId = (int) $current->id;
        $cursor = $parent;

        while ($cursor !== null):
            if ((int) $cursor->id === $currentId):
                return lang('backend/categories.messages.circularParent');
            endif;

            if ($cursor->parent_id === null):
                break;
            endif;

            $cursor = $this->db->query($parentSql, [(int) $cursor->parent_id])->getRow();
        endwhile;

        return null;
    }

    /**
     * Costruisce la gerarchia annidata e i percorsi completi delle categorie.
     *
     * I nodi senza un padre disponibile vengono mantenuti al primo livello, mentre
     * la protezione sugli antenati impedisce ricorsioni infinite in presenza di dati anomali.
     *
     * @param array<int, object> $rows Categorie estratte dal database
     * @return array{tree: array<int, array<string, mixed>>, pathsById: array<int, string>, pathsByUuid: array<string, string>}
     */
    private function buildCategoryHierarchy(array $rows): array
    {
        $rowsById = [];
        $childrenByParent = [];

        foreach ($rows as $row):
            $id = (int) $row->id;
            $rowsById[$id] = $row;
        endforeach;

        foreach ($rows as $row):
            $parentId = ($row->parent_id !== null && isset($rowsById[(int) $row->parent_id])) ? (int) $row->parent_id : 0;
            $childrenByParent[$parentId][] = (int) $row->id;
        endforeach;

        foreach ($childrenByParent as &$childrenIds):
            usort($childrenIds, static function(int $firstId, int $secondId) use ($rowsById): int {
                return strnatcasecmp((string) $rowsById[$firstId]->category, (string) $rowsById[$secondId]->category);
            });
        endforeach;
        unset($childrenIds);

        $pathsById = [];
        $pathsByUuid = [];
        $visited = [];

        $buildNode = function(int $id, string $parentPath, array $ancestors = []) use (&$buildNode, &$childrenByParent, &$pathsById, &$pathsByUuid, &$rowsById, &$visited): ?array {
            if (isset($ancestors[$id]) || ! isset($rowsById[$id])):
                return null;
            endif;

            $row = $rowsById[$id];
            $path = ($parentPath === '') ? (string) $row->category : $parentPath . ' > ' . $row->category;
            $ancestors[$id] = true;
            $visited[$id] = true;
            $pathsById[$id] = $path;
            $pathsByUuid[(string) $row->uuid] = $path;
            $children = [];

            foreach ($childrenByParent[$id] ?? [] as $childId):
                $child = $buildNode($childId, $path, $ancestors);
                if ($child !== null):
                    $children[] = $child;
                endif;
            endforeach;

            return [
                'id' => $id,
                'uuid' => (string) $row->uuid,
                'parent_id' => ($row->parent_id !== null) ? (int) $row->parent_id : null,
                'category' => (string) $row->category,
                'status' => (int) ($row->status ?? 0),
                'has_products' => (int) ($row->has_products ?? 0),
                'deleted_at' => $row->deleted_at ?? null,
                'path' => $path,
                'children' => $children,
            ];
        };

        $tree = [];

        foreach ($childrenByParent[0] ?? [] as $rootId):
            $node = $buildNode($rootId, '');
            if ($node !== null):
                $tree[] = $node;
            endif;
        endforeach;

        /* Mantiene visibili anche eventuali nodi orfani o appartenenti a cicli preesistenti. */
        foreach (array_keys($rowsById) as $id):
            if (isset($visited[$id])):
                continue;
            endif;

            $node = $buildNode($id, '');
            if ($node !== null):
                $tree[] = $node;
            endif;
        endforeach;

        return [
            'tree' => $tree,
            'pathsById' => $pathsById,
            'pathsByUuid' => $pathsByUuid,
        ];
    }

    /**
     * Restituisce l'albero delle categorie e le mappe dei percorsi completi.
     *
     * @param bool $includeDeleted Include anche le categorie presenti nel cestino
     * @return array{tree: array<int, array<string, mixed>>, pathsById: array<int, string>, pathsByUuid: array<string, string>}
     */
    public function getCategoryHierarchy(bool $includeDeleted = false): array
    {
        $sql = "select id, uuid, parent_id, category, status, deleted_at,
                    exists (
                        select 1 from categories_to_products where categories_to_products.category_id = categories.id
                    ) as has_products
                from categories";

        if ($includeDeleted === false):
            $sql .= " where deleted_at is null";
        endif;

        $sql .= " order by category asc";

        return $this->buildCategoryHierarchy($this->db->query($sql)->getResult());
    }

    /**
     * Restituisce le categorie selezionabili come padre, escludendo il ramo corrente.
     */
    public function getParentOptions(?string $excludeUuid = null): array
    {
        $sql = "select id, uuid, parent_id, category, status,
                    exists (
                        select 1 from categories_to_products where categories_to_products.category_id = categories.id
                    ) as has_products
                from categories
                where deleted_at is null
                order by category asc";
        $rows = $this->db->query($sql)->getResult();
        $excludedIds = [];

        if ($excludeUuid !== null):
            foreach ($rows as $row):
                if ($row->uuid === $excludeUuid):
                    $excludedIds[(int) $row->id] = true;
                    break;
                endif;
            endforeach;

            $changed = true;
            while ($changed):
                $changed = false;
                foreach ($rows as $row):
                    if ($row->parent_id !== null && isset($excludedIds[(int) $row->parent_id]) && ! isset($excludedIds[(int) $row->id])):
                        $excludedIds[(int) $row->id] = true;
                        $changed = true;
                    endif;
                endforeach;
            endwhile;
        endif;

        $options = array_values(array_filter($rows, static function(object $row) use ($excludedIds): bool {
            return ! isset($excludedIds[(int) $row->id]) && (int) $row->has_products === 0;
        }));

        $hierarchy = $this->buildCategoryHierarchy($rows);

        foreach ($options as $option):
            $option->path = $hierarchy['pathsById'][(int) $option->id] ?? $option->category;
        endforeach;

        usort($options, static function(object $first, object $second): int {
            return strnatcasecmp((string) $first->path, (string) $second->path);
        });

        return $options;
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
                'rules' => ['in_list[active,suspended,trashed,all]']
            ],
        ];
    }

    /**
     * Regole di validazione per i filtri di ricerca dei category.
     * 
     * Verifica che category, stato e intervalli temporali rispettino i formati previsti.
     *
     * @return array Regole per i filtri
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.category' => [
                'label' => lang('backend/categories.labels.category'), 
                'rules' => ['permit_empty', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'], 
            ],
            'searchFields.status' => [
                'label' => lang('backend/categories.labels.status'), 
                'rules' => ['permit_empty', 'in_list[0,1]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/categories.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/categories.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.updated_at-from' => [
                'label' => lang('backend/categories.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.updated_at-to' => [
                'label' => lang('backend/categories.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /**
     * Regole di validazione per la creazione di un category.
     * 
     * Verifica la correttezza formale del category, ne impone l'univocità
     * e include il controllo sulle immagini caricate.
     *
     * @return array Regole strutturate
     */
    public function addValidationRules(): array
    {
        return [
            'parent_id' => [
                'label' => lang('backend/categories.labels.parentCategory'),
                'rules' => ['permit_empty', 'is_natural_no_zero'],
            ],
            'category' => [
                'label' => lang('backend/categories.labels.category'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/categories.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/categories.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/categories.errors.safeText')
                ]
            ],
            'images' => [
                'label' => lang('backend/categories.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Regole di validazione per la modifica di un category.
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
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', "is_unique[categories.uuid,uuid,{$posts['uuid']}]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'parent_id' => [
                'label' => lang('backend/categories.labels.parentCategory'),
                'rules' => ['permit_empty', 'is_natural_no_zero'],
            ],
            'category' => [
                'label' => lang('backend/categories.labels.category'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/categories.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/categories.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/categories.errors.safeText')
                ]
            ],
            'images' => [
                'label' => lang('backend/categories.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    /**
     * Whitelist dei campi fisici importabili dal CRUD category.
     * Esclude esplicitamente colonne di sicurezza e di stato gestite dal backend.
     *
     * @return list<string>
     */
    public function importAllowedFields(): array
    {
        return ['uuid', 'parent_id', 'category', 'status', 'note'];
    }

    /**
     * Whitelist dei campi esportabili dal CRUD category.
     *
     * @return list<string>
     */
    public function exportAllowedFields(): array
    {
        return ['uuid', 'parent_id', 'category', 'status', 'note'];
    }

    /**
     * Regole applicative dedicate all'inserimento di category tramite ImportModel.
     *
     * Restano separate dalle regole del form CRUD perché l'importazione non gestisce
     * immagini, permessi o altri dati di interfaccia. Le regole vengono sovrapposte
     * al fallback strutturale generato dallo schema della tabella.
     *
     * @return array Regole specifiche per i record categories pianificati come insert
     */
    public function importAddValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['permit_empty', 'is_unique[categories.uuid]', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'parent_id' => [
                'label' => lang('backend/categories.labels.parentCategory'),
                'rules' => ['permit_empty', 'is_natural_no_zero'],
            ],
            'category' => [
                'label' => lang('backend/categories.labels.category'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/categories.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/categories.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/categories.errors.safeText')
                ]
            ],
        ];
    }

    /**
     * Regole applicative dedicate alla modifica di category tramite ImportModel.
     *
     * ImportModel applica queste regole solo alle colonne presenti nel CSV, permettendo
     * aggiornamenti parziali. L'UUID resta il riferimento del record e le regole specifiche
     * sostituiscono, per i campi interessati, il fallback derivato dallo schema SQL.
     *
     * @param array $data Dati di contesto del record, incluso l'UUID corrente
     * @return array Regole specifiche per i record categories pianificati come update
     */
    public function importEditValidationRules(array $data): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'parent_id' => [
                'label' => lang('backend/categories.labels.parentCategory'),
                'rules' => ['permit_empty', 'is_natural_no_zero'],
            ],
            'category' => [
                'label' => lang('backend/categories.labels.category'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'regex_match[/^[\p{L}\p{M}]+(?:[ \'\x{2018}\x{2019}`\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}][\p{L}\p{M}]+)*$/u]'],
            ],
            'status' => [
                'label' => lang('backend/categories.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/categories.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/categories.errors.safeText')
                ]
            ],
        ];
    }

    /**
     * Vincoli di dominio aggiuntivi per l'import CRUD dei category.
     *
     * L'importazione mantiene la protezione dei record cestinati.
     *
     * @return list<string>
     */
    public function importBusinessValidationErrors(string $action, array $data, ?array $currentRow): array
    {
        if ($action === 'update' && $currentRow !== null && ($currentRow['deleted_at'] ?? null) !== null):
            return [lang('backend/categories.messages.cannotModifyDeleted')];
        endif;

        $candidate = $data + ($currentRow ?? []);
        $parentId = isset($candidate['parent_id']) && $candidate['parent_id'] !== '' ? (int) $candidate['parent_id'] : null;
        $currentUuid = $action === 'update' ? (string) ($candidate['uuid'] ?? '') : null;
        $lockRows = $this->db->transDepth > 0;
        $parentError = $this->parentAssignmentError($parentId, $currentUuid !== '' ? $currentUuid : null, $lockRows);

        if ($parentError !== null):
            return [$parentError];
        endif;

        if ($action === 'update' && $currentUuid !== null && (int) ($candidate['status'] ?? 1) === 0):
            $error = $this->suspensionError($currentUuid, $lockRows);
            return $error === null ? [] : [$error];
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
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/categories.errors.uuid'),
                    'regex_match' => lang('backend/categories.errors.uuid')
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
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/categories.errors.uuid'),
                    'regex_match' => lang('backend/categories.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/categories.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
                'errors' => [
                    'in_list' => lang('backend/categories.errors.context'),
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
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('backend/categories.errors.uuid'),
                    'regex_match' => lang('backend/categories.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/categories.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('backend/categories.errors.context'),
                    'in_list' => lang('backend/categories.errors.context')
                ]
            ],
        ];
    }

    /**
     * Regole di validazione per il recupero asincrono dell'albero categorie.
     *
     * L'UUID è obbligatorio nelle viste Edit e Show, mentre non è previsto in Add.
     *
     * @param array $data Dati ricevuti dalla richiesta
     * @return array Regole per context e UUID
     */
    public function treeValidationRules(array $data): array
    {
        $uuidRules = ['permit_empty', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'];

        if (in_array($data['context'] ?? '', ['edit', 'show'], true)):
            $uuidRules = ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'];
        endif;

        return [
            'context' => [
                'label' => lang('backend/categories.labels.context'),
                'rules' => ['required', 'in_list[add,edit,show]'],
                'errors' => [
                    'required' => lang('backend/categories.errors.context'),
                    'in_list' => lang('backend/categories.errors.context')
                ]
            ],
            'uuid' => [
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => $uuidRules,
                'errors' => [
                    'required' => lang('backend/categories.errors.uuid'),
                    'regex_match' => lang('backend/categories.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Regole di validazione per l'estrazione dei metadati del category.
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
                'label' => lang('backend/categories.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('backend/categories.errors.uuid'),
                    'regex_match' => lang('backend/categories.errors.uuid')
                ]
            ],
        ];
    }

    /**
     * Crea un nuovo category in modo transazionale.
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

            $this->db->transBegin();

            $parentId = ($posts['parent_id'] ?? '') !== '' ? (int) $posts['parent_id'] : null;
            $parentError = $this->parentAssignmentError($parentId, null, true);

            if ($parentError !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $parentError];
            endif;

            /* Genero uuid */
            $uuid = $this->generateUUID();

            /* Inserimento dati nella tabella principale */
            $sql = "insert into categories (uuid, parent_id, category, status, note, created_at) values (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $parentId, $posts['category'], $posts['status'], (trim($posts['note']) !== '' ? $posts['note'] : null), date('Y-m-d H:i:s')]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                
                $uploadService = $this->getUploadService();
                    
                $filenames = $uploadService->doUpload($posts['images'], 'categories', $uuid);
                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Categories non completato.');
                endif;

                $this->insertImages($filenames, $uuid, 'categories', 'add');
            endif;

            /* 3. Verifico eventuali errori SQL prima di fare il commit */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                \App\Libraries\ImageFileSystemService::removeAllImages('categories', $uuid);

                log_message('error', lang('backend/categories.messages.addError'));
                return ['result' => false, 'message' => lang('backend/categories.messages.addError')];
            endif;

            /* Recupero dati del category appena inserito */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                $this->db->transRollback();
                \App\Libraries\ImageFileSystemService::removeAllImages('categories', $uuid);
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Se le query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('ADD_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.addCategory'), esc($data['row']->category)), $currentAdmin);

            $message = sprintf(lang('backend/categories.messages.addSuccess'), esc($data['row']->category));
            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            if ($uuid !== ''):
                \App\Libraries\ImageFileSystemService::removeAllImages('categories', $uuid);
            endif;

			log_message('error', lang('backend/categories.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/categories.messages.addError')];
        }
    }

    /**
     * Aggiorna un category esistente in modo transazionale.
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

            $this->db->transBegin();
            $this->db->query("select id from categories where uuid = ? for update", [$posts['uuid']]);

            $uploadUuid = $posts['uuid'];

            /* Recupero i dati del category prima dell'aggiornamento */
            $category = $this->getByUUID($posts['uuid']);

            if($category['result'] === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => $category['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($category['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            if ((int) $posts['status'] === 0 && ($error = $this->suspensionError($posts['uuid'], true)) !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $error];
            endif;

            /* Una option disabilitata non viene inviata: non trasformarla implicitamente in radice. */
            $parentValue = array_key_exists('parent_id', $posts) ? $posts['parent_id'] : $category['row']->parent_id;
            $parentId = $parentValue === null || $parentValue === '' ? null : (int) $parentValue;
            $posts['parent_id'] = $parentId;
            $parentError = $this->parentAssignmentError($parentId, $posts['uuid'], true);

            if ($parentError !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $parentError];
            endif;

            $parentCategory = null;
            if ($parentId !== null):
                $parent = $this->db->query("select category from categories where id = ? limit 1", [$parentId])->getRow();
                $parentCategory = $parent->category ?? null;
            endif;

            /* Se non è stato effettuato alcun cambio sui dati gestiti, interrompiamo subito */
            if( ! $this->hasCategoryChanged($posts, $category['row'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.noDataChanged')];
            endif;

            $updated_at = date('Y-m-d H:i:s');

            /* Aggiorno la tabella principale */
            $sql = 'update categories set parent_id = ?, category = ?, status = ?, note = ?, updated_at = ? where uuid = ?';
            $this->db->query($sql, [$parentId, $posts['category'], $posts['status'], (trim($posts['note']) !== '' ? $posts['note'] : null), $updated_at, $posts['uuid']]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):

                $uploadService = $this->getUploadService();

                $filenames = $uploadService->doUpload($posts['images'], 'categories', $posts['uuid']);
                $uploadedFilenames = $filenames ?: [];

                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Categories non completato.');
                endif;

                $this->insertImages($filenames, $posts['uuid'], 'categories', 'edit');
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                $this->cleanupUploadedImages($uploadedFilenames, $uploadUuid);
                log_message('error', lang('backend/categories.messages.editError'));
                return ['result' => false, 'message' => lang('backend/categories.messages.editError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dell'oggetto in memoria da restituire alla vista */
            $category['row']->parent_id = $parentId;
            $category['row']->parent_category = $parentCategory;
            $category['row']->category = $posts['category'];
            $category['row']->status = $posts['status'];
            $category['row']->note = $posts['note'];
            $category['row']->updated_at = $updated_at;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EDIT_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.editCategory'), esc($category['row']->category)), $currentAdmin);

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/categories.messages.editSuccess'), esc($posts['category'])), 
                'row'     => $category['row']
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uploadUuid);
            log_message('error', lang('backend/categories.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
            return ['result' => false, 'message' => lang('backend/categories.messages.editError')];
        }
    }

    /**
     * Verifica se i dati del category o le immagini hanno subito modifiche.
     *
     * @param array $posts Dati provenienti dal form
     * @param object $original L'oggetto rappresentativo dello stato attuale nel database
     * @return bool True se è necessaria la query di UPDATE, false altrimenti
     */
    public function hasCategoryChanged(array $posts, object $original): bool
    {
        /* Controlla i campi base e i file */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        return false;
    }

    /**
     * Elimina definitivamente un category non associato a prodotti.
     * 
     * Rimuove i record da `categories` e `images`, quindi elimina la relativa cartella immagini.
     *
     * @param array $posts Dataset contenente l'UUID del category da eliminare
     * @return array Esito dell'operazione
     */
    public function hardDelete(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            $this->db->transBegin();
            $this->db->query("select id from categories where uuid = ? for update", [$posts['uuid']]);

            /* Recupero i dati del category prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => $data['message']];
            endif;

			if ($data['row']->deleted_at === null):
				$this->db->transRollback();
				return ['result' => false, 'message' => lang('backend/categories.messages.hardDeleteRequiresTrash')];
			endif;

            if ($this->hasProducts($posts['uuid'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.deleteAssociated')];
            endif;

            if ($this->hasChildren($posts['uuid'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.deleteHasChildren')];
            endif;

            /* Eliminazione category */
            $sql = "delete from categories where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            /* Eliminazione immagini dal database */
            $sql = "delete from images where entity = 'categories' and entity_uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/categories.messages.hardDeleteError'));

                return ['result' => false, 'message' => lang('backend/categories.messages.hardDeleteError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('HARD_DELETE_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.hardDeleteCategory'), esc($data['row']->category)), $currentAdmin);

			if ( ! \App\Libraries\ImageFileSystemService::removeAllImages('categories', $posts['uuid'])):
				log_message('error', lang('backend/categories.messages.hardDeleteImagesError'));
				return [
					'result' => true,
					'cleanup' => false,
					'message' => sprintf(lang('backend/categories.messages.hardDeleteSuccess'), esc($data['row']->category)) . ' ' . lang('backend/categories.messages.hardDeleteImagesError')
				];
			endif;

			return ['result' => true, 'cleanup' => true, 'message' => sprintf(lang('backend/categories.messages.hardDeleteSuccess'), esc($data['row']->category))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/categories.messages.hardDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/categories.messages.hardDeleteError')];

        }
    }

    /**
     * Cestina logicamente un category non associato a prodotti.
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

            $this->db->transBegin();
            $this->db->query("select id from categories where uuid = ? for update", [$posts['uuid']]);

            /* Recupero i dati del category prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            if ($this->hasProducts($posts['uuid'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.deleteAssociated')];
            endif;

            if ($this->hasChildren($posts['uuid'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.deleteHasChildren')];
            endif;

            /* Cestinamento */
            $sql = "update categories set deleted_at = now() where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/categories.messages.softDeleteError'));

                return ['result' => false, 'message' => lang('backend/categories.messages.softDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('SOFT_DELETE_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.softDeleteCategory'), esc($data['row']->category)), $currentAdmin);

            /* Nota: usa una stringa di lingua dedicata come softDelSuccess se l'hai creata */
            return ['result' => true, 'message' => sprintf(lang('backend/categories.messages.softDeleteSuccess'), esc($data['row']->category))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/categories.messages.softDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/categories.messages.softDeleteError')];

        }
    }

    /**
     * Ripristina un category precedentemente spostato nel cestino.
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

            $this->db->transBegin();

            /* Recupero diretto dal DB per aggirare eventuali filtri sui record attivi */
            $sql = "select * from categories where uuid = ? for update";
            $row = $this->db->query($sql, [$posts['uuid']])->getRow();

			if (empty($row)):
				$this->db->transRollback();
				return ['result' => false, 'message' => lang('backend/categories.messages.notFound')];
			endif;

			if ($row->deleted_at === null):
				$this->db->transRollback();
				return ['result' => false, 'message' => lang('backend/categories.messages.restoreRequiresTrash')];
			endif;

            if ($row->parent_id !== null):
                $parent = $this->db->query("select status, deleted_at from categories where id = ? limit 1 for update", [(int) $row->parent_id])->getRow();
                if ($parent === null || $parent->deleted_at !== null || (int) $parent->status !== 1):
                    $this->db->transRollback();
                    return ['result' => false, 'message' => lang('backend/categories.messages.restoreParentUnavailable')];
                endif;
            endif;

            if ((int) $row->status === 0 && ($error = $this->suspensionError($posts['uuid'], true)) !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $error];
            endif;

            $parentError = $this->parentAssignmentError($row->parent_id === null ? null : (int) $row->parent_id, $posts['uuid'], true);
            if ($parentError !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $parentError];
            endif;

            /* Ripristino del category */
            $sqlUpdate = "update categories set deleted_at = null, restored_at = ? where uuid = ?";
            $this->db->query($sqlUpdate, [date('Y-m-d H:i:s'), $posts['uuid']]);
            
            $message = sprintf(lang('backend/categories.messages.restoreDeleteSuccess'), esc($row->category));

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/categories.messages.restoreDeleteError'));

                return ['result' => false, 'message' => lang('backend/categories.messages.restoreDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESTORE_DELETE_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.restoreDeleteCategory'), esc($row->category)), $currentAdmin);

            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/categories.messages.restoreDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/categories.messages.restoreDeleteError')];

        }
    }

    /**
     * Alterna lo stato del category.
     * 
     * Il cambio è bloccato sui record cestinati. La logica legge lo stato corrente,
     * lo inverte e aggiorna il timestamp di modifica in modo transazionale.
     *
     * @param array $posts Dataset contenente l'UUID
     * @return array Esito con il category aggiornato
     */
    public function changeStatus(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);

            $this->db->transBegin();
            $this->db->query('select id from categories where uuid = ? for update', [$posts['uuid']]);

            /* Recupero i dati del category */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/categories.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            $currentStatus = (int) $data['row']->status;

            if ($currentStatus === 1 && ($error = $this->suspensionError($posts['uuid'], true)) !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $error];
            endif;

            $parentError = $this->parentAssignmentError($data['row']->parent_id === null ? null : (int) $data['row']->parent_id, $posts['uuid'], true);
            if ($parentError !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => $parentError];
            endif;

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

            /* Cambio stato category */
            $sql = "update categories set status = ?, updated_at = ? where uuid = ?";
            $this->db->query($sql, [$newStatus, $updatedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/categories.messages.changeStatusError'));

                return ['result' => false, 'message' => lang('backend/categories.messages.changeStatusError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_STATUS_CATEGORIES', 'categories', sprintf(lang('backend/categories.audits.changeStatusCategory'), esc($data['row']->category)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/categories.messages.changeStatusSuccess'), esc($data['row']->category)), 'category' => $data['row']];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/categories.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/categories.messages.changeStatusError')];

        }
    }
}
