<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Products\Models\ProductStatisticsModel;
use Tests\Support\Database\WarehouseDatabaseFixture;

/** Le statistiche vengono eseguite solo sulle cinque tabelle temporanee del test. */
class ProductStatisticsModelTest extends CIUnitTestCase
{
    use WarehouseDatabaseFixture;

    protected function setUp(): void
    {
        parent::setUp();
        Factories::reset('models');
        $this->startWarehouseDatabase();
    }

    protected function tearDown(): void
    {
        $this->stopWarehouseDatabase();
        Factories::reset('models');
        parent::tearDown();
    }

    private function seedStatistics(): void
    {
        $this->warehouseDatabase->query("insert into brands (id, uuid, brand, status, created_at, deleted_at) values
            (1, 'brand-1', 'A Brand', 1, now(), null), (2, 'brand-2', 'B Brand', 0, now(), null),
            (3, 'brand-3', 'Deleted Brand', 1, now(), now()), (4, 'brand-4', 'Unused Brand', 1, now(), null)");
        $this->warehouseDatabase->query("insert into categories (id, uuid, category, parent_id, status, created_at, deleted_at) values
            (1, 'category-1', 'Informatica', null, 1, now(), null), (2, 'category-2', 'Notebook', 1, 1, now(), null),
            (3, 'category-3', 'Notebook', null, 0, now(), null), (4, 'category-4', 'Deleted', null, 1, now(), now())");
        $this->warehouseDatabase->query("insert into products (id, uuid, sku, product, quantity, status, created_at, deleted_at) values
            (1, 'product-1', 'sku-1', 'One', 50, 1, now(), null), (2, 'product-2', 'sku-2', 'Two', 99, 0, now(), null),
            (3, 'product-3', 'sku-3', 'Deleted', 1, 1, now(), now()), (4, 'product-4', 'sku-4', 'Four', 1, 1, now(), null)");
        $this->warehouseDatabase->query('insert into brands_to_products values (1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 4, 0), (2, 1, 1), (2, 4, 1), (3, 1, 1)');
        $this->warehouseDatabase->query('insert into categories_to_products values (2, 1, 1), (2, 2, 1), (2, 4, 0), (3, 1, 1), (4, 4, 1)');
        $this->warehouseDatabase->query('update products set price = case id when 1 then 1.23 when 2 then 2.34 when 3 then 999999.99 else 500.00 end');
    }

    public function testCountsProductsNotQuantitiesAndExcludesDeletedOrDisabledAssociations(): void
    {
        $this->seedStatistics();
        $rows = (new ProductStatisticsModel())->getTopBrands();

        $this->assertSame([
            ['id' => 1, 'label' => 'A Brand', 'count' => 2],
            ['id' => 2, 'label' => 'B Brand', 'count' => 2],
        ], $rows);
    }

    public function testCategoriesUseDirectAssociationsAndCompletePaths(): void
    {
        $this->seedStatistics();
        $rows = (new ProductStatisticsModel())->getTopCategories();

        $this->assertSame([
            ['id' => 2, 'label' => 'Informatica > Notebook', 'count' => 2],
            ['id' => 3, 'label' => 'Notebook', 'count' => 1],
        ], $rows);
    }

    public function testLimitsAndEmptyTables(): void
    {
        $model = new ProductStatisticsModel();
        $this->assertSame([], $model->getTopBrands());
        $this->assertSame([], $model->getTopCategories());
        $this->seedStatistics();
        $this->assertCount(1, $model->getTopBrands(0));
        $this->assertCount(1, $model->getTopCategories(1));
    }

    public function testInventoryValueCountsEachProductOnceIncludingSuspendedAndUnassociatedProducts(): void
    {
        $this->seedStatistics();
        $this->assertSame('793.16', (new ProductStatisticsModel())->getInventoryValue());
    }

    public function testValueRankingsUseExactAmountsAndDirectEnabledAssociations(): void
    {
        $this->seedStatistics();
        $model = new ProductStatisticsModel();

        $this->assertSame([
            ['id' => 2, 'label' => 'B Brand', 'value' => '561.50'],
            ['id' => 1, 'label' => 'A Brand', 'value' => '293.16'],
        ], $model->getTopBrandsByValue());
        $this->assertSame([
            ['id' => 2, 'label' => 'Informatica > Notebook', 'value' => '293.16'],
            ['id' => 3, 'label' => 'Notebook', 'value' => '61.50'],
        ], $model->getTopCategoriesByValue());
        $this->assertCount(1, $model->getTopBrandsByValue(0));
        $this->assertCount(1, $model->getTopCategoriesByValue(1));

        // Prodotti diversi di uguale valore devono essere sommati entrambi.
        $this->warehouseDatabase->query("insert into products (id, uuid, sku, product, quantity, price, created_at) values (5, 'product-5', 'sku-5', 'Five', 1, 61.50, now())");
        $this->warehouseDatabase->query('insert into brands_to_products values (1, 5, 1)');
        $this->assertSame('354.66', $model->getTopBrandsByValue()[1]['value']);
        $this->assertSame('854.66', $model->getInventoryValue());
    }

    public function testEmptyAndZeroValuesAreNotErrorsAndLargeAmountsKeepTheirCents(): void
    {
        $model = new ProductStatisticsModel();
        $this->assertSame('0.00', $model->getInventoryValue());
        $this->assertSame([], $model->getTopBrandsByValue());
        $this->assertSame([], $model->getTopCategoriesByValue());
        $this->seedStatistics();
        $this->warehouseDatabase->query('update products set quantity = 0');
        $this->assertSame('0.00', $model->getTopBrandsByValue()[0]['value']);
        $this->assertSame('0.00', $model->getTopCategoriesByValue()[0]['value']);
        $this->warehouseDatabase->query('update products set quantity = 4294967295, price = 9999999999.99 where id = 1');
        $this->assertSame('42949672949957050327.05', $model->getInventoryValue());
        $this->assertSame('42949672949957050327.05', $model->getTopBrandsByValue()[0]['value']);
    }
}
