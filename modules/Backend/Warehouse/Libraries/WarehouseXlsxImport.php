<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Modules\Backend\Warehouse\Config\Package;
use Modules\Backend\Warehouse\Models\WarehouseImportModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

/**
 * Gestisce il trasporto XLSX e lo staging privato del pacchetto Warehouse.
 * La logica di schema, validazione, piano e scrittura appartiene al model dedicato.
 */
class WarehouseXlsxImport
{
    private const VERSION = 3;
    private const DIRECTORY = 'uploads/warehouse/';
    private const TTL = 3600;
    private const MAX_ROWS = 50000;
    private const MAX_CELLS = 100000;
    private const MAX_UNCOMPRESSED_BYTES = 104857600;

    /** Associa il trasporto XLSX al modello che applica il contratto Import. */
    public function __construct(private readonly WarehouseImportModel $model)
    {
    }

    /**
     * Valida l'intero file e restituisce lo stesso piano insert/update/skip del componente Import.
     * Non crea backup e non scrive dati applicativi durante questa fase.
     */
    public function prepare(UploadedFile $file): array
    {
        if ($this->owner() === null):
            throw new \DomainException(lang('backend/warehouse.messages.sessionUnavailable'));
        endif;

        $this->cleanupExpiredImports();
        $importId = bin2hex(random_bytes(32));
        $directory = $this->directory($importId);

        if ( ! mkdir($directory, 0750, true) && ! is_dir($directory)):
            throw new \RuntimeException('Staging Warehouse non disponibile.');
        endif;

        try
        {
            $file->move($directory, 'package.xlsx', true);
            $source = $this->readWorkbook($directory . 'package.xlsx');

            if ($source['errors'] !== []):
                $this->removeStaging($directory);
                return ['result' => false, 'validationErrors' => array_slice($source['errors'], 0, 500)];
            endif;

            $planned = $this->model->planPackage($source['workbook']);

            if ( ! $planned['result']):
                $this->removeStaging($directory);
                return $planned;
            endif;

            $preview = ['result' => true, 'importId' => $importId, 'plan' => $planned['plan'], 'sheets' => $planned['sheets']];

            if (($planned['plan']['insert'] + $planned['plan']['update'] + $planned['plan']['skip']) === 0):
                $this->removeStaging($directory);
                throw new \DomainException(lang('backend/warehouse.messages.emptyFile'));
            endif;

            if ($planned['plan']['insert'] === 0 && $planned['plan']['update'] === 0):
                $this->removeStaging($directory);
                $preview['importId'] = null;
                return $preview;
            endif;

            $this->writeJson($directory . 'plan.json', $planned);
            $manifest = [
                'version' => self::VERSION, 'importId' => $importId, 'owner' => $this->owner(),
                'status' => 'staged', 'mode' => 'merge', 'createdAt' => time(), 'updatedAt' => time(),
                'expiresAt' => time() + self::TTL,
                'fileHash' => $this->fileHash($directory . 'package.xlsx'),
                'planHash' => $this->fileHash($directory . 'plan.json'),
                'plan' => $planned['plan'], 'backup' => null, 'failure' => null,
                'preflight' => 0,
            ];
            $this->writeJson($directory . 'manifest.json', $manifest);

            return $preview;
        }
        catch (\Throwable $e)
        {
            if ( ! is_file($directory . 'manifest.json')):
                $this->removeStaging($directory);
            endif;
            throw $e;
        }
    }

