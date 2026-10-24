<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Categories\Models\CategoriesModel;
use Modules\Backend\Products\Models\ProductsModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Isola i conteggi dei tre CRUD in tabelle temporanee della connessione di test. */
class WarehouseTrashCountsTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private BrandsModel|CategoriesModel|ProductsModel $model;

    public static function models(): array
    {
        return [
            'brands' => [BrandsModel::class, 'brands'],
            'categories' => [CategoriesModel::class, 'categories'],
            'products' => [ProductsModel::class, 'products'],
        ];
    }

    /** Prepara solo una tabella temporanea; i dati permanenti non vengono modificati. */
    private function prepareModel(string $modelClass, string $table): void
    {
        $this->database = Database::connect('tests', false);
        $this->assertSame('essential_test', $this->database->getDatabase());
        $this->assertSame('essential_test', $this->database->query('select database() as db')->getRow()->db);
        $this->database->query('create temporary table ' . $table . ' (id int primary key, status tinyint not null, deleted_at datetime null)');
        $this->model = new $modelClass();
        (new \ReflectionProperty($this->model, 'db'))->setValue($this->model, $this->database);
    }

    protected function tearDown(): void
    {
        if (isset($this->database)):
            $this->database->close();
        endif;

        parent::tearDown();
    }

    #[DataProvider('models')]
    public function testEmptyTableReturnsIntegerZeroes(string $modelClass, string $table): void
    {
        $this->prepareModel($modelClass, $table);

        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 0, 'all' => 0], $this->model->getTrashCounts());
    }

    #[DataProvider('models')]
    public function testActiveAndSuspendedAreSeparate(string $modelClass, string $table): void
    {
        $this->prepareModel($modelClass, $table);
        $this->database->query('insert into ' . $table . " values (1, 1, null), (2, 0, null), (3, 0, '2026-10-23 10:00:00'), (4, 1, '2026-10-23 10:00:00')");

        $this->assertSame(['active' => 1, 'suspended' => 1, 'trashed' => 2, 'all' => 4], $this->model->getTrashCounts());
    }

    #[DataProvider('models')]
    public function testCountsFollowSoftDeleteRestoreAndHardDelete(string $modelClass, string $table): void
    {
        $this->prepareModel($modelClass, $table);
        $this->database->query('insert into ' . $table . ' values (1, 1, null)');
        $this->assertSame(['active' => 1, 'suspended' => 0, 'trashed' => 0, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('update ' . $table . ' set deleted_at = now() where id = 1');
        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 1, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('update ' . $table . ' set deleted_at = null where id = 1');
        $this->assertSame(['active' => 1, 'suspended' => 0, 'trashed' => 0, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('delete from ' . $table . ' where id = 1');
        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 0, 'all' => 0], $this->model->getTrashCounts());
    }
}
