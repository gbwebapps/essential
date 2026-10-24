<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Models;

use App\Interfaces\ExportValidationInterface;
use App\Interfaces\ImportValidationInterface;
use App\Libraries\ImageFileSystemService;
use App\Models\Backend\BackendModel;
use Modules\Backend\Categories\Models\CategoriesModel;

/**
 * Gestisce l'anagrafica prodotti e le associazioni con marche e categorie.
 *
 * Le operazioni di scrittura mantengono allineate la tabella principale,
 * le due tabelle pivot e le eventuali immagini del prodotto.
 */
class ProductsModel extends BackendModel implements ImportValidationInterface, ExportValidationInterface
{
    /**
     * Nome della tabella principale utilizzato dal motore CRUD condiviso.
     *
     * @var string|null
     */
    protected ?string $module = 'products';

    /**
     * Abilita il filtro del cestino nel modello base.
     *
     * @var bool
     */
    protected bool $hasSoftDelete = true;

    protected bool $hasStatusFilter = true;

    /**
     * Campi ammessi per paginazione, ordinamento e filtri della lista.
     *
     * @var array<int, string>
     */
    protected array $showAllAllowedFields = [
        'id',
        'column',
        'order',
        'page',
        'rows',
        'searchFields',
        'searchDates',
        'trash_filter',
    ];

    /**
     * Campi ammessi nella ricerca avanzata.
     *
     * @var array<int, string>
     */
    protected array $showAllSearchAllowedFields = [
        'sku',
        'product',
        'status',
        'quantity-from',
        'quantity-to',
        'price-from',
        'price-to',
        'brand_uuid',
        'category_uuid',
    ];

    /**
     * Colonne temporali ammesse nei filtri per intervallo.
     *
     * @var array<int, string>
     */
    protected array $showAllSearchAllowedDates = ['created_at', 'updated_at'];

    /**
     * Colonne ammesse per l'ordinamento della lista.
     *
     * @var array<int, string>
     */
    protected array $allowedOrderColumns = ['id', 'sku', 'product', 'quantity', 'price', 'total_value', 'status'];

    /**
     * Campi accettati durante l'inserimento.
     *
     * @var array<int, string>
     */
    protected array $addAllowedFields = [
        'sku',
        'product',
        'note',
        'quantity',
        'price',
        'status',
        'brand_uuids',
        'category_uuids',
        'images',
    ];

    /**
     * Campi accettati durante la modifica.
     *
     * @var array<int, string>
     */
    protected array $editAllowedFields = [
        'uuid',
        'sku',
        'product',
        'note',
        'quantity',
        'price',
        'status',
        'brand_uuids',
        'category_uuids',
        'images',
    ];

    /**
     * Campi accettati nelle operazioni di eliminazione e ripristino.
     *
     * @var array<int, string>
     */
    protected array $delAllowedFields = ['uuid'];

    /**
     * Campi accettati durante il cambio rapido di stato.
     *
     * @var array<int, string>
     */
    protected array $changeStatusAllowedFields = ['uuid'];

    /**
     * Campi confrontati per rilevare una modifica dei dati principali.
     *
     * @var array<int, string>
     */
    protected array $toCompare = ['sku', 'product', 'note', 'quantity', 'price', 'status'];

    /**
     * Query base per la lista paginata dei prodotti.
     *
     * @var string|null
     */
    protected ?string $getDataQuery = "select
                                            products.id,
                                            products.uuid,
                                            products.sku,
                                            products.product,
                                            products.quantity,
                                            products.price,
                                            (products.quantity * products.price) as total_value,
                                            products.status,
                                            products.created_at,
                                            products.updated_at,
                                            products.deleted_at,
                                            products.restored_at,
                                            (
                                                select images.filename
                                                from images
                                                where images.entity_uuid = products.uuid
                                                    and images.entity = 'products'
                                                    and images.is_cover = 1
                                                limit 1
                                            ) as cover,
                                            (
                                                select count(*)
                                                from images
                                                where images.entity_uuid = products.uuid
                                                    and images.entity = 'products'
                                            ) as images_num,
                                            (
                                                select group_concat(brands.brand order by brands.brand separator ', ')
                                                from brands_to_products
                                                inner join brands on brands.id = brands_to_products.brand_id
                                                where brands_to_products.product_id = products.id
                                            ) as brands,
                                            (
                                                select group_concat(categories.category order by categories.category separator ', ')
                                                from categories_to_products
                                                inner join categories on categories.id = categories_to_products.category_id
                                                where categories_to_products.product_id = products.id
                                            ) as categories
                                        from products
                                        where 1 = 1";

    /**
     * Query base per il recupero di un singolo prodotto.
     *
     * @var string|null
     */
    protected ?string $getUUIDQuery = "select
                                            id,
                                            uuid,
                                            sku,
                                            product,
                                            note,
                                            quantity,
                                            price,
                                            status,
                                            created_at,
                                            updated_at,
                                            deleted_at,
                                            restored_at
                                        from products
                                        where uuid = ?
                                        limit 1";