    /**
     * Riesegue tutte le validazioni sotto lock, crea il backup e applica una sola transazione.
     * Il piano e gli UUID generati restano server-side e non vengono accettati dal browser.
     */
    public function execute(string $importId): array
    {
        $lock = $this->acquireLock();

        try
        {
            $manifest = $this->readManifest($importId, true);
            $directory = $this->directory($importId);

            if ($manifest['status'] === 'completed'):
                return $manifest['result'] + ['alreadyCompleted' => true, 'isFinished' => true];
            endif;

            if ($manifest['status'] === 'processing' || ($manifest['failure']['recoveryRequired'] ?? false)):
                throw new WarehouseRecoveryException(lang('backend/warehouse.messages.importNeedsReview'));
            endif;

            if ($manifest['status'] !== 'staged'):
                throw new \DomainException(lang('backend/warehouse.messages.importNotReady'));
            endif;

            $planned = $this->readPlan($importId, $manifest);
            $total = $planned['plan']['insert'] + $planned['plan']['update'];
            $offset = $manifest['preflight'];

            if ($offset < $total):
                $chunk = $this->model->validatePreparedChunk($planned, $offset);

                if ( ! $chunk['result']):
                    return ['result' => false, 'validationErrors' => $chunk['validationErrors']];
                endif;

                if ($chunk['processed'] <= $offset || $chunk['processed'] > $total):
                    throw new \RuntimeException('Cursore di verifica Warehouse non valido.');
                endif;

                $manifest['preflight'] = $chunk['processed'];
                $manifest['updatedAt'] = time();
                $manifest['expiresAt'] = time() + self::TTL;
                $this->writeJson($directory . 'manifest.json', $manifest);

                return [
                    'result' => true, 'isFinished' => false, 'processed' => $chunk['processed'],
                    'progressMessage' => $chunk['processed'] === $total
                        ? sprintf(lang('backend/warehouse.messages.importReady'), $chunk['processed'], $total)
                        : sprintf(lang('backend/warehouse.messages.importProgress'), $chunk['processed'], $total),
                ];
            endif;

            $source = $this->readWorkbook($directory . 'package.xlsx');

            if ($source['errors'] !== []):
                throw new \DomainException(implode("\n", $source['errors']));
            endif;

            /* Persistito prima di ogni operazione che può arrivare al commit. */
            $manifest['status'] = 'processing';
            $this->writeJson($directory . 'manifest.json', $manifest);

            try
            {
                $result = $this->model->executePackagePlan($source['workbook'], $planned, function(array $snapshot) use ($importId, $directory, &$manifest): void {
                    $manifest['backup'] = $this->model->createPackageBackup($importId, $snapshot);
                    $this->writeJson($directory . 'manifest.json', $manifest);
                    $this->assertBackup($importId, $manifest['backup']);
                });
            }
            catch (\Throwable $e)
            {
                $manifest['status'] = 'failed';
                $manifest['failure'] = ['reason' => $e->getMessage(), 'recoveryRequired' => $e instanceof WarehouseRecoveryException];

                try
                {
                    $this->writeJson($directory . 'manifest.json', $manifest);
                }
                catch (\Throwable $manifestError)
                {
                    log_message('critical', 'Stato import Warehouse non persistito: ' . $importId . ' — ' . $manifestError->getMessage());
                    throw new WarehouseRecoveryException(lang('backend/warehouse.messages.importNeedsReview'), 0, $e);
                }
                throw $e;
            }

            $manifest['status'] = 'completed';
            $manifest['result'] = $result;
            $manifest['completedAt'] = time();
            $manifest['expiresAt'] = time() + self::TTL;

            try
            {
                $this->writeJson($directory . 'manifest.json', $manifest);
            }
            catch (\Throwable $e)
            {
                /* Il commit è già avvenuto: nessun retry cieco può essere consentito. */
                log_message('critical', 'Commit Warehouse senza aggiornamento manifest: ' . $importId);
                throw new WarehouseRecoveryException(lang('backend/warehouse.messages.importNeedsReview'), 0, $e);
            }

            return $result + ['result' => true, 'alreadyCompleted' => false, 'isFinished' => true];
        }
        finally
        {
            $this->releaseLock($lock);
        }
    }

    /** Recupera una preview persistita oppure lo stato da verificare di un import interrotto. */
    public function getImportStatus(string $importId): ?array
    {
        try
        {
            $manifest = $this->readManifest($importId);
            $result = ['importId' => $importId, 'status' => $manifest['status'], 'plan' => $manifest['plan']];

            if ($manifest['status'] === 'staged'):
                $planned = $this->readPlan($importId, $manifest);
                $result['sheets'] = $planned['sheets'];
            endif;

            return $result;
        }
        catch (\Throwable)
        {
            return null;
        }
    }

    /** Annulla soltanto staging della sessione corrente che non richiedono recovery. */
    public function deleteImport(string $importId): bool
    {
        $lock = $this->acquireLock();

        try
        {
            $manifest = $this->readManifest($importId, false, true);

            if ( ! in_array($manifest['status'], ['staged', 'completed'], true)):
                return false;
            endif;

            $this->removeStaging($this->directory($importId));
            return ! is_dir($this->directory($importId));
        }
        finally
        {
            $this->releaseLock($lock);
        }
    }

