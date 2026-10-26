<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Models;

use App\Interfaces\ImportValidationInterface;
use App\Models\Backend\Components\ImportModel;
use Modules\Backend\Warehouse\Config\Package;
use Modules\Backend\Warehouse\Libraries\WarehouseRecoveryException;

/**
 * Applica il contratto del componente Import al pacchetto relazionale Warehouse.
 *
 * Riusa schema SQL, regole strutturali e provider dei CRUD. La pianificazione delle
 * relazioni e la transazione unica costituiscono le varianti del pacchetto XLSX.
 */
class WarehouseImportModel extends ImportModel
{
    /**
     * Espone lo schema reale delle cinque tabelle e verifica le whitelist di dominio.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getPackageStructures(): array
    {
        $structures = [];

        foreach (Package::TABLES as $table):
            $structure = array_values(array_filter($this->getTableStructure($table), static fn(array $field): bool => ! ($field['generated'] ?? false)));

            if ($structure === []):
                throw new \DomainException(lang('backend/components/import.messages.noStructure'));
            endif;

            if (isset(Package::MODELS[$table])):
                $allowed = $this->provider($table)->importAllowedFields();

                if ($allowed === [] || array_diff($allowed, array_column($structure, 'name')) !== []):
                    throw new \DomainException(lang('backend/components/import.messages.noStructure'));
                endif;
            endif;

            $structures[$table] = $structure;
        endforeach;

        return $structures;
    }

    /**
     * Legge uno snapshot completo e ordinato, opzionalmente con lock per l'esecuzione.
     */
    public function readPackageSnapshot(bool $lockRows = false): array
    {
        $snapshot = [];

        foreach ($this->getPackageStructures() as $table => $structure):
            $columns = array_column($structure, 'name');
            $keys = isset(Package::PIVOTS[$table]) ? array_keys(Package::PIVOTS[$table]) : ['id'];
            $builder = $this->db->table($table)->select($columns);

            foreach ($keys as $key):
                $builder->orderBy($key, 'asc');
            endforeach;

            $query = $this->db->query($builder->getCompiledSelect() . ($lockRows ? ' for update' : ''));

            if ($query === false):
                throw new \RuntimeException('Lettura snapshot Warehouse non riuscita.');
            endif;

            $snapshot[$table] = ['headers' => $columns, 'rows' => $query->getResultArray()];
        endforeach;

        return $snapshot;
    }

    /** Espone soltanto le colonne scambiabili, senza date o metadati del ciclo di vita. */
    public function getPackageImportStructures(): array
    {
        $structures = $this->getPackageStructures();

        foreach ($structures as $table => &$structure):
            $allowed = isset(Package::MODELS[$table])
                ? array_merge($this->provider($table)->importAllowedFields(), Package::METADATA)
                : array_column($structure, 'name');
            $structure = array_values(array_filter($structure, static fn(array $field): bool =>
                in_array($field['name'], $allowed, true) && ! in_array($field['name'], Package::TIMESTAMPS, true)
            ));
        endforeach;
        unset($structure);

        return $structures;
    }

