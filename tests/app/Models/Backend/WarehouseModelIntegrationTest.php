<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Warehouse\Models\WarehouseImportModel;
use Tests\Support\Database\WarehouseDatabaseFixture;

class WarehouseModelIntegrationTest extends CIUnitTestCase
{
    use WarehouseDatabaseFixture;

    private BaseConnection $database;
    private WarehouseImportModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startWarehouseDatabase();
        $this->database = $this->warehouseDatabase;
        $this->model = new WarehouseImportModel();

        $this->seed();
    }

    protected function tearDown(): void
    {
        $this->stopWarehouseDatabase();
        parent::tearDown();
    }

    public function testMergeResolvesUuidAndPreservesMissingEntitiesAndAssociations(): void
    {
        $workbook = $this->workbook($this->package());
        $planned = $this->model->planPackage($workbook);
        $this->assertTrue($planned['result'], implode('\n', $planned['validationErrors'] ?? []));
        $baseline = $planned['baseline'];
        $backupCalls = 0;
        $counts = $this->model->executePackagePlan(
            $workbook,
            $planned,
            function(array $snapshot) use (&$backupCalls, $baseline): void {
                $backupCalls++;
                $this->assertSame($baseline, $this->baseline());
                $this->assertSame('OLD-SKU', $snapshot['products']['rows'][0]['sku']);
            }
        );

        $this->assertSame(1, $backupCalls);
        $this->assertSame(['inserted' => 6, 'updated' => 4, 'skipped' => 1], $counts);
        $this->assertSame('NEW-SKU', $this->database->table('products')->where('id', 30)->get()->getRow()->sku);
        $this->assertSame('Keep product', $this->database->table('products')->where('id', 31)->get()->getRow()->product);

        $newBrand = $this->database->table('brands')->where('uuid', $this->uuid(103))->get()->getRow();
        $newProduct = $this->database->table('products')->where('uuid', $this->uuid(303))->get()->getRow();
        $newCategory = $this->database->table('categories')->where('uuid', $this->uuid(204))->get()->getRow();

        $this->assertNotNull($newBrand);
        $this->assertNotNull($newProduct);
        $this->assertNotNull($newCategory);
        $this->assertNotSame(100, (int) $newBrand->id);
        $this->assertNotSame(100, (int) $newProduct->id);
        $this->assertNotNull($newProduct->created_at);
        $this->assertNull($newProduct->updated_at);
        $updatedProduct = $this->database->table('products')->where('id', 30)->get()->getRow();
        $this->assertSame('2026-10-22 10:00:00', $updatedProduct->created_at);
        $this->assertNotNull($updatedProduct->updated_at);
        $this->assertSame(10, (int) $newCategory->parent_id);
        $this->assertSame(1, $this->database->table('brands_to_products')->where(['brand_id' => $newBrand->id, 'product_id' => $newProduct->id])->countAllResults());
        $this->assertSame(1, $this->database->table('categories_to_products')->where(['category_id' => $newCategory->id, 'product_id' => $newProduct->id])->countAllResults());
        $this->assertSame(1, $this->database->table('brands_to_products')->where(['brand_id' => 1, 'product_id' => 30])->countAllResults());
        $this->assertSame(1, $this->database->table('categories_to_products')->where(['category_id' => 12, 'product_id' => 31])->countAllResults());
    }

    public function testFinalValidationFailureRollsBackEveryTable(): void
    {
        $before = $this->baseline();
        $workbook = $this->workbook($this->package());
        $planned = $this->model->planPackage($workbook);
        $this->assertTrue($planned['result'], implode('\n', $planned['validationErrors'] ?? []));
        $reader = $this->model;
        $model = $this->getMockBuilder(WarehouseImportModel::class)->onlyMethods(['readPackageSnapshot'])->getMock();
        $model->method('readPackageSnapshot')->willReturnCallback(static function(bool $lockRows = false) use ($reader): array {
            $snapshot = $reader->readPackageSnapshot($lockRows);

            if ( ! $lockRows):
                $snapshot['brands_to_products']['rows'] = [];
            endif;

            return $snapshot;
        });

        try
        {
            $model->executePackagePlan($workbook, $planned, static function(array $snapshot): void {});
            $this->fail('Final validation must abort the transaction.');
        }
        catch (\DomainException $e)
        {
            $this->assertStringContainsString(sprintf(lang('backend/warehouse.messages.missingProductRelations'), 'NEW-SKU'), $e->getMessage());
        }

        $this->assertSame($before, $this->baseline());
    }

    public function testConcurrentChangeRejectsImportBeforeBackupAndWrites(): void
    {
        $workbook = $this->workbook($this->package());
        $planned = $this->model->planPackage($workbook);
        $this->assertTrue($planned['result'], implode('\n', $planned['validationErrors'] ?? []));
        $this->database->table('products')->where('id', 31)->update(['quantity' => 50]);
        $afterConcurrentChange = $this->baseline();
        $backupCalled = false;

        try
        {
            $this->model->executePackagePlan($workbook, $planned, static function(array $snapshot) use (&$backupCalled): void {
                $backupCalled = true;
            });
            $this->fail('The changed baseline must abort the import.');
        }
        catch (\DomainException $e)
        {
            $this->assertSame(lang('backend/warehouse.messages.databaseChanged'), $e->getMessage());
        }

        $this->assertFalse($backupCalled);
        $this->assertSame($afterConcurrentChange, $this->baseline());
    }

    public function testNaturalKeyCollisionDoesNotMergeDifferentUuids(): void
    {
        $package = $this->package();
        $package['brands'][1]['brand'] = 'Keep brand';
        $before = $this->baseline();
        $planned = $this->model->planPackage($this->workbook($package));

        $this->assertFalse($planned['result']);
        $this->assertNotEmpty($planned['validationErrors']);
        $this->assertSame($before, $this->baseline());
    }

    public function testPreservedProductLinkPreventsTurningLeafIntoParent(): void
    {
        $package = $this->package();
        $package['categories'][2]['parent_id'] = 11;
        $before = $this->baseline();
        $planned = $this->model->planPackage($this->workbook($package));

        $this->assertFalse($planned['result']);
        $this->assertContains(sprintf(lang('backend/warehouse.messages.mixedCategoryContents'), 11), $planned['validationErrors']);
        $this->assertSame($before, $this->baseline());
    }

    public function testWorkbookRejectsSuspendedBrandsCategoriesAndParentsOnTheFinalState(): void
    {
        foreach ([['brands', 0, 'suspendedBrand', 2], ['categories', 1, 'suspendedCategory', 11], ['categories', 0, 'suspendedParent', 11]] as [$table, $index, $message, $id]):
            $package = $this->package();
            $package[$table][$index]['status'] = 0;
            $before = $this->baseline();
            $planned = $this->model->planPackage($this->workbook($package));
            $this->assertFalse($planned['result']);
            $this->assertContains(sprintf(lang('backend/warehouse.messages.' . $message), $id), $planned['validationErrors']);
            $this->assertSame($before, $this->baseline());
        endforeach;
    }

    public function testInactivePivotsAndPreservedProductsStillBlockSuspendedContainers(): void
    {
        $package = $this->package();
        $package['brands'][0]['status'] = 0;
        $package['brands_to_products'][0]['status'] = 0;
        $planned = $this->model->planPackage($this->workbook($package));
        $this->assertFalse($planned['result']);
        $this->assertContains(sprintf(lang('backend/warehouse.messages.suspendedBrand'), 2), $planned['validationErrors']);

        $this->database->table('brands')->where('id', 1)->update(['status' => 0]);
        $this->database->table('products')->where('id', 31)->update(['deleted_at' => '2026-10-23 10:00:00']);
        $planned = $this->model->planPackage($this->workbook($this->package()));
        $this->assertFalse($planned['result']);
        $this->assertContains(sprintf(lang('backend/warehouse.messages.suspendedBrand'), 1), $planned['validationErrors']);
    }

    public function testWorkbookAllowsReactivationWithNewRelationsAndSuspensionOfEmptyBrand(): void
    {
        $this->database->table('brands')->where('id', 2)->update(['status' => 0]);
        $this->database->table('categories')->where('id', 10)->update(['status' => 0]);
        $planned = $this->model->planPackage($this->workbook($this->package()));
        $this->assertTrue($planned['result'], implode('\n', $planned['validationErrors'] ?? []));

        $package = $this->package();
        $package['brands'][0]['status'] = 0;
        unset($package['brands_to_products'][0]);
        $planned = $this->model->planPackage($this->workbook($package));
        $this->assertTrue($planned['result'], implode('\n', $planned['validationErrors'] ?? []));
    }

    /** Firma lo stato reale, incluse tutte le righe assenti dal workbook. */
    private function baseline(): string
    {
        return $this->model->snapshotHash($this->model->readPackageSnapshot());
    }

    /** Costruisce lo stesso contratto prodotto dal parser XLSX, senza date tecniche. */
    private function workbook(array $package): array
    {
        $workbook = [];

        foreach ($this->model->getPackageImportStructures() as $table => $structure):
            $headers = array_column($structure, 'name');
            $rows = [];

            foreach ($package[$table] as $index => $row):
                $rows[] = array_intersect_key($row, array_flip($headers)) + ['_row' => $index + 2];
            endforeach;

            $workbook[$table] = ['headers' => $headers, 'rows' => $rows];
        endforeach;

        return $workbook;
    }

    private function seed(): void
    {
        $this->database->table('brands')->insertBatch([
            $this->entity(['id' => 1, 'uuid' => $this->uuid(101), 'brand' => 'Keep brand']),
            $this->entity(['id' => 2, 'uuid' => $this->uuid(102), 'brand' => 'Existing brand']),
        ]);
        $this->database->table('categories')->insertBatch([
            $this->entity(['id' => 10, 'uuid' => $this->uuid(201), 'parent_id' => null, 'category' => 'Root']),
            $this->entity(['id' => 11, 'uuid' => $this->uuid(202), 'parent_id' => 10, 'category' => 'Leaf']),
            $this->entity(['id' => 12, 'uuid' => $this->uuid(203), 'parent_id' => null, 'category' => 'Keep category']),
        ]);
        $this->database->table('products')->insertBatch([
            $this->entity(['id' => 30, 'uuid' => $this->uuid(301), 'sku' => 'OLD-SKU', 'product' => 'Existing product', 'quantity' => 1, 'price' => '1.00']),
            $this->entity(['id' => 31, 'uuid' => $this->uuid(302), 'sku' => 'KEEP-SKU', 'product' => 'Keep product', 'quantity' => 2, 'price' => '2.00']),
        ]);
        $this->database->table('brands_to_products')->insertBatch([
            ['brand_id' => 1, 'product_id' => 30, 'status' => 1],
            ['brand_id' => 1, 'product_id' => 31, 'status' => 1],
        ]);
        $this->database->table('categories_to_products')->insertBatch([
            ['category_id' => 11, 'product_id' => 30, 'status' => 1],
            ['category_id' => 12, 'product_id' => 31, 'status' => 1],
        ]);
    }

    private function package(): array
    {
        return [
            'brands' => [
                $this->entity(['id' => 2, 'uuid' => $this->uuid(102), 'brand' => 'Updated brand']),
                $this->entity(['id' => 100, 'uuid' => $this->uuid(103), 'brand' => 'New brand']),
            ],
            'categories' => [
                $this->entity(['id' => 10, 'uuid' => $this->uuid(201), 'parent_id' => null, 'category' => 'Updated root']),
                $this->entity(['id' => 11, 'uuid' => $this->uuid(202), 'parent_id' => 10, 'category' => 'Updated leaf']),
                $this->entity(['id' => 100, 'uuid' => $this->uuid(204), 'parent_id' => 10, 'category' => 'New leaf']),
            ],
            'products' => [
                $this->entity(['id' => 30, 'uuid' => $this->uuid(301), 'sku' => 'NEW-SKU', 'product' => 'Updated product', 'quantity' => 3, 'price' => '3.50']),
                $this->entity(['id' => 100, 'uuid' => $this->uuid(303), 'sku' => 'NEW-PRODUCT', 'product' => 'New product', 'quantity' => 4, 'price' => '4.50']),
            ],
            'brands_to_products' => [
                ['brand_id' => 2, 'product_id' => 30, 'status' => 1],
                ['brand_id' => 100, 'product_id' => 100, 'status' => 1],
            ],
            'categories_to_products' => [
                ['category_id' => 11, 'product_id' => 30, 'status' => 1],
                ['category_id' => 100, 'product_id' => 100, 'status' => 1],
            ],
        ];
    }

    private function entity(array $fields): array
    {
        return $fields + ['note' => null, 'status' => 1, 'created_at' => '2026-10-22 10:00:00', 'updated_at' => null, 'deleted_at' => null, 'restored_at' => null];
    }

    private function uuid(int $id): string
    {
        return '00000000-0000-4000-8000-' . str_pad((string) $id, 12, '0', STR_PAD_LEFT);
    }
}