    /**
     * Query base per il conteggio della lista paginata.
     *
     * @var string|null
     */
    protected ?string $getNumRowsQuery = 'select count(*) as count from products where 1 = 1';

    /**
     * Inizializza i servizi condivisi del modello backend.
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
                                 from products');

        if ($query === false || ($counts = $query->getRowArray()) === null):
            throw new \RuntimeException(lang('backend/global.messages.getDataError'));
        endif;

        $total = (int) $counts['total'];
        $trashed = (int) $counts['trashed'];
        $suspended = (int) $counts['suspended'];

        return ['active' => (int) $counts['active'], 'suspended' => $suspended, 'trashed' => $trashed, 'all' => $total];
    }

    /**
     * Elimina soltanto le immagini caricate durante l'operazione corrente.
     *
     * È utilizzato in caso di rollback per non rimuovere immagini preesistenti.
     *
     * @param array<int, string> $filenames File caricati nella transazione corrente
     * @param string $uuid Identificativo del prodotto
     */
    protected function cleanupUploadedImages(array $filenames, string $uuid): void
    {
        if ($uuid === ''):
            return;
        endif;

        foreach ($filenames as $filename):
            try {
                ImageFileSystemService::removeSingleImage('products', $uuid, $filename);
            } catch (\Throwable $e) {
                log_message('error', 'Pulizia immagine Products non completata - ' . $e->getMessage());
            }
        endforeach;
    }

    /**
     * Registra un'attività del modulo senza alterare l'esito già consolidato.
     *
     * L'audit viene eseguito dopo il commit. Un eventuale errore del sistema
     * di tracciamento viene quindi registrato nei log senza simulare un
     * rollback ormai non più possibile.
     *
     * @param string $action Codice dell'azione amministrativa
     * @param string $message Descrizione localizzata dell'attività
     */
    private function logProductActivity(string $action, string $message): void
    {
        try {
            $currentAdmin = service('authorization')->currentAdmin();

            log_admin_activity($action, 'products', $message, $currentAdmin);

        } catch (\Throwable $e) {
            log_message('error', 'Registrazione audit Products non completata - ' . $e->getMessage());
        }
    }

    /**
     * Costruisce i filtri testuali, numerici e relazionali della lista.
     *
     * @param array<string, mixed> $searchFields Filtri validati ricevuti dal client
     * @param array<int, mixed> $params Parametri associati alla query preparata
     * @return string Clausole SQL aggiuntive
     */
    protected function buildFilters(array $searchFields, array &$params): string
    {
        $whereClause = '';

        foreach ($searchFields as $key => $value):
            if ( ! in_array($key, $this->showAllSearchAllowedFields, true)):
                continue;
            endif;

            if (trim((string) $value) === ''):
                continue;
            endif;

            if (in_array($key, ['sku', 'product'], true)):
                $whereClause .= " and products.{$key} like ?";
                $params[] = "%{$value}%";

            elseif ($key === 'status'):
                $whereClause .= ' and products.status = ?';
                $params[] = (int) $value;

            elseif ($key === 'quantity-from'):
                $whereClause .= ' and products.quantity >= ?';
                $params[] = (int) $value;

            elseif ($key === 'quantity-to'):
                $whereClause .= ' and products.quantity <= ?';
                $params[] = (int) $value;

            elseif ($key === 'price-from'):
                $whereClause .= ' and products.price >= ?';
                $params[] = (string) $value;

            elseif ($key === 'price-to'):
                $whereClause .= ' and products.price <= ?';
                $params[] = (string) $value;

            elseif ($key === 'brand_uuid'):
                $whereClause .= " and exists (
                                    select 1
                                    from brands_to_products
                                    inner join brands on brands.id = brands_to_products.brand_id
                                    where brands_to_products.product_id = products.id
                                        and brands.uuid = ?
                                )";
                $params[] = (string) $value;

