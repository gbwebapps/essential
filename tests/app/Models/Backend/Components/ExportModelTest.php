<?php declare(strict_types = 1);

namespace App\Models\Backend {
    use App\Interfaces\ExportValidationInterface;

    final class ExportContractFixtureModel extends BackendModel implements ExportValidationInterface
    {
        protected function initModel(): void
        {
        }

        public function exportAllowedFields(): array
        {
            return ['uuid', 'title', 'ghost_column', 'title'];
        }
    }

    final class ExportBrokenFixtureModel extends BackendModel implements ExportValidationInterface
    {
        public function __construct()
        {
            throw new \RuntimeException('Broken export provider');
        }

        public function exportAllowedFields(): array
        {
            return ['uuid'];
        }
    }
}

namespace App\Models\Backend\Components {
    use CodeIgniter\Config\Factories;
    use CodeIgniter\Database\BaseConnection;
    use CodeIgniter\Database\BaseResult;
    use CodeIgniter\Test\CIUnitTestCase;
    use Config\Services;

    if (! function_exists(__NAMESPACE__ . '\log_admin_activity')) {
        function log_admin_activity(string $action, string $section, string $details, ?object $currentAdmin = null): bool
        {
            return true;
        }
    }

    final class TestableExportModel extends ExportModel
    {
        protected function initModel(): void
        {
        }
    }

    class ExportModelTest extends CIUnitTestCase
    {
        private const SESSION_TOKEN = 'phpunit-export-session';
        private const FALLBACK_TABLE = 'export_fallback_fixture';
        private const CONTRACT_TABLE = 'export_contract_fixture';
        private const BROKEN_TABLE = 'export_broken_fixture';

        /** @var list<string> */
        private array $exportIds = [];

        protected function setUp(): void
        {
            parent::setUp();
            Factories::reset();
            Services::resetSingle('authorization');
            session()->set('backendSession', self::SESSION_TOKEN);
            $this->ensureExportDirectory();
        }

        protected function tearDown(): void
        {
            foreach ($this->exportIds as $exportId):
                $this->removeExportDirectory($exportId);
            endforeach;

            session()->remove('backendSession');
            Factories::reset();
            Services::resetSingle('authorization');
            parent::tearDown();
        }

        public function testGenerateValidationRulesReturnsExpectedContract(): void
        {
            $rules = (new TestableExportModel())->generateValidationRules();

            $this->assertSame(['entity', 'order', 'column', 'trash_filter', 'page'], array_keys($rules));
            $this->assertContains('required', $rules['entity']['rules']);
            $this->assertContains('alpha_dash', $rules['entity']['rules']);
            $this->assertContains('in_list[asc,desc,ASC,DESC]', $rules['order']['rules']);
            $this->assertContains('in_list[active,trashed,all]', $rules['trash_filter']['rules']);
        }

        public function testGetExportColumnsRejectsInvalidContextBeforeDatabaseLookup(): void
        {
            $model = new TestableExportModel();
            $db = $this->createMock(BaseConnection::class);
            $db->expects($this->never())->method('tableExists');
            $this->injectDatabase($model, $db);

            $this->assertSame([], $model->getExportColumns('admins', 'invalid'));
        }

        public function testGetExportColumnsReturnsEmptyArrayWhenTableDoesNotExist(): void
        {
            [$model] = $this->createModelWithSchema('missing_table', [], [], false);

            $this->assertSame([], $model->getExportColumns('missing_table'));
        }

        public function testDatabaseContextReturnsPhysicalColumnsExceptSensitiveColumns(): void
        {
            [$model] = $this->createModelWithSchema('admins', ['id', 'uuid', 'email', 'password_hash', 'created_at']);

            $this->assertSame(['id', 'uuid', 'email', 'created_at'], $model->getExportColumns('admins', ExportModel::CONTEXT_DATABASE));
        }