    /**
     * Legge i cinque fogli senza formule e consente colonne parziali in qualunque ordine.
     * I nomi devono appartenere contemporaneamente allo schema reale e alla whitelist del dominio.
     */
    private function readWorkbook(string $path): array
    {
        $this->validateArchive($path);
        $structures = $this->model->getPackageImportStructures();
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $cells = 0;
        $worksheetInfo = $reader->listWorksheetInfo($path);
        $names = array_column($worksheetInfo, 'worksheetName');
        $expected = Package::TABLES;
        sort($names);
        sort($expected);

        if ($names !== $expected):
            throw new \DomainException(lang('backend/warehouse.messages.invalidSheets'));
        endif;

        foreach ($worksheetInfo as $info):
            $cells += $info['totalRows'] * $info['totalColumns'];

            if ($info['totalColumns'] > count($structures[$info['worksheetName']])):
                throw new \DomainException(sprintf(lang('backend/warehouse.messages.invalidHeaders'), $info['worksheetName']));
            endif;

            if ($info['totalRows'] > self::MAX_ROWS + 1 || $cells > self::MAX_CELLS):
                throw new \DomainException(lang('backend/warehouse.messages.xlsxTooLarge'));
            endif;
        endforeach;

        $spreadsheet = $reader->load($path);

        try
        {
            $workbook = [];
            $errors = [];

            foreach (Package::TABLES as $table):
                $sheet = $spreadsheet->getSheetByName($table);
                $headers = [];
                $columnCount = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

                for ($column = 1; $column <= $columnCount; $column++):
                    $value = $sheet->getCell([$column, 1])->getValue();
                    $headers[] = is_string($value) ? trim($value) : '';
                endfor;

                $schemaColumns = array_column($structures[$table], 'name');
                $allowed = isset(Package::MODELS[$table])
                    ? array_merge(model(Package::MODELS[$table])->importAllowedFields(), Package::METADATA)
                    : $schemaColumns;
                $required = isset(Package::MODELS[$table]) ? ['uuid'] : array_keys(Package::PIVOTS[$table]);

                if (count($headers) < 2 || count($headers) !== count(array_unique($headers)) || in_array('', $headers, true)
                    || array_diff($headers, $schemaColumns) !== [] || array_diff($headers, $allowed) !== [] || array_diff($required, $headers) !== []):
                    $errors[] = sprintf(lang('backend/warehouse.messages.invalidHeaders'), $table);
                    continue;
                endif;

                $rows = [];

                for ($line = 2; $line <= $sheet->getHighestDataRow(); $line++):
                    $row = [];
                    $notEmpty = false;

                    foreach ($headers as $index => $field):
                        $cell = $sheet->getCell([$index + 1, $line]);

                        if ($cell->getDataType() === DataType::TYPE_FORMULA):
                            $errors[] = sprintf(lang('backend/warehouse.messages.formulasNotAllowed'), $table, $line);
                        endif;

                        $value = $cell->getValue();

                        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText):
                            $value = $value->getPlainText();
                        endif;

                        if ((! is_scalar($value) && $value !== null) || is_bool($value) || $cell->getDataType() === DataType::TYPE_ERROR):
                            $errors[] = sprintf(lang('backend/warehouse.messages.invalidCell'), $table, $line, $field);
                            $value = null;
                        endif;

                        $value = is_string($value) ? trim($value) : $value;
                        $row[$field] = $value === '' || (is_string($value) && strtolower($value) === 'null') ? null : $value;
                        $notEmpty = $notEmpty || $row[$field] !== null;
                    endforeach;

                    if ($notEmpty):
                        $rows[] = $row + ['_row' => $line];
                    endif;
                endfor;

                $workbook[$table] = ['headers' => $headers, 'rows' => $rows];
            endforeach;

            return ['workbook' => $workbook, 'errors' => $errors];
        }
        finally
        {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /** Verifica formato ZIP/XLSX e limita la dimensione decompattata prima del parser XML. */
    private function validateArchive(string $path): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true):
            throw new \DomainException(lang('backend/warehouse.messages.invalidXlsx'));
        endif;

