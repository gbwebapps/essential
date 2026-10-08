<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use App\Interfaces\ExportValidationInterface;
use App\Models\Backend\BackendModel;

class ExportModel extends BackendModel
{
    public const CONTEXT_CRUD = 'crud';
    public const CONTEXT_DATABASE = 'database';

    private const EXPORT_CHUNK_SIZE = 5;
    private const EXPORT_VERSION = 1;
    private const EXPORT_MANIFEST = 'manifest.json';
    private const EXPORT_FILE = 'data.csv';
    private const EXPORT_LOCK = 'export.lock';

    /** @var list<string> */
    private array $blockedTables = ['sessions'];

    /** @var array<string, list<string>> */
    private array $sensitiveColumns = [
        'admins' => ['password_hash'],
        'admins_tokens' => ['token_hash'],
        'admins_2fa' => ['secret'],
        'admins_2fa_codes' => ['code'],
        'admins_sessions' => ['data'],
        'settings' => ['value'],
    ];

    /**
     * Restituisce le colonne sensibili escluse dall'esportazione per la tabella indicata.
     *
     * @param string $table Nome fisico della tabella.
     * @return list<string> Colonne che non devono essere esposte nell'export.
     */

    private function getSensitiveColumns(string $table): array
    {
        return $this->sensitiveColumns[$table] ?? [];
    }

    /**
     * Restituisce le regole di validazione della richiesta iniziale di esportazione.
     *
     * @return array<string, array<string, mixed>> Regole compatibili con il validator di CodeIgniter.
     */

    public function generateValidationRules(): array
    {
        return [
            'entity' => ['label' => lang('backend/components/export.labels.entity'), 'rules' => ['required', 'alpha_dash']],
            'order' => ['label' => lang('backend/components/export.labels.order'), 'rules' => ['permit_empty', 'in_list[asc,desc,ASC,DESC]']],
            'column' => ['label' => lang('backend/components/export.labels.column'), 'rules' => ['permit_empty', 'alpha_dash']],
            'trash_filter' => ['label' => lang('backend/components/export.labels.trash_filter'), 'rules' => ['permit_empty', 'in_list[active,trashed,all]']],
            'page' => ['label' => lang('backend/components/export.labels.page'), 'rules' => ['permit_empty', 'is_natural_no_zero']],
        ];
    }

    /**
     * Determina le colonne esportabili in funzione della tabella e del contesto.
     *
     * CRUD applica la whitelist del model quando disponibile; DATABASE usa lo schema fisico
     * escludendo le colonne classificate come sensibili.
     *
     * @param string $table Nome fisico della tabella.
     * @param string $context Contesto di esportazione: crud oppure database.
     * @return list<string> Colonne autorizzate per l'esportazione.
     */

    public function getExportColumns(string $table, string $context = self::CONTEXT_CRUD): array
    {
        if (! $this->isValidContext($context) || in_array($table, $this->blockedTables, true) || ! $this->db->tableExists($table)):
            return [];
        endif;

        $schemaColumns = $this->db->getFieldNames($table);

        if ($context === self::CONTEXT_DATABASE):
            return array_values(array_filter($schemaColumns, fn (string $column): bool => ! in_array($column, $this->getSensitiveColumns($table), true)));
        endif;

        $targetModel = $this->getTargetModelInstance($table);

        if ($targetModel !== null):
            $columns = [];

            foreach ($targetModel->exportAllowedFields() as $column):
                if (in_array($column, $schemaColumns, true) && ! in_array($column, $this->getSensitiveColumns($table), true)):
                    $columns[] = $column;
                endif;
            endforeach;

            return array_values(array_unique($columns));
        endif;

        $fields = $this->db->getFieldData($table);
        $columns = [];

        foreach ($fields as $field):
            if ($field->primary_key !== 1 && $field->name !== 'id' && ! in_array($field->name, $this->getSensitiveColumns($table), true)):
                $columns[] = $field->name;
            endif;
        endforeach;

        return $columns;
    }

    /**
     * Restituisce le colonne che devono essere sempre incluse nel CSV.
     *
     * Quando disponibile e autorizzato, uuid resta l'identificatore applicativo obbligatorio.
     *
     * @param string $table Nome fisico della tabella.
     * @param string $context Contesto di esportazione: crud oppure database.
     * @return list<string> Colonne obbligatorie nell'output.
     */