        public function testDatabaseContextBlocksSessionsTable(): void
        {
            [$model] = $this->createModelWithSchema('sessions', ['id', 'ip_address', 'timestamp', 'data']);

            $this->assertSame([], $model->getExportColumns('sessions', ExportModel::CONTEXT_DATABASE));
        }

        public function testDatabaseContextExcludesSettingsValue(): void
        {
            [$model] = $this->createModelWithSchema('settings', ['id', 'class', 'key', 'value', 'created_at', 'updated_at']);

            $this->assertSame(['id', 'class', 'key', 'created_at', 'updated_at'], $model->getExportColumns('settings', ExportModel::CONTEXT_DATABASE));
        }

        public function testCrudFallsBackSafelyWhenExportProviderCannotBeInstantiated(): void
        {
            $fields = [$this->field('id', 1), $this->field('uuid'), $this->field('title')];
            [$model] = $this->createModelWithSchema(self::BROKEN_TABLE, ['id', 'uuid', 'title'], $fields);

            $this->assertSame(['uuid', 'title'], $model->getExportColumns(self::BROKEN_TABLE, ExportModel::CONTEXT_CRUD));
        }

        public function testCrudContextUsesExportValidationWhitelistAndSchemaIntersection(): void
        {
            [$model] = $this->createModelWithSchema(self::CONTRACT_TABLE, ['id', 'uuid', 'title', 'internal_note']);

            $this->assertSame(['uuid', 'title'], $model->getExportColumns(self::CONTRACT_TABLE, ExportModel::CONTEXT_CRUD));
        }

        public function testCrudFallbackExcludesIdPrimaryKeysAndSensitiveColumns(): void
        {
            $fields = [$this->field('id', 1), $this->field('uuid'), $this->field('legacy_pk', 1), $this->field('title')];
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'uuid', 'legacy_pk', 'title'], $fields);

