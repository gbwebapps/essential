<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class ImportModelTest extends CIUnitTestCase
{
    private const SESSION_TOKEN = 'phpunit-import-session';

    /** @var string[] */
    private array $temporaryFiles = [];

    /** @var string[] */
    private array $stagingImportIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        session()->set('backendSession', self::SESSION_TOKEN);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file):
            if (is_file($file)):
                @unlink($file);
            endif;
        endforeach;

        foreach ($this->stagingImportIds as $importId):
            $this->removeDirectory(WRITEPATH . 'uploads/staging/' . $importId);
            $this->removeDirectory(WRITEPATH . 'backups/imports/' . $importId);
        endforeach;

        session()->remove('backendSession');
        Services::resetSingle('validation');
        parent::tearDown();
    }

    public function testGetTargetModelInstanceReturnsInstanceWhenClassExists(): void
    {
        $model = new ImportModel();
        $result = $this->invokeMethod($model, 'getTargetModelInstance', ['groups']);

        $this->assertInstanceOf(\App\Models\Backend\GroupsModel::class, $result);
    }

    public function testGetTargetModelInstanceReturnsNullForInvalidEntity(): void
    {
        $model = new ImportModel();

        $this->assertNull($this->invokeMethod($model, 'getTargetModelInstance', ['../admins']));
    }

    public function testGetTargetModelInstanceReturnsNullWhenClassDoesNotExist(): void
    {
        $model = new ImportModel();

        $this->assertNull($this->invokeMethod($model, 'getTargetModelInstance', ['ghost_entity_that_does_not_exist']));
    }

    public function testGetTableStructureReturnsEmptyArrayWhenTableDoesNotExist(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('tableExists')->with('missing_table')->willReturn(false);
        $this->injectDatabase($model, $db);

        $this->assertSame([], $model->getTableStructure('missing_table'));
    }

    public function testGetTableStructureReturnsCompletePortableMetadata(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $id = (object) ['name' => 'id', 'type' => 'int', 'max_length' => 11, 'primary_key' => 1, 'nullable' => false, 'default' => null];
        $email = (object) ['name' => 'email', 'type' => 'varchar', 'max_length' => 255, 'primary_key' => 0, 'nullable' => false, 'default' => null];
        $notes = (object) ['name' => 'notes', 'type' => 'text', 'max_length' => null, 'primary_key' => 0, 'nullable' => true, 'default' => null];
        $emailIndex = (object) ['name' => 'email_idx', 'fields' => ['email']];

        $db->method('tableExists')->with('real_table')->willReturn(true);
        $db->method('getFieldData')->with('real_table')->willReturn([$id, $email, $notes]);
        $db->method('getIndexData')->with('real_table')->willReturn([$emailIndex]);
        $db->method('getPlatform')->willReturn('SQLite3');
        $this->injectDatabase($model, $db);

        $this->assertSame([
            ['name' => 'id', 'type' => 'int', 'max_length' => 11, 'primary_key' => 1, 'is_index' => false, 'nullable' => false, 'default' => null, 'auto_increment' => false, 'generated' => false],
            ['name' => 'email', 'type' => 'varchar', 'max_length' => 255, 'primary_key' => 0, 'is_index' => true, 'nullable' => false, 'default' => null, 'auto_increment' => false, 'generated' => false],
            ['name' => 'notes', 'type' => 'text', 'max_length' => null, 'primary_key' => 0, 'is_index' => false, 'nullable' => true, 'default' => null, 'auto_increment' => false, 'generated' => false],
        ], $model->getTableStructure('real_table'));
    }

    public function testGetImportTableStructureRejectsUnknownMode(): void
    {
        $model = new ImportModel();

        $this->assertSame([], $model->getImportTableStructure('admins', 'unknown'));
    }

    public function testGetImportTableStructureDatabaseModeReturnsWritablePhysicalSchema(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getTableStructure'])->getMock();
        $structure = $this->structureWithGeneratedColumn();
        $model->method('getTableStructure')->with('any_table')->willReturn($structure);

        $result = $model->getImportTableStructure('any_table', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertSame(['id', 'email'], array_column($result, 'name'));
    }

    public function testGetImportTableStructureRejectsCompositePrimaryKey(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getTableStructure'])->getMock();
        $structure = [
            $this->field('id', 'int', 11, 1),
            $this->field('tenant_id', 'int', 11, 1),
            $this->field('email', 'varchar', 255, 0),
        ];
        $model->method('getTableStructure')->willReturn($structure);

        $this->assertSame([], $model->getImportTableStructure('any_table', ImportModel::IMPORT_MODE_DATABASE));
    }

    public function testGetImportTableStructureCrudFailsClosedWithoutDomainProvider(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getTableStructure'])->getMock();
        $model->method('getTableStructure')->willReturn([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);

        $this->assertSame([], $model->getImportTableStructure('ghost_entity_that_does_not_exist', ImportModel::IMPORT_MODE_CRUD));
    }

    public function testParseAndValidateCsvRejectsUnknownMode(): void
    {
        $model = new ImportModel();
        $file = $this->uploadedFileFor("id,email\n1,test@example.com\n");

        $result = $model->parseAndValidateCsv($file, 'admins', 'unknown');

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.invalidEntity'), $result['message']);
    }

    public function testParseAndValidateCsvReturnsNoStructureError(): void
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getImportTableStructure'])->getMock();
        $model->method('getImportTableStructure')->willReturn([]);
        $file = $this->uploadedFileFor("id,email\n1,test@example.com\n");

        $result = $model->parseAndValidateCsv($file, 'admins', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.noStructure'), $result['message']);
    }

    public function testParseAndValidateCsvReturnsFileReadErrorForUnreadableFile(): void
    {
        $model = $this->parseModel([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);
        $file = $this->uploadedFileMock('Z:/path/that/does/not/exist.csv');

        set_error_handler(static fn(): bool => true);
        try {
            $result = $model->parseAndValidateCsv($file, 'users', ImportModel::IMPORT_MODE_DATABASE);
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.fileReadError'), $result['message']);
    }

    public function testParseAndValidateCsvRejectsInsufficientColumns(): void
    {
        $model = $this->parseModel([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id\n1\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.insufficientColumns'), $result['message']);
    }

    public function testParseAndValidateCsvRejectsDuplicateHeaders(): void
    {
        $model = $this->parseModel([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email,email\n1,a@b.test,a@b.test\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('email', $result['message']);
    }

    public function testParseAndValidateCsvRejectsMissingPrimaryKeyHeader(): void
    {
        $model = $this->parseModel([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("email,name\na@b.test,Mario\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertSame(sprintf(lang('backend/components/import.messages.missingPrimaryKey'), 'id'), $result['message']);
    }

    public function testParseAndValidateCsvRejectsUnknownColumns(): void
    {
        $model = $this->parseModel([$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)]);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email,fake\n1,a@b.test,x\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('fake', $result['message']);
    }

    public function testParseAndValidateCsvRejectsDuplicatePrimaryKeys(): void
    {
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];
        $model = $this->parseModel($structure, true);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n1,a@b.test\n1,c@d.test\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('1', $result['message']);
    }

    public function testParseAndValidateCsvReturnsEmptyFileWhenThereAreNoDataRows(): void
    {
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];
        $model = $this->parseModel($structure, false);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.emptyFile'), $result['message']);
    }

    public function testParseAndValidateCsvReturnsValidationErrors(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $model = $this->parseModel($structure, false, ['email' => 'required|valid_email']);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n1,not-an-email\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('validationErrors', $result);
        $this->assertNotEmpty($result['validationErrors']);
    }

    public function testParseAndValidateCsvCreatesStagingManifestForProcessableRows(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $model = $this->parseModel($structure, false, []);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n,first@example.com\n2,second@example.com\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertTrue($result['status']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $result['importId']);
        $this->assertSame(['insert' => 2, 'update' => 0, 'skip' => 0], $result['plan']);
        $this->assertCount(2, $result['rows']);

        $importId = (string) $result['importId'];
        $this->stagingImportIds[] = $importId;
        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;
        $this->assertFileExists($directory . 'data.csv');
        $this->assertFileExists($directory . 'manifest.json');

        $manifest = json_decode((string) file_get_contents($directory . 'manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('users', $manifest['entity']);
        $this->assertSame(ImportModel::IMPORT_MODE_DATABASE, $manifest['mode']);
        $this->assertSame('staged', $manifest['status']);
        $this->assertSame($result['plan'], $manifest['plan']);
        $this->assertSame(hash('sha256', self::SESSION_TOKEN), $manifest['owner']);
        $this->assertSame(['id', 'email', '__import_action', '__import_snapshot'], $manifest['headers']);
    }

    public function testParseAndValidateCsvReturnsNullImportIdWhenEveryRowIsSkipped(): void
    {
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];
        $builder = $this->createMock(BaseBuilder::class);
        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getResultArray')->willReturn([['id' => '1', 'email' => 'same@example.com']]);
        $builder->method('whereIn')->willReturnSelf();
        $builder->method('select')->willReturnSelf();
        $builder->method('get')->willReturn($resultSet);
        $model = $this->parseModel($structure, true, [], $builder);

        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n1,same@example.com\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertTrue($result['status']);
        $this->assertNull($result['importId']);
        $this->assertSame(['insert' => 0, 'update' => 0, 'skip' => 1], $result['plan']);
    }

    public function testParseAndValidateCsvTruncatesValidationErrorsAtConfiguredLimit(): void
    {
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];
        $model = $this->parseModel($structure, false, ['email' => 'valid_email']);
        $csv = "id,email\n";
        for ($i = 1; $i <= 501; $i++):
            $csv .= $i . ",invalid-email-" . $i . "\n";
        endfor;

        $result = $model->parseAndValidateCsv($this->uploadedFileFor($csv), 'users', ImportModel::IMPORT_MODE_DATABASE);

        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('validationErrors', $result);
        $this->assertCount(501, $result['validationErrors']);
        $this->assertSame(
            lang('backend/components/import.messages.additionalErrorsNotShown', [1]),
            $result['validationErrors'][500]
        );
    }

    public function testDeleteStagingImportRejectsInvalidImportId(): void
    {
        $model = new ImportModel();

        $this->assertFalse($model->deleteStagingImport('../invalid'));
    }

    public function testDeleteStagingImportRemovesUntouchedStaging(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $model = $this->parseModel($structure, true, []);
        $result = $model->parseAndValidateCsv($this->uploadedFileFor("id,email\n,first@example.com\n"), 'users', ImportModel::IMPORT_MODE_DATABASE);
        $importId = (string) $result['importId'];
        $this->stagingImportIds[] = $importId;

        $this->assertTrue($model->deleteStagingImport($importId));
        $this->assertDirectoryDoesNotExist(WRITEPATH . 'uploads/staging/' . $importId);
    }

    public function testBackupImportRejectsInvalidImportId(): void
    {
        $model = new ImportModel();

        $this->assertFalse($model->backupImport('invalid'));
    }

    public function testBackupImportIsIdempotentWhenReadyBackupIsAlreadyValid(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $backup = $this->attachValidBackup($importId);
        $backupPath = WRITEPATH . 'backups/imports/' . $importId . '/backup.sql';

        $this->assertTrue($model->backupImport($importId));
        $this->assertSame($backup['fileHash'], hash_file('sha256', $backupPath));
        $this->assertSame($backup, $this->readManifest($importId)['backup']);
    }

    public function testBackupImportRejectsTamperedStagingBeforeBackupCreation(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $path = WRITEPATH . 'uploads/staging/' . $importId . '/data.csv';
        $contents = (string) file_get_contents($path);
        $tampered = str_replace('email', 'xmail', $contents);
        $this->assertSame(strlen($contents), strlen($tampered));
        file_put_contents($path, $tampered);

        $this->assertFalse($model->backupImport($importId));
        $this->assertDirectoryDoesNotExist(WRITEPATH . 'backups/imports/' . $importId);
    }

    public function testExecuteImportRejectsInvalidImportId(): void
    {
        $model = new ImportModel();
        $result = $model->executeImport('invalid');

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.importationUndone'), $result['message']);
    }

    public function testExecuteImportIsIdempotentWhenManifestIsAlreadyCompleted(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('completed', ['insert' => 2, 'update' => 1, 'skip' => 0], ['processed' => 3, 'inserted' => 2, 'updated' => 1]);

        $result = $model->executeImport($importId);

        $this->assertTrue($result['status']);
        $this->assertTrue($result['isFinished']);
        $this->assertSame(0, $result['inserted']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(2, $result['totalInserted']);
        $this->assertSame(1, $result['totalUpdated']);
        $this->assertSame(3, $result['processed']);
    }

    public function testExecuteImportRequiresBackupBeforeFirstWrite(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.backupError'), $result['message']);
    }

    public function testExecuteImportFailsClosedWhenRecoveryFlagExists(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        file_put_contents(WRITEPATH . 'uploads/staging/' . $importId . '/recovery.required', '{}');

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertTrue($result['recoveryRequired']);
        $this->assertSame(lang('backend/components/import.messages.importTransactionError'), $result['message']);
    }

    public function testResolveStagingContextRejectsDifferentSessionOwner(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);

        session()->set('backendSession', 'different-session');
        try {
            $context = $this->invokeMethod($model, 'resolveStagingContext', [$importId, true]);
        } finally {
            session()->set('backendSession', self::SESSION_TOKEN);
        }

        $this->assertNull($context);
    }

    public function testResolveStagingContextRejectsExpiredManifestUnlessExplicitlyAllowed(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        $manifest['expiresAt'] = time() - 1;
        $this->writeManifest($importId, $manifest);

        $this->assertNull($this->invokeMethod($model, 'resolveStagingContext', [$importId, false, false]));
        $this->assertIsArray($this->invokeMethod($model, 'resolveStagingContext', [$importId, false, true]));
    }

    public function testResolveStagingContextDetectsSameSizeStagingTamperingWhenChecksumIsRequired(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $path = WRITEPATH . 'uploads/staging/' . $importId . '/data.csv';
        $contents = (string) file_get_contents($path);
        $tampered = str_replace('email', 'xmail', $contents);
        $this->assertSame(strlen($contents), strlen($tampered));
        file_put_contents($path, $tampered);

        $this->assertIsArray($this->invokeMethod($model, 'resolveStagingContext', [$importId, false]));
        $this->assertNull($this->invokeMethod($model, 'resolveStagingContext', [$importId, true]));
    }

    public function testResolveStagingContextRejectsInconsistentProgressAccounting(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('processing', ['insert' => 2, 'update' => 0, 'skip' => 0], ['processed' => 1, 'inserted' => 1, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        $manifest['progress']['processed'] = 2;
        $this->writeManifest($importId, $manifest);

        $this->assertNull($this->invokeMethod($model, 'resolveStagingContext', [$importId, false]));
    }

    public function testCleanupExpiredStagingImportsRemovesExpiredSafeStaging(): void
    {
        $model = new ImportModel();
        $importId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        $manifest['expiresAt'] = time() - 10;
        $this->writeManifest($importId, $manifest);

        $this->invokeMethod($model, 'cleanupExpiredStagingImports');

        $this->assertDirectoryDoesNotExist(WRITEPATH . 'uploads/staging/' . $importId);
    }

    public function testCleanupExpiredStagingImportsPreservesFailedRecoveryState(): void
    {
        $model = new ImportModel();
        $importId = $this->createStagingFixture('failed', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 1, 'inserted' => 1, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        $manifest['expiresAt'] = time() - 10;
        $manifest['failure'] = ['at' => time() - 20, 'reason' => 'phpunit recovery', 'recoveryRequired' => true];
        $this->writeManifest($importId, $manifest);

        $this->invokeMethod($model, 'cleanupExpiredStagingImports');

        $this->assertDirectoryExists(WRITEPATH . 'uploads/staging/' . $importId);
    }

    public function testCleanupExpiredImportBackupsRemovesOnlyOldOrphans(): void
    {
        $model = new ImportModel();
        $orphanId = bin2hex(random_bytes(32));
        $this->stagingImportIds[] = $orphanId;
        $backupDirectory = WRITEPATH . 'backups/imports/' . $orphanId;
        mkdir($backupDirectory, 0750, true);
        $backupPath = $backupDirectory . '/backup.sql';
        file_put_contents($backupPath, '-- orphan backup');
        touch($backupPath, time() - 2592001);

        $activeId = $this->createStagingFixture('staged', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $activeBackupDirectory = WRITEPATH . 'backups/imports/' . $activeId;
        mkdir($activeBackupDirectory, 0750, true);
        $activeBackupPath = $activeBackupDirectory . '/backup.sql';
        file_put_contents($activeBackupPath, '-- active backup');
        touch($activeBackupPath, time() - 2592001);

        $this->invokeMethod($model, 'cleanupExpiredImportBackups');

        $this->assertDirectoryDoesNotExist($backupDirectory);
        $this->assertFileExists($activeBackupPath);
    }

    public function testDeleteStagingImportRefusesProcessingImport(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('processing', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);

        $this->assertFalse($model->deleteStagingImport($importId));
        $this->assertDirectoryExists(WRITEPATH . 'uploads/staging/' . $importId);
    }

    public function testDeleteStagingImportRefusesPartialFailedImport(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('failed', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 1, 'inserted' => 1, 'updated' => 0]);

        $this->assertFalse($model->deleteStagingImport($importId));
        $this->assertDirectoryExists(WRITEPATH . 'uploads/staging/' . $importId);
    }

    public function testIsImportBackupValidDetectsSameSizeChecksumTampering(): void
    {
        $model = new ImportModel();
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $backup = $this->attachValidBackup($importId, '-- backup A');
        $manifest = $this->readManifest($importId);
        $backupPath = WRITEPATH . 'backups/imports/' . $importId . '/backup.sql';
        file_put_contents($backupPath, '-- backup B');
        $this->assertSame($backup['fileSize'], filesize($backupPath));

        $this->assertTrue($this->invokeMethod($model, 'isImportBackupValid', [$importId, $manifest, false]));
        $this->assertFalse($this->invokeMethod($model, 'isImportBackupValid', [$importId, $manifest, true]));
    }

    public function testExecuteImportRejectsTamperedStagingBeforeOpeningTransaction(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->expects($this->never())->method('transBegin');
        $this->injectDatabase($model, $db);
        $data = "id,email,__import_action,__import_snapshot\n,first@example.com,insert,\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);
        $path = WRITEPATH . 'uploads/staging/' . $importId . '/data.csv';
        $contents = (string) file_get_contents($path);
        $tampered = str_replace('first@example.com', 'xirst@example.com', $contents);
        $this->assertSame(strlen($contents), strlen($tampered));
        file_put_contents($path, $tampered);

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.fileNotFoundError'), $result['message']);
    }

    public function testExecuteImportRejectsAmbiguousPendingChunkAndCreatesRecoveryFlag(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        file_put_contents(WRITEPATH . 'uploads/staging/' . $importId . '/chunk.pending', json_encode([
            'startedAt' => time(),
            'cursor' => $manifest['progress']['cursor'],
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
        ], JSON_THROW_ON_ERROR));

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertTrue($result['recoveryRequired']);
        $this->assertFileExists(WRITEPATH . 'uploads/staging/' . $importId . '/recovery.required');
    }

    public function testExecuteImportRemovesStalePendingChunkWhenManifestIsAhead(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('completed', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 1, 'inserted' => 1, 'updated' => 0]);
        $manifest = $this->readManifest($importId);
        $journalPath = WRITEPATH . 'uploads/staging/' . $importId . '/chunk.pending';
        file_put_contents($journalPath, json_encode([
            'startedAt' => time() - 1,
            'cursor' => $manifest['progress']['cursor'],
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
        ], JSON_THROW_ON_ERROR));

        $result = $model->executeImport($importId);

        $this->assertTrue($result['status']);
        $this->assertTrue($result['isFinished']);
        $this->assertFileDoesNotExist($journalPath);
        $this->assertFileDoesNotExist(WRITEPATH . 'uploads/staging/' . $importId . '/recovery.required');
    }

    public function testExecuteImportMarksCorruptedReadyBackupAsFailedWithoutRecovery(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0]);
        $backup = $this->attachValidBackup($importId, '-- backup A');
        $backupPath = WRITEPATH . 'backups/imports/' . $importId . '/backup.sql';
        file_put_contents($backupPath, '-- backup B');
        $this->assertSame($backup['fileSize'], filesize($backupPath));

        $result = $model->executeImport($importId);
        $manifest = $this->readManifest($importId);

        $this->assertFalse($result['status']);
        $this->assertFalse($result['recoveryRequired']);
        $this->assertSame(lang('backend/components/import.messages.backupError'), $result['message']);
        $this->assertSame('failed', $manifest['status']);
        $this->assertFalse($manifest['failure']['recoveryRequired']);
    }

    public function testExecuteImportMarksMissingBackupAfterCommittedProgressAsRecoveryRequired(): void
    {
        $model = new ImportModel();
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $this->injectDatabase($model, $db);
        $importId = $this->createStagingFixture('processing', ['insert' => 2, 'update' => 0, 'skip' => 0], ['processed' => 1, 'inserted' => 1, 'updated' => 0]);

        $result = $model->executeImport($importId);
        $manifest = $this->readManifest($importId);

        $this->assertFalse($result['status']);
        $this->assertTrue($result['recoveryRequired']);
        $this->assertSame('failed', $manifest['status']);
        $this->assertTrue($manifest['failure']['recoveryRequired']);
    }

    public function testExecuteImportFailsClosedWhenTransactionCannotStart(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->expects($this->once())->method('transBegin')->willReturn(false);
        $db->expects($this->never())->method('transCommit');
        $db->expects($this->never())->method('transRollback');
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n,first@example.com,insert,\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);
        $manifest = $this->readManifest($importId);

        $this->assertFalse($result['status']);
        $this->assertSame(lang('backend/components/import.messages.importTransactionError'), $result['message']);
        $this->assertSame('failed', $manifest['status']);
    }

    public function testExecuteImportDetectsConcurrentUpdateConflictAndRollsBack(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false), $this->field('email', 'varchar', 255, 0, false)];
        $snapshotModel = new ImportModel();
        $expectedSnapshot = $this->invokeMethod($snapshotModel, 'buildRowSnapshot', [['id' => '1', 'email' => 'before@example.com'], ['id', 'email'], ImportModel::IMPORT_MODE_DATABASE]);
        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRowArray')->willReturn(['id' => '1', 'email' => 'changed-concurrently@example.com']);
        $builder = $this->createMock(BaseBuilder::class);
        $builder->method('where')->willReturnSelf();
        $builder->method('select')->willReturnSelf();
        $builder->method('get')->willReturn($resultSet);
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->method('table')->with('users')->willReturn($builder);
        $db->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transRollback');
        $db->expects($this->never())->method('transCommit');
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n1,after@example.com,update,{$expectedSnapshot}\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 0, 'update' => 1, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);
        $manifest = $this->readManifest($importId);

        $this->assertFalse($result['status']);
        $this->assertFalse($result['recoveryRequired']);
        $this->assertSame(lang('backend/components/import.messages.importationUndone'), $result['message']);
        $this->assertSame('failed', $manifest['status']);
    }

    public function testExecuteImportDetectsPrimaryKeyCollisionBeforeInsertAndRollsBack(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false), $this->field('email', 'varchar', 255, 0, false)];
        $builder = $this->createMock(BaseBuilder::class);
        $builder->method('where')->willReturnSelf();
        $builder->method('countAllResults')->willReturn(1);
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->method('table')->with('users')->willReturn($builder);
        $db->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transRollback');
        $db->expects($this->never())->method('transCommit');
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n1,new@example.com,insert,\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertFalse($result['recoveryRequired']);
        $this->assertSame(lang('backend/components/import.messages.importationUndone'), $result['message']);
    }

    public function testExecuteImportRollsBackWhenTransactionStatusFails(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $builder = $this->createMock(BaseBuilder::class);
        $builder->method('insert')->willReturn(true);
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->method('table')->with('users')->willReturn($builder);
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(false);
        $db->expects($this->once())->method('transRollback');
        $db->expects($this->never())->method('transCommit');
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n,first@example.com,insert,\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertFalse($result['recoveryRequired']);
        $this->assertSame(lang('backend/components/import.messages.importTransactionError'), $result['message']);
    }

    public function testExecuteImportTreatsCommitFailureAsAmbiguousRecoveryState(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $builder = $this->createMock(BaseBuilder::class);
        $builder->method('insert')->willReturn(true);
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->method('table')->with('users')->willReturn($builder);
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(false);
        $db->expects($this->never())->method('transRollback');
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n,first@example.com,insert,\n";
        $importId = $this->createStagingFixture('ready', ['insert' => 1, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);

        $this->assertFalse($result['status']);
        $this->assertTrue($result['recoveryRequired']);
        $this->assertFileExists(WRITEPATH . 'uploads/staging/' . $importId . '/chunk.pending');
        $this->assertFileExists(WRITEPATH . 'uploads/staging/' . $importId . '/recovery.required');
    }

    public function testExecuteImportCommitsExactlyOneChunkAndPersistsServerSideProgress(): void
    {
        $structure = [$this->field('id', 'int', 11, 1, false, null, true), $this->field('email', 'varchar', 255, 0, false)];
        $builder = $this->createMock(BaseBuilder::class);
        $builder->expects($this->exactly(5))->method('insert')->willReturn(true);
        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->with('users')->willReturn(true);
        $db->method('table')->with('users')->willReturn($builder);
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);
        $model = $this->executionModel($db, $structure);
        $data = "id,email,__import_action,__import_snapshot\n";
        for ($i = 1; $i <= 6; $i++):
            $data .= ",user{$i}@example.com,insert,\n";
        endfor;
        $importId = $this->createStagingFixture('ready', ['insert' => 6, 'update' => 0, 'skip' => 0], ['processed' => 0, 'inserted' => 0, 'updated' => 0], $data);
        $this->attachValidBackup($importId);

        $result = $model->executeImport($importId);
        $manifest = $this->readManifest($importId);

        $this->assertTrue($result['status']);
        $this->assertFalse($result['isFinished']);
        $this->assertSame(5, $result['inserted']);
        $this->assertSame(5, $result['totalInserted']);
        $this->assertSame(5, $result['processed']);
        $this->assertSame('processing', $manifest['status']);
        $this->assertSame(5, $manifest['progress']['processed']);
        $this->assertSame(5, $manifest['progress']['inserted']);
        $this->assertFileDoesNotExist(WRITEPATH . 'uploads/staging/' . $importId . '/chunk.pending');
    }

    public function testBuildDynamicRulesMapsInsertSchemaCorrectly(): void
    {
        $model = new ImportModel();
        $structure = [
            $this->field('id', 'int', 11, 1, false, null, true),
            $this->field('email', 'varchar', 100, 0, false),
            $this->field('note', 'text', null, 0, true),
            $this->field('created_at', 'datetime', null, 0, false),
        ];

        $rules = $this->invokeMethod($model, 'buildDynamicRules', [$structure, 'insert', ImportModel::IMPORT_MODE_CRUD]);

        $this->assertSame('permit_empty|integer', $rules['id']);
        $this->assertSame('required|string|max_length[100]', $rules['email']);
        $this->assertSame('permit_empty|string', $rules['note']);
        $this->assertSame('permit_empty|valid_date[Y-m-d H:i:s]', $rules['created_at']);
    }

    public function testBuildDynamicRulesRequiresPrimaryKeyOnUpdate(): void
    {
        $model = new ImportModel();
        $structure = [$this->field('uuid', 'varchar', 36, 1, false), $this->field('email', 'varchar', 255, 0, false)];

        $rules = $this->invokeMethod($model, 'buildDynamicRules', [$structure, 'update', ImportModel::IMPORT_MODE_DATABASE]);

        $this->assertSame('required|string|max_length[36]', $rules['uuid']);
        $this->assertSame('required|string|max_length[255]', $rules['email']);
    }

    public function testBuildRowSnapshotIgnoresCrudLifecycleTimestamps(): void
    {
        $model = new ImportModel();
        $rowA = ['id' => '1', 'email' => 'a@b.test', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'];
        $rowB = ['id' => '1', 'email' => 'a@b.test', 'created_at' => '2026-02-01 00:00:00', 'updated_at' => '2026-02-01 00:00:00'];
        $headers = ['id', 'email', 'created_at', 'updated_at'];

        $crudA = $this->invokeMethod($model, 'buildRowSnapshot', [$rowA, $headers, ImportModel::IMPORT_MODE_CRUD]);
        $crudB = $this->invokeMethod($model, 'buildRowSnapshot', [$rowB, $headers, ImportModel::IMPORT_MODE_CRUD]);
        $databaseA = $this->invokeMethod($model, 'buildRowSnapshot', [$rowA, $headers, ImportModel::IMPORT_MODE_DATABASE]);
        $databaseB = $this->invokeMethod($model, 'buildRowSnapshot', [$rowB, $headers, ImportModel::IMPORT_MODE_DATABASE]);

        $this->assertSame($crudA, $crudB);
        $this->assertNotSame($databaseA, $databaseB);
    }

    public function testValidateStagingDefinitionAcceptsAuthorizedTechnicalHeaders(): void
    {
        $model = new ImportModel();
        $headers = ['id', 'email', '__import_action', '__import_snapshot'];
        $manifest = ['headers' => $headers];
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];

        $this->assertTrue($this->invokeMethod($model, 'validateStagingDefinition', [$headers, $manifest, $structure, 'id']));
    }

    public function testValidateStagingDefinitionRejectsUnexpectedColumns(): void
    {
        $model = new ImportModel();
        $headers = ['id', 'email', 'forged', '__import_action', '__import_snapshot'];
        $manifest = ['headers' => $headers];
        $structure = [$this->field('id', 'int', 11, 1), $this->field('email', 'varchar', 255, 0)];

        $this->assertFalse($this->invokeMethod($model, 'validateStagingDefinition', [$headers, $manifest, $structure, 'id']));
    }

    public function testIsCsvRowEmptyDoesNotTreatZeroAsEmpty(): void
    {
        $model = new ImportModel();

        $this->assertTrue($this->invokeMethod($model, 'isCsvRowEmpty', [['', '   ', null]]));
        $this->assertFalse($this->invokeMethod($model, 'isCsvRowEmpty', [['0', '']]));
    }

    private function parseModel(array $structure, bool $tableExists = false, array $dynamicRules = [], ?BaseBuilder $builder = null): ImportModel
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getImportTableStructure', 'buildDynamicRules'])->getMock();
        $model->method('getImportTableStructure')->willReturn($structure);
        $model->method('buildDynamicRules')->willReturn($dynamicRules);

        $db = $this->createMock(BaseConnection::class);
        $db->method('tableExists')->willReturn($tableExists);
        if ($builder !== null):
            $db->method('table')->willReturn($builder);
        endif;
        $this->injectDatabase($model, $db);

        return $model;
    }

    private function uploadedFileFor(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_model_');
        if ($path === false):
            $this->fail('Unable to create temporary CSV file.');
        endif;

        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $this->uploadedFileMock($path);
    }

    private function uploadedFileMock(string $path): UploadedFile
    {
        $file = $this->getMockBuilder(UploadedFile::class)->disableOriginalConstructor()->onlyMethods(['getTempName'])->getMock();
        $file->method('getTempName')->willReturn($path);

        return $file;
    }

    private function injectDatabase(ImportModel $model, BaseConnection $db): void
    {
        $inject = function(BaseConnection $connection): void {
            $this->db = $connection;
        };
        $bound = \Closure::bind($inject, $model, ImportModel::class);
        $bound($db);
    }

    private function invokeMethod(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    private function field(string $name, string $type, ?int $maxLength, int $primaryKey, bool $nullable = true, mixed $default = null, bool $autoIncrement = false, bool $generated = false): array
    {
        return ['name' => $name, 'type' => $type, 'max_length' => $maxLength, 'primary_key' => $primaryKey, 'is_index' => false, 'nullable' => $nullable, 'default' => $default, 'auto_increment' => $autoIncrement, 'generated' => $generated];
    }

    private function structureWithGeneratedColumn(): array
    {
        return [
            $this->field('id', 'int', 11, 1, false, null, true),
            $this->field('email', 'varchar', 255, 0, false),
            $this->field('computed_value', 'varchar', 255, 0, true, null, false, true),
        ];
    }

    private function createStagingFixture(string $status, array $plan, array $progress, ?string $data = null): string
    {
        $importId = bin2hex(random_bytes(32));
        $this->stagingImportIds[] = $importId;
        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;
        if ( ! is_dir($directory)):
            mkdir($directory, 0750, true);
        endif;

        $data ??= "id,email,__import_action,__import_snapshot\n";
        file_put_contents($directory . 'data.csv', $data);
        $newlinePosition = strpos($data, "\n");
        $dataOffset = $newlinePosition === false ? strlen($data) : $newlinePosition + 1;
        $progress['cursor'] = $progress['cursor'] ?? $dataOffset;
        $now = time();
        $manifest = [
            'version' => 1,
            'importId' => $importId,
            'entity' => 'users',
            'mode' => ImportModel::IMPORT_MODE_DATABASE,
            'status' => $status,
            'createdAt' => $now,
            'updatedAt' => $now,
            'expiresAt' => $now + 3600,
            'owner' => hash('sha256', self::SESSION_TOKEN),
            'file' => 'data.csv',
            'fileSize' => filesize($directory . 'data.csv'),
            'fileHash' => hash_file('sha256', $directory . 'data.csv'),
            'headers' => ['id', 'email', '__import_action', '__import_snapshot'],
            'plan' => $plan,
            'backup' => null,
            'failure' => null,
            'progress' => ['cursor' => (int) $progress['cursor'], 'processed' => (int) $progress['processed'], 'inserted' => (int) $progress['inserted'], 'updated' => (int) $progress['updated']],
        ];
        $this->writeManifest($importId, $manifest);

        return $importId;
    }

    private function executionModel(BaseConnection $db, array $structure): ImportModel
    {
        $model = $this->getMockBuilder(ImportModel::class)->onlyMethods(['getTableStructure', 'getImportTableStructure', 'buildDynamicRules'])->getMock();
        $model->method('getTableStructure')->willReturn($structure);
        $model->method('getImportTableStructure')->willReturn($structure);
        $model->method('buildDynamicRules')->willReturn([]);
        $this->injectDatabase($model, $db);

        return $model;
    }

    private function readManifest(string $importId): array
    {
        $path = WRITEPATH . 'uploads/staging/' . $importId . '/manifest.json';
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function writeManifest(string $importId, array $manifest): void
    {
        $path = WRITEPATH . 'uploads/staging/' . $importId . '/manifest.json';
        file_put_contents($path, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function attachValidBackup(string $importId, string $contents = '-- phpunit backup'): array
    {
        $directory = WRITEPATH . 'backups/imports/' . $importId . DIRECTORY_SEPARATOR;
        if ( ! is_dir($directory)):
            mkdir($directory, 0750, true);
        endif;
        $path = $directory . 'backup.sql';
        file_put_contents($path, $contents);
        $backup = [
            'file' => 'backup.sql',
            'fileSize' => filesize($path),
            'fileHash' => hash_file('sha256', $path),
            'createdAt' => time(),
            'rowCount' => 0,
        ];
        $manifest = $this->readManifest($importId);
        $manifest['backup'] = $backup;
        $this->writeManifest($importId, $manifest);

        return $backup;
    }

    private function removeDirectory(string $directory): void
    {
        if ( ! is_dir($directory)):
            return;
        endif;

        $items = scandir($directory);
        if ($items === false):
            return;
        endif;

        foreach ($items as $item):
            if ($item === '.' || $item === '..'):
                continue;
            endif;
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)):
                $this->removeDirectory($path);
            else:
                @unlink($path);
            endif;
        endforeach;

        @rmdir($directory);
    }
}