    /**
     * Firma dati e schema senza dipendere dai tipi numerici restituiti dal driver.
     */
    public function snapshotHash(array $snapshot): string
    {
        foreach ($snapshot as &$table):
            foreach ($table['rows'] as &$row):
                foreach ($row as &$value):
                    $value = $value === null ? null : (string) $value;
                endforeach;
                unset($value);
            endforeach;
            unset($row);
        endforeach;
        unset($table);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    /**
     * Pianifica l'intero workbook prima di creare staging eseguibile o backup.
     *
     * Gli UUID vuoti vengono generati una volta e conservati nel piano. Gli ID del
     * file sono riferimenti interni: non identificano mai i record del database.
     */
    public function planPackage(array $workbook, array $generatedUuids = [], ?array $snapshot = null): array
    {
        $structures = $this->getPackageStructures();
        $snapshot ??= $this->readPackageSnapshot();
        $errors = [];
        $operations = [];
        $references = [];
        $projected = [];
        $currentByUuid = [];

        foreach (Package::MODELS as $table => $class):
            $currentByUuid[$table] = [];
            $currentById = [];

            foreach ($snapshot[$table]['rows'] as $record):
                $currentByUuid[$table][strtolower($record['uuid'])] = $record;
                $currentById[(int) $record['id']] = strtolower($record['uuid']);
            endforeach;

            $projected[$table] = $currentByUuid[$table];
            $operations[$table] = [];
            $references[$table] = [];
            $seenUuids = [];
            $nextId = 1;

            foreach ($snapshot[$table]['rows'] as $record):
                $nextId = max($nextId, (int) $record['id'] + 1);
            endforeach;

            foreach ($workbook[$table]['rows'] as $source):
                $line = $source['_row'];
                unset($source['_row']);
                $uuid = $source['uuid'] ?? null;

                if ($uuid === null || $uuid === ''):
                    $uuid = $generatedUuids[$table][$line] ?? $this->generateUUID();
                    $generatedUuids[$table][$line] = $uuid;
                else:
                    $uuid = is_string($uuid) ? strtolower(trim($uuid)) : '';
                endif;

                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1):
                    $errors[] = $this->rowError($table, $line, 'uuid');
                    continue;
                endif;

                if (isset($seenUuids[$uuid])):
                    $errors[] = $this->duplicateError($table, $line, 'uuid');
                    continue;
                endif;

                $seenUuids[$uuid] = true;
                $fileId = $source['id'] ?? null;
                $current = $currentByUuid[$table][$uuid] ?? null;

                if ($table === 'products' && $current === null && $fileId === null):
                    $errors[] = ui_message('backend/warehouse.messages.requiredId', [$table, $line], lang('backend/warehouse.messages.requiredId'));
                    continue;
                endif;

                if ($fileId !== null):
                    if ( ! $this->isReferenceId($fileId)):
                        $errors[] = $this->rowError($table, $line, 'id');
                        continue;
                    endif;

                    if (isset($references[$table][(int) $fileId])):
                        $errors[] = $this->duplicateError($table, $line, 'id');
                        continue;
                    endif;

                    if (isset($currentById[(int) $fileId]) && $currentById[(int) $fileId] !== $uuid):
                        $errors[] = $this->duplicateError($table, $line, 'id');
                        continue;
                    endif;

                    if ($current !== null && (int) $current['id'] !== (int) $fileId):
                        $errors[] = ui_message('backend/warehouse.messages.idUuidMismatch', [$table, $line], lang('backend/warehouse.messages.idUuidMismatch'));
                        continue;
                    endif;

                    $references[$table][(int) $fileId] = $uuid;
                endif;

                if ($current === null && $nextId > 4294967295):
                    $errors[] = $this->rowError($table, $line, 'id');
                    continue;
                endif;

                $data = array_intersect_key($source, array_flip($this->provider($table)->importAllowedFields()));
                $data['uuid'] = $uuid;

                foreach ($data as &$value):
                    $value = is_string($value) ? trim($value) : $value;
                endforeach;
                unset($value);

                $candidate = $data + ($current ?? [
                    'id' => $nextId++, 'created_at' => null, 'updated_at' => null,
                    'deleted_at' => null, 'restored_at' => null,
                ]);
                $operations[$table][] = [
                    'uuid' => $uuid, 'line' => $line, 'source' => $source,
                    'data' => $data, 'current' => $current, 'candidate' => $candidate,
                ];
                $projected[$table][$uuid] = $candidate;
            endforeach;
        endforeach;

        /* Le categorie possono riferirsi a righe collocate più avanti nello stesso foglio. */
        foreach ($operations['categories'] as &$operation):
            $parentProvided = array_key_exists('parent_id', $operation['data']);
            $parentUuid = null;

            if ($parentProvided && $operation['data']['parent_id'] !== null):
                $parentId = $operation['data']['parent_id'];

                if ( ! $this->isReferenceId($parentId) || ! isset($references['categories'][(int) $parentId])):
                    $errors[] = $this->rowError('categories', $operation['line'], 'parent_id');
                    continue;
                endif;

                $parentUuid = $references['categories'][(int) $parentId];
                $operation['data']['parent_id'] = (int) $projected['categories'][$parentUuid]['id'];
            elseif ($parentProvided):
                $operation['data']['parent_id'] = null;
            endif;

            $operation['parentUuid'] = $parentUuid;
            $operation['candidate'] = $operation['data'] + $operation['candidate'];
            $projected['categories'][$operation['uuid']] = $operation['candidate'];
        endforeach;
        unset($operation);

        $sheets = [];
        $totals = ['insert' => 0, 'update' => 0, 'skip' => 0];

        foreach (Package::MODELS as $table => $class):
            $sheet = ['headers' => $workbook[$table]['headers'], 'rows' => [], 'plan' => ['insert' => 0, 'update' => 0, 'skip' => 0]];
            $invalidFields = [];

            foreach ($operations[$table] as &$operation):
                $changed = $this->changedFields($operation['data'], $operation['current']);
                $action = $operation['current'] === null ? 'insert' : ($changed === [] ? 'skip' : 'update');
                $operation['action'] = $action;
                $operation['changed'] = $changed;

                if ($action !== 'skip'):
                    $rowErrors = $this->validateEntity($table, $operation, $structures[$table]);
                    $invalidFields[$operation['line']] = array_keys($rowErrors);
                    array_push($errors, ...array_values($rowErrors));
                endif;

                $sheet['plan'][$action]++;
                $totals[$action]++;

                if ($action !== 'skip' && count($sheet['rows']) < 50):
                    $sheet['rows'][] = ['record' => ['uuid' => $operation['uuid']] + $operation['source'], 'action' => $action, 'changed' => $changed];
                endif;
            endforeach;
            unset($operation);
            $sheets[$table] = $sheet;
            array_push($errors, ...$this->validateUniqueValues($table, $operations[$table], $invalidFields));
        endforeach;

        foreach (Package::PIVOTS as $table => $fields):
            $operations[$table] = [];
            $sheet = ['headers' => $workbook[$table]['headers'], 'rows' => [], 'plan' => ['insert' => 0, 'update' => 0, 'skip' => 0]];
            $existing = [];
            $seen = [];
            $keys = array_keys($fields);

            foreach ($snapshot[$table]['rows'] as $record):
                $existing[$record[$keys[0]] . ':' . $record[$keys[1]]] = $record;
            endforeach;
            $projected[$table] = $existing;

            foreach ($workbook[$table]['rows'] as $source):
                $line = $source['_row'];
                unset($source['_row']);
                $data = $source;
                $uuids = [];

                foreach ($fields as $field => $entity):
                    $fileId = $source[$field] ?? null;

                    if ( ! $this->isReferenceId($fileId) || ! isset($references[$entity][(int) $fileId])):
                        $errors[] = $this->rowError($table, $line, $field);
                        continue 2;
                    endif;

                    $uuids[$field] = $references[$entity][(int) $fileId];
                    $data[$field] = (int) $projected[$entity][$uuids[$field]]['id'];
                endforeach;

                $key = $data[$keys[0]] . ':' . $data[$keys[1]];

                if (isset($seen[$key])):
                    $errors[] = $this->duplicateError($table, $line, implode('+', $keys));
                    continue;
                endif;

                $seen[$key] = true;
                $current = $existing[$key] ?? null;
                $candidate = $data + ($current ?? ['status' => 1]);
                $changed = $this->changedFields($data, $current);
                $action = $current === null ? 'insert' : ($changed === [] ? 'skip' : 'update');
                $rowErrors = $this->validatePivot($table, $candidate, $structures[$table], $line);
                array_push($errors, ...$rowErrors);
                $operations[$table][] = ['action' => $action, 'data' => $candidate, 'uuids' => $uuids, 'line' => $line];
                $projected[$table][$key] = $candidate;
                $sheet['plan'][$action]++;
                $totals[$action]++;

                if ($action !== 'skip' && count($sheet['rows']) < 50):
                    $sheet['rows'][] = ['record' => $source, 'action' => $action, 'changed' => $changed];
                endif;
            endforeach;
            $sheets[$table] = $sheet;
        endforeach;

        if ($errors === []):
            array_push($errors, ...$this->validateRelations($projected));
        endif;

        if ($errors !== []):
            return ['result' => false, 'validationErrors' => array_slice($errors, 0, 500)];
        endif;

        return [
            'result' => true, 'plan' => $totals, 'sheets' => $sheets,
            'operations' => $operations, 'generatedUuids' => $generatedUuids,
            'baseline' => $this->snapshotHash($snapshot),
            'schemaHash' => hash('sha256', json_encode($structures, JSON_THROW_ON_ERROR)),
        ];
    }

