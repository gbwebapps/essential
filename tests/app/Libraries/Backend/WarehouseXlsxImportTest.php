<?php declare(strict_types = 1);

namespace Tests\App\Libraries\Backend;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Warehouse\Config\Views;
use Modules\Backend\Warehouse\Libraries\WarehouseXlsxImport;
use Modules\Backend\Warehouse\Libraries\WarehouseRecoveryException;
use Modules\Backend\Warehouse\Models\WarehouseImportModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\WarehouseDatabaseFixture;

/**
 * Verifica parser, staging, avanzamento e recupero sul contratto attuale Warehouse.
 */
class WarehouseXlsxImportTest extends CIUnitTestCase
{
    use WarehouseDatabaseFixture;

    private array $stagingDirectories = [];
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->startWarehouseDatabase();
        session()->set('backendSession', 'warehouse-import-tests');
    }

    protected function tearDown(): void
    {
        foreach ($this->stagingDirectories as $directory):
            $importId = basename(rtrim($directory, '/\\'));
            $backupDirectory = WRITEPATH . 'backups/warehouse/' . $importId . '/';

            foreach ([$directory, $backupDirectory] as $path):
                if ( ! is_dir($path)):
                    continue;
                endif;

                foreach (['package.xlsx', 'plan.json', 'manifest.json', 'backup.sql'] as $file):
                    foreach ([$file, $file . '.tmp'] as $name):
                        if (is_file($path . $name)):
                            unlink($path . $name);
                        endif;
                    endforeach;
                endforeach;

                rmdir($path);
            endforeach;
        endforeach;

        foreach ($this->temporaryFiles as $path):
            if (is_file($path)):
                unlink($path);
            endif;
        endforeach;

        session()->remove('backendSession');
        $this->stopWarehouseDatabase();
        parent::tearDown();
    }

    /** La preparazione crea un piano verificato, senza backup e senza scritture applicative. */
    public function testPreparationDoesNotWriteToDatabaseAndCanBeRecoveredByOwner(): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $before = $model->snapshotHash($model->readPackageSnapshot());
        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());

        $this->assertTrue($prepared['result']);
        $this->assertSame(['insert' => 6, 'update' => 0, 'skip' => 0], $prepared['plan']);
        $this->assertSame('staged', $importer->getImportStatus($prepared['importId'])['status']);
        $this->assertSame($before, $model->snapshotHash($model->readPackageSnapshot()));

        $manifest = $this->readManifest($prepared['importId']);
        $this->assertSame(3, $manifest['version']);
        $this->assertSame('merge', $manifest['mode']);
        $this->assertSame(0, $manifest['preflight']);
        $this->assertNull($manifest['backup']);
        $this->assertSame(hash_file('sha256', $this->directory($prepared['importId']) . 'plan.json'), $manifest['planHash']);

        session()->set('backendSession', 'another-session');
        $this->assertNull($importer->getImportStatus($prepared['importId']));
        $this->expectException(\DomainException::class);
        $importer->execute($prepared['importId']);
    }

    /** Il backup precede il primo inserimento e il completamento è idempotente. */
    public function testExecutionCreatesBackupAndCannotApplyTheSamePackageTwice(): void
    {
        $model = $this->model();
        $executor = new WarehouseImportModel();
        $model->expects($this->once())->method('executePackagePlan')->willReturnCallback(
            function(array $workbook, array $planned, callable $backup) use ($executor): array {
                return $executor->executePackagePlan($workbook, $planned, function(array $snapshot) use ($backup): void {
                    $this->assertSame([], $snapshot['products']['rows']);
                    $this->assertSame(0, $this->warehouseDatabase->table('products')->countAllResults());
                    $backup($snapshot);
                });
            }
        );

        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());
        $first = $importer->execute($prepared['importId']);
        $second = $importer->execute($prepared['importId']);

        $this->assertFalse($first['isFinished']);
        $this->assertSame(5, $first['processed']);
        $this->assertFalse($second['isFinished']);
        $this->assertSame(6, $second['processed']);
        $this->assertSame(0, $this->warehouseDatabase->table('products')->countAllResults());
        $this->assertNull($this->readManifest($prepared['importId'])['backup']);

        $result = $importer->execute($prepared['importId']);
        $this->assertTrue($result['isFinished']);
        $this->assertFalse($result['alreadyCompleted']);
        $this->assertSame(6, $result['inserted']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['skipped']);

        $backup = WRITEPATH . 'backups/warehouse/' . $prepared['importId'] . '/backup.sql';
        $this->assertFileExists($backup);
        $this->assertSame(hash_file('sha256', $backup), $this->readManifest($prepared['importId'])['backup']['fileHash']);
        $this->assertSame('completed', $importer->getImportStatus($prepared['importId'])['status']);
        $this->assertTrue($importer->execute($prepared['importId'])['alreadyCompleted']);
        $this->assertSame(1, $this->warehouseDatabase->table('products')->countAllResults());
    }

    /** Le date tecniche restano server-side e un file identico non crea un nuovo import. */
    public function testReimportingUnchangedWorkbookReportsPerfectSynchronization(): void
    {
        $importer = new WarehouseXlsxImport(new WarehouseImportModel());
        $prepared = $importer->prepare($this->upload());
        $this->complete($importer, $prepared['importId']);
        $model = new WarehouseImportModel();
        $snapshot = $model->readPackageSnapshot();
        $before = $model->snapshotHash($snapshot);

        foreach (['brands', 'categories', 'products'] as $table):
            foreach ($snapshot[$table]['rows'] as $row):
                $this->assertNotNull($row['created_at']);
                $this->assertNull($row['updated_at']);
            endforeach;
        endforeach;

        /* Gli ID effettivi sono generati dal database: si riesporta lo stato aggiornato. */
        $workbook = new Spreadsheet();
        $workbook->removeSheetByIndex(0);

        foreach ($model->getPackageImportStructures() as $table => $structure):
            $headers = array_column($structure, 'name');
            $sheet = $workbook->createSheet()->setTitle($table);
            $sheet->fromArray($headers, null, 'A1');

            foreach ($snapshot[$table]['rows'] as $index => $row):
                foreach ($headers as $column => $field):
                    $value = $row[$field];
                    $type = $value === null ? DataType::TYPE_NULL : DataType::TYPE_STRING;
                    $sheet->setCellValueExplicit([$column + 1, $index + 2], $value, $type);
                endforeach;
            endforeach;
        endforeach;

        $unchanged = $importer->prepare($this->uploadWorkbook($workbook));
        $this->assertTrue($unchanged['result']);
        $this->assertNull($unchanged['importId']);
        $this->assertSame(['insert' => 0, 'update' => 0, 'skip' => 6], $unchanged['plan']);
        $this->assertSame($before, $model->snapshotHash($model->readPackageSnapshot()));
    }

    public function testChangedStagingIsRejectedBeforeDatabaseWrites(): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());
        file_put_contents($this->directory($prepared['importId']) . 'package.xlsx', 'changed');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(lang('backend/warehouse.messages.fileChanged'));
        $importer->execute($prepared['importId']);
    }

    public function testInterruptedProcessingIsNotAutomaticallyExecutedAgain(): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());
        $manifest = $this->readManifest($prepared['importId']);
        $manifest['status'] = 'processing';
        file_put_contents($this->directory($prepared['importId']) . 'manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));

        $this->expectException(WarehouseRecoveryException::class);
        $this->expectExceptionMessage(lang('backend/warehouse.messages.importNeedsReview'));
        $importer->execute($prepared['importId']);
    }

    public function testFailedMergeRetainsStagingAndBackupForVerification(): void
    {
        $model = $this->model();
        $model->expects($this->once())->method('executePackagePlan')->willReturnCallback(
            function(array $workbook, array $planned, callable $backup) use ($model): array {
                $backup($model->readPackageSnapshot());
                throw new \DomainException('Fixture rollback');
            }
        );

        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());

        try
        {
            $this->complete($importer, $prepared['importId']);
            $this->fail('The failed merge must raise an exception.');
        }
        catch (\DomainException $e)
        {
            $this->assertSame('Fixture rollback', $e->getMessage());
        }

        $this->assertSame('failed', $importer->getImportStatus($prepared['importId'])['status']);
        $this->assertFileExists($this->directory($prepared['importId']) . 'package.xlsx');
        $this->assertFileExists(WRITEPATH . 'backups/warehouse/' . $prepared['importId'] . '/backup.sql');
        $this->assertSame(0, $this->warehouseDatabase->table('products')->countAllResults());
    }

    public function testRenamedNonXlsxFileIsRejected(): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $upload = $this->upload();
        file_put_contents($this->temporaryFiles[array_key_last($this->temporaryFiles)], 'not an XLSX archive');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(lang('backend/warehouse.messages.invalidXlsx'));
        (new WarehouseXlsxImport($model))->prepare($upload);
    }

    public function testGlobalLockPreventsConcurrentExecution(): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $importer = new WarehouseXlsxImport($model);
        $prepared = $importer->prepare($this->upload());
        $lock = fopen(WRITEPATH . 'uploads/warehouse/import.lock', 'c+');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        try
        {
            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage(lang('backend/warehouse.messages.importBusy'));
            $importer->execute($prepared['importId']);
        }
        finally
        {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function testRecoveredStagingExposesCurrentConfirmationForm(): void
    {
        $importer = new WarehouseXlsxImport(new WarehouseImportModel());
        $prepared = $importer->prepare($this->upload());
        $status = $importer->getImportStatus($prepared['importId']);
        $output = view(Views::IMPORT_PREVIEW_PARTIAL, $status);

        $this->assertSame('staged', $status['status']);
        $this->assertStringContainsString('id="importForm"', $output);
        $this->assertStringContainsString('name="step" value="confirm"', $output);
        $this->assertStringContainsString($prepared['importId'], $output);
        $this->assertCount(5, $status['sheets']);

        $this->complete($importer, $prepared['importId']);
        $completed = $importer->getImportStatus($prepared['importId']);
        $this->assertSame('completed', $completed['status']);
        $this->assertArrayNotHasKey('sheets', $completed);
    }

    public function testAllModuleLanguagesExposeTheImportKeys(): void
    {
        $expected = require ROOTPATH . 'Modules/Backend/Warehouse/Language/it/backend/warehouse.php';

        foreach (['en', 'de', 'es', 'fr', 'zh'] as $locale):
            $actual = require ROOTPATH . 'Modules/Backend/Warehouse/Language/' . $locale . '/backend/warehouse.php';

            foreach ($expected as $section => $values):
                $this->assertSame(array_keys($values), array_keys($actual[$section]), $locale . ': ' . $section);
            endforeach;

            $this->assertSame($actual['labels']['validationSummary'], lang('backend/warehouse.labels.validationSummary', [], $locale));
        endforeach;
    }

    #[DataProvider('invalidPackages')]
    public function testInvalidWorkbooksAreRejectedBeforeStagingBecomesExecutable(string $case): void
    {
        $model = $this->model();
        $model->expects($this->never())->method('executePackagePlan');
        $importer = new WarehouseXlsxImport($model);

        try
        {
            $prepared = $importer->prepare($this->upload($case));
            $this->assertFalse($prepared['result']);
            $this->assertNotEmpty($prepared['validationErrors']);
        }
        catch (\DomainException $e)
        {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame(0, $this->warehouseDatabase->table('products')->countAllResults());

        foreach ($this->stagingDirectories as $directory):
            $this->assertDirectoryDoesNotExist($directory);
        endforeach;
    }

    public static function invalidPackages(): array
    {
        return array_map(static fn(string $case): array => [$case], [
            'cycle', 'mixed', 'missing-brand', 'unknown-category', 'duplicate-uuid',
            'duplicate-id', 'duplicate-pivot', 'duplicate-sku', 'duplicate-product',
            'negative-quantity', 'technical-created-at', 'technical-deleted-at',
            'formula', 'extra-sheet', 'wrong-header', 'oversized-dimensions',
        ]);
    }

    /** Usa il model reale per schema e dominio; intercetta solo il confine di scrittura. */
    private function model(): WarehouseImportModel
    {
        return $this->getMockBuilder(WarehouseImportModel::class)->onlyMethods(['executePackagePlan'])->getMock();
    }

    private function complete(WarehouseXlsxImport $importer, string $importId): array
    {
        for ($request = 0; $request < 10; $request++):
            $result = $importer->execute($importId);

            if (($result['isFinished'] ?? false) === true):
                return $result;
            endif;

            $this->assertTrue($result['result']);
        endfor;

        $this->fail('The fixture must finish within ten chunk requests.');
    }

    private function upload(?string $invalidCase = null): UploadedFile
    {
        return $this->uploadWorkbook($this->workbook($invalidCase));
    }

    private function uploadWorkbook(Spreadsheet $spreadsheet): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'warehouse-test-');
        $this->temporaryFiles[] = $path;

        try
        {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
        }
        finally
        {
            $spreadsheet->disconnectWorksheets();
        }

        $upload = $this->getMockBuilder(UploadedFile::class)
            ->setConstructorArgs([$path, 'package.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', filesize($path), UPLOAD_ERR_OK])
            ->onlyMethods(['move'])->getMock();

        $upload->method('move')->willReturnCallback(function(string $directory, ?string $name) use ($path): bool {
            $this->stagingDirectories[] = $directory;
            return rename($path, $directory . $name);
        });

        return $upload;
    }

    private function workbook(?string $invalidCase): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $data = [
            'brands' => [
                ['id', 'uuid', 'brand', 'note', 'status'],
                [10, '00000000-0000-4000-8000-000000000101', 'Fixture Brand', null, 1],
            ],
            'categories' => [
                ['id', 'uuid', 'parent_id', 'category', 'note', 'status'],
                [20, '00000000-0000-4000-8000-000000000201', null, 'Root', null, 1],
                [21, '00000000-0000-4000-8000-000000000202', 20, 'Leaf', null, 1],
            ],
            'products' => [
                ['id', 'uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'],
                [30, '00000000-0000-4000-8000-000000000301', '0001', 'Fixture Product', null, 2, '9.90', 1],
            ],
            'brands_to_products' => [['brand_id', 'product_id', 'status'], [10, 30, 1]],
            'categories_to_products' => [['category_id', 'product_id', 'status'], [21, 30, 1]],
        ];

        foreach ($data as $table => $rows):
            $worksheet = $spreadsheet->createSheet()->setTitle($table);

            foreach ($rows as $rowIndex => $row):
                foreach ($row as $columnIndex => $value):
                    $type = is_string($value) ? DataType::TYPE_STRING : ($value === null ? DataType::TYPE_NULL : DataType::TYPE_NUMERIC);
                    $worksheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], $value, $type);
                endforeach;
            endforeach;
        endforeach;

        switch ($invalidCase):
            case 'cycle': $spreadsheet->getSheetByName('categories')->setCellValue('C2', 21); break;
            case 'mixed': $spreadsheet->getSheetByName('categories_to_products')->setCellValue('A2', 20); break;
            case 'missing-brand': $spreadsheet->getSheetByName('brands_to_products')->setCellValue('C2', 0); break;
            case 'unknown-category': $spreadsheet->getSheetByName('categories_to_products')->setCellValue('A2', 99); break;
            case 'duplicate-uuid': $spreadsheet->getSheetByName('categories')->setCellValue('B3', '00000000-0000-4000-8000-000000000201'); break;
            case 'duplicate-id': $spreadsheet->getSheetByName('categories')->setCellValue('A3', 20); break;
            case 'duplicate-pivot': $spreadsheet->getSheetByName('brands_to_products')->fromArray([[10, 30, 1]], null, 'A3'); break;
            case 'duplicate-sku':
            case 'duplicate-product':
                $spreadsheet->getSheetByName('products')->fromArray([
                    [31, '00000000-0000-4000-8000-000000000302', $invalidCase === 'duplicate-sku' ? '0001' : '0002',
                        $invalidCase === 'duplicate-product' ? 'Fixture Product' : 'Other Product', null, 1, '1.00', 1],
                ], null, 'A3');
                break;
            case 'negative-quantity': $spreadsheet->getSheetByName('products')->setCellValue('F2', -1); break;
            case 'technical-created-at': $spreadsheet->getSheetByName('products')->setCellValue('I1', 'created_at'); break;
            case 'technical-deleted-at': $spreadsheet->getSheetByName('brands')->setCellValue('F1', 'deleted_at'); break;
            case 'formula': $spreadsheet->getSheetByName('products')->setCellValueExplicit('E2', '=1+1', DataType::TYPE_FORMULA); break;
            case 'extra-sheet': $spreadsheet->createSheet()->setTitle('unwanted'); break;
            case 'wrong-header': $spreadsheet->getSheetByName('brands')->setCellValue('C1', 'name'); break;
            case 'oversized-dimensions': $spreadsheet->getSheetByName('products')->setCellValue('A50002', 1); break;
        endswitch;

        return $spreadsheet;
    }

    private function readManifest(string $importId): array
    {
        return json_decode(file_get_contents($this->directory($importId) . 'manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function directory(string $importId): string
    {
        return WRITEPATH . 'uploads/warehouse/' . $importId . DIRECTORY_SEPARATOR;
    }
}
