<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Models;

use App\Interfaces\ExportValidationInterface;
use App\Models\Backend\BackendModel;
use Modules\Backend\Categories\Models\CategoriesModel;

/**
 * Gestisce prodotti, immagini e associazioni molti-a-molti con marche e categorie.
 */
class ProductsModel extends BackendModel implements ExportValidationInterface
{
    protected ?string $module = 'products';
    protected bool $hasSoftDelete = true;

    protected array $showAllAllowedFields = ['id', 'column', 'order', 'page', 'rows', 'searchFields', 'searchDates', 'trash_filter'];
    protected array $showAllSearchAllowedFields = ['sku', 'product', 'status', 'quantity-from', 'quantity-to', 'price-from', 'price-to', 'brand_uuid', 'category_uuid'];
    protected array $showAllSearchAllowedDates = ['created_at', 'updated_at'];
    protected array $allowedOrderColumns = ['id', 'sku', 'product', 'quantity', 'price', 'status'];
    protected array $addAllowedFields = ['sku', 'product', 'note', 'quantity', 'price', 'status', 'brand_uuids', 'category_uuids', 'images'];
    protected array $editAllowedFields = ['uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status', 'brand_uuids', 'category_uuids', 'images'];
    protected array $delAllowedFields = ['uuid'];
    protected array $changeStatusAllowedFields = ['uuid'];
    protected array $toCompare = ['sku', 'product', 'note', 'quantity', 'price', 'status'];

    protected ?string $getDataQuery = "select products.id, products.uuid, products.sku, products.product, products.quantity, products.price,
                                        products.status, products.created_at, products.updated_at, products.deleted_at, products.restored_at,
                                        (select images.filename from images where images.entity_uuid = products.uuid and images.entity = 'products' and images.is_cover = 1 limit 1) as cover,
                                        (select count(*) from images where images.entity_uuid = products.uuid and images.entity = 'products') as images_num,
                                        (select count(*) from brands_to_products where brands_to_products.product_id = products.id) as brands_num,
                                        (select count(*) from categories_to_products where categories_to_products.product_id = products.id) as categories_num,
                                        (select group_concat(brands.brand order by brands.brand separator ', ')
                                            from brands_to_products inner join brands on brands.id = brands_to_products.brand_id
                                            where brands_to_products.product_id = products.id) as brands,
                                        (select group_concat(categories.category order by categories.category separator ', ')
                                            from categories_to_products inner join categories on categories.id = categories_to_products.category_id
                                            where categories_to_products.product_id = products.id) as categories
                                        from products where 1 = 1";

    protected ?string $getUUIDQuery = "select id, uuid, sku, product, note, quantity, price, status, created_at, updated_at, deleted_at, restored_at
                                        from products where uuid = ? limit 1";

    protected ?string $getNumRowsQuery = 'select count(*) as count from products where 1 = 1';

    protected function initModel(): void
    {
        parent::initModel();
    }

    protected function cleanupUploadedImages(array $filenames, string $uuid): void
    {
        foreach ($filenames as $filename):
            \App\Libraries\ImageFileSystemService::removeSingleImage('products', $uuid, $filename);
        endforeach;
    }

    /**
     * Estende i filtri del motore CRUD con intervalli numerici e relazioni UUID.
     */
    protected function buildFilters(array $searchFields, array &$params): string
    {
        $whereClause = '';

        foreach ($searchFields as $key => $value):
            if ( ! in_array($key, $this->showAllSearchAllowedFields, true) || trim((string) $value) === ''):
                continue;
            endif;

            if (in_array($key, ['sku', 'product', 'status'], true)):
                $whereClause .= " and products.{$key} like ?";
                $params[] = "%{$value}%";
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
                $whereClause .= ' and exists (select 1 from brands_to_products inner join brands on brands.id = brands_to_products.brand_id where brands_to_products.product_id = products.id and brands.uuid = ?)';
                $params[] = (string) $value;
            elseif ($key === 'category_uuid'):
                $whereClause .= ' and exists (select 1 from categories_to_products inner join categories on categories.id = categories_to_products.category_id where categories_to_products.product_id = products.id and categories.uuid = ?)';
                $params[] = (string) $value;
            endif;
        endforeach;

        return $whereClause;
    }