        try
        {
            if ($zip->numFiles > 1000 || $zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false):
                throw new \DomainException(lang('backend/warehouse.messages.invalidXlsx'));
            endif;

            $size = 0;

            for ($index = 0; $index < $zip->numFiles; $index++):
                $entry = $zip->statIndex($index);

                if ($entry === false || $entry['encryption_method'] !== 0):
                    throw new \DomainException(lang('backend/warehouse.messages.invalidXlsx'));
                endif;

                $size += $entry['size'];

                if ($size > self::MAX_UNCOMPRESSED_BYTES):
                    throw new \DomainException(lang('backend/warehouse.messages.xlsxTooLarge'));
                endif;
            endfor;
        }
        finally
        {
            $zip->close();
        }
    }

    /** Legge il manifest verificando proprietà, versione, scadenza e integrità del file originale. */
    private function readManifest(string $importId, bool $verifyFile = false, bool $allowExpired = false): array
    {
        $directory = $this->directory($importId);
        $path = $directory . 'manifest.json';

        if ( ! is_file($path)):
            throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
        endif;

        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $owner = $this->owner();

        if ( ! is_array($manifest) || ($manifest['version'] ?? null) !== self::VERSION || ($manifest['importId'] ?? null) !== $importId
            || ! is_string($manifest['owner'] ?? null) || $owner === null || ! hash_equals($manifest['owner'], $owner)
            || ! is_int($manifest['expiresAt'] ?? null)
            || (! $allowExpired && $manifest['expiresAt'] < time() && in_array($manifest['status'] ?? null, ['staged', 'completed'], true))
            || ! in_array($manifest['status'] ?? null, ['staged', 'processing', 'failed', 'completed'], true)
            || ($manifest['mode'] ?? null) !== 'merge'
            || ! is_string($manifest['fileHash'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $manifest['fileHash']) !== 1
            || ! is_string($manifest['planHash'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $manifest['planHash']) !== 1
            || ! is_array($manifest['plan'] ?? null)):
            throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
        endif;

        foreach (['insert', 'update', 'skip'] as $counter):
            if ( ! is_int($manifest['plan'][$counter] ?? null) || $manifest['plan'][$counter] < 0):
                throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
            endif;
        endforeach;

        if ( ! is_int($manifest['preflight'] ?? null) || $manifest['preflight'] < 0
            || $manifest['preflight'] > $manifest['plan']['insert'] + $manifest['plan']['update']):
            throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
        endif;

        if ($manifest['status'] === 'completed'):
            foreach (['inserted' => 'insert', 'updated' => 'update', 'skipped' => 'skip'] as $resultKey => $planKey):
                if (($manifest['result'][$resultKey] ?? null) !== $manifest['plan'][$planKey]):
                    throw new WarehouseRecoveryException(lang('backend/warehouse.messages.importNeedsReview'));
                endif;
            endforeach;
        endif;

        if ($verifyFile && ! hash_equals($manifest['fileHash'], $this->fileHash($directory . 'package.xlsx'))):
            throw new \DomainException(lang('backend/warehouse.messages.fileChanged'));
        endif;

        return $manifest;
    }

    /** Verifica che il piano sia quello prodotto dalla preparazione e non sia stato alterato. */
    private function readPlan(string $importId, array $manifest): array
    {
        $path = $this->directory($importId) . 'plan.json';

        if ( ! hash_equals((string) ($manifest['planHash'] ?? ''), $this->fileHash($path))):
            throw new \DomainException(lang('backend/warehouse.messages.fileChanged'));
        endif;

        $plan = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if ( ! is_array($plan) || ($plan['plan'] ?? null) !== $manifest['plan'] || ! is_string($plan['baseline'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $plan['baseline']) !== 1
            || ! is_string($plan['schemaHash'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $plan['schemaHash']) !== 1
            || ! is_array($plan['operations'] ?? null) || array_keys($plan['operations']) !== Package::TABLES
            || ! is_array($plan['generatedUuids'] ?? null) || ! is_array($plan['sheets'] ?? null)):
            throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
        endif;

        return $plan;
    }

    /** Impedisce qualsiasi scrittura applicativa se il backup appena creato è incompleto. */
    private function assertBackup(string $importId, array $backup): void
    {
        $path = WRITEPATH . 'backups/warehouse/' . $importId . '/backup.sql';

        if (($backup['file'] ?? null) !== 'backup.sql' || ! is_file($path)
            || filesize($path) !== ($backup['fileSize'] ?? null)
            || ! hash_equals((string) ($backup['fileHash'] ?? ''), $this->fileHash($path))):
            throw new \RuntimeException(lang('backend/components/import.messages.backupError'));
        endif;
    }

    /** Pubblica gli artefatti JSON solo dopo una scrittura integrale. */
    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $temporary = $path . '.tmp';

        if (file_put_contents($temporary, $json, LOCK_EX) !== strlen($json)):
            throw new \RuntimeException('Scrittura staging Warehouse incompleta.');
        endif;

        if ( ! @rename($temporary, $path)):
            /* Come nel componente Import, Windows può richiedere il fallback sotto lock. */
            if (file_put_contents($path, $json, LOCK_EX) !== strlen($json)):
                throw new \RuntimeException('Pubblicazione staging Warehouse non riuscita.');
            endif;
            unlink($temporary);
        endif;
    }

    /** Calcola l'hash di un artefatto esistente senza accettare percorsi dal browser. */
    private function fileHash(string $path): string
    {
        $hash = is_file($path) ? hash_file('sha256', $path) : false;

        if ($hash === false):
            throw new \DomainException(lang('backend/warehouse.messages.fileChanged'));
        endif;

        return $hash;
    }

    /** Serializza l'esecuzione dei pacchetti Warehouse sul server. */
    private function acquireLock()
    {
        $root = WRITEPATH . self::DIRECTORY;

        if ( ! is_dir($root) && ! mkdir($root, 0750, true) && ! is_dir($root)):
            throw new \RuntimeException('Staging Warehouse non disponibile.');
        endif;

        $lock = fopen($root . 'import.lock', 'c+');

        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)):
            if (is_resource($lock)):
                fclose($lock);
            endif;
            throw new \DomainException(lang('backend/warehouse.messages.importBusy'));
        endif;

        return $lock;
    }

    /** Rilascia il lock anche dopo un errore di lettura, validazione o scrittura. */
    private function releaseLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /** Risolve esclusivamente identificatori crittografici generati dal server. */
    private function directory(string $importId): string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
            throw new \DomainException(lang('backend/warehouse.messages.invalidImportId'));
        endif;

        return WRITEPATH . self::DIRECTORY . $importId . DIRECTORY_SEPARATOR;
    }

    /** Vincola gli artefatti alla sessione amministrativa senza conservarne il token. */
    private function owner(): ?string
    {
        $token = session()->get('backendSession');
        return is_string($token) && $token !== '' ? hash('sha256', $token) : null;
    }

    /** Cancella solo gli artefatti noti di uno staging già autorizzato. */
    private function removeStaging(string $directory): void
    {
        foreach (['package.xlsx', 'plan.json', 'plan.json.tmp', 'manifest.json', 'manifest.json.tmp'] as $file):
            if (is_file($directory . $file)):
                unlink($directory . $file);
            endif;
        endforeach;

        if (is_dir($directory)):
            rmdir($directory);
        endif;
    }

    /** Rimuove gli staging scaduti sicuri; conserva errori, recovery e relativi backup. */
    private function cleanupExpiredImports(): void
    {
        $root = WRITEPATH . self::DIRECTORY;

        if ( ! is_dir($root)):
            return;
        endif;

        $lock = $this->acquireLock();

        try
        {
            foreach (glob($root . '*', GLOB_ONLYDIR) ?: [] as $directory):
                $id = basename($directory);

                if (preg_match('/^[a-f0-9]{64}$/', $id) !== 1 || ! is_file($directory . '/manifest.json')):
                    continue;
                endif;

                $manifest = json_decode((string) file_get_contents($directory . '/manifest.json'), true);

                if (is_array($manifest) && ($manifest['version'] ?? null) === self::VERSION
                    && is_int($manifest['expiresAt'] ?? null) && $manifest['expiresAt'] < time()
                    && in_array($manifest['status'] ?? null, ['staged', 'completed'], true)):
                    $this->removeStaging($this->directory($id));
                endif;
            endforeach;
        }
        finally
        {
            $this->releaseLock($lock);
        }
    }
}
