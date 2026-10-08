<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Models\Backend\Components\ImportModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Validation\Validation;
use Config\Services;

class ImportControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    private ImportModel $mockImportModel;
    private IncomingRequest $mockRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');

        $this->mockImportModel = $this->createMock(ImportModel::class);
        $this->mockRequest = $this->createMock(IncomingRequest::class);

        $this->controller = $this->getMockBuilder(ImportController::class)
            ->onlyMethods(['validateData', 'jsonResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->injectControllerDependencies();
    }

    protected function tearDown(): void
    {
        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');
        parent::tearDown();
    }

    public function testShowModalNotAjaxReturnsBadRequest(): void
    {
        $this->mockRequest->method('isAJAX')->willReturn(false);

        $result = $this->controller->showModal();

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testShowModalValidationFailsReturnsJsonError(): void
    {
        $this->configureAjaxPost(['entity' => '']);
        $this->controller->method('validateData')->willReturn(false);
        $this->injectValidator(['entity' => 'Required field']);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertStringContainsString('Required field', $data['message']);
            return true;
        }))->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->showModal());
    }

    public function testShowModalReturnsErrorWhenStructureIsEmpty(): void
    {
        $this->configureAjaxPost(['entity' => 'users']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_CRUD)->willReturn([]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => lang('backend/components/import.messages.noStructure'),
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->showModal());
    }

    public function testShowModalSuccessReturnsJsonWithOutput(): void
    {
        $this->configureAjaxPost(['entity' => 'users']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_CRUD)->willReturn($this->sampleStructure());
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertTrue($data['result']);
            $this->assertIsString($data['output']);
            return true;
        }))->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->showModal());
    }

    public function testShowDatabaseModalUsesDatabaseMode(): void
    {
        $this->configureAjaxPost(['entity' => 'users']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_DATABASE)->willReturn($this->sampleStructure());
        $jsonReturn = $this->createMock(Response::class);
        $this->controller->method('jsonResponse')->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->showDatabaseModal());
    }

    public function testDownloadInvalidEntityRedirectsBack(): void
    {
        $result = $this->controller->download('invalid entity !');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertTrue($result->hasHeader('Location'));
    }

    public function testDownloadEmptyStructureRedirectsBack(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_CRUD)->willReturn([]);

        $result = $this->controller->download('users');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertTrue($result->hasHeader('Location'));
    }

    public function testDownloadSuccessReturnsCsvResponse(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_CRUD)->willReturn($this->sampleStructure());

        $result = $this->controller->download('users');

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertStringContainsString('text/csv', $result->getHeaderLine('Content-Type'));
    }

    public function testDownloadDatabaseUsesDatabaseMode(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('users', ImportModel::IMPORT_MODE_DATABASE)->willReturn($this->sampleStructure());

        $result = $this->controller->downloadDatabase('users');

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessCsvNotAjaxReturnsBadRequest(): void
    {
        $this->mockRequest->method('isAJAX')->willReturn(false);

        $result = $this->controller->processCsv();

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testProcessCsvValidationFailsReturnsJsonError(): void
    {
        $this->configureAjaxPost(['entity' => '']);
        $this->controller->method('validateData')->willReturn(false);
        $this->injectValidator(['csvFile' => 'Invalid file format']);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'Invalid file format',
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processCsv());
    }

    public function testProcessCsvReturnsErrorWhenUploadedFileIsMissing(): void
    {
        $this->configureAjaxPost(['entity' => 'users']);
        $this->mockRequest->method('getFile')->with('csvFile')->willReturn(null);
        $this->controller->method('validateData')->willReturn(true);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => lang('backend/components/import.errors.uploaded'),
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processCsv());
    }

    public function testProcessCsvModelValidationFailsWithStructuralErrors(): void
    {
        $file = $this->configureCsvUploadRequest('users');
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('parseAndValidateCsv')->with($file, 'users', ImportModel::IMPORT_MODE_CRUD)->willReturn([
            'status' => false,
            'validationErrors' => ['Row 2' => 'Missing value'],
        ]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertIsString($data['errorOutput']);
            return true;
        }))->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processCsv());
    }

    public function testProcessCsvModelFailsWithGenericError(): void
    {
        $file = $this->configureCsvUploadRequest('users');
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('parseAndValidateCsv')->with($file, 'users', ImportModel::IMPORT_MODE_CRUD)->willReturn([
            'status' => false,
            'message' => 'Generic parsing error',
        ]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'Generic parsing error',
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processCsv());
    }

    public function testProcessCsvSuccessReturnsPreviewData(): void
    {
        $file = $this->configureCsvUploadRequest('users');
        $this->controller->method('validateData')->willReturn(true);
        $importId = str_repeat('a', 64);
        $plan = ['insert' => 5, 'update' => 2, 'skip' => 0];

        $this->mockImportModel->expects($this->once())->method('parseAndValidateCsv')->with($file, 'users', ImportModel::IMPORT_MODE_CRUD)->willReturn([
            'status' => true,
            'headers' => ['uuid', 'email'],
            'rows' => [],
            'importId' => $importId,
            'plan' => $plan,
        ]);

        $jsonReturn = $this->createMock(Response::class);
        $this->controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data) use ($importId): bool {
            $this->assertTrue($data['result']);
            $this->assertIsString($data['output']);
            $this->assertSame($importId, $data['importId']);
            $this->assertTrue($data['hasProcessableData']);
            return true;
        }))->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processCsv());
    }

    public function testProcessDatabaseCsvUsesDatabaseMode(): void
    {
        $file = $this->configureCsvUploadRequest('users');
        $this->controller->method('validateData')->willReturn(true);
        $importId = str_repeat('b', 64);

        $this->mockImportModel->expects($this->once())->method('parseAndValidateCsv')->with($file, 'users', ImportModel::IMPORT_MODE_DATABASE)->willReturn([
            'status' => true,
            'headers' => ['id', 'uuid', 'email'],
            'rows' => [],
            'importId' => $importId,
            'plan' => ['insert' => 1, 'update' => 0, 'skip' => 0],
        ]);

        $jsonReturn = $this->createMock(Response::class);
        $this->controller->method('jsonResponse')->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->processDatabaseCsv());
    }

    public function testExecuteImportNotAjaxReturnsBadRequest(): void
    {
        $this->mockRequest->method('isAJAX')->willReturn(false);

        $result = $this->controller->executeImport();

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testExecuteImportValidationFailsReturnsJsonError(): void
    {
        $this->configureAjaxPost(['importId' => '', 'step' => 'confirm']);
        $this->controller->method('validateData')->willReturn(false);
        $this->injectValidator(['importId' => 'Invalid import ID']);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'Invalid import ID',
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->executeImport());
    }

    public function testExecuteImportBackupFailsReturnsJsonError(): void
    {
        $importId = str_repeat('a', 64);
        $this->configureAjaxPost(['importId' => $importId, 'step' => 'confirm']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(false);
        $this->mockImportModel->expects($this->never())->method('executeImport');
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => lang('backend/components/import.messages.backupError'),
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->executeImport());
    }

    public function testExecuteImportModelExecutionFailsReturnsJsonError(): void
    {
        $importId = str_repeat('b', 64);
        $this->configureAjaxPost(['importId' => $importId, 'step' => 'confirm']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn([
            'status' => false,
            'message' => 'Import execution error',
            'recoveryRequired' => false,
        ]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'Import execution error',
            'recoveryRequired' => false,
            'importId' => null,
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->executeImport());
    }

    public function testExecuteImportRecoveryRequiredReturnsRecoveryPayload(): void
    {
        $importId = str_repeat('c', 64);
        $this->configureAjaxPost(['importId' => $importId, 'step' => 'confirm']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn([
            'status' => false,
            'message' => 'Technical recovery message',
            'recoveryRequired' => true,
        ]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => sprintf(lang('backend/components/import.messages.recoveryRequired'), $importId),
            'recoveryRequired' => true,
            'importId' => $importId,
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->executeImport());
    }

    public function testExecuteImportSuccessReturnsServerSideProgress(): void
    {
        $importId = str_repeat('d', 64);
        $this->configureAjaxPost(['importId' => $importId, 'step' => 'confirm']);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn([
            'status' => true,
            'message' => 'Import completed successfully.',
            'isFinished' => true,
            'inserted' => 5,
            'updated' => 2,
            'totalInserted' => 15,
            'totalUpdated' => 7,
            'processed' => 22,
        ]);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertTrue($data['result']);
            $this->assertSame('Import completed successfully.', $data['message']);
            $this->assertTrue($data['isFinished']);
            $this->assertSame(5, $data['inserted']);
            $this->assertSame(2, $data['updated']);
            $this->assertSame(15, $data['totalInserted']);
            $this->assertSame(7, $data['totalUpdated']);
            $this->assertSame(22, $data['processed']);
            $this->assertIsString($data['progressOutput']);
            $this->assertSame(sprintf(lang('backend/components/import.messages.processedRows'), 22), $data['progressMessage']);
            return true;
        }))->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->executeImport());
    }

    public function testDeleteFileNotAjaxReturnsBadRequest(): void
    {
        $this->mockRequest->method('isAJAX')->willReturn(false);

        $result = $this->controller->deleteFile();

        $this->assertSame(400, $result->getStatusCode());
    }

    public function testDeleteFileValidationFailsReturnsJsonError(): void
    {
        $this->configureAjaxPost(['importId' => 'invalid']);
        $this->controller->method('validateData')->willReturn(false);
        $this->injectValidator(['importId' => 'Invalid import ID']);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'Invalid import ID',
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->deleteFile());
    }

    public function testDeleteFileReturnsErrorWhenStagingDeletionFails(): void
    {
        $importId = str_repeat('e', 64);
        $this->configureAjaxPost(['importId' => $importId]);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('deleteStagingImport')->with($importId)->willReturn(false);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => lang('backend/components/import.messages.stagingDeleteError'),
        ])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->deleteFile());
    }

    public function testDeleteFileSuccessDelegatesDeletionToModel(): void
    {
        $importId = str_repeat('f', 64);
        $this->configureAjaxPost(['importId' => $importId]);
        $this->controller->method('validateData')->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('deleteStagingImport')->with($importId)->willReturn(true);
        $jsonReturn = $this->createMock(Response::class);

        $this->controller->expects($this->once())->method('jsonResponse')->with(['result' => true])->willReturn($jsonReturn);

        $this->assertSame($jsonReturn, $this->controller->deleteFile());
    }

    public function testConstructorInitializesModel(): void
    {
        $controller = new ImportController();
        $property = new \ReflectionProperty(ImportController::class, 'importModel');
        $property->setAccessible(true);

        $this->assertInstanceOf(ImportModel::class, $property->getValue($controller));
    }

    private function configureAjaxPost(array $data): void
    {
        $this->mockRequest->method('isAJAX')->willReturn(true);
        $this->mockRequest->method('is')->with('post')->willReturn(true);
        $this->mockRequest->method('getPost')->willReturnCallback(static fn (?string $key = null) => $key === null ? $data : ($data[$key] ?? null));
    }

    private function configureCsvUploadRequest(string $entity): UploadedFile
    {
        $this->configureAjaxPost(['entity' => $entity]);
        $file = $this->createMock(UploadedFile::class);
        $this->mockRequest->method('getFile')->with('csvFile')->willReturn($file);
        return $file;
    }

    private function injectValidator(array $errors): void
    {
        $validator = $this->createMock(Validation::class);
        $validator->method('getErrors')->willReturn($errors);
        $inject = function (Validation $validator): void { $this->validator = $validator; };
        $binder = \Closure::bind($inject, $this->controller, ImportController::class);
        $binder($validator);
    }

    private function injectControllerDependencies(): void
    {
        $response = $this->createMock(Response::class);
        $inject = function (ImportModel $model, IncomingRequest $request, Response $response): void {
            $this->importModel = $model;
            $this->request = $request;
            $this->response = $response;
        };
        $binder = \Closure::bind($inject, $this->controller, ImportController::class);
        $binder($this->mockImportModel, $this->mockRequest, $response);
    }

    private function sampleStructure(): array
    {
        return [
            [
                'name' => 'uuid',
                'type' => 'varchar',
                'max_length' => 36,
                'primary_key' => 0,
                'is_index' => true,
                'nullable' => false,
                'default' => null,
                'auto_increment' => false,
                'generated' => false,
            ],
            [
                'name' => 'email',
                'type' => 'varchar',
                'max_length' => 255,
                'primary_key' => 0,
                'is_index' => true,
                'nullable' => false,
                'default' => null,
                'auto_increment' => false,
                'generated' => false,
            ],
        ];
    }
}