    public function getRequiredExportColumns(string $table, string $context = self::CONTEXT_CRUD): array
    {
        if (! $this->isValidContext($context) || ! $this->db->tableExists($table)):
            return [];
        endif;

        $allowedColumns = $this->getExportColumns($table, $context);

        return in_array('uuid', $allowedColumns, true) ? ['uuid'] : [];
    }

    /**
     * Risolve il provider di validazione export associato a una entità CRUD.
     *
     * In caso di model assente, incompatibile o non istanziabile restituisce null e consente il fallback generico.
     *
     * @param string $entity Nome dell'entità o tabella.
     * @return ExportValidationInterface|null Provider export-specifico, se disponibile.
     */

    private function getTargetModelInstance(string $entity): ?ExportValidationInterface
    {
        $classBase = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $entity)));
        $className = 'App\\Models\\Backend\\' . $classBase . 'Model';

        if (! class_exists($className)):
            return null;
        endif;

        try {
            $model = model($className);
        } catch (\Throwable $e) {
            log_message('error', 'ExportModel: unable to resolve export validation provider for entity ' . $entity . ' - ' . $e->getMessage());
            return null;
        }

        return $model instanceof ExportValidationInterface ? $model : null;
    }

    /**
     * Recupera la chiave primaria fisica della tabella indicata.
     *
     * @param string $table Nome fisico della tabella.
     * @return string|null Nome della primary key oppure null se non disponibile.
     */

    public function getPrimaryKey(string $table): ?string
    {
        if (! $this->db->tableExists($table)):
            return null;
        endif;

        foreach ($this->db->getFieldData($table) as $field):
            if ($field->primary_key == 1):
                return $field->name;
            endif;
        endforeach;

        return null;
    }

    /**
     * Avvia o continua una esportazione CSV asincrona.
     *
     * La prima chiamata valida e congela server-side entità, colonne e filtri; le chiamate successive
     * utilizzano esclusivamente exportId e non accettano più cursore, filename o progresso dal browser.
     *
     * @param array<string, mixed> $posts Payload completo della prima richiesta; vuoto nelle continuazioni.
     * @param string|null $exportId Identificatore server-side dell'export; null alla prima richiesta.
     * @param string $context Contesto determinato dall'endpoint server.
     * @return array<string, mixed> Stato, progresso o risultato finale dell'esportazione.
     */

    public function generate(array $posts = [], ?string $exportId = null, string $context = self::CONTEXT_CRUD): array
    {
        if (! $this->isValidContext($context)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')];
        endif;

        if ($exportId === null):
            $contextData = $this->createExport($posts, $context);

            if ($contextData['result'] === false):
                return $contextData;
            endif;

            $exportId = $contextData['exportId'];
        endif;

        return $this->processExport($exportId, $context);
    }

    /**
     * Elimina gli artefatti di un export appartenente alla sessione amministrativa corrente.
     *
     * Usa un lock esclusivo bloccante per evitare la rimozione mentre un chunk è ancora in scrittura.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @return bool True se la rimozione è completata correttamente, false altrimenti.
     */

    public function deleteExport(string $exportId): bool
    {
        $lockHandle = $this->acquireExportLock($exportId, true);

        if ($lockHandle === null):
            return false;
        endif;

        $directory = $this->getExportDirectory($exportId);

        try {
            $context = $this->resolveExportContext($exportId, null, true);

            if ($context === null):
                return false;
            endif;

            foreach ([self::EXPORT_FILE, self::EXPORT_MANIFEST] as $fileName):
                $path = $directory . $fileName;
                if (is_file($path) && ! @unlink($path)):
                    return false;
                endif;
            endforeach;
        } finally {
            $this->releaseExportLock($lockHandle);
        }

        $lockPath = $directory . self::EXPORT_LOCK;

        if (is_file($lockPath) && ! @unlink($lockPath)):
            return false;
        endif;

        return ! is_dir($directory) || @rmdir($directory);
    }

    /**
     * Risolve il file scaricabile di un export completato e appartenente alla sessione corrente.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @return array{path:string,fileName:string}|null Percorso fisico e nome download oppure null.
     */

    public function getDownloadFile(string $exportId): ?array
    {
        $context = $this->resolveExportContext($exportId);

        if ($context === null || ($context['manifest']['status'] ?? null) !== 'completed'):
            return null;
        endif;

        $filePath = $context['filePath'];

        if (! is_file($filePath)):
            return null;
        endif;

        $fileSize = filesize($filePath);

        if ($fileSize === false || $fileSize !== (int) ($context['manifest']['fileSize'] ?? -1)):
            return null;
        endif;

        return ['path' => $filePath, 'fileName' => (string) $context['manifest']['fileName']];
    }

    /**
     * Crea una nuova sessione export e persiste la definizione autorizzata nel manifest.
     *
     * Dopo questa fase entità, colonne e filtri non dipendono più dai valori inviati dal client.
     *
     * @param array<string, mixed> $posts Payload della richiesta iniziale.
     * @param string $context Contesto determinato dall'endpoint server.
     * @return array<string, mixed> Risultato della creazione con exportId oppure errore.
     */

    private function createExport(array $posts, string $context): array
    {
        $entity = is_string($posts['entity'] ?? null) ? $posts['entity'] : '';

        if ($entity === '' || in_array($entity, $this->blockedTables, true) || ! $this->db->tableExists($entity)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')];
        endif;

        $schemaColumns = $this->db->getFieldNames($entity);

        if (! in_array('id', $schemaColumns, true)):
            return ['result' => false, 'message' => 'Colonna id mancante. Esportazione a cursore impossibile.'];
        endif;

        $allowedExportColumns = $this->getExportColumns($entity, $context);
        $requiredExportColumns = $this->getRequiredExportColumns($entity, $context);
        $requestedColumns = $posts['selected_columns'] ?? [];

        if ($requestedColumns === ''):
            $requestedColumns = [];
        endif;

        if (! is_array($requestedColumns)):
            return ['result' => false, 'message' => 'Le colonne richieste non sono valide.'];
        endif;

        $outputColumns = $requiredExportColumns;

        foreach ($requestedColumns as $column):
            if (! is_string($column) || ! in_array($column, $allowedExportColumns, true)):
                return ['result' => false, 'message' => 'Le colonne richieste non sono valide.'];
            endif;

            if (! in_array($column, $outputColumns, true)):
                $outputColumns[] = $column;
            endif;
        endforeach;

        if ($outputColumns === []):
            return ['result' => false, 'message' => lang('backend/components/export.messages.noColumnsSelected')];
        endif;

        $owner = $this->getOwnerFingerprint();

        if ($owner === null):
            return ['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')];
        endif;

        $filters = $this->normalizeFilters($posts, $schemaColumns, $entity);
        $root = WRITEPATH . 'exports/staging/';

        if (! is_dir($root) && ! mkdir($root, 0750, true) && ! is_dir($root)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
        endif;

        $exportId = bin2hex(random_bytes(32));
        $directory = $this->getExportDirectory($exportId);

        if (! mkdir($directory, 0750, true)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
        endif;

        $manifest = [
            'version' => self::EXPORT_VERSION,
            'exportId' => $exportId,
            'entity' => $entity,
            'context' => $context,
            'owner' => $owner,
            'status' => 'processing',
            'columns' => $outputColumns,
            'filters' => $filters,
            'cursor' => 0,
            'processed' => 0,
            'fileSize' => 0,
            'fileName' => 'export_' . $entity . '_' . date('d_m_Y_H_i_s') . '.csv',
            'createdAt' => time(),
            'updatedAt' => time(),
        ];

        if (! $this->persistManifest($exportId, $manifest)):
            @rmdir($directory);
            return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
        endif;

        return ['result' => true, 'exportId' => $exportId];
    }

    /**
     * Esegue un singolo chunk usando esclusivamente lo stato persistito nel manifest.
     *
     * Applica ownership, lock esclusivo, cursore server-side e rollback del CSV in caso di errore.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @param string $context Contesto atteso per l'endpoint chiamante.
     * @return array<string, mixed> Stato del chunk o risposta finale.
     */

    private function processExport(string $exportId, string $context): array
    {
        $lockHandle = $this->acquireExportLock($exportId, false);

        if ($lockHandle === null):
            return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
        endif;

        try {
            $resolved = $this->resolveExportContext($exportId, $context);

            if ($resolved === null):
                return $this->errorResponse(lang('backend/components/export.messages.invalidEntity'), $exportId);
            endif;

            $manifest = $resolved['manifest'];

            if (($manifest['status'] ?? null) === 'completed'):
                return $this->completedResponse($manifest);
            endif;

            $entity = (string) $manifest['entity'];
            $schemaColumns = $this->db->getFieldNames($entity);

            if (! in_array('id', $schemaColumns, true)):
                return $this->errorResponse(lang('backend/components/export.messages.invalidEntity'), $exportId);
            endif;

            if (! $this->validateFrozenColumns($manifest['columns'], $entity, $context)):
                return $this->errorResponse(lang('backend/components/export.messages.invalidEntity'), $exportId);
            endif;

            [$sql, $bindings] = $this->buildQuery($manifest, $schemaColumns);
            $records = $this->db->query($sql, $bindings)->getResultArray();

            if ($records === [] && (int) $manifest['processed'] === 0):
                return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
            endif;

            if ($records === []):
                $manifest['status'] = 'completed';
                $manifest['updatedAt'] = time();

                if (! $this->persistManifest($exportId, $manifest)):
                    return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
                endif;

                $this->logCompletedExport($manifest);
                return $this->completedResponse($manifest);
            endif;

            $filePath = $resolved['filePath'];
            $expectedSize = (int) $manifest['fileSize'];
            $file = $this->openFileForCommittedAppend($filePath, $expectedSize);

            if ($file === null):
                return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
            endif;

            $writeOk = true;

            if ($expectedSize === 0):
                $writeOk = fwrite($file, "\xEF\xBB\xBF") !== false && fputcsv($file, $manifest['columns'], ',') !== false;
            endif;

            if ($writeOk):
                foreach ($records as $row):
                    $csvRow = [];
                    foreach ($manifest['columns'] as $column):
                        $csvRow[] = $row[$column] ?? null;
                    endforeach;

                    if (fputcsv($file, $csvRow, ',') === false):
                        $writeOk = false;
                        break;
                    endif;
                endforeach;
            endif;

            if (! $writeOk || ! fflush($file)):
                @ftruncate($file, $expectedSize);
                fclose($file);
                return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
            endif;

            $newSize = ftell($file);

            if ($newSize === false):
                @ftruncate($file, $expectedSize);
                fclose($file);
                return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
            endif;

            $lastRecord = end($records);
            $manifest['cursor'] = (int) $lastRecord['id'];
            $manifest['processed'] = (int) $manifest['processed'] + count($records);
            $manifest['fileSize'] = $newSize;
            $manifest['updatedAt'] = time();
            $isFinished = count($records) < self::EXPORT_CHUNK_SIZE;

            if ($isFinished):
                $manifest['status'] = 'completed';
            endif;

            if (! $this->persistManifest($exportId, $manifest)):
                @ftruncate($file, $expectedSize);
                fclose($file);
                return $this->errorResponse(lang('backend/components/export.messages.noDataFound'), $exportId);
            endif;

            fclose($file);

            if ($isFinished):
                $this->logCompletedExport($manifest);
                return $this->completedResponse($manifest);
            endif;

            return [
                'result' => true,
                'isFinished' => false,
                'exportId' => $exportId,
                'processedCount' => (int) $manifest['processed'],
                'progressMessage' => sprintf(lang('backend/components/export.messages.processedRows'), (int) $manifest['processed']),
            ];
        } finally {
            $this->releaseExportLock($lockHandle);
        }
    }

    /**
     * Verifica che le colonne congelate nel manifest siano ancora autorizzate.
     *
     * @param list<string> $columns Colonne persistite nel manifest.
     * @param string $entity Entità esportata.
     * @param string $context Contesto di esportazione.
     * @return bool True se tutte le colonne risultano ancora consentite.
     */

    private function validateFrozenColumns(array $columns, string $entity, string $context): bool
    {
        $allowed = $this->getExportColumns($entity, $context);

        foreach ($columns as $column):
            if (! in_array($column, $allowed, true)):
                return false;
            endif;
        endforeach;

        return $columns !== [];
    }

    /**
     * Costruisce la query SQL del chunk corrente a partire dal manifest server-side.
     *
     * @param array<string, mixed> $manifest Stato persistito dell'export.
     * @param list<string> $schemaColumns Colonne fisiche attuali della tabella.
     * @return array{0:string,1:list<mixed>} SQL parametrico e relativi binding.
     */

    private function buildQuery(array $manifest, array $schemaColumns): array
    {
        $entity = (string) $manifest['entity'];
        $queryColumns = $manifest['columns'];

        if (! in_array('id', $queryColumns, true)):
            $queryColumns[] = 'id';
        endif;

        $protectedQueryColumns = array_map(fn (string $column): string => $this->db->protectIdentifiers($column), $queryColumns);
        $sql = 'select ' . implode(', ', $protectedQueryColumns) . ' from ' . $this->db->protectIdentifiers($entity) . ' where 1 = 1';
        $bindings = [];
        $filterableColumns = array_values(array_diff($schemaColumns, $this->getSensitiveColumns($entity)));

        foreach ($manifest['filters'] as $key => $value):
            if ($key === 'trash_filter'):
                continue;
            endif;

            if (str_ends_with($key, '-from')):
                $field = substr($key, 0, -5);
                if (in_array($field, $filterableColumns, true)):
                    $sql .= ' and ' . $this->db->protectIdentifiers($field) . ' >= ?';
                    $bindings[] = $value;
                endif;
                continue;
            endif;

            if (str_ends_with($key, '-to')):
                $field = substr($key, 0, -3);
                if (in_array($field, $filterableColumns, true)):
                    $sql .= ' and ' . $this->db->protectIdentifiers($field) . ' <= ?';
                    $bindings[] = $value;
                endif;
                continue;
            endif;

            if (in_array($key, $filterableColumns, true)):
                $sql .= ' and ' . $this->db->protectIdentifiers($key) . ' like ?';
                $bindings[] = '%' . $value . '%';
            endif;
        endforeach;

        if (($manifest['filters']['trash_filter'] ?? null) === 'active' && in_array('deleted_at', $schemaColumns, true)):
            $sql .= ' and ' . $this->db->protectIdentifiers('deleted_at') . ' is null';
        elseif (($manifest['filters']['trash_filter'] ?? null) === 'trashed' && in_array('deleted_at', $schemaColumns, true)):
            $sql .= ' and ' . $this->db->protectIdentifiers('deleted_at') . ' is not null';
        endif;

        if ((int) $manifest['cursor'] > 0):
            $sql .= ' and ' . $this->db->protectIdentifiers('id') . ' > ?';
            $bindings[] = (int) $manifest['cursor'];
        endif;

        $sql .= ' order by ' . $this->db->protectIdentifiers('id') . ' ASC LIMIT ' . self::EXPORT_CHUNK_SIZE;

        return [$sql, $bindings];
    }

    /**
     * Normalizza i filtri ricevuti nella richiesta iniziale eliminando campi non autorizzati o sensibili.
     *
     * @param array<string, mixed> $posts Payload iniziale dell'export.
     * @param list<string> $schemaColumns Colonne fisiche della tabella.
     * @param string $entity Entità esportata.
     * @return array<string, scalar|null> Filtri autorizzati da congelare nel manifest.
     */

    private function normalizeFilters(array $posts, array $schemaColumns, string $entity): array
    {
        $filterableColumns = array_values(array_diff($schemaColumns, $this->getSensitiveColumns($entity)));
        $systemKeys = ['entity', 'column', 'order', 'page', 'rows', 'search_bar_visible', 'selected_columns'];
        $filters = [];

        foreach ($posts as $key => $value):
            if (in_array($key, $systemKeys, true) || $value === '' || $value === null || is_array($value) || is_object($value)):
                continue;
            endif;

            if ($key === 'trash_filter'):
                if (in_array($value, ['active', 'trashed', 'all'], true)):
                    $filters[$key] = $value;
                endif;
                continue;
            endif;

            if (str_ends_with($key, '-from') && in_array(substr($key, 0, -5), $filterableColumns, true)):
                $filters[$key] = $value;
                continue;
            endif;

            if (str_ends_with($key, '-to') && in_array(substr($key, 0, -3), $filterableColumns, true)):
                $filters[$key] = $value;
                continue;
            endif;

            if (in_array($key, $filterableColumns, true)):
                $filters[$key] = $value;
            endif;
        endforeach;

        return $filters;
    }

    /**
     * Carica e valida il contesto server-side associato a exportId.
     *
     * Verifica struttura del manifest, ownership, contesto, stato e disponibilità della tabella.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @param string|null $context Contesto richiesto; null per non vincolarlo.
     * @param bool $allowAnyStatus Se true consente la risoluzione indipendentemente dallo stato operativo.
     * @return array{manifest:array<string,mixed>,filePath:string}|null Contesto validato oppure null.
     */

    private function resolveExportContext(string $exportId, ?string $context = null, bool $allowAnyStatus = false): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $exportId) !== 1):
            return null;
        endif;

        $directory = $this->getExportDirectory($exportId);
        $manifestPath = $directory . self::EXPORT_MANIFEST;

        if (! is_file($manifestPath)):
            return null;
        endif;

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($manifest)
            || ($manifest['version'] ?? null) !== self::EXPORT_VERSION
            || ($manifest['exportId'] ?? null) !== $exportId
            || ! is_string($manifest['entity'] ?? null)
            || ! is_string($manifest['context'] ?? null)
            || ! is_string($manifest['owner'] ?? null)
            || ! is_array($manifest['columns'] ?? null)
            || ! is_array($manifest['filters'] ?? null)
            || ! is_int($manifest['cursor'] ?? null)
            || ! is_int($manifest['processed'] ?? null)
            || ! is_int($manifest['fileSize'] ?? null)
            || ! is_string($manifest['fileName'] ?? null)):
            return null;
        endif;

        if (! $allowAnyStatus && ! in_array($manifest['status'] ?? null, ['processing', 'completed'], true)):
            return null;
        endif;

        if ($context !== null && $manifest['context'] !== $context):
            return null;
        endif;

        $owner = $this->getOwnerFingerprint();

        if ($owner === null || ! hash_equals($manifest['owner'], $owner)):
            return null;
        endif;

        if (in_array($manifest['entity'], $this->blockedTables, true) || ! $this->db->tableExists($manifest['entity'])):
            return null;
        endif;

        return ['manifest' => $manifest, 'filePath' => $directory . self::EXPORT_FILE];
    }

    /**
     * Persiste il manifest mediante scrittura temporanea e sostituzione atomica.
     *
     * Il fallback di scrittura diretta gestisce il comportamento di rename su Windows/Laragon.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @param array<string, mixed> $manifest Stato completo da salvare.
     * @return bool True se la persistenza è riuscita.
     */

    private function persistManifest(string $exportId, array $manifest): bool
    {
        $directory = $this->getExportDirectory($exportId);

        if (! is_dir($directory)):
            return false;
        endif;

        try {
            $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        $manifestPath = $directory . self::EXPORT_MANIFEST;
        $tempPath = $manifestPath . '.tmp.' . bin2hex(random_bytes(6));

        if (file_put_contents($tempPath, $json, LOCK_EX) === false):
            return false;
        endif;

        if (! @rename($tempPath, $manifestPath)):
            $written = file_put_contents($manifestPath, $json, LOCK_EX);
            @unlink($tempPath);
            return $written !== false;
        endif;

        return true;
    }

    /**
     * Acquisisce il lock esclusivo associato alla sessione export.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @param bool $blocking True per un lock bloccante, false per un tentativo non bloccante.
     * @return resource|null Handle del lock oppure null se non acquisibile.
     */

    private function acquireExportLock(string $exportId, bool $blocking)
    {
        if (preg_match('/^[a-f0-9]{64}$/', $exportId) !== 1):
            return null;
        endif;

        $directory = $this->getExportDirectory($exportId);

        if (! is_dir($directory)):
            return null;
        endif;

        $handle = fopen($directory . self::EXPORT_LOCK, 'c+');

        if ($handle === false):
            return null;
        endif;

        if (! flock($handle, $blocking ? LOCK_EX : LOCK_EX | LOCK_NB)):
            fclose($handle);
            return null;
        endif;

        return $handle;
    }

    /**
     * Rilascia e chiude un lock precedentemente acquisito.
     *
     * @param resource $handle Handle restituito da acquireExportLock().
     * @return void
     */

    private function releaseExportLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Apre il CSV nel punto coerente con l'ultimo fileSize confermato dal manifest.
     *
     * Se trova byte successivi allo stato committed li tronca prima di proseguire.
     *
     * @param string $filePath Percorso fisico del CSV.
     * @param int $expectedSize Dimensione committed registrata nel manifest.
     * @return resource|null Handle del file oppure null se lo stato non è recuperabile.
     */

    private function openFileForCommittedAppend(string $filePath, int $expectedSize)
    {
        $file = fopen($filePath, 'c+b');

        if ($file === false):
            return null;
        endif;

        clearstatcache(true, $filePath);
        $actualSize = filesize($filePath);

        if ($actualSize === false || $actualSize < $expectedSize):
            fclose($file);
            return null;
        endif;

        if ($actualSize > $expectedSize && ! ftruncate($file, $expectedSize)):
            fclose($file);
            return null;
        endif;

        if (fseek($file, $expectedSize) !== 0):
            fclose($file);
            return null;
        endif;

        return $file;
    }

    /**
     * Calcola il fingerprint della sessione amministrativa proprietaria dell'export.
     *
     * @return string|null Hash SHA-256 della backendSession oppure null se non disponibile.
     */

    private function getOwnerFingerprint(): ?string
    {
        $sessionToken = session()->get('backendSession');

        return is_string($sessionToken) && $sessionToken !== '' ? hash('sha256', $sessionToken) : null;
    }

    /**
     * Restituisce la directory di staging associata all'export.
     *
     * @param string $exportId Identificatore crittografico dell'export.
     * @return string Percorso assoluto della directory di staging.
     */

    private function getExportDirectory(string $exportId): string
    {
        return WRITEPATH . 'exports/staging/' . $exportId . DIRECTORY_SEPARATOR;
    }

    /**
     * Registra l'attività amministrativa relativa a un export completato.
     *
     * @param array<string, mixed> $manifest Manifest finale dell'export.
     * @return void
     */

    private function logCompletedExport(array $manifest): void
    {
        $currentAdmin = service('authorization')->currentAdmin();
        log_admin_activity(
            'EXPORT_DATA',
            (string) $manifest['entity'],
            sprintf(lang('backend/components/export.messages.exportSuccess'), (int) $manifest['processed'], (string) $manifest['entity']),
            $currentAdmin
        );
    }

    /**
     * Costruisce una risposta di errore uniforme, includendo exportId quando disponibile.
     *
     * @param string $message Messaggio applicativo.
     * @param string|null $exportId Identificatore dell'export, se già creato.
     * @return array<string, mixed> Payload di errore.
     */

    private function errorResponse(string $message, ?string $exportId = null): array
    {
        $response = ['result' => false, 'message' => $message];

        if ($exportId !== null):
            $response['exportId'] = $exportId;
        endif;

        return $response;
    }

    /**
     * Costruisce la risposta finale di una esportazione completata.
     *
     * @param array<string, mixed> $manifest Manifest finale dell'export.
     * @return array<string, mixed> Payload con conteggio, messaggio e URL di download.
     */

    private function completedResponse(array $manifest): array
    {
        return [
            'result' => true,
            'isFinished' => true,
            'exportId' => (string) $manifest['exportId'],
            'processedCount' => (int) $manifest['processed'],
            'message' => sprintf(lang('backend/components/export.messages.exportSuccess'), (int) $manifest['processed'], (string) $manifest['entity']),
            'downloadUrl' => base_url('backend/export/download/' . $manifest['exportId']),
        ];
    }

    /**
     * Verifica che il contesto appartenga ai contesti export supportati.
     *
     * @param string $context Contesto da validare.
     * @return bool True per crud o database.
     */

    private function isValidContext(string $context): bool
    {
        return in_array($context, [self::CONTEXT_CRUD, self::CONTEXT_DATABASE], true);
    }
}