            elseif ($key === 'category_uuid'):
                $whereClause .= " and exists (
                                    select 1
                                    from categories_to_products
                                    inner join categories on categories.id = categories_to_products.category_id
                                    where categories_to_products.product_id = products.id
                                        and categories.uuid = ?
                                )";
                $params[] = (string) $value;
            endif;
        endforeach;

        return $whereClause;
    }

    /**
     * Restituisce le marche non cestinate selezionabili nei form.
     *
     * @return array<int, object>
     */
    public function getBrandOptions(): array
    {
        $sql = "select uuid, brand, status
                from brands
                where deleted_at is null
                order by brand asc";

        return $this->db->query($sql)->getResult();
    }

    /**
     * Restituisce le categorie foglia non cestinate selezionabili nei form.
     *
     * Il percorso completo viene aggiunto a ogni record per distinguere
     * categorie omonime collocate in rami diversi.
     *
     * @return array<int, object>
     */
    public function getCategoryOptions(): array
    {
        $sql = "select categories.uuid, categories.category, categories.status
                from categories
                where categories.deleted_at is null
                    and not exists (
                        select 1
                        from categories as child
                        where child.parent_id = categories.id
                    )
                order by categories.category asc";

        $categories = $this->db->query($sql)->getResult();
        $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy();

        foreach ($categories as $category):
            $category->path = $hierarchy['pathsByUuid'][(string) $category->uuid] ?? $category->category;
        endforeach;

        usort($categories, static function (object $first, object $second): int {
            return strnatcasecmp((string) $first->path, (string) $second->path);
        });

        return $categories;
    }

    /**
     * Recupera un prodotto completo di marche e categorie associate.
     *
     * @param string $uuid Identificativo del prodotto
     * @return array{result: bool, row?: object, message?: string}
     */
    public function getProduct(string $uuid): array
    {
        return $this->findProduct($uuid);
    }

    /**
     * Recupera e blocca il prodotto durante una transazione di scrittura.
     *
     * @param string $uuid Identificativo del prodotto
     * @return array{result: bool, row?: object, message?: string}
     */
    private function getProductForUpdate(string $uuid): array
    {
        return $this->findProduct($uuid, true);
    }

    /**
     * Recupera un prodotto e completa il record con le relazioni pivot.
     *
     * @param string $uuid Identificativo del prodotto
     * @param bool $lockRow Applica un blocco pessimista alla riga principale
     * @return array{result: bool, row?: object, message?: string}
     */
    private function findProduct(string $uuid, bool $lockRow = false): array
    {
        try {
            $sql = $this->getUUIDQuery . ($lockRow ? ' for update' : '');
            $product = $this->db->query($sql, [$uuid])->getRow();

            if ($product === null):
                return ['result' => false, 'message' => lang('backend/products.messages.notFound')];
            endif;

            $productId = (int) $product->id;

            $brandsSql = "select brands.uuid, brands.brand, brands.status
                          from brands_to_products
                          inner join brands on brands.id = brands_to_products.brand_id
                          where brands_to_products.product_id = ?
                          order by brands.brand asc";

            $categoriesSql = "select categories.uuid, categories.category, categories.status
                              from categories_to_products
                              inner join categories on categories.id = categories_to_products.category_id
                              where categories_to_products.product_id = ?
                              order by categories.category asc";

            $product->brands = $this->db->query($brandsSql, [$productId])->getResult();
            $product->categories = $this->db->query($categoriesSql, [$productId])->getResult();
            $product->brand_uuids = [];
            $product->category_uuids = [];

            foreach ($product->brands as $brand):
                $product->brand_uuids[] = (string) $brand->uuid;
            endforeach;

            $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy(true);

            foreach ($product->categories as $category):
                $category->path = $hierarchy['pathsByUuid'][(string) $category->uuid] ?? $category->category;
                $product->category_uuids[] = (string) $category->uuid;
            endforeach;

            return ['result' => true, 'row' => $product];

        } catch (\Throwable $e) {
            log_message('error', lang('backend/global.messages.getUUIDError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/global.messages.getUUIDError')];
        }
    }

    /* VALIDAZIONE -------------------------------------------------------------------------------- */

    /**
     * Regole di validazione per paginazione, ordinamento e cestino.
     *
     * @return array<string, array<string, mixed>>
     */
    public function showAllValidationRules(): array
    {
        return [
            'column' => [
                'rules' => ['required', 'alpha_dash'],
            ],
            'order' => [
                'rules' => ['required', 'in_list[asc,desc]'],
            ],
            'page' => [
                'rules' => ['required', 'is_natural_no_zero'],
            ],
            'rows' => [
                'rules' => ['required', 'is_natural_no_zero'],
            ],
            'trash_filter' => [
                'rules' => ['in_list[active,suspended,trashed,all]'],
            ],
        ];
    }

    /**
     * Regole di validazione per i filtri avanzati della lista.
     *
     * @return array<string, array<string, mixed>>
     */
    public function showAllSearchValidationRules(): array
    {
        $rules = [
            'searchFields.sku' => [
                'label' => lang('backend/products.labels.sku'),
                'rules' => ['permit_empty', 'max_length[100]', 'regex_match[/^[A-Za-z0-9._\/-]+$/]'],
            ],
            'searchFields.product' => [
                'label' => lang('backend/products.labels.product'),
                'rules' => ['permit_empty', 'max_length[255]', 'safeText'],
            ],
            'searchFields.status' => [
                'label' => lang('backend/products.labels.status'),
                'rules' => ['permit_empty', 'in_list[0,1]'],
            ],
            'searchFields.quantity-from' => [
                'label' => lang('backend/products.labels.quantityFrom'),
                'rules' => ['permit_empty', 'is_natural', 'less_than_equal_to[4294967295]'],
            ],
            'searchFields.quantity-to' => [
                'label' => lang('backend/products.labels.quantityTo'),
                'rules' => ['permit_empty', 'is_natural', 'less_than_equal_to[4294967295]'],
            ],
            'searchFields.price-from' => [
                'label' => lang('backend/products.labels.priceFrom'),
                'rules' => ['permit_empty', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]'],
            ],
            'searchFields.price-to' => [
                'label' => lang('backend/products.labels.priceTo'),
                'rules' => ['permit_empty', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]'],
            ],
            'searchFields.brand_uuid' => [
                'label' => lang('backend/products.labels.brands'),
                'rules' => ['permit_empty', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'searchFields.category_uuid' => [
                'label' => lang('backend/products.labels.categories'),
                'rules' => ['permit_empty', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];

        foreach (['created_at-from', 'created_at-to', 'updated_at-from', 'updated_at-to'] as $field):
            $rules['searchDates.' . $field] = [
                'label' => lang('backend/products.labels.date'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ];
        endforeach;

        return $rules;
    }

    /**
     * Costruisce le regole condivise dai form di inserimento e modifica.
     *
     * @param string|null $uuid UUID escluso dai controlli di unicità in modifica
     * @return array<string, array<string, mixed>>
     */
    private function productValidationRules(?string $uuid = null): array
    {
        $skuUniqueRule = ($uuid === null)
            ? 'is_unique[products.sku]'
            : "is_unique[products.sku,uuid,{$uuid}]";

        $productUniqueRule = ($uuid === null)
            ? 'is_unique[products.product]'
            : "is_unique[products.product,uuid,{$uuid}]";

        $rules = [
            'sku' => [
                'label' => lang('backend/products.labels.sku'),
                'rules' => ['required', 'trim', 'max_length[100]', $skuUniqueRule, 'regex_match[/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/]'],
            ],
            'product' => [
                'label' => lang('backend/products.labels.product'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', $productUniqueRule, 'safeText'],
            ],
            'quantity' => [
                'label' => lang('backend/products.labels.quantity'),
                'rules' => ['required', 'is_natural', 'less_than_equal_to[4294967295]'],
            ],
            'price' => [
                'label' => lang('backend/products.labels.price'),
                'rules' => ['required', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]'],
            ],
            'status' => [
                'label' => lang('backend/products.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/products.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => lang('backend/categories.errors.safeText')
                ]
            ],
            'brand_uuids' => [
                'label' => lang('backend/products.labels.brands'),
                'rules' => ['required', 'is_array'],
            ],
            'brand_uuids.*' => [
                'label' => lang('backend/products.labels.brands'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'category_uuids' => [
                'label' => lang('backend/products.labels.categories'),
                'rules' => ['required', 'is_array'],
            ],
            'category_uuids.*' => [
                'label' => lang('backend/products.labels.categories'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'images' => [
                'label' => lang('backend/products.labels.images'),
                'rules' => ['permit_empty', 'checkImages'],
            ],
        ];

        if ($uuid !== null):
            $rules = [
                'uuid' => [
                    'label' => lang('backend/products.labels.uuid'),
                    'rules' => [
                        'required',
                        "is_unique[products.uuid,uuid,{$uuid}]",
                        'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]',
                    ],
                ],
            ] + $rules;
        endif;

        return $rules;
    }

    /**
     * Regole del form di inserimento.
     *
     * @return array<string, array<string, mixed>>
     */
    public function addValidationRules(): array
    {
        return $this->productValidationRules();
    }

    /**
     * Regole del form di modifica.
     *
     * @param array<string, mixed> $posts Payload della richiesta
     * @return array<string, array<string, mixed>>
     */
    public function editValidationRules(array $posts): array
    {
        return $this->productValidationRules((string) ($posts['uuid'] ?? ''));
    }

    /**
     * Campi fisici importabili dal componente CSV condiviso.
     *
     * Le associazioni con marche e categorie non vengono modificate: l'importazione
     * locale è riservata all'aggiornamento dei dati dei prodotti esistenti.
     *
     * @return list<string>
     */
    public function importAllowedFields(): array
    {
        return ['uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'];
    }

    /**
     * Campi esportabili dal componente CSV condiviso.
     *
     * Le relazioni pivot saranno gestite dal futuro importatore/esportatore
     * dedicato al catalogo e non dal componente CRUD generico.
     *
     * @return array<int, string>
     */
    public function exportAllowedFields(): array
    {
        return ['uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'];
    }

    /**
     * Regole applicative per i record che l'importatore classifica come inserimenti.
     *
     * Gli inserimenti vengono comunque bloccati dai vincoli di dominio perché il CSV
     * locale non contiene le associazioni obbligatorie con marche e categorie.
     *
     * @return array<string, array<string, mixed>>
     */
    public function importAddValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/products.labels.uuid'),
                'rules' => ['permit_empty', 'is_unique[products.uuid]', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'sku' => [
                'label' => lang('backend/products.labels.sku'),
                'rules' => ['required', 'trim', 'max_length[100]', 'is_unique[products.sku]', 'regex_match[/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/]'],
            ],
            'product' => [
                'label' => lang('backend/products.labels.product'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'is_unique[products.product]', 'safeText'],
            ],
            'note' => [
                'label' => lang('backend/products.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
            ],
            'quantity' => [
                'label' => lang('backend/products.labels.quantity'),
                'rules' => ['required', 'is_natural', 'less_than_equal_to[4294967295]'],
            ],
            'price' => [
                'label' => lang('backend/products.labels.price'),
                'rules' => ['required', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]'],
            ],
            'status' => [
                'label' => lang('backend/products.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
        ];
    }

    /**
     * Regole applicative per l'aggiornamento parziale di prodotti tramite CSV.
     *
     * @param array<string, mixed> $data Dati del record, incluso l'UUID corrente
     * @return array<string, array<string, mixed>>
     */
    public function importEditValidationRules(array $data): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/products.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'sku' => [
                'label' => lang('backend/products.labels.sku'),
                'rules' => ['required', 'trim', 'max_length[100]', "is_unique[products.sku,uuid,{$data['uuid']}]", 'regex_match[/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/]'],
            ],
            'product' => [
                'label' => lang('backend/products.labels.product'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', "is_unique[products.product,uuid,{$data['uuid']}]", 'safeText'],
            ],
            'note' => [
                'label' => lang('backend/products.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
            ],
            'quantity' => [
                'label' => lang('backend/products.labels.quantity'),
                'rules' => ['required', 'is_natural', 'less_than_equal_to[4294967295]'],
            ],
            'price' => [
                'label' => lang('backend/products.labels.price'),
                'rules' => ['required', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]'],
            ],
            'status' => [
                'label' => lang('backend/products.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
        ];
    }

    /**
     * Protegge le associazioni pivot e i prodotti presenti nel cestino.
     *
     * @return list<string>
     */
    public function importBusinessValidationErrors(string $action, array $data, ?array $currentRow): array
    {
        if ($action === 'insert'):
            return [lang('backend/products.messages.importExistingOnly')];
        endif;

        if ($currentRow !== null && ($currentRow['deleted_at'] ?? null) !== null):
            return [lang('backend/products.messages.cannotModifyDeleted')];
        endif;

        return [];
    }

    /**
     * Regole per eliminazione e ripristino.
     *
     * @return array<string, array<string, mixed>>
     */
    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/products.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    /**
     * Regole per il cambio rapido di stato.
     *
     * @return array<string, array<string, mixed>>
     */
    public function changeStatusValidationRules(): array
    {
        return $this->delValidationRules() + [
            'context' => [
                'label' => lang('backend/products.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
            ],
        ];
    }

    /**
     * Regole per il ricaricamento dei dati generali.
     *
     * @return array<string, array<string, mixed>>
     */
    public function generalDataValidationRules(): array
    {
        return $this->delValidationRules() + [
            'context' => [
                'label' => lang('backend/products.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
        ];
    }

    /**
     * Regole per il ricaricamento dei metadati.
     *
     * @return array<string, array<string, mixed>>
     */
    public function metaDataValidationRules(): array
    {
        return $this->delValidationRules();
    }

    /* RELAZIONI ----------------------------------------------------------------------------------- */

    /**
     * Normalizza un elenco di UUID preservando il primo ordine ricevuto.
     *
     * @param array<int, mixed> $uuids Elenco proveniente dal form
     * @return array<int, string>
     */
    private function normalizeUuidList(array $uuids): array
    {
        return array_values(array_unique(array_map('strval', $uuids)));
    }

    /**
     * Valida e blocca le marche e le categorie richieste.
     *
     * Il lock sulle righe impedisce che una marca o una categoria vengano
     * eliminate, oppure che una categoria riceva figli, mentre il prodotto
     * sta creando le associazioni pivot.
     *
     * @param array<int, mixed> $brandUuids UUID delle marche selezionate
     * @param array<int, mixed> $categoryUuids UUID delle categorie selezionate
     * @return array{result: bool, brand_ids?: array<int, int>, category_ids?: array<int, int>, message?: string}
     */
    private function resolveRelations(array $brandUuids, array $categoryUuids): array
    {
        $brandUuids = $this->normalizeUuidList($brandUuids);
        $categoryUuids = $this->normalizeUuidList($categoryUuids);

        if ($brandUuids === [] || $categoryUuids === []):
            return ['result' => false, 'message' => lang('backend/products.messages.relationsRequired')];
        endif;

        $brandPlaceholders = implode(', ', array_fill(0, count($brandUuids), '?'));
        $brandsSql = "select id, uuid
                      from brands
                      where uuid in ({$brandPlaceholders})
                          and deleted_at is null
                          and status = 1
                      order by id asc
                      for update";
        $brands = $this->db->query($brandsSql, $brandUuids)->getResult();

        if (count($brands) !== count($brandUuids)):
            return ['result' => false, 'message' => lang('backend/products.messages.invalidBrands')];
        endif;

        $categoryPlaceholders = implode(', ', array_fill(0, count($categoryUuids), '?'));
        $categoriesSql = "select id, uuid
                          from categories
                          where uuid in ({$categoryPlaceholders})
                              and deleted_at is null
                              and status = 1
                          order by id asc
                          for update";
        $categories = $this->db->query($categoriesSql, $categoryUuids)->getResult();

        if (count($categories) !== count($categoryUuids)):
            return ['result' => false, 'message' => lang('backend/products.messages.invalidCategories')];
        endif;

        $categoryIds = array_map(static function (object $category): int {
            return (int) $category->id;
        }, $categories);

        $categoryIdPlaceholders = implode(', ', array_fill(0, count($categoryIds), '?'));
        $childrenSql = "select 1
                        from categories
                        where parent_id in ({$categoryIdPlaceholders})
                        limit 1";
        $hasChildren = $this->db->query($childrenSql, $categoryIds)->getRow();

        if ($hasChildren !== null):
            return ['result' => false, 'message' => lang('backend/products.messages.categoryHasChildren')];
        endif;

        $brandIds = array_map(static function (object $brand): int {
            return (int) $brand->id;
        }, $brands);

        return [
            'result' => true,
            'brand_ids' => $brandIds,
            'category_ids' => $categoryIds,
        ];
    }

    /**
     * Inserisce le associazioni pivot del prodotto.
     *
     * @param int $productId Identificativo numerico del prodotto
     * @param array<int, int> $brandIds Identificativi delle marche
     * @param array<int, int> $categoryIds Identificativi delle categorie
     * @return bool True quando tutte le query sono state eseguite
     */
    private function insertRelations(int $productId, array $brandIds, array $categoryIds): bool
    {
        foreach ($brandIds as $brandId):
            $sql = 'insert into brands_to_products (brand_id, product_id, status) values (?, ?, 1)';

            if ($this->db->query($sql, [$brandId, $productId]) === false):
                return false;
            endif;
        endforeach;

        foreach ($categoryIds as $categoryId):
            $sql = 'insert into categories_to_products (category_id, product_id, status) values (?, ?, 1)';

            if ($this->db->query($sql, [$categoryId, $productId]) === false):
                return false;
            endif;
        endforeach;

        return true;
    }

    /**
     * Sostituisce integralmente le associazioni del prodotto.
     *
     * @param int $productId Identificativo numerico del prodotto
     * @param array<int, int> $brandIds Nuove marche associate
     * @param array<int, int> $categoryIds Nuove categorie associate
     * @return bool True quando eliminazioni e inserimenti sono riusciti
     */
    private function replaceRelations(int $productId, array $brandIds, array $categoryIds): bool
    {
        $deleteBrands = $this->db->query(
            'delete from brands_to_products where product_id = ?',
            [$productId]
        );

        $deleteCategories = $this->db->query(
            'delete from categories_to_products where product_id = ?',
            [$productId]
        );

        if ($deleteBrands === false || $deleteCategories === false):
            return false;
        endif;

        return $this->insertRelations($productId, $brandIds, $categoryIds);
    }

    /**
     * Verifica se sono cambiate le associazioni pivot.
     *
     * @param object $product Stato corrente del prodotto
     * @param array<int, mixed> $brandUuids Nuove marche selezionate
     * @param array<int, mixed> $categoryUuids Nuove categorie selezionate
     * @return bool True quando almeno una relazione è cambiata
     */
    private function relationsChanged(object $product, array $brandUuids, array $categoryUuids): bool
    {
        $currentBrands = $this->normalizeUuidList($product->brand_uuids ?? []);
        $currentCategories = $this->normalizeUuidList($product->category_uuids ?? []);
        $newBrands = $this->normalizeUuidList($brandUuids);
        $newCategories = $this->normalizeUuidList($categoryUuids);

        sort($currentBrands);
        sort($currentCategories);
        sort($newBrands);
        sort($newCategories);

        return $currentBrands !== $newBrands || $currentCategories !== $newCategories;
    }

    /**
     * Verifica se dati, immagini o associazioni hanno subito modifiche.
     *
     * @param array<string, mixed> $posts Dati provenienti dal form
     * @param object $product Stato corrente del prodotto
     * @return bool True quando è necessario aggiornare il record
     */
    public function hasProductChanged(array $posts, object $product): bool
    {
        if ($this->hasDataChanged($posts, $product)):
            return true;
        endif;

        return $this->relationsChanged(
            $product,
            $posts['brand_uuids'] ?? [],
            $posts['category_uuids'] ?? []
        );
    }

    /* OPERAZIONI CRUD ----------------------------------------------------------------------------- */

    /**
     * Crea un prodotto e le relative associazioni in modo transazionale.
     *
     * @param array<string, mixed> $posts Dataset validato dal controller
     * @return array{result: bool, message: string}
     */
    public function add(array $posts): array
    {
        $uuid = '';
        $uploadedFilenames = [];

        try {
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);
            $uuid = $this->generateUUID();

            $this->db->transBegin();

            $relations = $this->resolveRelations(
                $posts['brand_uuids'],
                $posts['category_uuids']
            );

            if ($relations['result'] === false):
                $this->db->transRollback();

                return $relations;
            endif;

            $sql = "insert into products
                        (uuid, sku, product, note, quantity, price, status, created_at)
                    values (?, ?, ?, ?, ?, ?, ?, ?)";

            $inserted = $this->db->query($sql, [
                $uuid,
                trim($posts['sku']),
                trim($posts['product']),
                trim($posts['note']) !== '' ? trim($posts['note']) : null,
                (int) $posts['quantity'],
                number_format((float) $posts['price'], 2, '.', ''),
                (int) $posts['status'],
                date('Y-m-d H:i:s'),
            ]);

            $productId = (int) $this->db->insertID();

            if ($inserted === false || $productId < 1):
                throw new \RuntimeException('Inserimento Products non completato.');
            endif;

            if ( ! $this->insertRelations($productId, $relations['brand_ids'], $relations['category_ids'])):
                throw new \RuntimeException('Associazioni Products non completate.');
            endif;

            if ( ! empty($posts['images'])):
                $filenames = $this->getUploadService()->doUpload($posts['images'], 'products', $uuid);
                $uploadedFilenames = $filenames ?: [];

                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Products non completato.');
                endif;

                if ( ! $this->insertImages($filenames, $uuid, 'products', 'add')):
                    throw new \RuntimeException('Registrazione immagini Products non completata.');
                endif;
            endif;

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Transazione Products non completata.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit Products non completato.');
            endif;

            $this->logProductActivity(
                'ADD_PRODUCTS',
                sprintf(lang('backend/products.audits.addProduct'), esc($posts['product']))
            );

            return [
                'result' => true,
                'message' => sprintf(lang('backend/products.messages.addSuccess'), esc($posts['product'])),
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uuid);

            log_message('error', lang('backend/products.messages.addError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.addError')];
        }
    }

    /**
     * Aggiorna un prodotto e sostituisce le relative associazioni.
     *
     * @param array<string, mixed> $posts Dataset validato dal controller
     * @return array{result: bool, message: string, row?: object}
     */
    public function edit(array $posts): array
    {
        $uuid = (string) ($posts['uuid'] ?? '');
        $uploadedFilenames = [];

        try {
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);
            $uuid = $posts['uuid'];

            $this->db->transBegin();

            $current = $this->getProductForUpdate($uuid);

            if ($current['result'] === false):
                $this->db->transRollback();

                return $current;
            endif;

            if ($current['row']->deleted_at !== null):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;

            $relations = $this->resolveRelations(
                $posts['brand_uuids'],
                $posts['category_uuids']
            );

            if ($relations['result'] === false):
                $this->db->transRollback();

                return $relations;
            endif;

            if ( ! $this->hasProductChanged($posts, $current['row'])):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.noDataChanged')];
            endif;

            $updatedAt = date('Y-m-d H:i:s');

            $sql = "update products
                    set sku = ?,
                        product = ?,
                        note = ?,
                        quantity = ?,
                        price = ?,
                        status = ?,
                        updated_at = ?
                    where uuid = ?";

            $updated = $this->db->query($sql, [
                trim($posts['sku']),
                trim($posts['product']),
                trim($posts['note']) !== '' ? trim($posts['note']) : null,
                (int) $posts['quantity'],
                number_format((float) $posts['price'], 2, '.', ''),
                (int) $posts['status'],
                $updatedAt,
                $uuid,
            ]);

            if ($updated === false):
                throw new \RuntimeException('Aggiornamento Products non completato.');
            endif;

            if ( ! $this->replaceRelations(
                (int) $current['row']->id,
                $relations['brand_ids'],
                $relations['category_ids']
            )):
                throw new \RuntimeException('Aggiornamento associazioni Products non completato.');
            endif;

            if ( ! empty($posts['images'])):
                $filenames = $this->getUploadService()->doUpload($posts['images'], 'products', $uuid);
                $uploadedFilenames = $filenames ?: [];

                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Products non completato.');
                endif;

                if ( ! $this->insertImages($filenames, $uuid, 'products', 'edit')):
                    throw new \RuntimeException('Registrazione immagini Products non completata.');
                endif;
            endif;

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Transazione Products non completata.');
            endif;

            $result = $this->getProduct($uuid);

            if ($result['result'] === false):
                throw new \RuntimeException('Rilettura Products non completata.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit Products non completato.');
            endif;

            $this->logProductActivity(
                'EDIT_PRODUCTS',
                sprintf(lang('backend/products.audits.editProduct'), esc($posts['product']))
            );

            return [
                'result' => true,
                'message' => sprintf(lang('backend/products.messages.editSuccess'), esc($posts['product'])),
                'row' => $result['row'],
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uuid);

            log_message('error', lang('backend/products.messages.editError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.editError')];
        }
    }

    /**
     * Elimina definitivamente un prodotto già cestinato.
     *
     * Le associazioni pivot vengono rimosse dalle foreign key con on delete
     * cascade; immagini e cartella fisica vengono gestite esplicitamente.
     *
     * @param array<string, mixed> $posts Dataset contenente l'UUID
     * @return array{result: bool, message: string, cleanup?: bool}
     */
    public function hardDelete(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            $this->db->transBegin();

            $product = $this->getProductForUpdate($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();

                return $product;
            endif;

            if ($product['row']->deleted_at === null):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.hardDeleteRequiresTrash')];
            endif;

            $deletedProduct = $this->db->query(
                'delete from products where uuid = ?',
                [$posts['uuid']]
            );

            $deletedImages = $this->db->query(
                "delete from images where entity = 'products' and entity_uuid = ?",
                [$posts['uuid']]
            );

            if ($deletedProduct === false || $deletedImages === false || $this->db->transStatus() === false):
                throw new \RuntimeException('Eliminazione Products non completata.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit eliminazione Products non completato.');
            endif;

            $this->logProductActivity(
                'HARD_DELETE_PRODUCTS',
                sprintf(lang('backend/products.audits.hardDeleteProduct'), esc($product['row']->product))
            );

            try {
                $cleanup = ImageFileSystemService::removeAllImages('products', $posts['uuid']);
            } catch (\Throwable $e) {
                $cleanup = false;
                log_message('error', lang('backend/products.messages.hardDeleteImagesError') . ' - ' . $e->getMessage());
            }

            $message = sprintf(lang('backend/products.messages.hardDeleteSuccess'), esc($product['row']->product));

            if ( ! $cleanup):
                log_message('error', lang('backend/products.messages.hardDeleteImagesError'));
                $message .= ' ' . lang('backend/products.messages.hardDeleteImagesError');
            endif;

            return [
                'result' => true,
                'cleanup' => $cleanup,
                'message' => $message,
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', lang('backend/products.messages.hardDeleteError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.hardDeleteError')];
        }
    }

    /**
     * Sposta un prodotto nel cestino senza rimuovere le associazioni pivot.
     *
     * @param array<string, mixed> $posts Dataset contenente l'UUID
     * @return array{result: bool, message: string}
     */
    public function softDelete(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            $this->db->transBegin();

            $product = $this->getProductForUpdate($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();

                return $product;
            endif;

            if ($product['row']->deleted_at !== null):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;

            $deleted = $this->db->query(
                'update products set deleted_at = now() where uuid = ?',
                [$posts['uuid']]
            );

            if ($deleted === false || $this->db->transStatus() === false):
                throw new \RuntimeException('Cestinamento Products non completato.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit cestinamento Products non completato.');
            endif;

            $this->logProductActivity(
                'SOFT_DELETE_PRODUCTS',
                sprintf(lang('backend/products.audits.softDeleteProduct'), esc($product['row']->product))
            );

            return [
                'result' => true,
                'message' => sprintf(lang('backend/products.messages.softDeleteSuccess'), esc($product['row']->product)),
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', lang('backend/products.messages.softDeleteError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.softDeleteError')];
        }
    }

    /**
     * Ripristina un prodotto precedentemente spostato nel cestino.
     *
     * @param array<string, mixed> $posts Dataset contenente l'UUID
     * @return array{result: bool, message: string}
     */
    public function restoreDelete(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            $this->db->transBegin();

            $product = $this->getProductForUpdate($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();

                return $product;
            endif;

            if ($product['row']->deleted_at === null):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.restoreRequiresTrash')];
            endif;

            $restoredAt = date('Y-m-d H:i:s');
            $relations = $this->resolveRelations($product['row']->brand_uuids, $product['row']->category_uuids);

            if ($relations['result'] === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => $relations['message']];
            endif;

            $restored = $this->db->query(
                'update products set deleted_at = null, restored_at = ? where uuid = ?',
                [$restoredAt, $posts['uuid']]
            );

            if ($restored === false || $this->db->transStatus() === false):
                throw new \RuntimeException('Ripristino Products non completato.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit ripristino Products non completato.');
            endif;

            $this->logProductActivity(
                'RESTORE_DELETE_PRODUCTS',
                sprintf(lang('backend/products.audits.restoreDeleteProduct'), esc($product['row']->product))
            );

            return [
                'result' => true,
                'message' => sprintf(lang('backend/products.messages.restoreDeleteSuccess'), esc($product['row']->product)),
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', lang('backend/products.messages.restoreDeleteError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.restoreDeleteError')];
        }
    }

    /**
     * Alterna lo stato attivo/inattivo di un prodotto.
     *
     * @param array<string, mixed> $posts Dataset contenente l'UUID
     * @return array{result: bool, message: string, product?: object}
     */
    public function changeStatus(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);

            $this->db->transBegin();

            $product = $this->getProductForUpdate($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();

                return $product;
            endif;

            if ($product['row']->deleted_at !== null):
                $this->db->transRollback();

                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;

            $newStatus = ((int) $product['row']->status === 1) ? 0 : 1;
            $updatedAt = date('Y-m-d H:i:s');

            $updated = $this->db->query(
                'update products set status = ?, updated_at = ? where uuid = ?',
                [$newStatus, $updatedAt, $posts['uuid']]
            );

            if ($updated === false || $this->db->transStatus() === false):
                throw new \RuntimeException('Cambio stato Products non completato.');
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit cambio stato Products non completato.');
            endif;

            $product['row']->status = $newStatus;
            $product['row']->updated_at = $updatedAt;

            $this->logProductActivity(
                'CHANGE_STATUS_PRODUCTS',
                sprintf(lang('backend/products.audits.changeStatusProduct'), esc($product['row']->product))
            );

            return [
                'result' => true,
                'message' => sprintf(lang('backend/products.messages.changeStatusSuccess'), esc($product['row']->product)),
                'product' => $product['row'],
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', lang('backend/products.messages.changeStatusError') . ' - ' . $e->getMessage());

            return ['result' => false, 'message' => lang('backend/products.messages.changeStatusError')];
        }
    }
}