    public function getBrandOptions(): array
    {
        return $this->db->query("select uuid, brand, status from brands where deleted_at is null order by brand asc")->getResult();
    }

    public function getCategoryOptions(): array
    {
        $rows = $this->db->query("select categories.uuid, categories.category, categories.status
                                  from categories
                                  where categories.deleted_at is null
                                  and not exists (select 1 from categories as child where child.parent_id = categories.id)
                                  order by categories.category asc")->getResult();
        $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy();

        foreach ($rows as $row):
            $row->path = $hierarchy['pathsByUuid'][(string) $row->uuid] ?? $row->category;
        endforeach;

        usort($rows, static fn(object $first, object $second): int => strnatcasecmp((string) $first->path, (string) $second->path));

        return $rows;
    }

    public function getProduct(string $uuid): array
    {
        $result = $this->getByUUID($uuid);

        if ($result['result'] === false):
            return $result;
        endif;

        $productId = (int) $result['row']->id;
        $result['row']->brand_uuids = [];
        $result['row']->category_uuids = [];
        $result['row']->brands = $this->db->query("select brands.uuid, brands.brand, brands.status
                                                  from brands_to_products
                                                  inner join brands on brands.id = brands_to_products.brand_id
                                                  where brands_to_products.product_id = ? order by brands.brand asc", [$productId])->getResult();
        $categories = $this->db->query("select categories.uuid, categories.category, categories.status
                                        from categories_to_products
                                        inner join categories on categories.id = categories_to_products.category_id
                                        where categories_to_products.product_id = ? order by categories.category asc", [$productId])->getResult();
        $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy(true);

        foreach ($result['row']->brands as $brand):
            $result['row']->brand_uuids[] = (string) $brand->uuid;
        endforeach;

        foreach ($categories as $category):
            $category->path = $hierarchy['pathsByUuid'][(string) $category->uuid] ?? $category->category;
            $result['row']->category_uuids[] = (string) $category->uuid;
        endforeach;

        $result['row']->categories = $categories;

        return $result;
    }

    public function showAllValidationRules(): array
    {
        return [
            'column' => ['rules' => ['required', 'alpha_dash']],
            'order' => ['rules' => ['required', 'in_list[asc,desc]']],
            'page' => ['rules' => ['required', 'is_natural_no_zero']],
            'rows' => ['rules' => ['required', 'is_natural_no_zero']],
            'trash_filter' => ['rules' => ['in_list[active,trashed,all]']],
        ];
    }

    public function showAllSearchValidationRules(): array
    {
        $rules = [
            'searchFields.sku' => ['label' => lang('backend/products.labels.sku'), 'rules' => ['permit_empty', 'max_length[100]', 'regex_match[/^[A-Za-z0-9._\/-]+$/]']],
            'searchFields.product' => ['label' => lang('backend/products.labels.product'), 'rules' => ['permit_empty', 'max_length[255]', 'safeText']],
            'searchFields.status' => ['label' => lang('backend/products.labels.status'), 'rules' => ['permit_empty', 'in_list[0,1]']],
            'searchFields.quantity-from' => ['label' => lang('backend/products.labels.quantityFrom'), 'rules' => ['permit_empty', 'is_natural', 'less_than_equal_to[4294967295]']],
            'searchFields.quantity-to' => ['label' => lang('backend/products.labels.quantityTo'), 'rules' => ['permit_empty', 'is_natural', 'less_than_equal_to[4294967295]']],
            'searchFields.price-from' => ['label' => lang('backend/products.labels.priceFrom'), 'rules' => ['permit_empty', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]']],
            'searchFields.price-to' => ['label' => lang('backend/products.labels.priceTo'), 'rules' => ['permit_empty', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]']],
            'searchFields.brand_uuid' => ['label' => lang('backend/products.labels.brands'), 'rules' => ['permit_empty', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']],
            'searchFields.category_uuid' => ['label' => lang('backend/products.labels.categories'), 'rules' => ['permit_empty', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']],
        ];

        foreach (['created_at-from', 'created_at-to', 'updated_at-from', 'updated_at-to'] as $field):
            $rules['searchDates.' . $field] = ['label' => lang('backend/products.labels.date'), 'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]']];
        endforeach;

        return $rules;
    }

    private function productValidationRules(?string $uuid = null): array
    {
        $skuUnique = ($uuid === null) ? 'is_unique[products.sku]' : "is_unique[products.sku,uuid,{$uuid}]";

        $rules = [
            'sku' => ['label' => lang('backend/products.labels.sku'), 'rules' => ['required', 'trim', 'max_length[100]', $skuUnique, 'regex_match[/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/]']],
            'product' => ['label' => lang('backend/products.labels.product'), 'rules' => ['required', 'trim', 'min_length[2]', 'max_length[255]', 'safeText']],
            'quantity' => ['label' => lang('backend/products.labels.quantity'), 'rules' => ['required', 'is_natural', 'less_than_equal_to[4294967295]']],
            'price' => ['label' => lang('backend/products.labels.price'), 'rules' => ['required', 'decimal', 'greater_than_equal_to[0]', 'less_than_equal_to[9999999999.99]']],
            'status' => ['label' => lang('backend/products.labels.status'), 'rules' => ['required', 'in_list[0,1]']],
            'note' => ['label' => lang('backend/products.labels.note'), 'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText']],
            'brand_uuids' => ['label' => lang('backend/products.labels.brands'), 'rules' => ['required', 'is_array']],
            'brand_uuids.*' => ['label' => lang('backend/products.labels.brands'), 'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']],
            'category_uuids' => ['label' => lang('backend/products.labels.categories'), 'rules' => ['required', 'is_array']],
            'category_uuids.*' => ['label' => lang('backend/products.labels.categories'), 'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']],
            'images' => ['label' => lang('backend/products.labels.images'), 'rules' => ['permit_empty', 'checkImages']],
        ];

        if ($uuid !== null):
            $rules = ['uuid' => ['label' => lang('backend/products.labels.uuid'), 'rules' => ['required', "is_unique[products.uuid,uuid,{$uuid}]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']]] + $rules;
        endif;

        return $rules;
    }

    public function addValidationRules(): array
    {
        return $this->productValidationRules();
    }

    public function editValidationRules(array $posts): array
    {
        return $this->productValidationRules((string) ($posts['uuid'] ?? ''));
    }

    public function exportAllowedFields(): array
    {
        return ['uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'];
    }

    public function delValidationRules(): array
    {
        return ['uuid' => ['label' => lang('backend/products.labels.uuid'), 'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']]];
    }

    public function changeStatusValidationRules(): array
    {
        return $this->delValidationRules() + [
            'context' => ['label' => lang('backend/products.labels.context'), 'rules' => ['permit_empty', 'in_list[show]']],
        ];
    }

    public function generalDataValidationRules(): array
    {
        return $this->delValidationRules() + [
            'context' => ['label' => lang('backend/products.labels.context'), 'rules' => ['required', 'in_list[show,edit]']],
        ];
    }

    public function metaDataValidationRules(): array
    {
        return $this->delValidationRules();
    }

    /**
     * Risolve e blocca le associazioni richieste, impedendo riferimenti invalidi.
     *
     * @return array{result: bool, brand_ids?: array<int>, category_ids?: array<int>, message?: string}
     */
    private function resolveRelations(array $brandUuids, array $categoryUuids): array
    {
        $brandUuids = array_values(array_unique(array_map('strval', $brandUuids)));
        $categoryUuids = array_values(array_unique(array_map('strval', $categoryUuids)));

        if ($brandUuids === [] || $categoryUuids === []):
            return ['result' => false, 'message' => lang('backend/products.messages.relationsRequired')];
        endif;

        $brandMarks = implode(', ', array_fill(0, count($brandUuids), '?'));
        $brands = $this->db->query("select id, uuid from brands where uuid in ({$brandMarks}) and deleted_at is null order by id for update", $brandUuids)->getResult();

        if (count($brands) !== count($brandUuids)):
            return ['result' => false, 'message' => lang('backend/products.messages.invalidBrands')];
        endif;

        $categoryMarks = implode(', ', array_fill(0, count($categoryUuids), '?'));
        $categories = $this->db->query("select id, uuid from categories where uuid in ({$categoryMarks}) and deleted_at is null order by id for update", $categoryUuids)->getResult();

        if (count($categories) !== count($categoryUuids)):
            return ['result' => false, 'message' => lang('backend/products.messages.invalidCategories')];
        endif;

        $categoryIds = array_map(static fn(object $row): int => (int) $row->id, $categories);
        $categoryIdMarks = implode(', ', array_fill(0, count($categoryIds), '?'));
        $hasChildren = $this->db->query("select 1 from categories where parent_id in ({$categoryIdMarks}) limit 1", $categoryIds)->getRow();

        if ($hasChildren !== null):
            return ['result' => false, 'message' => lang('backend/products.messages.categoryHasChildren')];
        endif;

        return [
            'result' => true,
            'brand_ids' => array_map(static fn(object $row): int => (int) $row->id, $brands),
            'category_ids' => $categoryIds,
        ];
    }

    private function insertRelations(int $productId, array $brandIds, array $categoryIds): void
    {
        foreach ($brandIds as $brandId):
            $this->db->query('insert into brands_to_products (brand_id, product_id, status) values (?, ?, 1)', [$brandId, $productId]);
        endforeach;

        foreach ($categoryIds as $categoryId):
            $this->db->query('insert into categories_to_products (category_id, product_id, status) values (?, ?, 1)', [$categoryId, $productId]);
        endforeach;
    }

    private function relationsChanged(object $product, array $brandUuids, array $categoryUuids): bool
    {
        $currentBrands = array_values(array_unique(array_map('strval', $product->brand_uuids ?? [])));
        $currentCategories = array_values(array_unique(array_map('strval', $product->category_uuids ?? [])));
        $newBrands = array_values(array_unique(array_map('strval', $brandUuids)));
        $newCategories = array_values(array_unique(array_map('strval', $categoryUuids)));
        sort($currentBrands);
        sort($currentCategories);
        sort($newBrands);
        sort($newCategories);

        return $currentBrands !== $newBrands || $currentCategories !== $newCategories;
    }

    public function add(array $posts): array
    {
        $uuid = '';
        $uploadedFilenames = [];

        try {
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);
            $uuid = $this->generateUUID();
            $this->db->transBegin();

            $relations = $this->resolveRelations($posts['brand_uuids'], $posts['category_uuids']);
            if ($relations['result'] === false):
                $this->db->transRollback();
                return $relations;
            endif;

            $this->db->query('insert into products (uuid, sku, product, note, quantity, price, status, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)', [
                $uuid,
                trim($posts['sku']),
                trim($posts['product']),
                trim($posts['note']) !== '' ? $posts['note'] : null,
                (int) $posts['quantity'],
                number_format((float) $posts['price'], 2, '.', ''),
                (int) $posts['status'],
                date('Y-m-d H:i:s'),
            ]);
            $productId = (int) $this->db->insertID();
            $this->insertRelations($productId, $relations['brand_ids'], $relations['category_ids']);

            if ( ! empty($posts['images'])):
                $filenames = $this->getUploadService()->doUpload($posts['images'], 'products', $uuid);
                $uploadedFilenames = $filenames ?: [];
                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Products non completato.');
                endif;
                $this->insertImages($filenames, $uuid, 'products', 'add');
            endif;

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Transazione Products non completata.');
            endif;

            $this->db->transCommit();
            log_admin_activity('ADD_PRODUCTS', 'products', sprintf(lang('backend/products.audits.addProduct'), esc($posts['product'])), service('authorization')->currentAdmin());

            return ['result' => true, 'message' => sprintf(lang('backend/products.messages.addSuccess'), esc($posts['product']))];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uuid);
            log_message('error', lang('backend/products.messages.addError') . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/products.messages.addError')];
        }
    }

    public function edit(array $posts): array
    {
        $uploadedFilenames = [];
        $uuid = (string) ($posts['uuid'] ?? '');

        try {
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);
            $this->db->transBegin();
            $this->db->query('select id from products where uuid = ? for update', [$uuid]);
            $current = $this->getProduct($uuid);

            if ($current['result'] === false):
                $this->db->transRollback();
                return $current;
            endif;

            if ($current['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;

            $relations = $this->resolveRelations($posts['brand_uuids'], $posts['category_uuids']);
            if ($relations['result'] === false):
                $this->db->transRollback();
                return $relations;
            endif;

            if ( ! $this->hasDataChanged($posts, $current['row']) && ! $this->relationsChanged($current['row'], $posts['brand_uuids'], $posts['category_uuids'])):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.noDataChanged')];
            endif;

            $updatedAt = date('Y-m-d H:i:s');
            $this->db->query('update products set sku = ?, product = ?, note = ?, quantity = ?, price = ?, status = ?, updated_at = ? where uuid = ?', [
                trim($posts['sku']),
                trim($posts['product']),
                trim($posts['note']) !== '' ? $posts['note'] : null,
                (int) $posts['quantity'],
                number_format((float) $posts['price'], 2, '.', ''),
                (int) $posts['status'],
                $updatedAt,
                $uuid,
            ]);

            $productId = (int) $current['row']->id;
            $this->db->query('delete from brands_to_products where product_id = ?', [$productId]);
            $this->db->query('delete from categories_to_products where product_id = ?', [$productId]);
            $this->insertRelations($productId, $relations['brand_ids'], $relations['category_ids']);

            if ( ! empty($posts['images'])):
                $filenames = $this->getUploadService()->doUpload($posts['images'], 'products', $uuid);
                $uploadedFilenames = $filenames ?: [];
                if ($filenames === false || count($filenames) !== count($posts['images'])):
                    throw new \RuntimeException('Upload immagini Products non completato.');
                endif;
                $this->insertImages($filenames, $uuid, 'products', 'edit');
            endif;

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Transazione Products non completata.');
            endif;

            $this->db->transCommit();
            $result = $this->getProduct($uuid);
            log_admin_activity('EDIT_PRODUCTS', 'products', sprintf(lang('backend/products.audits.editProduct'), esc($posts['product'])), service('authorization')->currentAdmin());

            return ['result' => true, 'message' => sprintf(lang('backend/products.messages.editSuccess'), esc($posts['product'])), 'row' => $result['row']];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->cleanupUploadedImages($uploadedFilenames, $uuid);
            log_message('error', lang('backend/products.messages.editError') . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/products.messages.editError')];
        }
    }

    public function hardDelete(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);
            $this->db->transBegin();
            $this->db->query('select id from products where uuid = ? for update', [$posts['uuid']]);
            $product = $this->getProduct($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();
                return $product;
            endif;
            if ($product['row']->deleted_at === null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.hardDeleteRequiresTrash')];
            endif;

            $this->db->query('delete from products where uuid = ?', [$posts['uuid']]);
            $this->db->query("delete from images where entity = 'products' and entity_uuid = ?", [$posts['uuid']]);

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Eliminazione Products non completata.');
            endif;

            $this->db->transCommit();
            log_admin_activity('HARD_DELETE_PRODUCTS', 'products', sprintf(lang('backend/products.audits.hardDeleteProduct'), esc($product['row']->product)), service('authorization')->currentAdmin());
            $cleanup = \App\Libraries\ImageFileSystemService::removeAllImages('products', $posts['uuid']);

            return [
                'result' => true,
                'cleanup' => $cleanup,
                'message' => sprintf(lang('backend/products.messages.hardDeleteSuccess'), esc($product['row']->product)) . ($cleanup ? '' : ' ' . lang('backend/products.messages.hardDeleteImagesError')),
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/products.messages.hardDeleteError') . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/products.messages.hardDeleteError')];
        }
    }

    public function softDelete(array $posts): array
    {
        return $this->setDeletedState($posts, true);
    }

    public function restoreDelete(array $posts): array
    {
        return $this->setDeletedState($posts, false);
    }

    private function setDeletedState(array $posts, bool $delete): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);
            $this->db->transBegin();
            $this->db->query('select id from products where uuid = ? for update', [$posts['uuid']]);
            $product = $this->getProduct($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();
                return $product;
            endif;

            if ($delete && $product['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;
            if ( ! $delete && $product['row']->deleted_at === null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.restoreRequiresTrash')];
            endif;

            if ($delete):
                $this->db->query('update products set deleted_at = now() where uuid = ?', [$posts['uuid']]);
            else:
                $this->db->query('update products set deleted_at = null, restored_at = ? where uuid = ?', [date('Y-m-d H:i:s'), $posts['uuid']]);
            endif;

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Aggiornamento cestino Products non completato.');
            endif;

            $this->db->transCommit();
            $action = $delete ? 'SOFT_DELETE_PRODUCTS' : 'RESTORE_DELETE_PRODUCTS';
            $audit = $delete ? 'softDeleteProduct' : 'restoreDeleteProduct';
            $message = $delete ? 'softDeleteSuccess' : 'restoreDeleteSuccess';
            log_admin_activity($action, 'products', sprintf(lang('backend/products.audits.' . $audit), esc($product['row']->product)), service('authorization')->currentAdmin());

            return ['result' => true, 'message' => sprintf(lang('backend/products.messages.' . $message), esc($product['row']->product))];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $message = $delete ? 'softDeleteError' : 'restoreDeleteError';
            log_message('error', lang('backend/products.messages.' . $message) . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/products.messages.' . $message)];
        }
    }

    public function changeStatus(array $posts): array
    {
        try {
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);
            $this->db->transBegin();
            $this->db->query('select id from products where uuid = ? for update', [$posts['uuid']]);
            $product = $this->getProduct($posts['uuid']);

            if ($product['result'] === false):
                $this->db->transRollback();
                return $product;
            endif;
            if ($product['row']->deleted_at !== null):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')];
            endif;

            $status = ((int) $product['row']->status === 1) ? 0 : 1;
            $updatedAt = date('Y-m-d H:i:s');
            $this->db->query('update products set status = ?, updated_at = ? where uuid = ?', [$status, $updatedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):
                throw new \RuntimeException('Cambio stato Products non completato.');
            endif;

            $this->db->transCommit();
            $product['row']->status = $status;
            $product['row']->updated_at = $updatedAt;
            log_admin_activity('CHANGE_STATUS_PRODUCTS', 'products', sprintf(lang('backend/products.audits.changeStatusProduct'), esc($product['row']->product)), service('authorization')->currentAdmin());

            return ['result' => true, 'message' => sprintf(lang('backend/products.messages.changeStatusSuccess'), esc($product['row']->product)), 'product' => $product['row']];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/products.messages.changeStatusError') . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/products.messages.changeStatusError')];
        }
    }
}
