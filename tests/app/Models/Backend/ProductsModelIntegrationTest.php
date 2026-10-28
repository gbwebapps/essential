<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\Components\ImportModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Modules\Backend\Products\Models\ProductsModel;
use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Categories\Models\CategoriesModel;

class ProductsModelIntegrationTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private ProductsModel $model;
    private string $firstBrandUuid;
    private string $secondBrandUuid;
    private string $firstCategoryUuid;
    private string $secondCategoryUuid;
    private string $productUuid = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = db_connect();
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->getDatabase());

        $this->database->transBegin();

        $identity = $this->database
            ->query('select uuid, email from admins order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($identity);

        $authorization = $this->createMock(AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn($identity);
        Services::injectMock('authorization', $authorization);

        $this->firstBrandUuid = $this->generateUuid();
        $this->secondBrandUuid = $this->generateUuid();
        $this->firstCategoryUuid = $this->generateUuid();
        $this->secondCategoryUuid = $this->generateUuid();

        $this->insertBrand($this->firstBrandUuid, 'Integration Brand A');
        $this->insertBrand($this->secondBrandUuid, 'Integration Brand B');
        $this->insertCategory($this->firstCategoryUuid, 'Integration Category A');
        $this->insertCategory($this->secondCategoryUuid, 'Integration Category B');

        $this->model = new ProductsModel();
    }

    protected function tearDown(): void
    {
        $this->database->transRollback();

        if ($this->productUuid !== ''):
            \App\Libraries\ImageFileSystemService::removeAllImages('products', $this->productUuid);
        endif;

        Services::resetSingle('authorization');

        parent::tearDown();
    }

    public function testProductLifecycleKeepsBothPivotTablesConsistent(): void
    {
        $sku = 'TEST-' . strtoupper(bin2hex(random_bytes(4)));

        $result = $this->model->add([
            'sku' => $sku,
            'product' => 'Integration Product',
            'note' => 'Initial note',
            'quantity' => '5',
            'price' => '19.90',
            'status' => '1',
            'brand_uuids' => [$this->firstBrandUuid],
            'category_uuids' => [$this->firstCategoryUuid],
            'images' => [],
        ]);

        $this->assertTrue($result['result']);

        $product = $this->database
            ->query('select id, uuid from products where sku = ?', [$sku])
            ->getRow();

        $this->assertNotNull($product);

        $this->productUuid = $product->uuid;

        $this->assertSame(1, $this->pivotCount('brands_to_products', (int) $product->id));
        $this->assertSame(1, $this->pivotCount('categories_to_products', (int) $product->id));

        $products = $this->model->getData([
            'column' => 'total_value',
            'order' => 'desc',
            'page' => '1',
            'rows' => '5',
            'trash_filter' => 'active',
            'searchFields' => ['sku' => $sku],
            'searchDates' => [],
        ]);

        $this->assertTrue($products['result']);
        $this->assertCount(1, $products['records']);
        $this->assertEquals(99.50, $products['records'][0]->total_value);

        $result = $this->model->edit([
            'uuid' => $this->productUuid,
            'sku' => $sku,
            'product' => 'Integration Product Updated',
            'note' => 'Updated note',
            'quantity' => '8',
            'price' => '29.90',
            'status' => '1',
            'brand_uuids' => [$this->secondBrandUuid],
            'category_uuids' => [$this->secondCategoryUuid],
            'images' => [],
        ]);

        $this->assertTrue($result['result']);
        $this->assertSame([$this->secondBrandUuid], $result['row']->brand_uuids);
        $this->assertSame([$this->secondCategoryUuid], $result['row']->category_uuids);

        $result = $this->model->changeStatus(['uuid' => $this->productUuid]);

        $this->assertTrue($result['result']);
        $this->assertSame(0, (int) $result['product']->status);

        $result = $this->model->softDelete(['uuid' => $this->productUuid]);

        $this->assertTrue($result['result']);
        $this->assertNotNull(
            $this->database->query('select deleted_at from products where uuid = ?', [$this->productUuid])->getRow()->deleted_at
        );

        $result = $this->model->restoreDelete(['uuid' => $this->productUuid]);

        $this->assertTrue($result['result']);

        $restored = $this->database
            ->query('select deleted_at, restored_at from products where uuid = ?', [$this->productUuid])
            ->getRow();

        $this->assertNull($restored->deleted_at);
        $this->assertNotNull($restored->restored_at);

        $this->assertTrue($this->model->softDelete(['uuid' => $this->productUuid])['result']);
        $this->assertTrue($this->model->hardDelete(['uuid' => $this->productUuid])['result']);

        $this->assertNull(
            $this->database->query('select uuid from products where uuid = ?', [$this->productUuid])->getRow()
        );
        $this->assertSame(0, $this->pivotCount('brands_to_products', (int) $product->id));
        $this->assertSame(0, $this->pivotCount('categories_to_products', (int) $product->id));
    }

    public function testProductCannotUseCategoryWithChildren(): void
    {
        $parent = $this->database
            ->query('select id from categories where uuid = ?', [$this->firstCategoryUuid])
            ->getRow();

        $this->assertNotNull($parent);

        $childUuid = $this->generateUuid();
        $this->insertCategory($childUuid, 'Integration Child Category', (int) $parent->id);

        $sku = 'TEST-' . strtoupper(bin2hex(random_bytes(4)));

        $result = $this->model->add([
            'sku' => $sku,
            'product' => 'Invalid Integration Product',
            'note' => '',
            'quantity' => '1',
            'price' => '1.00',
            'status' => '1',
            'brand_uuids' => [$this->firstBrandUuid],
            'category_uuids' => [$this->firstCategoryUuid],
            'images' => [],
        ]);

        $this->assertFalse($result['result']);
        $this->assertSame(lang('backend/products.messages.categoryHasChildren'), $result['message']);
        $this->assertNull(
            $this->database->query('select uuid from products where sku = ?', [$sku])->getRow()
        );
    }

    public function testLocalImportContractUpdatesExistingProductsOnly(): void
    {
        $allowedFields = ['uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'];

        $this->assertSame($allowedFields, $this->model->importAllowedFields());

        $importModel = new ImportModel();
        $structure = $importModel->getImportTableStructure('products');

        $this->assertSame($allowedFields, array_column($structure, 'name'));

        $this->assertSame(
            [lang('backend/products.messages.importExistingOnly')],
            $this->model->importBusinessValidationErrors('insert', [], null)
        );

        $this->assertSame(
            [],
            $this->model->importBusinessValidationErrors('update', [], ['deleted_at' => null])
        );

        $this->assertSame(
            [lang('backend/products.messages.cannotModifyDeleted')],
            $this->model->importBusinessValidationErrors('update', [], ['deleted_at' => '2026-01-01 00:00:00'])
        );
    }

    public function testAssociatedContainersCannotBeSuspendedByStatusEditOrCsv(): void
    {
        $this->addStatusProduct();
        $brands = new BrandsModel();
        $categories = new CategoriesModel();
        $brand = $brands->changeStatus(['uuid' => $this->firstBrandUuid]);
        $category = $categories->changeStatus(['uuid' => $this->firstCategoryUuid]);
        $this->assertFalse($brand['result']);
        $this->assertFalse($category['result']);
        $this->assertSame(lang('backend/brands.messages.suspendAssociated'), $brand['message']);
        $this->assertSame(lang('backend/categories.messages.suspendAssociated'), $category['message']);
        $brand = $brands->edit(['uuid' => $this->firstBrandUuid, 'brand' => 'Changed brand', 'status' => '0', 'note' => '', 'images' => []]);
        $category = $categories->edit(['uuid' => $this->firstCategoryUuid, 'category' => 'Changed category', 'parent_id' => '', 'status' => '0', 'note' => '', 'images' => []]);
        $this->assertFalse($brand['result']);
        $this->assertFalse($category['result']);

        $brandRow = $this->database->table('brands')->where('uuid', $this->firstBrandUuid)->get()->getRowArray();
        $categoryRow = $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->get()->getRowArray();
        $this->assertSame([lang('backend/brands.messages.suspendAssociated')], $brands->importBusinessValidationErrors('update', ['status' => '0'], $brandRow));
        $this->assertSame([lang('backend/categories.messages.suspendAssociated')], $categories->importBusinessValidationErrors('update', ['status' => '0'], $categoryRow));
        $this->assertSame(1, (int) $brandRow['status']);
        $this->assertSame(1, $this->database->transDepth);

        // Il cestino del prodotto e lo stato delle pivot non liberano i contenitori.
        $this->database->table('products')->where('uuid', $this->productUuid)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $productId = (int) $this->database->table('products')->where('uuid', $this->productUuid)->get()->getRow()->id;
        $this->database->table('brands_to_products')->where('product_id', $productId)->update(['status' => 0]);
        $this->database->table('categories_to_products')->where('product_id', $productId)->update(['status' => 0]);
        $this->assertFalse($brands->changeStatus(['uuid' => $this->firstBrandUuid])['result']);
        $this->assertFalse($categories->changeStatus(['uuid' => $this->firstCategoryUuid])['result']);
    }

    public function testEmptyContainersCanBeSuspendedAndReactivated(): void
    {
        foreach ([[new BrandsModel(), 'brands', $this->firstBrandUuid], [new CategoriesModel(), 'categories', $this->firstCategoryUuid]] as [$model, $table, $uuid]):
            $result = $model->changeStatus(['uuid' => $uuid]);
            $this->assertTrue($result['result'], $result['message']);
            $this->assertSame(0, (int) $this->database->table($table)->where('uuid', $uuid)->get()->getRow()->status);
            $result = $model->changeStatus(['uuid' => $uuid]);
            $this->assertTrue($result['result'], $result['message']);
            $this->assertSame(1, (int) $this->database->table($table)->where('uuid', $uuid)->get()->getRow()->status);
        endforeach;
    }

    public function testParentsAndSuspendedParentAssignmentsAreProtected(): void
    {
        $model = new CategoriesModel();
        $parentId = (int) $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->get()->getRow()->id;
        $child = $this->generateUuid();
        $this->insertCategory($child, 'Status child', $parentId);
        $result = $model->changeStatus(['uuid' => $this->firstCategoryUuid]);
        $this->assertFalse($result['result']);
        $this->assertSame(lang('backend/categories.messages.suspendHasChildren'), $result['message']);
        $this->database->table('categories')->where('uuid', $child)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->assertFalse($model->changeStatus(['uuid' => $this->firstCategoryUuid])['result']);

        $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->update(['status' => 0]);
        $added = $model->add(['category' => 'New child', 'parent_id' => (string) $parentId, 'status' => '1', 'note' => '', 'images' => []]);
        $edited = $model->edit(['uuid' => $this->secondCategoryUuid, 'category' => 'Changed', 'parent_id' => (string) $parentId, 'status' => '1', 'note' => '', 'images' => []]);
        $this->assertFalse($added['result']);
        $this->assertFalse($edited['result']);
        $this->assertSame(lang('backend/categories.messages.parentSuspended'), $added['message']);
        $this->database->table('categories')->where('uuid', $this->secondCategoryUuid)->update(['parent_id' => $parentId]);
        $omittedParent = $model->edit(['uuid' => $this->secondCategoryUuid, 'category' => 'Changed', 'status' => '1', 'note' => '', 'images' => []]);
        $this->assertFalse($omittedParent['result']);
        $this->assertSame(lang('backend/categories.messages.parentSuspended'), $omittedParent['message']);
        $this->assertSame($parentId, (int) $this->database->table('categories')->where('uuid', $this->secondCategoryUuid)->get()->getRow()->parent_id);
        $this->assertSame([lang('backend/categories.messages.parentSuspended')], $model->importBusinessValidationErrors('insert', ['parent_id' => $parentId], null));
        $childRow = $this->database->table('categories')->where('uuid', $child)->get()->getRowArray();
        $childRow['deleted_at'] = null;
        $this->assertSame([lang('backend/categories.messages.parentSuspended')], $model->importBusinessValidationErrors('update', ['note' => 'Partial update'], $childRow));
        $restored = $model->restoreDelete(['uuid' => $child]);
        $this->assertFalse($restored['result']);
        $this->assertSame(lang('backend/categories.messages.restoreParentUnavailable'), $restored['message']);
    }

    public function testSuspendedRelationsCannotBeAssignedOrRestored(): void
    {
        $this->addStatusProduct();
        $this->database->table('brands')->where('uuid', $this->firstBrandUuid)->update(['status' => 0]);
        $resolve = new \ReflectionMethod($this->model, 'resolveRelations');
        $this->assertFalse($resolve->invoke($this->model, [$this->firstBrandUuid], [$this->firstCategoryUuid])['result']);
        $this->model->softDelete(['uuid' => $this->productUuid]);
        $restored = $this->model->restoreDelete(['uuid' => $this->productUuid]);
        $this->assertFalse($restored['result']);
        $this->assertSame(lang('backend/products.messages.invalidBrands'), $restored['message']);
        $this->database->table('brands')->where('uuid', $this->firstBrandUuid)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $brand = (new BrandsModel())->restoreDelete(['uuid' => $this->firstBrandUuid]);
        $this->assertFalse($brand['result']);
        $this->assertSame(lang('backend/brands.messages.suspendAssociated'), $brand['message']);

        $this->database->table('brands')->where('uuid', $this->firstBrandUuid)->update(['status' => 1, 'deleted_at' => null]);
        $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->update(['status' => 0]);
        $this->assertFalse($resolve->invoke($this->model, [$this->firstBrandUuid], [$this->firstCategoryUuid])['result']);
        $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $category = (new CategoriesModel())->restoreDelete(['uuid' => $this->firstCategoryUuid]);
        $this->assertFalse($category['result']);
        $this->assertSame(lang('backend/categories.messages.suspendAssociated'), $category['message']);
        $this->database->table('categories')->where('uuid', $this->firstCategoryUuid)->update(['status' => 1, 'deleted_at' => null]);
        $restored = $this->model->restoreDelete(['uuid' => $this->productUuid]);
        $this->assertTrue($restored['result'], $restored['message']);
    }

    /** Tutte le scritture del prodotto di prova sono annullate dalla transazione del test. */
    private function addStatusProduct(): void
    {
        $sku = 'STATUS-' . bin2hex(random_bytes(5));
        $result = $this->model->add([
            'sku' => $sku, 'product' => 'Status product ' . $sku, 'quantity' => '1', 'price' => '1.00',
            'status' => '1', 'note' => '', 'images' => [],
            'brand_uuids' => [$this->firstBrandUuid], 'category_uuids' => [$this->firstCategoryUuid],
        ]);
        $this->assertTrue($result['result'], $result['message']);
        $this->productUuid = $this->database->table('products')->where('sku', $sku)->get()->getRow()->uuid;
    }

    private function insertBrand(string $uuid, string $brand): void
    {
        $this->database->query(
            'insert into brands (uuid, brand, note, status, created_at) values (?, ?, ?, ?, ?)',
            [$uuid, $brand . ' ' . bin2hex(random_bytes(3)), null, 1, date('Y-m-d H:i:s')]
        );
    }

    private function insertCategory(string $uuid, string $category, ?int $parentId = null): void
    {
        $this->database->query(
            'insert into categories (uuid, parent_id, category, note, status, created_at) values (?, ?, ?, ?, ?, ?)',
            [$uuid, $parentId, $category . ' ' . bin2hex(random_bytes(3)), null, 1, date('Y-m-d H:i:s')]
        );
    }

    private function pivotCount(string $table, int $productId): int
    {
        $allowedTables = ['brands_to_products', 'categories_to_products'];

        $this->assertContains($table, $allowedTables);

        return (int) $this->database
            ->query("select count(*) as count from {$table} where product_id = ?", [$productId])
            ->getRow()
            ->count;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);

        return vsprintf('%08s-%04s-%04s-%04s-%12s', sscanf($hex, '%8s%4s%4s%4s%12s'));
    }
}

