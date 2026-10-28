<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Models\Backend\AdminsModel;
use App\Models\Backend\BackendModel;
use App\Models\Backend\Components\ExportModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Categories\Models\CategoriesModel;
use Modules\Backend\Products\Models\ProductsModel;
use PHPUnit\Framework\Attributes\DataProvider;

/** Verifica che lista, paginazione ed export dividano i record nello stesso modo. */
class CrudStatusFiltersTest extends CIUnitTestCase
{
    private BaseConnection $database;

    public static function models(): array
    {
        return [
            'admins' => [AdminsModel::class, 'admins'],
            'brands' => [BrandsModel::class, 'brands'],
            'categories' => [CategoriesModel::class, 'categories'],
            'products' => [ProductsModel::class, 'products'],
        ];
    }

    /** Usa solo una tabella temporanea della connessione al database locale configurato. */
    private function prepareModel(string $modelClass, string $table): BackendModel
    {
        $this->database = Database::connect('tests', false);
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->getDatabase());
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->query('select database() as db')->getRow()->db);
        $this->database->query('create temporary table ' . $table . ' (id int primary key, status tinyint not null, deleted_at datetime null)');
        $this->database->query('insert into ' . $table . " values (1, 1, null), (2, 0, null), (3, 1, '2026-10-23 10:00:00'), (4, 0, '2026-10-23 10:00:00')");
        $model = new $modelClass();
        (new \ReflectionProperty($model, 'db'))->setValue($model, $this->database);
        (new \ReflectionProperty($model, 'getDataQuery'))->setValue($model, 'select id, status, deleted_at from ' . $table . ' where 1 = 1');

        return $model;
    }

    protected function tearDown(): void
    {
        if (isset($this->database)):
            $this->database->close();
        endif;

        parent::tearDown();
    }

    #[DataProvider('models')]
    public function testListAndPaginationUseDisjointFilters(string $modelClass, string $table): void
    {
        $model = $this->prepareModel($modelClass, $table);
        $this->assertTrue($model->supportsStatusFilter());

        foreach (['active' => [1], 'suspended' => [2], 'trashed' => [3, 4], 'all' => [1, 2, 3, 4]] as $filter => $ids):
            $result = $model->getData(['trash_filter' => $filter, 'column' => 'id', 'order' => 'asc', 'page' => 1, 'rows' => 20, 'searchFields' => [], 'searchDates' => []]);
            $this->assertTrue($result['result']);
            $this->assertSame($ids, array_map('intval', array_column($result['records'], 'id')));
            $this->assertSame(count($ids), $result['pagination']['totalRows']);
        endforeach;
    }

    #[DataProvider('models')]
    public function testExportUsesTheSameFilters(string $modelClass, string $table): void
    {
        $this->prepareModel($modelClass, $table);
        $export = new ExportModel();
        (new \ReflectionProperty($export, 'db'))->setValue($export, $this->database);
        $buildQuery = new \ReflectionMethod($export, 'buildQuery');

        foreach (['active' => [1], 'suspended' => [2], 'trashed' => [3, 4], 'all' => [1, 2, 3, 4]] as $filter => $ids):
            [$sql, $bindings] = $buildQuery->invoke($export, [
                'entity' => $table, 'columns' => ['id', 'status', 'deleted_at'], 'cursor' => 0,
                'context' => ExportModel::CONTEXT_CRUD, 'filters' => ['trash_filter' => $filter],
            ], ['id', 'status', 'deleted_at']);
            $rows = $this->database->query($sql, $bindings)->getResultArray();
            $this->assertSame($ids, array_map('intval', array_column($rows, 'id')));
        endforeach;
    }

    public function testSuspendedFilterIsRejectedForUnsupportedExportEntities(): void
    {
        $export = new ExportModel();
        $normalize = new \ReflectionMethod($export, 'normalizeFilters');

        $this->expectException(\InvalidArgumentException::class);
        $normalize->invoke($export, ['trash_filter' => 'suspended'], ['id', 'deleted_at'], 'logs');
    }

    public function testDatabaseExportCannotSilentlyIgnoreSuspendedFilter(): void
    {
        $this->prepareModel(BrandsModel::class, 'brands');
        $export = new ExportModel();
        (new \ReflectionProperty($export, 'db'))->setValue($export, $this->database);
        $buildQuery = new \ReflectionMethod($export, 'buildQuery');

        $this->expectException(\InvalidArgumentException::class);
        $buildQuery->invoke($export, [
            'entity' => 'brands', 'columns' => ['id'], 'cursor' => 0,
            'context' => ExportModel::CONTEXT_DATABASE, 'filters' => ['trash_filter' => 'suspended'],
        ], ['id', 'status', 'deleted_at']);
    }

    public function testOtherModelsKeepThePreviousSoftDeleteBehavior(): void
    {
        $model = new class extends BackendModel {
            protected function initModel(): void
            {
            }
        };

        $filter = new \ReflectionMethod($model, 'buildTrashFilter');
        $this->assertFalse($model->supportsStatusFilter());
        $this->assertSame(' and legacy.deleted_at is null', strtolower($filter->invoke($model, 'active', 'legacy', true)));
        $this->assertSame('', $filter->invoke($model, 'active', 'legacy', false));
    }
}