    /**
     * Ri-valida un blocco di operazioni già pianificate, senza scritture applicative.
     * Il cursore è conservato nello staging; il controllo globale viene ripetuto al commit.
     */
    public function validatePreparedChunk(array $planned, int $offset): array
    {
        $structures = $this->getPackageStructures();
        $position = 0;
        $processed = 0;
        $errors = [];

        foreach (Package::TABLES as $table):
            foreach ($planned['operations'][$table] as $operation):
                if ($operation['action'] === 'skip'):
                    continue;
                endif;

                if ($position++ < $offset):
                    continue;
                endif;

                $rowErrors = isset(Package::MODELS[$table])
                    ? $this->validateEntity($table, $operation, $structures[$table])
                    : $this->validatePivot($table, $operation['data'], $structures[$table], $operation['line']);
                array_push($errors, ...array_values($rowErrors));
                $processed++;

                if ($processed === Package::CHUNK_SIZE):
                    break 2;
                endif;
            endforeach;
        endforeach;

        return ['result' => $errors === [], 'processed' => $offset + $processed, 'validationErrors' => $errors];
    }

    /**
     * Riapplica schema, regole e piano sotto lock; esegue il backup e poi le sole differenze.
     */
    public function executePackagePlan(array $workbook, array $staged, callable $backup): array
    {
        if ($this->db->transDepth !== 0):
            throw new \RuntimeException('La transazione Warehouse non può essere annidata.');
        endif;

        /* I range lock impediscono nuove righe concorrenti durante backup e scrittura. */
        if ($this->db->query('set transaction isolation level repeatable read') === false || ! $this->db->transBegin()):
            throw new \RuntimeException('Impossibile avviare la transazione Warehouse.');
        endif;

        $commitAttempted = false;

        try
        {
            $snapshot = $this->readPackageSnapshot(true);

            if ( ! hash_equals($staged['baseline'], $this->snapshotHash($snapshot))):
                throw new \DomainException(lang('backend/warehouse.messages.databaseChanged'));
            endif;

            $fresh = $this->planPackage($workbook, $staged['generatedUuids'], $snapshot);

            if ( ! $fresh['result']):
                throw new \DomainException(implode("\n", $fresh['validationErrors']));
            endif;

            if ($fresh['schemaHash'] !== $staged['schemaHash'] || $fresh['plan'] !== $staged['plan']
                || ! hash_equals(
                    hash('sha256', json_encode($fresh['operations'], JSON_THROW_ON_ERROR)),
                    hash('sha256', json_encode($staged['operations'], JSON_THROW_ON_ERROR))
                )):
                throw new \DomainException(lang('backend/warehouse.messages.databaseChanged'));
            endif;

            /* Il backup è successivo a tutte le validazioni e precedente alla prima scrittura. */
            $backup($snapshot);
            $ids = [];
            $now = new \CodeIgniter\Database\RawSql('now()');

            foreach (Package::MODELS as $table => $class):
                foreach ($snapshot[$table]['rows'] as $row):
                    $ids[$table][strtolower($row['uuid'])] = (int) $row['id'];
                endforeach;

                foreach ($fresh['operations'][$table] ?? [] as $operation):
                    if ($operation['action'] === 'skip'):
                        continue;
                    endif;

                    $data = $operation['data'];
                    unset($data['parent_id']);

                    if ($operation['action'] === 'insert'):
                        $data['created_at'] = $now;
                        $this->assertQuery($this->db->table($table)->insert($data));
                        $ids[$table][$operation['uuid']] = (int) $this->db->insertID();
                    else:
                        unset($data['uuid']);
                        $data['updated_at'] = $now;
                        $this->assertQuery($this->db->table($table)->where('uuid', $operation['uuid'])->update($data));
                    endif;
                endforeach;
            endforeach;

            foreach ($fresh['operations']['categories'] ?? [] as $operation):
                if ($operation['action'] === 'skip' || ! array_key_exists('parent_id', $operation['data'])):
                    continue;
                endif;

                $parentId = $operation['parentUuid'] === null ? null : $ids['categories'][$operation['parentUuid']];
                $this->assertQuery($this->db->table('categories')->where('uuid', $operation['uuid'])->update(['parent_id' => $parentId]));
            endforeach;

            foreach (Package::PIVOTS as $table => $fields):
                foreach ($fresh['operations'][$table] ?? [] as $operation):
                    if ($operation['action'] === 'skip'):
                        continue;
                    endif;

                    $data = $operation['data'];
                    $key = [];

                    foreach ($fields as $field => $entity):
                        $data[$field] = $ids[$entity][$operation['uuids'][$field]];
                        $key[$field] = $data[$field];
                    endforeach;

                    $query = $operation['action'] === 'insert'
                        ? $this->db->table($table)->insert($data)
                        : $this->db->table($table)->where($key)->update(array_diff_key($data, $key));
                    $this->assertQuery($query);
                endforeach;
            endforeach;

            /* Controlla anche il risultato effettivo, prima di consentire il commit. */
            $final = $this->readPackageSnapshot();
            $relationErrors = $this->validateRelations(array_map(static fn(array $table): array => $table['rows'], $final));

            if ($relationErrors !== []):
                throw new \DomainException(implode("\n", $relationErrors));
            endif;

            if ( ! $this->db->transStatus()):
                throw new \RuntimeException('Stato transazione Warehouse non valido.');
            endif;

            $commitAttempted = true;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('Commit Warehouse non riuscito.');
            endif;

            return ['inserted' => $fresh['plan']['insert'], 'updated' => $fresh['plan']['update'], 'skipped' => $fresh['plan']['skip']];
        }
        catch (\Throwable $e)
        {
            $rolledBack = $this->db->transRollback();

            if ($commitAttempted || ! $rolledBack):
                throw new WarehouseRecoveryException(lang('backend/warehouse.messages.importNeedsReview'), 0, $e);
            endif;

            throw $e;
        }
    }

