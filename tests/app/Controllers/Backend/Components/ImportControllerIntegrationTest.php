<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Models\Backend\Components\ImportModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;

class ImportControllerIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private ImportModel $mockImportModel;

    protected function setUp(): void
    {
        parent::setUp();

        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');
        Services::resetSingle('security');

        $this->mockImportModel = $this->createMock(ImportModel::class);
        Factories::injectMock('models', ImportModel::class, $this->mockImportModel);

        $this->withRoutes([
            ['POST', '__test/import/showModal', '\\' . ImportController::class . '::showModal'],
            ['POST', '__test/import/database/showModal', '\\' . ImportController::class . '::showDatabaseModal'],
            ['GET', '__test/import/download/(:segment)', '\\' . ImportController::class . '::download/$1'],
            ['GET', '__test/import/database/download/(:segment)', '\\' . ImportController::class . '::downloadDatabase/$1'],
            ['POST', '__test/import/processCsv', '\\' . ImportController::class . '::processCsv'],
            ['POST', '__test/import/database/processCsv', '\\' . ImportController::class . '::processDatabaseCsv'],
            ['POST', '__test/import/executeImport', '\\' . ImportController::class . '::executeImport'],
            ['POST', '__test/import/deleteFile', '\\' . ImportController::class . '::deleteFile'],
        ]);
    }

    protected function tearDown(): void
    {
        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');
        Services::resetSingle('security');
        parent::tearDown();
    }

    public function testShowModalRejectsNonAjaxRequestWithBadRequest(): void
    {
        $result = $this->postWithCsrf('__test/import/showModal', ['entity' => 'admins']);

        $result->assertStatus(400);
    }

    public function testShowModalReturnsJsonValidationErrorForMissingEntity(): void
    {
        $result = $this->ajaxPost('__test/import/showModal', ['entity' => '']);

        $result->assertStatus(200);
        $this->assertJsonContentType($result);

        $payload = $this->decodeJson($result);
        $this->assertFalse($payload['result']);
        $this->assertArrayHasKey('message', $payload);
        $this->assertIsString($payload['message']);
        $this->assertNotSame('', $payload['message']);
    }

    public function testShowModalCrudUsesCrudModeAndReturnsRenderedOutput(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('admins', ImportModel::IMPORT_MODE_CRUD)->willReturn($this->sampleStructure());

        $result = $this->ajaxPost('__test/import/showModal', ['entity' => 'admins']);

        $result->assertStatus(200);
        $this->assertJsonContentType($result);

        $payload = $this->decodeJson($result);
        $this->assertTrue($payload['result']);
        $this->assertArrayHasKey('output', $payload);
        $this->assertIsString($payload['output']);
        $this->assertNotSame('', trim($payload['output']));
    }

    public function testShowModalDatabaseUsesDatabaseModeAndReturnsRenderedOutput(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('admins', ImportModel::IMPORT_MODE_DATABASE)->willReturn($this->sampleStructure());

        $result = $this->ajaxPost('__test/import/database/showModal', ['entity' => 'admins']);

        $result->assertStatus(200);
        $this->assertJsonContentType($result);

        $payload = $this->decodeJson($result);
        $this->assertTrue($payload['result']);
        $this->assertIsString($payload['output']);
        $this->assertNotSame('', trim($payload['output']));
    }

    public function testShowModalReturnsJsonErrorWhenStructureIsUnavailable(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('admins', ImportModel::IMPORT_MODE_CRUD)->willReturn([]);

        $result = $this->ajaxPost('__test/import/showModal', ['entity' => 'admins']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertSame(lang('backend/components/import.messages.noStructure'), $payload['message']);
    }

    public function testDownloadRejectsInvalidEntityBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('getImportTableStructure');

        $result = $this->get('__test/import/download/invalid%20entity');

        $this->assertTrue(in_array($result->response()->getStatusCode(), [302, 303], true));
    }

    public function testDownloadCrudProducesRealCsvDownloadResponse(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('admins', ImportModel::IMPORT_MODE_CRUD)->willReturn($this->sampleStructure());

        $result = $this->get('__test/import/download/admins');

        $result->assertStatus(200);
        $response = $result->response();

        $this->assertInstanceOf(DownloadResponse::class, $response);
        $response->buildHeaders();

        $this->assertStringStartsWith('text/csv', strtolower($response->getHeaderLine('Content-Type')));
        $this->assertStringContainsString('attachment', strtolower($response->getHeaderLine('Content-Disposition')));
        $this->assertStringContainsString('template_import_admins.csv', $response->getHeaderLine('Content-Disposition'));

        $expectedCsv = "\xEF\xBB\xBFid,email\n";
        $this->assertSame(strlen($expectedCsv), $response->getContentLength());

        ob_start();
        $response->sendBody();
        $body = (string) ob_get_clean();

        $this->assertSame($expectedCsv, $body);
    }

    public function testDownloadDatabaseUsesDatabaseModeAndProducesCsv(): void
    {
        $this->mockImportModel->expects($this->once())->method('getImportTableStructure')->with('admins', ImportModel::IMPORT_MODE_DATABASE)->willReturn($this->sampleStructure());

        $result = $this->get('__test/import/database/download/admins');

        $result->assertStatus(200);
        $response = $result->response();

        $this->assertInstanceOf(DownloadResponse::class, $response);
        $response->buildHeaders();
        $this->assertStringContainsString('template_import_admins.csv', $response->getHeaderLine('Content-Disposition'));

        ob_start();
        $response->sendBody();
        $body = (string) ob_get_clean();

        $this->assertSame("\xEF\xBB\xBFid,email\n", $body);
    }

    public function testProcessCsvRejectsNonAjaxRequestWithBadRequest(): void
    {
        $result = $this->postWithCsrf('__test/import/processCsv', ['entity' => 'admins']);

        $result->assertStatus(400);
    }

    public function testProcessCsvValidatesMissingUploadBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('parseAndValidateCsv');

        $result = $this->ajaxPost('__test/import/processCsv', ['entity' => 'admins']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertArrayHasKey('message', $payload);
        $this->assertIsString($payload['message']);
        $this->assertNotSame('', $payload['message']);
    }

    public function testProcessDatabaseCsvValidatesMissingUploadBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('parseAndValidateCsv');

        $result = $this->ajaxPost('__test/import/database/processCsv', ['entity' => 'admins']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertArrayHasKey('message', $payload);
    }

    public function testExecuteImportRejectsNonAjaxRequestWithBadRequest(): void
    {
        $result = $this->postWithCsrf('__test/import/executeImport', ['importId' => str_repeat('a', 64), 'step' => 'confirm']);

        $result->assertStatus(400);
    }

    public function testExecuteImportRejectsInvalidImportIdBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('backupImport');
        $this->mockImportModel->expects($this->never())->method('executeImport');

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => 'invalid', 'step' => 'confirm']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertArrayHasKey('message', $payload);
    }

    public function testExecuteImportRejectsInvalidStepBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('backupImport');
        $this->mockImportModel->expects($this->never())->method('executeImport');

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => str_repeat('a', 64), 'step' => 'invalid']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
    }

    public function testExecuteImportStopsWhenPreventiveBackupFails(): void
    {
        $importId = str_repeat('b', 64);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(false);
        $this->mockImportModel->expects($this->never())->method('executeImport');

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => $importId, 'step' => 'confirm']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertSame(lang('backend/components/import.messages.backupError'), $payload['message']);
    }

    public function testExecuteImportReturnsGenericModelFailureWithoutRecoveryData(): void
    {
        $importId = str_repeat('c', 64);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn(['status' => false, 'message' => 'Execution failed.']);

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => $importId, 'step' => 'confirm']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertSame('Execution failed.', $payload['message']);
        $this->assertFalse($payload['recoveryRequired']);
        $this->assertNull($payload['importId']);
    }

    public function testExecuteImportReturnsRecoveryContractWhenRecoveryIsRequired(): void
    {
        $importId = str_repeat('d', 64);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn(['status' => false, 'message' => 'Internal failure.', 'recoveryRequired' => true]);

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => $importId, 'step' => 'confirm']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertTrue($payload['recoveryRequired']);
        $this->assertSame($importId, $payload['importId']);
        $this->assertSame(sprintf(lang('backend/components/import.messages.recoveryRequired'), $importId), $payload['message']);
    }

    public function testExecuteImportReturnsServerSideProgressContract(): void
    {
        $importId = str_repeat('e', 64);
        $this->mockImportModel->expects($this->once())->method('backupImport')->with($importId)->willReturn(true);
        $this->mockImportModel->expects($this->once())->method('executeImport')->with($importId)->willReturn([
            'status' => true,
            'message' => 'Import completed.',
            'isFinished' => true,
            'inserted' => 2,
            'updated' => 1,
            'totalInserted' => 12,
            'totalUpdated' => 8,
            'processed' => 20,
        ]);

        $result = $this->ajaxPost('__test/import/executeImport', ['importId' => $importId, 'step' => 'confirm']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertTrue($payload['result']);
        $this->assertSame('Import completed.', $payload['message']);
        $this->assertTrue($payload['isFinished']);
        $this->assertSame(2, $payload['inserted']);
        $this->assertSame(1, $payload['updated']);
        $this->assertSame(12, $payload['totalInserted']);
        $this->assertSame(8, $payload['totalUpdated']);
        $this->assertSame(20, $payload['processed']);
        $this->assertIsString($payload['progressOutput']);
        $this->assertNotSame('', trim($payload['progressOutput']));
        $this->assertSame(sprintf(lang('backend/components/import.messages.processedRows'), 20), $payload['progressMessage']);
        $this->assertArrayNotHasKey('entity', $payload);
        $this->assertArrayNotHasKey('tempFile', $payload);
        $this->assertArrayNotHasKey('offset', $payload);
        $this->assertArrayNotHasKey('nextOffset', $payload);
    }

    public function testDeleteFileRejectsNonAjaxRequestWithBadRequest(): void
    {
        $result = $this->postWithCsrf('__test/import/deleteFile', ['importId' => str_repeat('f', 64)]);

        $result->assertStatus(400);
    }

    public function testDeleteFileRejectsInvalidImportIdBeforeCallingModel(): void
    {
        $this->mockImportModel->expects($this->never())->method('deleteStagingImport');

        $result = $this->ajaxPost('__test/import/deleteFile', ['importId' => '../invalid']);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertArrayHasKey('message', $payload);
    }

    public function testDeleteFileReturnsErrorWhenModelRefusesDeletion(): void
    {
        $importId = str_repeat('1', 64);
        $this->mockImportModel->expects($this->once())->method('deleteStagingImport')->with($importId)->willReturn(false);

        $result = $this->ajaxPost('__test/import/deleteFile', ['importId' => $importId]);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertFalse($payload['result']);
        $this->assertSame(lang('backend/components/import.messages.stagingDeleteError'), $payload['message']);
    }

    public function testDeleteFileReturnsSuccessWhenModelDeletesStaging(): void
    {
        $importId = str_repeat('2', 64);
        $this->mockImportModel->expects($this->once())->method('deleteStagingImport')->with($importId)->willReturn(true);

        $result = $this->ajaxPost('__test/import/deleteFile', ['importId' => $importId]);

        $result->assertStatus(200);
        $payload = $this->decodeJson($result);

        $this->assertTrue($payload['result']);
        $this->assertSame(csrf_token(), $payload['csrfName']);
        $this->assertIsString($payload['csrfHash']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $payload['csrfHash']);
    }

    private function ajaxPost(string $path, array $data): TestResponse
    {
        return $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->post($path, $this->withCsrf($data));
    }

    private function postWithCsrf(string $path, array $data): TestResponse
    {
        return $this->post($path, $this->withCsrf($data));
    }

    private function withCsrf(array $data): array
    {
        $data[csrf_token()] = csrf_hash();

        return $data;
    }

    private function decodeJson(TestResponse $result): array
    {
        $payload = json_decode((string) $result->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);

        return $payload;
    }

    private function assertJsonContentType(TestResponse $result): void
    {
        $this->assertStringStartsWith('application/json', strtolower($result->response()->getHeaderLine('Content-Type')));
    }

    private function sampleStructure(): array
    {
        return [
            [
                'name' => 'id',
                'type' => 'int',
                'max_length' => 11,
                'primary_key' => 1,
                'is_index' => false,
                'nullable' => false,
                'default' => null,
                'auto_increment' => true,
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