            $this->assertSame(['uuid', 'title'], $model->getExportColumns(self::FALLBACK_TABLE, ExportModel::CONTEXT_CRUD));
        }

        public function testRequiredExportColumnsForcesUuidWhenAvailable(): void
        {
            [$model] = $this->createModelWithSchema(self::CONTRACT_TABLE, ['id', 'uuid', 'title']);

            $this->assertSame(['uuid'], $model->getRequiredExportColumns(self::CONTRACT_TABLE));
        }

        public function testRequiredExportColumnsReturnsEmptyArrayWithoutUuid(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);

            $this->assertSame([], $model->getRequiredExportColumns(self::FALLBACK_TABLE));
        }

        public function testGetPrimaryKeyReturnsPhysicalPrimaryKey(): void
        {
            $fields = [$this->field('id'), $this->field('uuid', 1)];
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'uuid'], $fields);

            $this->assertSame('uuid', $model->getPrimaryKey(self::FALLBACK_TABLE));
        }

        public function testGenerateRejectsInvalidContextWithoutDatabaseAccess(): void
        {
            $model = new TestableExportModel();
            $db = $this->createMock(BaseConnection::class);
            $db->expects($this->never())->method('tableExists');
            $this->injectDatabase($model, $db);

            $result = $model->generate(['entity' => self::FALLBACK_TABLE], null, 'invalid');

            $this->assertFalse($result['result']);
        }

        public function testGenerateRejectsMissingEntity(): void
        {
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);

            $result = $model->generate([]);

            $this->assertFalse($result['result']);
            $this->assertSame(lang('backend/components/export.messages.invalidEntity'), $result['message']);
        }

        public function testGenerateRejectsBlockedSessionsTable(): void
        {
            [$model, $db] = $this->createModelWithSchema('sessions', ['id', 'ip_address', 'timestamp', 'data']);
            $db->expects($this->never())->method('query');

            $result = $model->generate(['entity' => 'sessions', 'selected_columns' => ['ip_address']], null, ExportModel::CONTEXT_DATABASE);

            $this->assertFalse($result['result']);
            $this->assertSame(lang('backend/components/export.messages.invalidEntity'), $result['message']);
        }

        public function testGenerateRejectsTableWithoutTechnicalIdCursor(): void
        {
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['uuid', 'title'], [$this->field('uuid', 1), $this->field('title')]);

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => ['title']]);

            $this->assertFalse($result['result']);
            $this->assertStringContainsString('id', $result['message']);
        }

        public function testGenerateRejectsNonArraySelectedColumns(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => 'title']);

            $this->assertFalse($result['result']);
            $this->assertSame('Le colonne richieste non sono valide.', $result['message']);
        }

        public function testGenerateRequiresSelectionWhenUuidIsUnavailable(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);
            $db->expects($this->never())->method('query');

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => []]);

            $this->assertFalse($result['result']);
            $this->assertSame(lang('backend/components/export.messages.noColumnsSelected'), $result['message']);
        }

        public function testGenerateRejectsColumnOutsideAuthorizedExportSchema(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);
            $db->expects($this->never())->method('query');

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => ['forged_column']]);

            $this->assertFalse($result['result']);
            $this->assertSame('Le colonne richieste non sono valide.', $result['message']);
        }

        public function testDatabaseContextRejectsSensitiveSelectedColumn(): void
        {
            [$model, $db] = $this->createModelWithSchema('admins', ['id', 'uuid', 'email', 'password_hash']);
            $db->expects($this->never())->method('query');

            $result = $model->generate(['entity' => 'admins', 'selected_columns' => ['password_hash']], null, ExportModel::CONTEXT_DATABASE);

            $this->assertFalse($result['result']);
            $this->assertSame('Le colonne richieste non sono valide.', $result['message']);
        }

        public function testGenerateRequiresBackendSessionOwnership(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);
            $db->expects($this->never())->method('query');

            session()->remove('backendSession');

            try {
                $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => ['title']]);
            } finally {
                session()->set('backendSession', self::SESSION_TOKEN);
            }

            $this->assertFalse($result['result']);
            $this->assertSame(lang('backend/components/export.messages.invalidEntity'), $result['message']);
        }

        public function testFirstChunkReturnsExportIdWritesBomHeaderAndKeepsIdTechnicalOnly(): void
        {
            $fields = [$this->field('id', 1), $this->field('uuid'), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'uuid', 'title'], $fields);
            $records = $this->records(1, 5, true);

            $executedSql = null;
            $db->expects($this->once())->method('query')->willReturnCallback(function ($sql, $bindings = []) use (&$executedSql, $records) {
                $executedSql = $sql;
                return $this->resultWithRows($records);
            });

            $result = $model->generate([
                'entity' => self::FALLBACK_TABLE,
                'selected_columns' => ['title', 'title'],
                'column' => 'title',
                'order' => 'desc',
            ]);

            $this->assertTrue($result['result']);
            $this->assertFalse($result['isFinished']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['exportId']);
            $this->assertSame(5, $result['processedCount']);
            $this->registerExportId($result['exportId']);
            $this->assertStringContainsString('select uuid, title, id from ' . self::FALLBACK_TABLE, $executedSql);
            $this->assertStringContainsString('order by id ASC LIMIT 5', $executedSql);
            $this->assertStringNotContainsString('order by title', $executedSql);

            $manifest = $this->readManifest($result['exportId']);
            $this->assertSame(['uuid', 'title'], $manifest['columns']);
            $this->assertSame(5, $manifest['cursor']);
            $this->assertSame(5, $manifest['processed']);
            $this->assertSame(hash('sha256', self::SESSION_TOKEN), $manifest['owner']);

            $raw = file_get_contents($this->dataPath($result['exportId']));
            $this->assertIsString($raw);
            $this->assertStringStartsWith("\xEF\xBB\xBF", $raw);

            $rows = $this->readCsvRows($this->dataPath($result['exportId']));
            $this->assertSame(['uuid', 'title'], $rows[0]);
            $this->assertSame(['uuid-1', 'Row 1'], $rows[1]);
            $this->assertCount(6, $rows);
        }

        public function testGenerateAllowsUuidOnlyWhenUuidIsRequired(): void
        {
            $fields = [$this->field('id', 1), $this->field('uuid'), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'uuid', 'title'], $fields);
            $records = $this->records(1, 5, true);
            $db->expects($this->once())->method('query')->willReturn($this->resultWithRows($records));

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => []]);

            $this->assertTrue($result['result']);
            $this->registerExportId($result['exportId']);
            $rows = $this->readCsvRows($this->dataPath($result['exportId']));
            $this->assertSame(['uuid'], $rows[0]);
            $this->assertSame(['uuid-1'], $rows[1]);
        }

        public function testFirstChunkWithNoDataReturnsErrorAndKeepsExportIdForCleanup(): void
        {
            $fields = [$this->field('id', 1), $this->field('title')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], $fields);
            $db->expects($this->once())->method('query')->willReturn($this->resultWithRows([]));

            $result = $model->generate(['entity' => self::FALLBACK_TABLE, 'selected_columns' => ['title']]);

            $this->assertFalse($result['result']);
            $this->assertSame(lang('backend/components/export.messages.noDataFound'), $result['message']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['exportId']);
            $this->registerExportId($result['exportId']);
        }

        public function testFirstChunkFreezesTextDateTrashFiltersAndIgnoresForgedFilter(): void
        {
            $fields = [$this->field('id', 1), $this->field('title'), $this->field('created_at'), $this->field('deleted_at')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title', 'created_at', 'deleted_at'], $fields);
            $records = $this->records(1, 5);

            $executedSql = null;
            $executedBindings = null;
            $db->expects($this->once())->method('query')->willReturnCallback(function ($sql, $bindings = []) use (&$executedSql, &$executedBindings, $records) {
                $executedSql = $sql;
                $executedBindings = $bindings;
                return $this->resultWithRows($records);
            });

            $result = $model->generate([
                'entity' => self::FALLBACK_TABLE,
                'selected_columns' => ['title'],
                'title' => 'needle',
                'created_at-from' => '2026-01-01 00:00:00',
                'created_at-to' => '2026-12-31 23:59:59',
                'trash_filter' => 'active',
                'forged_filter' => 'must-disappear',
            ]);

            $this->assertTrue($result['result']);
            $this->registerExportId($result['exportId']);
            $this->assertStringContainsString('title like ?', $executedSql);
            $this->assertStringContainsString('created_at >= ?', $executedSql);
            $this->assertStringContainsString('created_at <= ?', $executedSql);
            $this->assertStringContainsString('deleted_at is null', strtolower($executedSql));
            $this->assertStringNotContainsString('forged_filter', $executedSql);
            $this->assertSame(['%needle%', '2026-01-01 00:00:00', '2026-12-31 23:59:59'], $executedBindings);

            $manifest = $this->readManifest($result['exportId']);
            $this->assertSame([
                'title' => 'needle',
                'created_at-from' => '2026-01-01 00:00:00',
                'created_at-to' => '2026-12-31 23:59:59',
                'trash_filter' => 'active',
            ], $manifest['filters']);
        }

        public function testDatabaseContextStripsSensitiveFilterBeforeBuildingSql(): void
        {
            [$model, $db] = $this->createModelWithSchema('admins', ['id', 'uuid', 'email', 'password_hash']);
            $records = [];
            for ($id = 1; $id <= 5; $id++):
                $records[] = ['id' => $id, 'uuid' => 'uuid-' . $id, 'email' => 'user' . $id . '@example.test'];
            endfor;

            $executedSql = null;
            $executedBindings = null;
            $db->expects($this->once())->method('query')->willReturnCallback(function ($sql, $bindings = []) use (&$executedSql, &$executedBindings, $records) {
                $executedSql = $sql;
                $executedBindings = $bindings;
                return $this->resultWithRows($records);
            });

            $result = $model->generate([
                'entity' => 'admins',
                'selected_columns' => ['email'],
                'password_hash' => 'secret-value',
            ], null, ExportModel::CONTEXT_DATABASE);

            $this->assertTrue($result['result']);
            $this->registerExportId($result['exportId']);
            $this->assertStringNotContainsString('password_hash', $executedSql);
            $this->assertSame([], $executedBindings);
        }

        public function testContinuationUsesOnlyFrozenManifestStateAndServerCursor(): void
        {
            $fields = [$this->field('id', 1), $this->field('title'), $this->field('deleted_at')];
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title', 'deleted_at'], $fields);

            $executed = [];
            $db->expects($this->exactly(2))->method('query')->willReturnCallback(function ($sql, $bindings = []) use (&$executed) {
                $executed[] = [$sql, $bindings];

                if (count($executed) === 1):
                    return $this->resultWithRows($this->records(1, 5));
                endif;

                return $this->resultWithRows([['id' => 6, 'title' => 'Row 6']]);
            });

            $first = $model->generate([
                'entity' => self::FALLBACK_TABLE,
                'selected_columns' => ['title'],
                'title' => 'needle',
                'trash_filter' => 'active',
            ]);

            $this->assertTrue($first['result']);
            $this->registerExportId($first['exportId']);

            $second = $model->generate([], $first['exportId']);

            $this->assertTrue($second['result']);
            $this->assertTrue($second['isFinished']);
            $this->assertSame(6, $second['processedCount']);
            $this->assertStringContainsString('id > ?', $executed[1][0]);
            $this->assertStringContainsString('title like ?', $executed[1][0]);
            $this->assertStringContainsString('deleted_at is null', strtolower($executed[1][0]));
            $this->assertSame(['%needle%', 5], $executed[1][1]);

            $manifest = $this->readManifest($first['exportId']);
            $this->assertSame(6, $manifest['cursor']);
            $this->assertSame(6, $manifest['processed']);
            $this->assertSame('completed', $manifest['status']);
        }

        public function testContinuationRejectsDifferentSessionOwnerBeforeDatabaseQuery(): void
        {
            $exportId = $this->createManifestFixture();
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            $db->expects($this->never())->method('query');

            session()->set('backendSession', 'different-session');

            try {
                $result = $model->generate([], $exportId);
            } finally {
                session()->set('backendSession', self::SESSION_TOKEN);
            }

            $this->assertFalse($result['result']);
            $this->assertSame($exportId, $result['exportId']);
        }

        public function testContinuationRejectsContextMismatchBeforeDatabaseQuery(): void
        {
            $exportId = $this->createManifestFixture(['context' => ExportModel::CONTEXT_CRUD]);
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            $db->expects($this->never())->method('query');

            $result = $model->generate([], $exportId, ExportModel::CONTEXT_DATABASE);

            $this->assertFalse($result['result']);
            $this->assertSame($exportId, $result['exportId']);
        }

        public function testConcurrentContinuationIsRejectedByExportLock(): void
        {
            $exportId = $this->createManifestFixture();
            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            $db->expects($this->never())->method('query');

            $lock = $this->invokePrivate($model, 'acquireExportLock', [$exportId, false]);
            $this->assertIsResource($lock);

            try {
                $result = $model->generate([], $exportId);
            } finally {
                $this->invokePrivate($model, 'releaseExportLock', [$lock]);
            }

            $this->assertFalse($result['result']);
            $this->assertSame($exportId, $result['exportId']);
        }

        public function testCompletedExportIsIdempotentAndDownloadUsesManifestName(): void
        {
            $exportId = $this->createManifestFixture([
                'status' => 'completed',
                'cursor' => 1,
                'processed' => 1,
                'fileName' => 'export_fixture.csv',
            ], [['title'], ['Row 1']]);

            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            $db->expects($this->never())->method('query');

            $result = $model->generate([], $exportId);

            $this->assertTrue($result['result']);
            $this->assertTrue($result['isFinished']);
            $this->assertSame(1, $result['processedCount']);
            $this->assertStringEndsWith('/backend/export/download/' . $exportId, $result['downloadUrl']);

            $download = $model->getDownloadFile($exportId);
            $this->assertIsArray($download);
            $this->assertSame('export_fixture.csv', $download['fileName']);
            $this->assertSame($this->dataPath($exportId), $download['path']);
        }

        public function testDeleteExportRemovesOnlyOwnedExport(): void
        {
            $exportId = $this->createManifestFixture();

            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);

            $this->assertTrue($model->deleteExport($exportId));
            $this->assertDirectoryDoesNotExist($this->exportDirectory($exportId));
        }

        public function testDeleteExportRejectsDifferentOwner(): void
        {
            $exportId = $this->createManifestFixture();

            [$model] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            session()->set('backendSession', 'different-session');

            try {
                $this->assertFalse($model->deleteExport($exportId));
            } finally {
                session()->set('backendSession', self::SESSION_TOKEN);
            }

            $this->assertDirectoryExists($this->exportDirectory($exportId));
        }

        public function testContinuationTruncatesUncommittedTailBeforeAppending(): void
        {
            $exportId = $this->createManifestFixture([
                'cursor' => 1,
                'processed' => 1,
            ], [['title'], ['Row 1']]);

            $manifest = $this->readManifest($exportId);
            file_put_contents($this->dataPath($exportId), "UNCOMMITTED", FILE_APPEND);

            [$model, $db] = $this->createModelWithSchema(self::FALLBACK_TABLE, ['id', 'title'], [$this->field('id', 1), $this->field('title')]);
            $db->expects($this->once())->method('query')->willReturn($this->resultWithRows([['id' => 2, 'title' => 'Row 2']]));
            $this->installAuthorizationMock();

            $result = $model->generate([], $exportId);

            $this->assertTrue($result['result']);
            $this->assertTrue($result['isFinished']);
            $raw = (string) file_get_contents($this->dataPath($exportId));
            $this->assertStringNotContainsString('UNCOMMITTED', $raw);
            $this->assertSame([['title'], ['Row 1'], ['Row 2']], $this->readCsvRows($this->dataPath($exportId)));
        }

        private function createModelWithSchema(string $table, array $schemaColumns, array $fieldData = [], bool $exists = true): array
        {
            $model = new TestableExportModel();
            $db = $this->createMock(BaseConnection::class);
            $db->method('tableExists')->willReturnCallback(static fn (string $candidate): bool => $exists && $candidate === $table);
            $db->method('getFieldNames')->willReturnCallback(static fn (string $candidate): array => $candidate === $table ? $schemaColumns : []);
            $db->method('getFieldData')->willReturnCallback(static fn (string $candidate): array => $candidate === $table ? $fieldData : []);
            $db->method('protectIdentifiers')->willReturnCallback(static fn ($identifier) => $identifier);
            $this->injectDatabase($model, $db);

            return [$model, $db];
        }

        private function injectDatabase(ExportModel $model, BaseConnection $db): void
        {
            $inject = function (BaseConnection $connection): void {
                $this->db = $connection;
            };

            $binder = \Closure::bind($inject, $model, \App\Models\BaseModel::class);
            $binder($db);
        }

        private function resultWithRows(array $rows): BaseResult
        {
            $result = $this->createMock(BaseResult::class);
            $result->method('getResultArray')->willReturn($rows);

            return $result;
        }

        private function field(string $name, int $primaryKey = 0): object
        {
            return (object) ['name' => $name, 'primary_key' => $primaryKey];
        }

        private function records(int $from, int $to, bool $withUuid = false): array
        {
            $records = [];

            for ($id = $from; $id <= $to; $id++):
                $row = ['id' => $id, 'title' => 'Row ' . $id];

                if ($withUuid):
                    $row['uuid'] = 'uuid-' . $id;
                endif;

                $records[] = $row;
            endfor;

            return $records;
        }

        private function installAuthorizationMock(): void
        {
            $authorization = $this->getMockBuilder(\stdClass::class)->addMethods(['currentAdmin'])->getMock();
            $authorization->method('currentAdmin')->willReturn((object) ['uuid' => 'phpunit-admin']);
            Services::injectMock('authorization', $authorization);
        }

        private function createManifestFixture(array $overrides = [], array $csvRows = []): string
        {
            $exportId = bin2hex(random_bytes(32));
            $this->registerExportId($exportId);

            $directory = $this->exportDirectory($exportId);
            mkdir($directory, 0750, true);

            $manifest = [
                'version' => 1,
                'exportId' => $exportId,
                'entity' => self::FALLBACK_TABLE,
                'context' => ExportModel::CONTEXT_CRUD,
                'owner' => hash('sha256', self::SESSION_TOKEN),
                'status' => 'processing',
                'columns' => ['title'],
                'filters' => [],
                'cursor' => 0,
                'processed' => 0,
                'fileSize' => 0,
                'fileName' => 'export_fixture.csv',
                'createdAt' => time(),
                'updatedAt' => time(),
            ];

            if ($csvRows !== []):
                $file = fopen($this->dataPath($exportId), 'wb');
                fwrite($file, "\xEF\xBB\xBF");

                foreach ($csvRows as $row):
                    fputcsv($file, $row, ',');
                endforeach;

                fclose($file);
                $manifest['fileSize'] = filesize($this->dataPath($exportId));
            endif;

            foreach ($overrides as $key => $value):
                $manifest[$key] = $value;
            endforeach;

            file_put_contents($directory . 'manifest.json', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return $exportId;
        }

        private function readManifest(string $exportId): array
        {
            return json_decode((string) file_get_contents($this->exportDirectory($exportId) . 'manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        }

        private function readCsvRows(string $path): array
        {
            $raw = (string) file_get_contents($path);

            if (str_starts_with($raw, "\xEF\xBB\xBF")):
                $raw = substr($raw, 3);
            endif;

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $raw);
            rewind($stream);

            $rows = [];

            while (($row = fgetcsv($stream, 0, ',')) !== false):
                $rows[] = $row;
            endwhile;

            fclose($stream);

            return $rows;
        }

        private function invokePrivate(object $object, string $method, array $arguments = []): mixed
        {
            $reflection = new \ReflectionMethod($object, $method);
            $reflection->setAccessible(true);

            return $reflection->invokeArgs($object, $arguments);
        }

        private function ensureExportDirectory(): void
        {
            $directory = WRITEPATH . 'exports/staging/';

            if (! is_dir($directory)):
                mkdir($directory, 0750, true);
            endif;
        }

        private function exportDirectory(string $exportId): string
        {
            return WRITEPATH . 'exports/staging/' . $exportId . DIRECTORY_SEPARATOR;
        }

        private function dataPath(string $exportId): string
        {
            return $this->exportDirectory($exportId) . 'data.csv';
        }

        private function registerExportId(string $exportId): void
        {
            if (! in_array($exportId, $this->exportIds, true)):
                $this->exportIds[] = $exportId;
            endif;
        }

        private function removeExportDirectory(string $exportId): void
        {
            $directory = $this->exportDirectory($exportId);

            if (! is_dir($directory)):
                return;
            endif;

            $items = scandir($directory);

            if ($items !== false):
                foreach ($items as $item):
                    if ($item === '.' || $item === '..'):
                        continue;
                    endif;

                    $path = $directory . $item;

                    if (is_file($path)):
                        @unlink($path);
                    endif;
                endforeach;
            endif;

            @rmdir($directory);
        }
    }
}