    /**
     * Crea un backup SQL del pacchetto sotto i lock della transazione corrente.
     */
    public function createPackageBackup(string $importId, array $snapshot): array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
            throw new \RuntimeException('ID backup Warehouse non valido.');
        endif;

        $directory = WRITEPATH . 'backups/warehouse/' . $importId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)):
            throw new \RuntimeException('Directory backup Warehouse non disponibile.');
        endif;

        $path = $directory . 'backup.sql';
        $handle = fopen($path . '.tmp', 'wb');

        if ($handle === false):
            throw new \RuntimeException('Backup Warehouse non disponibile.');
        endif;

        try
        {
            $this->writeBackupBytes($handle, "/* Warehouse package backup */\nset foreign_key_checks = 0;\n");

            foreach (array_reverse(Package::TABLES) as $table):
                $this->writeBackupBytes($handle, 'delete from ' . $this->db->protectIdentifiers($table) . ";\n");
            endforeach;

            foreach ($snapshot as $table => $data):
                $columns = array_map(fn(string $column): string => $this->db->protectIdentifiers($column), $data['headers']);

                foreach ($data['rows'] as $row):
                    $values = array_map(fn(string $column): string => $row[$column] === null ? 'null' : (string) $this->db->escape($row[$column]), $data['headers']);
                    $this->writeBackupBytes($handle, 'insert into ' . $this->db->protectIdentifiers($table) . ' (' . implode(', ', $columns) . ') values (' . implode(', ', $values) . ");\n");
                endforeach;
            endforeach;

            $this->writeBackupBytes($handle, "set foreign_key_checks = 1;\n");

            if ( ! fflush($handle)):
                throw new \RuntimeException('Scrittura backup Warehouse incompleta.');
            endif;
        }
        finally
        {
            fclose($handle);
        }

        if ( ! rename($path . '.tmp', $path)):
            throw new \RuntimeException('Pubblicazione backup Warehouse non riuscita.');
        endif;

        $hash = hash_file('sha256', $path);
        $size = filesize($path);

        if ($hash === false || $size === false):
            throw new \RuntimeException('Verifica backup Warehouse non riuscita.');
        endif;

        return ['file' => 'backup.sql', 'fileHash' => $hash, 'fileSize' => $size];
    }

    /** Recupera il provider esplicito della singola anagrafica. */
    private function provider(string $table): ImportValidationInterface
    {
        return model(Package::MODELS[$table]);
    }

    /** Combina fallback SQL e le stesse validation rules utilizzate dall'import CRUD. */
    private function validateEntity(string $table, array $operation, array $structure): array
    {
        $provider = $this->provider($table);
        $fields = array_flip($provider->importAllowedFields());
        $structure = array_values(array_filter($structure, static fn(array $field): bool => isset($fields[$field['name']])));
        $action = $operation['action'];
        $rules = $this->buildDynamicRules($structure, $action, self::IMPORT_MODE_CRUD);
        $specific = $action === 'insert'
            ? $provider->importAddValidationRules()
            : $provider->importEditValidationRules($operation['data']);
        $rules = array_replace($rules, array_intersect_key($specific, $fields));

        if ($action === 'update'):
            $rules = array_intersect_key($rules, $operation['data']);
        endif;

        $validator = \Config\Services::validation();
        $validator->reset()->setRules($rules);
        $validator->run($operation['data']);
        $errors = [];

        foreach ($validator->getErrors() as $field => $error):
            $errors[$field] = ui_message('backend/warehouse.messages.invalidCell', [$table, $operation['line'], $field], lang('backend/warehouse.messages.invalidCell')) . ' ' . $error;
        endforeach;

        if ($table === 'products' && array_key_exists('price', $operation['data'])
            && preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', (string) $operation['data']['price']) !== 1):
            $errors['price'] ??= $this->rowError($table, $operation['line'], 'price');
        endif;

        if ($errors === []):
            /* Le regole sulle relazioni dei provider CSV leggono il database corrente.
             * Il pacchetto le verifica invece sullo stato finale, incluse riattivazioni e nuove pivot. */
            if ($operation['current'] !== null && $operation['current']['deleted_at'] !== null):
                $errors[] = lang('backend/' . $table . '.messages.cannotModifyDeleted');
            endif;
        endif;

        return $errors;
    }

    /**
     * Controlla i vincoli UNIQUE tra righe del file con la collation effettiva SQL.
     * Le collisioni con record già salvati restano affidate alle regole dei provider.
     */
    private function validateUniqueValues(string $table, array $operations, array $invalidFields): array
    {
        if ($operations === []):
            return [];
        endif;

        $columns = $this->db->query('show full columns from ' . $this->db->protectIdentifiers($table))->getResultArray();
        $metadata = array_column($columns, null, 'Field');
        $allowed = array_flip($this->provider($table)->importAllowedFields());
        $errors = [];
        $indexes = $this->db->getIndexData($table);

        /* Le regole di dominio valgono anche prima dell'applicazione dei nuovi indici SQL. */
        foreach ($this->provider($table)->importAddValidationRules() as $field => $definition):
            $rules = $definition['rules'] ?? [];
            $rules = is_array($rules) ? $rules : explode('|', $rules);

            if (in_array('is_unique[' . $table . '.' . $field . ']', $rules, true)):
                $indexes['import_' . $field] = (object) ['type' => 'UNIQUE', 'fields' => [$field]];
            endif;
        endforeach;

        $checkedIndexes = [];

        foreach ($indexes as $index):
            if (strtoupper($index->type) !== 'UNIQUE' || array_diff($index->fields, array_keys($allowed)) !== []
                || $index->fields === ['uuid']):
                continue;
            endif;

            $indexKey = implode('|', $index->fields);

            if (isset($checkedIndexes[$indexKey])):
                continue;
            endif;

            $checkedIndexes[$indexKey] = true;
            $seen = [];

            foreach (array_chunk($operations, 100) as $chunk):
                $expressions = [];
                $bindings = [];
                $values = [];

                foreach ($chunk as $position => $operation):
                    $values[$position] = [];

                    foreach ($index->fields as $field):
                        $value = $operation['candidate'][$field] ?? null;

                        if ($value === null):
                            unset($values[$position]);
                            continue 2;
                        endif;

                        $alias = 'v' . count($expressions);
                        $collation = $metadata[$field]['Collation'] ?? null;

                        if ($collation !== null):
                            if (preg_match('/^[a-z0-9_]+$/i', $collation) !== 1):
                                throw new \RuntimeException('Collation Warehouse non valida.');
                            endif;

                            $charset = explode('_', $collation, 2)[0];
                            $expression = 'convert(? using ' . $charset . ')';
                            $expression = str_contains($collation, '_nopad_') ? $expression : 'rtrim(' . $expression . ')';
                            $expressions[] = 'hex(weight_string(' . $expression . ' collate ' . $collation . ')) as ' . $alias;
                        else:
                            $expressions[] = '? as ' . $alias;
                        endif;

                        $bindings[] = (string) $value;
                        $values[$position][] = $alias;
                    endforeach;
                endforeach;

                if ($expressions === []):
                    continue;
                endif;

                $query = $this->db->query('select ' . implode(', ', $expressions), $bindings);

                if ($query === false):
                    throw new \RuntimeException('Verifica univocità Warehouse non riuscita.');
                endif;

                $weights = $query->getRowArray();

                foreach ($values as $position => $aliases):
                    $key = json_encode(array_map(static fn(string $alias): string => (string) $weights[$alias], $aliases), JSON_THROW_ON_ERROR);

                    if (isset($seen[$key])):
                        /* Un campo già respinto dal provider non deve produrre un secondo errore. */
                        if (array_intersect($index->fields, $invalidFields[$chunk[$position]['line']] ?? []) === []):
                            $errors[] = $this->duplicateError($table, $chunk[$position]['line'], implode('+', $index->fields));
                        endif;
                    endif;
                    $seen[$key] = true;
                endforeach;
            endforeach;
        endforeach;

        return $errors;
    }

    /** Applica le regole SQL e lo stato 0/1 alle tabelle a chiave composta. */
    private function validatePivot(string $table, array $data, array $structure, int $line): array
    {
        $rules = $this->buildDynamicRules($structure, 'insert', self::IMPORT_MODE_DATABASE);

        foreach (array_keys(Package::PIVOTS[$table]) as $field):
            $rules[$field] = 'required|is_natural_no_zero|less_than_equal_to[4294967295]';
        endforeach;
        $rules['status'] = 'required|in_list[0,1]';
        $validator = \Config\Services::validation();
        $validator->reset()->setRules($rules);
        $validator->run($data);

        return array_map(fn(string $error): string => $table . ' — ' . $line . ': ' . $error, array_values($validator->getErrors()));
    }

    /** Verifica le differenze normalizzando valori SQL e celle Excel equivalenti. */
    private function changedFields(array $data, ?array $current): array
    {
        if ($current === null):
            return array_keys($data);
        endif;

        $changed = [];

        foreach ($data as $field => $value):
            /* L'UUID identifica il record già risolto e non è un campo modificabile. */
            if ($field === 'uuid'):
                continue;
            endif;

            $old = $current[$field] ?? null;
            $same = (string) $old === (string) $value;

            if ($field === 'price' && is_numeric($old) && is_scalar($value) && preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', (string) $value) === 1):
                $same = number_format((float) $old, 2, '.', '') === number_format((float) $value, 2, '.', '');
            endif;

            if ( ! $same):
                $changed[] = $field;
            endif;
        endforeach;

        return $changed;
    }

    /** Controlla gerarchia e associazioni sullo stato finale, inclusi i record conservati. */
    private function validateRelations(array $projected): array
    {
        $categories = array_column($projected['categories'], null, 'id');
        $brands = array_column($projected['brands'], null, 'id');
        $products = array_column($projected['products'], null, 'id');
        $parents = [];
        $hasChildren = [];
        $hasBrand = [];
        $hasCategory = [];
        $errors = [];

        foreach ($categories as $id => $category):
            $parentId = $category['parent_id'] ?? null;
            $parents[$id] = $parentId === null ? null : (int) $parentId;

            if ($parentId !== null):
                $hasChildren[(int) $parentId] = true;

                if ( ! isset($categories[$parentId]) || (int) $parentId === (int) $id || $categories[$parentId]['deleted_at'] !== null):
                    $errors[] = ui_message('backend/warehouse.messages.invalidParent', [$id], lang('backend/warehouse.messages.invalidParent'));
                elseif ((int) $categories[$parentId]['status'] !== 1):
                    $errors[] = ui_message('backend/warehouse.messages.suspendedParent', [$id], lang('backend/warehouse.messages.suspendedParent'));
                endif;
            endif;
        endforeach;

        $resolved = [];

        foreach ($parents as $id => $parentId):
            $visited = [];
            $current = $id;

            while (($parents[$current] ?? null) !== null && ! isset($resolved[$current])):
                if (isset($visited[$current])):
                    $errors[] = lang('backend/warehouse.messages.categoryCycle');
                    break 2;
                endif;

                $visited[$current] = true;
                $current = $parents[$current];
            endwhile;

            $resolved += $visited;
        endforeach;

        foreach ($projected['brands_to_products'] as $row):
            if ( ! isset($brands[$row['brand_id']], $products[$row['product_id']]) || $brands[$row['brand_id']]['deleted_at'] !== null):
                $errors[] = ui_message('backend/warehouse.messages.invalidRelation', ['brands_to_products', 0], lang('backend/warehouse.messages.invalidRelation'));
            elseif ((int) $brands[$row['brand_id']]['status'] !== 1):
                $errors[] = ui_message('backend/warehouse.messages.suspendedBrand', [$row['brand_id']], lang('backend/warehouse.messages.suspendedBrand'));
            endif;

            if ((int) $row['status'] === 1):
                $hasBrand[$row['product_id']] = true;
            endif;
        endforeach;

        foreach ($projected['categories_to_products'] as $row):
            if ( ! isset($categories[$row['category_id']], $products[$row['product_id']]) || $categories[$row['category_id']]['deleted_at'] !== null):
                $errors[] = ui_message('backend/warehouse.messages.invalidRelation', ['categories_to_products', 0], lang('backend/warehouse.messages.invalidRelation'));
            elseif ((int) $categories[$row['category_id']]['status'] !== 1):
                $errors[] = ui_message('backend/warehouse.messages.suspendedCategory', [$row['category_id']], lang('backend/warehouse.messages.suspendedCategory'));
            endif;

            if (isset($hasChildren[$row['category_id']])):
                $errors[] = ui_message('backend/warehouse.messages.mixedCategoryContents', [$row['category_id']], lang('backend/warehouse.messages.mixedCategoryContents'));
            endif;

            if ((int) $row['status'] === 1):
                $hasCategory[$row['product_id']] = true;
            endif;
        endforeach;

        foreach ($products as $id => $product):
            if ( ! isset($hasBrand[$id], $hasCategory[$id])):
                $errors[] = ui_message('backend/warehouse.messages.missingProductRelations', [$product['sku'] ?? $id], lang('backend/warehouse.messages.missingProductRelations'));
            endif;
        endforeach;

        return array_values(array_unique($errors));
    }

    /** Valida gli ID locali al workbook senza conversioni permissive. */
    private function isReferenceId(mixed $value): bool
    {
        return ! is_bool($value) && filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0 && (int) $value <= 4294967295;
    }

    /** Identifica il campo che contiene il valore non valido. */
    private function rowError(string $table, int $line, string $field): string
    {
        return ui_message('backend/warehouse.messages.invalidCell', [$table, $line, $field], lang('backend/warehouse.messages.invalidCell'));
    }

    /** Identifica un duplicato all'interno del singolo foglio. */
    private function duplicateError(string $table, int $line, string $field): string
    {
        return ui_message('backend/warehouse.messages.duplicateValue', [$table, $line, $field], lang('backend/warehouse.messages.duplicateValue'));
    }

    /** Converte ogni falso del Query Builder in un fallimento della transazione. */
    private function assertQuery(mixed $result): void
    {
        if ($result === false):
            throw new \RuntimeException('Scrittura Warehouse non riuscita.');
        endif;
    }

    /** Scrive integralmente un blocco del backup, anche in caso di write parziali. */
    private function writeBackupBytes($handle, string $bytes): void
    {
        while ($bytes !== ''):
            $written = fwrite($handle, $bytes);

            if ($written === false || $written === 0):
                throw new \RuntimeException('Scrittura backup Warehouse non riuscita.');
            endif;

            $bytes = substr($bytes, $written);
        endwhile;
    }
}
