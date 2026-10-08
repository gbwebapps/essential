<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Interfaces\ImportValidationInterface;
use App\Models\Backend\BackendModel;

/**
 * Modello dedicato al Componente Globale di Importazione Dati (ImportModel).
 * 
 * Estende il BackendModel e orchestra l'intero ciclo di vita dell'importazione massiva da file CSV. 
 * Implementa un approccio a due fasi (Parsing/Staging e Esecuzione asincrona) per gestire 
 * file di grandi dimensioni senza bloccare il server. Integra funzionalità avanzate come 
 * la validazione dinamica dedotta dallo schema SQL, il calcolo differenziale per gli aggiornamenti (Upsert) 
 * e la creazione di backup di sicurezza preventivi.
 */
class ImportModel extends BackendModel 
{
    public const IMPORT_MODE_CRUD = 'crud';
    public const IMPORT_MODE_DATABASE = 'database';

    private const STAGING_VERSION = 1;
    private const STAGING_TTL = 3600;
    private const STAGING_FILENAME = 'data.csv';
    private const STAGING_MANIFEST = 'manifest.json';
    private const STAGING_LOCK = 'import.lock';
    private const STAGING_RECOVERY_FLAG = 'recovery.required';
    private const STAGING_CHUNK_JOURNAL = 'chunk.pending';
    private const BACKUP_FILENAME = 'backup.sql';
    private const IMPORT_CHUNK_SIZE = 5;
    private const BACKUP_CHUNK_SIZE = 1000;
    private const BACKUP_MAX_STATEMENT_BYTES = 1048576;
    private const BACKUP_RETENTION = 2592000; // 30 giorni

    private const STATUS_STAGED = 'staged';
    private const STATUS_READY = 'ready';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_FAILED = 'failed';
    private const STATUS_COMPLETED = 'completed';

    /**
     * Recupera il model dell'entità solo quando espone regole di validazione specifiche per l'importazione.
     *
     * Il provider è richiesto solo dal flusso CRUD, che opera in opt-in e fail-closed.
     * Tools > Database non usa model di dominio e resta basato esclusivamente sullo schema SQL.
     *
     * @param string $entity Il nome della tabella/modulo (es. 'admins', 'logs')
     * @return BackendModel|null Il model dell'entità, se disponibile
     */
    private function getTargetModelInstance(string $entity): ?BackendModel
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $entity) !== 1):
            return null;
        endif;

        $modelClass = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $entity)));
        $modelName = 'App\\Models\\Backend\\' . $modelClass . 'Model';

        if ( ! class_exists($modelName)):
            return null;
        endif;

        try {
            $model = new $modelName();
        } catch (\Throwable $e) {
            log_message('error', 'ImportModel: unable to instantiate ' . $modelName . ': ' . $e->getMessage());
            return null;
        }

        return $model instanceof BackendModel ? $model : null;
    }

    /**
     * Restituisce il provider import-specifico solo per i model che aderiscono esplicitamente al contratto.
     */
    private function getImportValidationProvider(string $entity): ?ImportValidationInterface
    {
        $model = $this->getTargetModelInstance($entity);

        return $model instanceof ImportValidationInterface ? $model : null;
    }

    /**
     * Rimuove opportunisticamente gli staging scaduti che non richiedono recovery.
     *
     * I fallimenti espliciti e gli import con flag/journal di recovery vengono conservati per consentire
     * diagnosi e ripristino manuale. Il cleanup usa lo stesso lock dell'esecuzione per non interferire
     * con chunk attivi.
     */
    private function cleanupExpiredStagingImports(): void
    {
        $rootDirectory = WRITEPATH . 'uploads/staging/';

        if ( ! is_dir($rootDirectory)):
            return;
        endif;

        $directories = glob($rootDirectory . '*', GLOB_ONLYDIR);

        if ($directories === false):
            return;
        endif;

        foreach ($directories as $directory):
            $importId = basename($directory);

            if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
                continue;
            endif;

            if (is_file($directory . DIRECTORY_SEPARATOR . self::STAGING_RECOVERY_FLAG)
                || is_file($directory . DIRECTORY_SEPARATOR . self::STAGING_CHUNK_JOURNAL)):
                continue;
            endif;

            $manifestPath = $directory . DIRECTORY_SEPARATOR . self::STAGING_MANIFEST;
            $expired = false;
            $status = null;
            $recoveryRequired = false;

            if (is_file($manifestPath)):
                $manifestJson = file_get_contents($manifestPath);

                if ($manifestJson !== false):
                    try {
                        $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
                        $expired = is_int($manifest['expiresAt'] ?? null) && $manifest['expiresAt'] < time();
                        $status = $manifest['status'] ?? null;
                        $recoveryRequired = (bool) ($manifest['failure']['recoveryRequired'] ?? false);
                    } catch (\JsonException) {
                        $modifiedAt = filemtime($manifestPath);
                        $expired = $modifiedAt !== false && $modifiedAt < (time() - self::STAGING_TTL);
                    }
                endif;
            else:
                $modifiedAt = filemtime($directory);
                $expired = $modifiedAt !== false && $modifiedAt < (time() - self::STAGING_TTL);
            endif;

            if ( ! $expired || ($status === self::STATUS_FAILED && $recoveryRequired)):
                continue;
            endif;

            $lockHandle = $this->acquireImportLock($importId);

            if ($lockHandle === null):
                continue;
            endif;

            try {
                foreach ([self::STAGING_FILENAME, self::STAGING_MANIFEST, self::STAGING_RECOVERY_FLAG, self::STAGING_CHUNK_JOURNAL] as $filename):
                    $path = $directory . DIRECTORY_SEPARATOR . $filename;
                    if (is_file($path)):
                        @unlink($path);
                    endif;
                endforeach;
            } finally {
                $this->releaseImportLock($lockHandle);
            }

            @unlink($directory . DIRECTORY_SEPARATOR . self::STAGING_LOCK);
            @rmdir($directory);
        endforeach;
    }

    /**
     * Rimuove i backup orfani più vecchi della retention configurata.
     *
     * Finché esiste uno staging omonimo il backup viene conservato: un import in recovery non deve
     * mai perdere il proprio snapshot. Vengono rimossi soltanto artefatti senza staging associato.
     */
    private function cleanupExpiredImportBackups(): void
    {
        $rootDirectory = WRITEPATH . 'backups/imports/';

        if ( ! is_dir($rootDirectory)):
            return;
        endif;

        $directories = glob($rootDirectory . '*', GLOB_ONLYDIR);

        if ($directories === false):
            return;
        endif;

        $stagingRoot = WRITEPATH . 'uploads/staging/';
        $threshold = time() - self::BACKUP_RETENTION;

        foreach ($directories as $directory):
            $importId = basename($directory);

            if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
                continue;
            endif;

            /* Uno staging ancora presente può essere attivo, completato ma consultabile o in recovery. */
            if (is_dir($stagingRoot . $importId)):
                continue;
            endif;

            $backupPath = $directory . DIRECTORY_SEPARATOR . self::BACKUP_FILENAME;
            $referencePath = is_file($backupPath) ? $backupPath : $directory;
            $modifiedAt = filemtime($referencePath);

            if ($modifiedAt === false || $modifiedAt >= $threshold):
                continue;
            endif;

            foreach ([self::BACKUP_FILENAME, self::BACKUP_FILENAME . '.tmp'] as $filename):
                $path = $directory . DIRECTORY_SEPARATOR . $filename;
                if (is_file($path)):
                    @unlink($path);
                endif;
            endforeach;

            @rmdir($directory);
        endforeach;
    }

    /**
     * Crea un'identità crittograficamente casuale per una nuova importazione e la relativa directory privata di staging.
     *
     * @return array{importId:string,directory:string,filePath:string}|null Contesto filesystem oppure null se non creabile
     */
    private function createStagingContext(): ?array
    {
        $this->cleanupExpiredStagingImports();
        $this->cleanupExpiredImportBackups();

        $rootDirectory = WRITEPATH . 'uploads/staging/';

        if ( ! is_dir($rootDirectory) && ! mkdir($rootDirectory, 0755, true) && ! is_dir($rootDirectory)):
            return null;
        endif;

        $importId = bin2hex(random_bytes(32));
        $directory = $rootDirectory . $importId . DIRECTORY_SEPARATOR;

        if ( ! mkdir($directory, 0750, true)):
            return null;
        endif;

        return [
            'importId' => $importId,
            'directory' => $directory,
            'filePath' => $directory . self::STAGING_FILENAME,
        ];
    }

    /**
     * Restituisce un'impronta non reversibile della sessione amministrativa corrente.
     *
     * L'impronta lega lo staging alla sessione che lo ha creato senza persistere il token di sessione in chiaro.
     */
    private function getImportOwnerFingerprint(): ?string
    {
        $sessionToken = session()->get('backendSession');

        if ( ! is_string($sessionToken) || $sessionToken === ''):
            return null;
        endif;

        return hash('sha256', $sessionToken);
    }

    /**
     * Persiste il manifest server-side che lega importId, entità, staging, stato e progresso dell'importazione.
     */
    private function writeStagingManifest(string $importId, string $entity, string $mode, string $filePath, array $headers, array $plan): bool
    {
        $fileHash = hash_file('sha256', $filePath);
        $fileSize = filesize($filePath);
        $owner = $this->getImportOwnerFingerprint();

        if ($fileHash === false || $fileSize === false || $owner === null):
            return false;
        endif;

        $handle = fopen($filePath, 'r');

        if ($handle === false):
            return false;
        endif;

        $headerRow = fgetcsv($handle, 0, ',');
        $dataOffset = $headerRow === false ? false : ftell($handle);
        fclose($handle);

        if ($dataOffset === false):
            return false;
        endif;

        $createdAt = time();
        $manifest = [
            'version' => self::STAGING_VERSION,
            'importId' => $importId,
            'entity' => $entity,
            'mode' => $mode,
            'status' => self::STATUS_STAGED,
            'createdAt' => $createdAt,
            'updatedAt' => $createdAt,
            'expiresAt' => $createdAt + self::STAGING_TTL,
            'owner' => $owner,
            'file' => self::STAGING_FILENAME,
            'fileSize' => $fileSize,
            'fileHash' => $fileHash,
            'headers' => array_values($headers),
            'plan' => $plan,
            'backup' => null,
            'failure' => null,
            'progress' => [
                'cursor' => $dataOffset,
                'processed' => 0,
                'inserted' => 0,
                'updated' => 0,
            ],
        ];

        return $this->persistStagingManifest($importId, $manifest);
    }

    /**
     * Scrive il manifest tramite file temporaneo per ridurre il rischio di JSON parzialmente scritto.
     */
    private function persistStagingManifest(string $importId, array $manifest): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
            return false;
        endif;

        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;
        $manifestPath = $directory . self::STAGING_MANIFEST;
        $tempPath = $manifestPath . '.tmp.' . bin2hex(random_bytes(6));

        if ( ! is_dir($directory)):
            return false;
        endif;

        $manifest['updatedAt'] = time();

        try {
            $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        if (file_put_contents($tempPath, $json, LOCK_EX) === false):
            return false;
        endif;

        if ( ! @rename($tempPath, $manifestPath)):
            /* Windows non sovrascrive sempre il file destinazione con rename(): fallback con lock esclusivo. */
            $written = file_put_contents($manifestPath, $json, LOCK_EX);
            @unlink($tempPath);
            return $written !== false;
        endif;

        return true;
    }

    /**
     * Acquisisce un lock esclusivo non bloccante per impedire l'esecuzione concorrente dello stesso import.
     *
     * @return resource|null
     */
    private function acquireImportLock(string $importId)
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
            return null;
        endif;

        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($directory)):
            return null;
        endif;

        $handle = fopen($directory . self::STAGING_LOCK, 'c+');

        if ($handle === false):
            return null;
        endif;

        if ( ! flock($handle, LOCK_EX | LOCK_NB)):
            fclose($handle);
            return null;
        endif;

        return $handle;
    }

    /**
     * Rilascia il lock esclusivo dell'importazione.
     *
     * @param resource $handle
     */
    private function releaseImportLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Registra nel manifest un fallimento senza eliminare staging e backup, così il recovery resta possibile.
     */
    private function markImportFailed(string $importId, array $manifest, string $reason): void
    {
        $progress = $manifest['progress'] ?? [];
        $manifest['status'] = self::STATUS_FAILED;
        $manifest['expiresAt'] = time() + self::STAGING_TTL;
        $manifest['failure'] = [
            'at' => time(),
            'reason' => $reason,
            'recoveryRequired' => (int) ($progress['processed'] ?? 0) > 0,
        ];

        if ( ! $this->persistStagingManifest($importId, $manifest)):
            log_message('critical', 'ImportModel: unable to update the error manifest for importId ' . $importId);
        endif;
    }

    /**
     * Crea un flag di recovery quando il database è già stato modificato ma il progresso non è più persistibile.
     */
    private function writeRecoveryFlag(string $importId, string $reason): void
    {
        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($directory)):
            return;
        endif;

        $payload = json_encode([
            'at' => time(),
            'reason' => $reason,
        ], JSON_UNESCAPED_SLASHES);

        if ($payload !== false):
            file_put_contents($directory . self::STAGING_RECOVERY_FLAG, $payload, LOCK_EX);
        endif;
    }


    /**
     * Scrive un journal prima del chunk. Se il processo termina in modo anomalo, la presenza del file
     * impedisce di rieseguire alla cieca un blocco il cui esito sul database potrebbe essere ambiguo.
     */
    private function writeChunkJournal(string $importId, array $progress): bool
    {
        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($directory)):
            return false;
        endif;

        $payload = json_encode([
            'startedAt' => time(),
            'cursor' => $progress['cursor'] ?? null,
            'processed' => $progress['processed'] ?? null,
            'inserted' => $progress['inserted'] ?? null,
            'updated' => $progress['updated'] ?? null,
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false):
            return false;
        endif;

        return file_put_contents($directory . self::STAGING_CHUNK_JOURNAL, $payload, LOCK_EX) !== false;
    }

    /**
     * Rimuove il journal solo quando l'esito del chunk è noto e il manifest è stato aggiornato.
     */
    private function deleteChunkJournal(string $importId): void
    {
        $path = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR . self::STAGING_CHUNK_JOURNAL;

        if (is_file($path)):
            @unlink($path);
        endif;
    }

    /**
     * Risolve un importId nel relativo contesto server-side e verifica ownership, scadenza e integrità di base.
     *
     * @return array{importId:string,entity:string,mode:string,filePath:string,manifest:array}|null
     */
    private function resolveStagingContext(string $importId, bool $verifyChecksum = false, bool $allowExpired = false): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1):
            return null;
        endif;

        $directory = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR;
        $manifestPath = $directory . self::STAGING_MANIFEST;
        $filePath = $directory . self::STAGING_FILENAME;

        if ( ! is_file($manifestPath) || ! is_file($filePath)):
            return null;
        endif;

        $manifestJson = file_get_contents($manifestPath);
        if ($manifestJson === false):
            return null;
        endif;

        try {
            $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (
            ! is_array($manifest)
            || ($manifest['version'] ?? null) !== self::STAGING_VERSION
            || ($manifest['importId'] ?? null) !== $importId
            || ! is_string($manifest['entity'] ?? null)
            || preg_match('/^[a-zA-Z0-9_-]+$/', $manifest['entity']) !== 1
            || ! $this->db->tableExists($manifest['entity'])
            || ! is_string($manifest['mode'] ?? null)
            || ! in_array($manifest['mode'], [self::IMPORT_MODE_CRUD, self::IMPORT_MODE_DATABASE], true)
            || ! is_int($manifest['expiresAt'] ?? null)
            || ! is_int($manifest['fileSize'] ?? null)
            || $manifest['fileSize'] <= 0
            || ! is_string($manifest['fileHash'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $manifest['fileHash']) !== 1
            || ! is_array($manifest['headers'] ?? null)
            || ! is_array($manifest['plan'] ?? null)
            || ! is_int($manifest['plan']['insert'] ?? null)
            || ! is_int($manifest['plan']['update'] ?? null)
            || ! is_int($manifest['plan']['skip'] ?? null)
            || $manifest['plan']['insert'] < 0
            || $manifest['plan']['update'] < 0
            || $manifest['plan']['skip'] < 0
            || ! is_string($manifest['status'] ?? null)
            || ! in_array($manifest['status'], [self::STATUS_STAGED, self::STATUS_READY, self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_COMPLETED], true)
            || ! is_array($manifest['progress'] ?? null)
            || ! is_int($manifest['progress']['cursor'] ?? null)
            || ! is_int($manifest['progress']['processed'] ?? null)
            || ! is_int($manifest['progress']['inserted'] ?? null)
            || ! is_int($manifest['progress']['updated'] ?? null)
            || $manifest['progress']['cursor'] < 0
            || $manifest['progress']['processed'] < 0
            || $manifest['progress']['inserted'] < 0
            || $manifest['progress']['updated'] < 0
            || $manifest['progress']['processed'] !== ($manifest['progress']['inserted'] + $manifest['progress']['updated'])
            || $manifest['progress']['inserted'] > $manifest['plan']['insert']
            || $manifest['progress']['updated'] > $manifest['plan']['update']
            || $manifest['progress']['processed'] > ($manifest['plan']['insert'] + $manifest['plan']['update'])
            || ! is_string($manifest['owner'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $manifest['owner']) !== 1
        ):
            return null;
        endif;

        if ( ! $allowExpired && $manifest['expiresAt'] < time()):
            return null;
        endif;

        $manifestOwner = $manifest['owner'];
        $currentOwner = $this->getImportOwnerFingerprint();

        if ($currentOwner === null || ! hash_equals($manifestOwner, $currentOwner)):
            return null;
        endif;

        $fileSize = filesize($filePath);
        if ($fileSize === false || (int) $manifest['fileSize'] !== $fileSize):
            return null;
        endif;

        if ($verifyChecksum):
            $fileHash = hash_file('sha256', $filePath);
            if ($fileHash === false || ! hash_equals((string) $manifest['fileHash'], $fileHash)):
                return null;
            endif;
        endif;

        return [
            'importId' => $importId,
            'entity' => $manifest['entity'],
            'mode' => $manifest['mode'],
            'filePath' => $filePath,
            'manifest' => $manifest,
        ];
    }

    /**
     * Determina se una riga CSV è realmente vuota senza trattare valori validi come "0" come falsy.
     */
    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value):
            if (trim((string) $value) !== ''):
                return false;
            endif;
        endforeach;

        return true;
    }

    /**
     * Calcola un'impronta deterministica dello stato DB rilevante per una riga di importazione.
     *
     * Vengono considerate esclusivamente le colonne presenti nel CSV. Nel flusso CRUD i timestamp
     * applicativi sono esclusi perché gestiti internamente; in modalità database restano dati ordinari.
     */
    private function buildRowSnapshot(array $row, array $headers, string $mode): string
    {
        $snapshot = [];

        foreach ($headers as $header):
            if (
                $mode === self::IMPORT_MODE_CRUD
                && in_array($header, ['created_at', 'updated_at'], true)
            ):
                continue;
            endif;

            $value = $row[$header] ?? null;
            $snapshot[$header] = $value === null ? null : (string) $value;
        endforeach;

        return hash(
            'sha256',
            json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * Verifica che lo staging sia tecnicamente coerente con manifest e schema autorizzato corrente.
     */
    private function validateStagingDefinition(array $headers, array $manifest, array $structure, ?string $primaryKey): bool
    {
        if ($headers === [] || $primaryKey === null):
            return false;
        endif;

        foreach ($headers as $header):
            if ( ! is_string($header) || $header === ''):
                return false;
            endif;
        endforeach;

        if (count($headers) !== count(array_unique($headers))):
            return false;
        endif;

        if ( ! in_array('__import_action', $headers, true) || ! in_array('__import_snapshot', $headers, true) || ! in_array($primaryKey, $headers, true)):
            return false;
        endif;

        $manifestHeaders = $manifest['headers'] ?? null;

        if ( ! is_array($manifestHeaders) || array_values($manifestHeaders) !== array_values($headers)):
            return false;
        endif;

        $tableColumns = array_column($structure, 'name');

        foreach ($headers as $header):
            if (in_array($header, ['__import_action', '__import_snapshot'], true)):
                continue;
            endif;

            if ( ! in_array($header, $tableColumns, true)):
                return false;
            endif;
        endforeach;

        return true;
    }

    /**
     * Rimuove una directory di staging appena creata quando il manifest non è ancora disponibile.
     */
    private function deleteStagingDirectory(string $directory): void
    {
        if ( ! is_dir($directory)):
            return;
        endif;

        foreach ([self::STAGING_FILENAME, self::STAGING_MANIFEST, self::STAGING_LOCK, self::STAGING_RECOVERY_FLAG, self::STAGING_CHUNK_JOURNAL] as $filename):
            $path = $directory . $filename;
            if (is_file($path)):
                @unlink($path);
            endif;
        endforeach;

        @rmdir($directory);
    }

    /**
     * Elimina in modo controllato tutti gli artefatti di staging associati a un'importazione.
     * Il backup non viene eliminato: costituisce un artefatto di recovery separato.
     */
    public function deleteStagingImport(string $importId): bool
    {
        $lockHandle = $this->acquireImportLock($importId);

        if ($lockHandle === null):
            return false;
        endif;

        try {
            $context = $this->resolveStagingContext($importId, false, true);

            if ($context === null):
                return false;
            endif;

            $manifest = $context['manifest'];
            $status = $manifest['status'] ?? null;
            $processed = (int) ($manifest['progress']['processed'] ?? 0);

            /* Non consentire la rimozione quando esistono scritture parziali da preservare per il recovery. */
            if ($status === self::STATUS_PROCESSING || ($processed > 0 && $status !== self::STATUS_COMPLETED)):
                return false;
            endif;

            $directory = dirname($context['filePath']) . DIRECTORY_SEPARATOR;

            foreach ([self::STAGING_FILENAME, self::STAGING_MANIFEST, self::STAGING_RECOVERY_FLAG, self::STAGING_CHUNK_JOURNAL] as $filename):
                $path = $directory . $filename;
                if (is_file($path) && ! unlink($path)):
                    return false;
                endif;
            endforeach;

            /* Un backup creato ma mai seguito da scritture non ha valore di recovery e può essere rimosso. */
            if ($processed === 0):
                $this->deleteBackupArtifacts($importId);
            endif;
        } finally {
            $this->releaseImportLock($lockHandle);
        }

        $lockPath = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR . self::STAGING_LOCK;
        $directory = dirname($lockPath);

        if (is_file($lockPath) && ! unlink($lockPath)):
            return false;
        endif;

        return ! is_dir($directory) || rmdir($directory);
    }

    /**
     * Esegue una sola volta il backup preventivo della tabella e lo collega al manifest dell'importazione.
     *
     * Il metodo è idempotente: se il backup valido esiste già, non ne crea un secondo.
     */
    public function backupImport(string $importId): bool
    {
        $lockHandle = $this->acquireImportLock($importId);

        if ($lockHandle === null):
            return false;
        endif;

        try {
            $context = $this->resolveStagingContext($importId, true);

            if ($context === null):
                return false;
            endif;

            $manifest = $context['manifest'];
            $status = $manifest['status'] ?? null;

            /*
             * Dopo l'avvio delle scritture lo stato deve essere valutato da executeImport(), che conosce
             * il progresso e può segnalare correttamente un eventuale recoveryRequired. Il controller non
             * deve mascherare questi casi come un generico errore di backup.
             */
            if (in_array($status, [self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_COMPLETED], true)):
                return true;
            endif;

            if ($status === self::STATUS_READY):
                if ($this->isImportBackupValid($importId, $manifest, false)):
                    return true;
                endif;

                /* Nessuna riga è stata ancora committed: un backup perso/corrotto può essere ricreato in sicurezza. */
                if ((int) ($manifest['progress']['processed'] ?? 0) !== 0):
                    return true;
                endif;

                $this->deleteBackupArtifacts($importId);
                $manifest['backup'] = null;
                $manifest['status'] = self::STATUS_STAGED;
                $status = self::STATUS_STAGED;
            endif;

            if ($status !== self::STATUS_STAGED):
                return false;
            endif;

            if ($this->isImportBackupValid($importId, $manifest, true)):
                $manifest['status'] = self::STATUS_READY;
                $manifest['expiresAt'] = time() + self::STAGING_TTL;
                return $this->persistStagingManifest($importId, $manifest);
            endif;

            $backup = $this->createTableBackup($context['entity'], $importId);

            if ($backup === null):
                return false;
            endif;

            $manifest['backup'] = $backup;
            $manifest['status'] = self::STATUS_READY;
            $manifest['expiresAt'] = time() + self::STAGING_TTL;

            if ( ! $this->persistStagingManifest($importId, $manifest)):
                $this->deleteBackupArtifacts($importId);
                return false;
            endif;

            return true;
        } finally {
            $this->releaseImportLock($lockHandle);
        }
    }

    /**
     * Scrive integralmente una stringa sullo stream gestendo anche eventuali write parziali.
     *
     * @param resource $handle
     */
    private function writeStream($handle, string $data): bool
    {
        $length = strlen($data);
        $written = 0;

        while ($written < $length):
            $result = fwrite($handle, substr($data, $written));

            if ($result === false || $result === 0):
                return false;
            endif;

            $written += $result;
        endwhile;

        return true;
    }

    /**
     * Serializza un valore del database in SQL preservando i payload binari byte-per-byte.
     */
    private function escapeBackupValue(mixed $value, array $field): string
    {
        if ($value === null):
            return 'NULL';
        endif;

        $type = strtolower((string) ($field['type'] ?? ''));
        $binaryTypes = [
            'binary', 'varbinary',
            'tinyblob', 'blob', 'mediumblob', 'longblob',
            'bit',
            'geometry', 'point', 'linestring', 'polygon',
            'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection',
        ];

        if (in_array($type, $binaryTypes, true)):
            return "X'" . bin2hex((string) $value) . "'";
        endif;

        return (string) $this->db->escape($value);
    }

    /**
     * Scrive uno statement INSERT multi-row, mantenendo la costruzione SQL centralizzata.
     *
     * @param resource $handle
     * @param list<string> $tuples
     */
    private function writeBackupInsertStatement($handle, string $prefix, array $tuples): bool
    {
        if ($tuples === []):
            return true;
        endif;

        return $this->writeStream(
            $handle,
            $prefix . implode(",\n", $tuples) . ";\n"
        );
    }

    /**
     * Crea un backup SQL ripristinabile e restituisce i metadati necessari a verificarne l'integrità.
     *
     * Il backup viene creato anche per una tabella vuota: in quel caso il TRUNCATE rappresenta lo stato iniziale.
     */
    private function createTableBackup(string $entity, string $importId): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $importId) !== 1 || ! $this->db->tableExists($entity)):
            return null;
        endif;

        $structure = $this->getTableStructure($entity);
        $primaryKeyFields = array_values(array_filter(
            $structure,
            static fn(array $field): bool => (int) ($field['primary_key'] ?? 0) === 1
        ));

        /* Il backup usa keyset pagination e richiede la stessa PK singola dell'import engine. */
        if (count($primaryKeyFields) !== 1 || (bool) ($primaryKeyFields[0]['generated'] ?? false)):
            return null;
        endif;

        $primaryKey = (string) $primaryKeyFields[0]['name'];

        /* Le colonne generated vengono ricalcolate dal database in fase di restore e non vanno inserite esplicitamente. */
        $backupStructure = array_values(array_filter(
            $structure,
            static fn(array $field): bool => ! (bool) ($field['generated'] ?? false)
        ));
        $backupFields = array_column($backupStructure, 'name');
        $backupStructureMap = array_column($backupStructure, null, 'name');

        if ($backupFields === []):
            return null;
        endif;

        $protectedBackupColumns = array_map(function($column) {
            return $this->db->protectIdentifiers((string) $column);
        }, $backupFields);

        $backupDirectory = WRITEPATH . 'backups/imports/' . $importId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0750, true) && ! is_dir($backupDirectory)):
            return null;
        endif;

        $backupPath = $backupDirectory . self::BACKUP_FILENAME;
        $tempPath = $backupPath . '.tmp';
        $handle = fopen($tempPath, 'w');

        if ($handle === false):
            return null;
        endif;

        $transactionStarted = false;
        $success = true;
        $totalRows = 0;
        $protectedTable = $this->db->protectIdentifiers($entity, true, null, false);

        try {
            /*
             * Una singola transazione di sola lettura mantiene coerente lo snapshot del backup
             * anche se altre richieste modificano la tabella durante la generazione del file.
             */
            /*
             * Forziamo REPEATABLE READ per questa sola transazione: il backup deve rappresentare
             * uno snapshot consistente anche se il DBGroup applicativo usa un isolation level diverso.
             */
            if ($this->db->getPlatform() === 'MySQLi'):
                $isolationResult = $this->db->query('set transaction isolation level repeatable read');

                if ($isolationResult === false):
                    throw new \RuntimeException('Unable to set REPEATABLE READ for the backup.');
                endif;
            endif;

            if ($this->db->transBegin() === false):
                throw new \RuntimeException('Unable to start the backup transaction.');
            endif;

            $transactionStarted = true;
            $totalRows = $this->db->table($entity)->countAllResults();

            $lines = [
                '/* Backup tabella: ' . $entity . ' - Data: ' . date('Y-m-d H:i:s') . ' */' . "\n",
                "set foreign_key_checks = 0;\n",
                'truncate table ' . $protectedTable . ";\n",
            ];

            foreach ($lines as $line):
                if ( ! $this->writeStream($handle, $line)):
                    $success = false;
                    break;
                endif;
            endforeach;

            /*
             * Keyset pagination: evita il costo crescente di OFFSET sulle tabelle grandi.
             * La PK singola è già un requisito fail-closed dell'import engine.
             */
            $lastPrimaryKey = null;

            while ($success):
                $builder = $this->db->table($entity)
                    ->select($backupFields)
                    ->orderBy($primaryKey, 'asc')
                    ->limit(self::BACKUP_CHUNK_SIZE);

                if ($lastPrimaryKey !== null):
                    $builder->where($primaryKey . ' >', $lastPrimaryKey);
                endif;

                $chunk = $builder->get()->getResultArray();

                if ($chunk === []):
                    break;
                endif;

                $insertPrefix = 'insert into ' . $protectedTable
                    . ' (' . implode(', ', $protectedBackupColumns) . ") values\n";
                $valueTuples = [];
                $statementBytes = strlen($insertPrefix) + 2;

                foreach ($chunk as $row):
                    if ( ! array_key_exists($primaryKey, $row)):
                        $success = false;
                        break 2;
                    endif;

                    $escapedValues = [];

                    foreach ($backupFields as $fieldName):
                        if ( ! array_key_exists($fieldName, $row)):
                            $success = false;
                            break 3;
                        endif;

                        $fieldConfig = $backupStructureMap[$fieldName] ?? [];
                        $escapedValues[] = $this->escapeBackupValue($row[$fieldName], $fieldConfig);
                    endforeach;

                    $tuple = '(' . implode(', ', $escapedValues) . ')';
                    $tupleBytes = strlen($tuple) + ($valueTuples === [] ? 0 : 2);

                    /*
                     * Evita statement multi-row eccessivamente grandi. Una singola riga più grande
                     * del limite viene comunque scritta da sola: non è possibile spezzare semanticamente una riga SQL.
                     */
                    if (
                        $valueTuples !== []
                        && ($statementBytes + $tupleBytes) > self::BACKUP_MAX_STATEMENT_BYTES
                    ):
                        if ( ! $this->writeBackupInsertStatement($handle, $insertPrefix, $valueTuples)):
                            $success = false;
                            break 2;
                        endif;

                        $valueTuples = [];
                        $statementBytes = strlen($insertPrefix) + 2;
                        $tupleBytes = strlen($tuple);
                    endif;

                    $valueTuples[] = $tuple;
                    $statementBytes += $tupleBytes;
                endforeach;

                if ( ! $this->writeBackupInsertStatement($handle, $insertPrefix, $valueTuples)):
                    $success = false;
                    break;
                endif;

                $lastRow = $chunk[array_key_last($chunk)];
                $nextPrimaryKey = $lastRow[$primaryKey] ?? null;

                if ($nextPrimaryKey === null || $nextPrimaryKey === $lastPrimaryKey):
                    $success = false;
                    break;
                endif;

                $lastPrimaryKey = $nextPrimaryKey;
            endwhile;

            if ($success && ! $this->writeStream($handle, "set foreign_key_checks = 1;\n")):
                $success = false;
            endif;

            if ($success):
                $success = fflush($handle);
            endif;

            if ($success && $this->db->transCommit() !== false):
                $transactionStarted = false;
            else:
                $this->db->transRollback();
                $transactionStarted = false;
                $success = false;
            endif;

        } catch (\Throwable $e) {
            if ($transactionStarted):
                $this->db->transRollback();
            endif;

            $success = false;
            log_message('error', 'ImportModel backup error [' . $importId . ']: ' . $e->getMessage());
        } finally {
            fclose($handle);
        }

        if ( ! $success):
            @unlink($tempPath);
            return null;
        endif;

        if ( ! rename($tempPath, $backupPath)):
            @unlink($tempPath);
            return null;
        endif;

        $fileSize = filesize($backupPath);
        $fileHash = hash_file('sha256', $backupPath);

        if ($fileSize === false || $fileHash === false):
            @unlink($backupPath);
            return null;
        endif;

        return [
            'file' => self::BACKUP_FILENAME,
            'fileSize' => $fileSize,
            'fileHash' => $fileHash,
            'createdAt' => time(),
            'rowCount' => $totalRows,
        ];
    }

    /**
     * Verifica che il backup associato al manifest esista e, opzionalmente, che l'hash corrisponda.
     */
    private function isImportBackupValid(string $importId, array $manifest, bool $verifyChecksum = false): bool
    {
        $backup = $manifest['backup'] ?? null;

        if (
            ! is_array($backup)
            || ($backup['file'] ?? null) !== self::BACKUP_FILENAME
            || ! is_int($backup['fileSize'] ?? null)
            || ! is_string($backup['fileHash'] ?? null)
        ):
            return false;
        endif;

        $backupPath = WRITEPATH . 'backups/imports/' . $importId . DIRECTORY_SEPARATOR . self::BACKUP_FILENAME;

        if ( ! is_file($backupPath)):
            return false;
        endif;

        $fileSize = filesize($backupPath);

        if ($fileSize === false || $fileSize !== $backup['fileSize']):
            return false;
        endif;

        if ($verifyChecksum):
            $fileHash = hash_file('sha256', $backupPath);

            if ($fileHash === false || ! hash_equals($backup['fileHash'], $fileHash)):
                return false;
            endif;
        endif;

        return true;
    }

    /**
     * Rimuove un backup incompleto o non ancora collegato a un manifest valido.
     */
    private function deleteBackupArtifacts(string $importId): void
    {
        $directory = WRITEPATH . 'backups/imports/' . $importId . DIRECTORY_SEPARATOR;
        $backupPath = $directory . self::BACKUP_FILENAME;

        if (is_file($backupPath)):
            @unlink($backupPath);
        endif;

        if (is_file($backupPath . '.tmp')):
            @unlink($backupPath . '.tmp');
        endif;

        if (is_dir($directory)):
            @rmdir($directory);
        endif;
    }

    /**
     * Estrae e formatta la struttura fisica (Schema) di una tabella del database.
     * 
     * Interroga il motore del database per ottenere l'elenco delle colonne, i tipi di dato (es. varchar, int), 
     * i limiti massimi di lunghezza e le configurazioni delle chiavi (Primary Key e Indici). 
     * Costituisce la base su cui il motore di importazione mappa le colonne del CSV e deduce 
     * le regole di validazione di fallback.
     *
     * @param string $table Il nome esatto della tabella
     * @return array Mappa strutturata delle colonne e dei relativi vincoli fisici
     */
    public function getTableStructure(string $table): array
    {
        /* Controllo di sicurezza sull'esistenza della tabella */
        if ( ! $this->db->tableExists($table)):
            return [];
        endif;

        $fields = $this->db->getFieldData($table);
        $indexes = $this->db->getIndexData($table);

        /*
         * CodeIgniter espone nullable/default tramite getFieldData(), ma non espone
         * in modo uniforme il flag auto_increment. Su MariaDB/MySQL recuperiamo quindi
         * anche i metadati SHOW COLUMNS per distinguere PK autoincrementali da UUID.
         */
        $columnMetadata = [];

        if ($this->db->getPlatform() === 'MySQLi'):
            $protectedTable = $this->db->protectIdentifiers($table, true, null, false);
            $columns = $this->db->query('show columns from ' . $protectedTable)->getResultArray();

            foreach ($columns as $column):
                $columnMetadata[$column['Field']] = $column;
            endforeach;
        endif;
        
        /* Creiamo un array piatto con i nomi di tutte le colonne indicizzate */
        $indexedColumns = [];
        foreach ($indexes as $index):
            foreach ($index->fields as $fieldName):
                $indexedColumns[] = $fieldName;
            endforeach;
        endforeach;

        $structure = [];

        foreach ($fields as $field):
            /* Verifichiamo se il campo è un indice (escludendo la primary key per differenziarli visivamente) */
            $isIndex = in_array($field->name, $indexedColumns, true) && $field->primary_key !== 1;

            $metadata = $columnMetadata[$field->name] ?? null;

            $structure[] = [
                'name' => $field->name,
                'type' => $field->type,
                'max_length' => $field->max_length,
                'primary_key' => $field->primary_key,
                'is_index' => $isIndex,
                'nullable' => $metadata !== null
                    ? $metadata['Null'] === 'YES'
                    : (bool) ($field->nullable ?? true),
                'default' => $metadata['Default'] ?? ($field->default ?? null),
                'auto_increment' => $metadata !== null
                    && str_contains(strtolower((string) ($metadata['Extra'] ?? '')), 'auto_increment'),
                'generated' => $metadata !== null
                    && (
                        str_contains(strtolower((string) ($metadata['Extra'] ?? '')), 'virtual generated')
                        || str_contains(strtolower((string) ($metadata['Extra'] ?? '')), 'stored generated')
                        || str_contains(strtolower((string) ($metadata['Extra'] ?? '')), 'persistent')
                    )
            ];

        endforeach;

        return $structure;
    }

    /**
     * Restituisce lo schema effettivamente esposto a un import in base al contesto.
     *
     * In modalità database viene restituito l'intero schema fisico. In modalità CRUD il model
     * deve implementare ImportValidationInterface e la struttura viene ridotta alla whitelist
     * importAllowedFields(); configurazioni incoerenti falliscono in modo chiuso.
     */
    public function getImportTableStructure(string $entity, string $mode = self::IMPORT_MODE_CRUD): array
    {
        if ( ! in_array($mode, [self::IMPORT_MODE_CRUD, self::IMPORT_MODE_DATABASE], true)):
            return [];
        endif;

        $structure = $this->getTableStructure($entity);

        if ($structure === []):
            return [];
        endif;

        /*
         * Le colonne generated (VIRTUAL/STORED) sono calcolate dal database e non sono scrivibili.
         * Vengono quindi escluse sia dai template sia dal motore di importazione.
         */
        $importableStructure = array_values(array_filter(
            $structure,
            static fn(array $field): bool => ! (bool) ($field['generated'] ?? false)
        ));

        $tablePrimaryKeys = array_values(array_filter(
            $importableStructure,
            static fn(array $field): bool => (int) ($field['primary_key'] ?? 0) === 1
        ));

        /* Il motore corrente gestisce in modo intenzionalmente fail-closed una singola Primary Key scrivibile. */
        if (count($tablePrimaryKeys) !== 1):
            log_message('error', 'ImportModel: unsupported schema (missing, composite, or generated Primary Key) for entity ' . $entity);
            return [];
        endif;

        if ($mode === self::IMPORT_MODE_DATABASE):
            return $importableStructure;
        endif;

        $targetModel = $this->getImportValidationProvider($entity);

        if ($targetModel === null):
            return [];
        endif;

        $allowedFields = array_values(array_unique($targetModel->importAllowedFields()));
        $schemaFields = array_column($importableStructure, 'name');

        if ($allowedFields === [] || array_diff($allowedFields, $schemaFields) !== []):
            log_message('error', 'ImportModel: inconsistent CRUD whitelist for entity ' . $entity);
            return [];
        endif;

        $filtered = array_values(array_filter(
            $importableStructure,
            static fn(array $field): bool => in_array($field['name'], $allowedFields, true)
        ));

        $primaryKeys = array_values(array_filter(
            $filtered,
            static fn(array $field): bool => (int) ($field['primary_key'] ?? 0) === 1
        ));

        return count($primaryKeys) === 1 ? $filtered : [];
    }

    /**
     * Fase 1: Analisi, validazione e preparazione del file CSV (Staging).
     * 
     * Metodo estremamente denso che esegue i controlli preliminari prima di scrivere a database.
     * 1. Confronta le intestazioni del CSV con le colonne reali della tabella (bloccando colonne estranee).
     * 2. Estrae gli ID dal CSV e scarica i record già esistenti dal DB per eseguire un diff in memoria.
     * 3. Analizza riga per riga: se i dati cambiano pianifica un 'update', se il record non esiste pianifica un 'insert', se sono identici fa 'skip'.
     * 4. Valida ogni riga tramite le regole del Model (o quelle dinamiche di fallback).
     * 5. Scrive le righe validate in un file temporaneo sicuro di Staging, aggiungendo la direttiva operativa (`__import_action`).
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file L'oggetto file caricato dalla richiesta HTTP
     * @param string $entity Il nome della tabella di destinazione
     * @param string $mode Contesto server-side dell'importazione: CRUD applicativo o database raw
     * @return array Struttura dati con l'esito, le statistiche dell'operazione (plan) e i primi record da mostrare in anteprima
     */
    public function parseAndValidateCsv(\CodeIgniter\HTTP\Files\UploadedFile $file, string $entity, string $mode = self::IMPORT_MODE_CRUD): array
    {
        if ( ! in_array($mode, [self::IMPORT_MODE_CRUD, self::IMPORT_MODE_DATABASE], true)):
            return ['status' => false, 'message' => lang('backend/components/import.messages.invalidEntity')];
        endif;

        $structure = $this->getImportTableStructure($entity, $mode);

        if ($structure === []):
            return ['status' => false, 'message' => lang('backend/components/import.messages.noStructure')];
        endif;

        $expectedHeaders = array_column($structure, 'name');

        /* Trova la chiave primaria reale dallo schema */
        $primaryKey = null;
        foreach ($structure as $col):
            if ($col['primary_key'] == 1):
                $primaryKey = $col['name'];
                break;
            endif;
        endforeach;

        if ($primaryKey === null):
            return ['status' => false, 'message' => lang('backend/components/import.messages.noStructure')];
        endif;

        $targetModel = $mode === self::IMPORT_MODE_CRUD ? $this->getImportValidationProvider($entity) : null;

        $plan = ['insert' => 0, 'update' => 0, 'skip' => 0];
        
        $handle = fopen($file->getTempName(), 'r');
        
        if ($handle === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        /* Lettura CSV con virgola e pulizia preventiva di spazi e BOM UTF-8 */
        $headerRow = fgetcsv($handle, 0, ',');

        if ($headerRow === false):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.emptyFile')];
        endif;

        $csvHeaders = array_map(static function($header): string {
            $normalized = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
            return trim($normalized ?? '');
        }, $headerRow);

        /* Controllo validità colonne CSV (Minimo 2 colonne) */
        if (count($csvHeaders) < 2):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.insufficientColumns')];
        endif;

        if (count($csvHeaders) !== count(array_unique($csvHeaders))):
            $headerCounts = array_count_values($csvHeaders);
            $duplicateHeaders = array_keys(array_filter($headerCounts, static fn(int $count): bool => $count > 1));
            fclose($handle);
            return [
                'status' => false,
                'message' => sprintf(lang('backend/components/import.messages.invalidColumns'), implode(', ', $duplicateHeaders)),
            ];
        endif;

        /* Controllo validità colonne CSV (Presenza obbligatoria Primary Key) */
        if ( ! in_array($primaryKey, $csvHeaders, true)):
            fclose($handle);
            return ['status' => false, 'message' => sprintf(lang('backend/components/import.messages.missingPrimaryKey'), $primaryKey)];
        endif;

        /* Controllo validità colonne CSV (Nessuna colonna estranea consentita) */
        $invalidColumns = array_diff($csvHeaders, $expectedHeaders);
        if ( ! empty($invalidColumns)):
            fclose($handle);
            return ['status' => false, 'message' => sprintf(lang('backend/components/import.messages.invalidColumns'), implode(', ', $invalidColumns))];
        endif;

        /* MAPPA IN MEMORIA OTTIMIZZATA */
        $existingDataMap = [];
        if ($this->db->tableExists($entity)):
            $pkIndex = array_search($primaryKey, $csvHeaders, true);
            if ($pkIndex !== false):
                $csvIds = [];
                $seenPrimaryKeys = [];
                $duplicatePrimaryKeys = [];

                while (($row = fgetcsv($handle, 0, ',')) !== false):
                    if (isset($row[$pkIndex]) && trim((string) $row[$pkIndex]) !== ''):
                        $csvId = trim((string) $row[$pkIndex]);
                        $csvIds[] = $csvId;

                        if (isset($seenPrimaryKeys[$csvId])):
                            $duplicatePrimaryKeys[$csvId] = true;
                        else:
                            $seenPrimaryKeys[$csvId] = true;
                        endif;
                    endif;
                endwhile;

                rewind($handle);
                fgetcsv($handle, 0, ',');

                if ($duplicatePrimaryKeys !== []):
                    fclose($handle);

                    return [
                        'status' => false,
                        'message' => sprintf(
                            lang('backend/components/import.messages.duplicatePrimaryKeys'),
                            implode(', ', array_keys($duplicatePrimaryKeys))
                        ),
                    ];
                endif;

                if ( ! empty($csvIds)):
                    $chunks = array_chunk(array_unique($csvIds), 1000);
                    foreach ($chunks as $chunk):
                        $builder = $this->db->table($entity)->whereIn($primaryKey, $chunk);

                        /* Tools/database non necessita colonne estranee al CSV; nel CRUD il model può usarle nei business guard. */
                        if ($mode === self::IMPORT_MODE_DATABASE):
                            $builder->select($csvHeaders);
                        endif;

                        $dbRecords = $builder->get()->getResultArray();
                        foreach ($dbRecords as $record):
                            $existingDataMap[$record[$primaryKey]] = $record;
                        endforeach;
                    endforeach;
                endif;
            endif;
        endif;

        $stagingContext = $this->createStagingContext();

        if ($stagingContext === null):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        $importId = $stagingContext['importId'];
        $stagingPath = $stagingContext['filePath'];
        $stagingHandle = fopen($stagingPath, 'w');

        if ($stagingHandle === false):
            fclose($handle);
            $this->deleteStagingDirectory($stagingContext['directory']);
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;
        
        $stagingHeaders = $csvHeaders;
        $stagingHeaders[] = '__import_action';
        $stagingHeaders[] = '__import_snapshot';

        if (fputcsv($stagingHandle, $stagingHeaders, ',') === false):
            fclose($handle);
            fclose($stagingHandle);
            $this->deleteStagingDirectory($stagingContext['directory']);
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        $rows = [];
        $errors = [];
        $lineNumber = 2;
        
        $validator = \Config\Services::validation();
        
        /* Genera i due set di fallback: insert e update hanno requisiti differenti. */
        $csvHeaderMap = array_flip($csvHeaders);
        /*
         * In insert manteniamo anche le regole delle colonne obbligatorie assenti dal CSV:
         * il validator può così bloccare il file prima dello staging. In update, invece,
         * il CSV può essere parziale e validiamo solo le colonne effettivamente presenti.
         */
        $fallbackInsertRules = $this->buildDynamicRules($structure, 'insert', $mode);
        $fallbackUpdateRules = array_intersect_key(
            $this->buildDynamicRules($structure, 'update', $mode),
            $csvHeaderMap
        );

        while (($row = fgetcsv($handle, 0, ',')) !== false):
            
            if ($this->isCsvRowEmpty($row)):
                $lineNumber++;
                continue;
            endif;

            if (count($row) !== count($csvHeaders)):
                $errors[] = sprintf(lang('backend/components/import.messages.wrongColumnsNumber'), $lineNumber, count($row), count($csvHeaders));
                $lineNumber++;
                continue;
            endif;

            $data = array_combine($csvHeaders, $row);

            foreach ($data as $key => $value):
                if (trim((string)$value) === '' || strtolower(trim((string)$value)) === 'null'):
                    $data[$key] = null;
                endif;
            endforeach;

            $idValue = $data[$primaryKey] ?? null;
            $hasPrimaryKeyValue = $idValue !== null && trim((string) $idValue) !== '';
            $action = 'insert';
            $changedColumns = [];

            if ($hasPrimaryKeyValue && array_key_exists($idValue, $existingDataMap)):
                $dbRow = $existingDataMap[$idValue];
                
                foreach ($data as $key => $value):
                    /* In CRUD i timestamp sono gestiti dal motore; in database mode sono dati ordinari del CSV. */
                    if (
                        $mode === self::IMPORT_MODE_CRUD
                        && in_array($key, ['created_at', 'updated_at'], true)
                    ):
                        continue;
                    endif;

                    if (array_key_exists($key, $dbRow) && (string) $dbRow[$key] !== (string) $value):
                        $changedColumns[] = $key;
                    endif;
                endforeach;

                /* Se l'array non è vuoto, c'è stato almeno un cambiamento */
                if ( ! empty($changedColumns)):
                    $action = 'update';
                    $plan['update']++;
                else:
                    $plan['skip']++;
                    $lineNumber++;
                    continue; 
                endif;
            else:
                $plan['insert']++;
            endif;

            $validator->reset();

            /* Le regole strutturali dello schema restano sempre la base della validazione. */
            $rules = $action === 'insert' ? $fallbackInsertRules : $fallbackUpdateRules;

            if ($targetModel !== null):
                if ($action === 'insert'):
                    /*
                     * In inserimento manteniamo anche le regole required dei campi import-specifici
                     * non presenti nel CSV, così l'assenza di un dato applicativamente obbligatorio
                     * viene rilevata prima della creazione dello staging.
                     */
                    $importAddRules = array_intersect_key(
                        $targetModel->importAddValidationRules(),
                        array_flip($expectedHeaders)
                    );
                    $rules = array_replace($rules, $importAddRules);
                else:
                    /*
                     * In modifica validiamo solo le colonne realmente presenti nel CSV,
                     * consentendo import parziali senza rendere obbligatori campi non forniti.
                     */
                    $importEditRules = array_intersect_key(
                        $targetModel->importEditValidationRules($data),
                        array_flip($csvHeaders)
                    );

                    $rules = array_replace($rules, $importEditRules);
                endif;
            endif;

            $rowHasErrors = false;

            if ( ! empty($rules)):
                $validator->setRules($rules);
                if ( ! $validator->run($data)):
                    $rowHasErrors = true;
                    foreach ($validator->getErrors() as $field => $error):
                        $errors[] = "Riga {$lineNumber} ({$field}): {$error}";
                    endforeach;
                endif;
            endif;

            if ( ! $rowHasErrors && $targetModel !== null):
                $currentRow = $action === 'update' ? ($existingDataMap[$idValue] ?? null) : null;
                $businessErrors = $targetModel->importBusinessValidationErrors($action, $data, $currentRow);

                foreach ($businessErrors as $error):
                    $rowHasErrors = true;
                    $errors[] = "Riga {$lineNumber}: {$error}";
                endforeach;
            endif;

            if ( ! $rowHasErrors):
                $stagingData = $data;
                $stagingData['__import_action'] = $action;
                $stagingData['__import_snapshot'] = $action === 'update'
                    ? $this->buildRowSnapshot($existingDataMap[$idValue], $csvHeaders, $mode)
                    : '';

                if (fputcsv($stagingHandle, array_values($stagingData), ',') === false):
                    fclose($handle);
                    fclose($stagingHandle);
                    $this->deleteStagingDirectory($stagingContext['directory']);
                    return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
                endif;

                /* 
                 * UPGRADE STRUTTURALE DELL'ANTEPRIMA:
                 * Esteso a 50 righe e trasformato in un array multidimensionale
                 * che contiene i dati associativi e i metadati sulle differenze.
                 */
                if (count($rows) < 50):
                    $rows[] = [
                        'record'  => $data,           /* Array associativo dei dati [colonna => valore] */
                        'action'  => $action,         /* 'insert' o 'update' */
                        'changed' => $changedColumns  /* Array con i nomi delle colonne modificate */
                    ];
                endif;
            endif;

            $lineNumber++;
        endwhile;

        fclose($handle);
        fclose($stagingHandle);

        /* Controllo di garanzia: il file conteneva solo l'intestazione o righe vuote */
        if (($plan['insert'] + $plan['update'] + $plan['skip']) === 0):
            $this->deleteStagingDirectory($stagingContext['directory']);
            return ['status' => false, 'message' => lang('backend/components/import.messages.emptyFile')];
        endif;

        if ( ! empty($errors)):
            $this->deleteStagingDirectory($stagingContext['directory']);

            $maxErrors = 500;
            $totalErrors = count($errors);
            
            if ($totalErrors > $maxErrors):
                $errors = array_slice($errors, 0, $maxErrors);
                $errors[] = lang('backend/components/import.messages.additionalErrorsNotShown', [$totalErrors - $maxErrors]);
            endif;

            return ['status' => false, 'validationErrors' => $errors];
        endif;

        if ($plan['insert'] === 0 && $plan['update'] === 0):
            $this->deleteStagingDirectory($stagingContext['directory']);

            return [
                'status' => true,
                'headers' => $csvHeaders,
                'rows' => $rows,
                'importId' => null,
                'plan' => $plan,
            ];
        endif;

        if ( ! $this->writeStagingManifest($importId, $entity, $mode, $stagingPath, $stagingHeaders, $plan)):
            $this->deleteStagingDirectory($stagingContext['directory']);
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        return [
            'status' => true,
            'headers' => $csvHeaders,
            'rows' => $rows,
            'importId' => $importId,
            'plan' => $plan,
        ];
    }

    /**
     * Fase 2: esegue materialmente l'importazione leggendo il file di staging validato.
     *
     * Il progresso è mantenuto server-side nel manifest: il client non può scegliere offset o saltare record.
     * Ogni chunk è protetto da lock esclusivo e transazione DB. Il backup preventivo deve esistere prima
     * della prima scrittura. In caso di errore lo staging e il backup vengono conservati per il recovery.
     *
     * @param string $importId Identità server-side dell'importazione generata in fase di staging
     * @return array Esito del chunk, progresso cumulativo e stato finale dell'importazione
     */
    public function executeImport(string $importId): array
    {
        $lockHandle = $this->acquireImportLock($importId);

        if ($lockHandle === null):
            return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
        endif;

        $handle = null;
        $transactionStarted = false;
        $manifest = [];

        try {
            $recoveryFlag = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR . self::STAGING_RECOVERY_FLAG;
            $chunkJournal = WRITEPATH . 'uploads/staging/' . $importId . DIRECTORY_SEPARATOR . self::STAGING_CHUNK_JOURNAL;

            /* Lo staging è piccolo (upload limitato dal controller): verifichiamo sempre l'hash prima di scrivere. */
            $context = $this->resolveStagingContext($importId, true);

            if ($context === null):
                return ['status' => false, 'message' => lang('backend/components/import.messages.fileNotFoundError')];
            endif;

            $entity = $context['entity'];
            $mode = $context['mode'];
            $filePath = $context['filePath'];
            $manifest = $context['manifest'];
            $status = $manifest['status'];
            $progress = $manifest['progress'];

            /* Ownership e integrità sono già state verificate da resolveStagingContext(). */
            if (is_file($recoveryFlag)):
                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => true,
                ];
            endif;

            /*
             * Un journal residuo non implica sempre ambiguità. Se il manifest è già avanzato rispetto
             * al progresso pre-chunk registrato nel journal, commit e persistenza del manifest erano riusciti
             * e il file è semplicemente rimasto da una pulizia interrotta. Se invece coincidono, l'esito del
             * commit non è ricostruibile con certezza e l'import viene bloccato in recovery.
             */
            if (is_file($chunkJournal)):
                $journalJson = file_get_contents($chunkJournal);
                $journalProgress = null;

                if ($journalJson !== false):
                    try {
                        $journal = json_decode($journalJson, true, 512, JSON_THROW_ON_ERROR);

                        if (
                            is_array($journal)
                            && is_int($journal['cursor'] ?? null)
                            && is_int($journal['processed'] ?? null)
                            && is_int($journal['inserted'] ?? null)
                            && is_int($journal['updated'] ?? null)
                        ):
                            $journalProgress = [
                                'cursor' => $journal['cursor'],
                                'processed' => $journal['processed'],
                                'inserted' => $journal['inserted'],
                                'updated' => $journal['updated'],
                            ];
                        endif;
                    } catch (\JsonException) {
                        $journalProgress = null;
                    }
                endif;

                $manifestAhead = $journalProgress !== null
                    && $progress['processed'] > $journalProgress['processed']
                    && $progress['cursor'] >= $journalProgress['cursor']
                    && $progress['inserted'] >= $journalProgress['inserted']
                    && $progress['updated'] >= $journalProgress['updated']
                    && ($progress['processed'] - $journalProgress['processed']) <= self::IMPORT_CHUNK_SIZE
                    && ($progress['processed'] - $journalProgress['processed'])
                        === (($progress['inserted'] - $journalProgress['inserted']) + ($progress['updated'] - $journalProgress['updated']));

                if ($manifestAhead):
                    /* Best effort: anche se unlink fallisce, il prossimo write-ahead journal lo sovrascriverà. */
                    @unlink($chunkJournal);
                else:
                    $this->writeRecoveryFlag($importId, 'Detected chunk.pending with an indeterminate commit outcome.');

                    return [
                        'status' => false,
                        'message' => lang('backend/components/import.messages.importTransactionError'),
                        'recoveryRequired' => true,
                    ];
                endif;
            endif;

            /* Una richiesta ripetuta dopo il completamento è idempotente. */
            if ($status === self::STATUS_COMPLETED):
                return [
                    'status' => true,
                    'message' => (($progress['inserted'] + $progress['updated']) === 0)
                        ? lang('backend/components/import.messages.importationNoRecordsModified')
                        : sprintf(lang('backend/components/import.messages.importSuccess'), $progress['inserted'], $progress['updated']),
                    'isFinished' => true,
                    'inserted' => 0,
                    'updated' => 0,
                    'totalInserted' => $progress['inserted'],
                    'totalUpdated' => $progress['updated'],
                    'processed' => $progress['processed'],
                ];
            endif;

            if ($status === self::STATUS_FAILED):
                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => (bool) ($manifest['failure']['recoveryRequired'] ?? false),
                ];
            endif;

            /* Nessuna scrittura è consentita prima del backup preventivo. */
            if ( ! in_array($status, [self::STATUS_READY, self::STATUS_PROCESSING], true)):
                return ['status' => false, 'message' => lang('backend/components/import.messages.backupError')];
            endif;

            if ( ! $this->isImportBackupValid($importId, $manifest, $status === self::STATUS_READY)):
                $recoveryRequired = (int) ($progress['processed'] ?? 0) > 0;
                $this->markImportFailed($importId, $manifest, 'Preventive backup is missing or no longer intact.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.backupError'),
                    'recoveryRequired' => $recoveryRequired,
                ];
            endif;

            $handle = fopen($filePath, 'r');

            if ($handle === false):
                return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
            endif;

            /* L'header viene sempre verificato rispetto a manifest e schema corrente. */
            $headerRow = fgetcsv($handle, 0, ',');

            if ($headerRow === false):
                $this->markImportFailed($importId, $manifest, 'Staging data is missing the header row.');
                return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
            endif;

            $headers = array_map(static function($header): string {
                $normalized = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
                return trim($normalized ?? '');
            }, $headerRow);

            $tableStructure = $this->getTableStructure($entity);
            $importStructure = $this->getImportTableStructure($entity, $mode);
            $targetModel = $mode === self::IMPORT_MODE_CRUD ? $this->getImportValidationProvider($entity) : null;

            if ($tableStructure === [] || $importStructure === [] || ($mode === self::IMPORT_MODE_CRUD && $targetModel === null)):
                $this->markImportFailed($importId, $manifest, 'Import schema is no longer available or authorized.');
                return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
            endif;

            $primaryKey = null;
            $primaryKeyConfig = null;
            $tableColumns = [];
            $tableStructureMap = [];

            foreach ($tableStructure as $col):
                $tableColumns[] = $col['name'];
                $tableStructureMap[$col['name']] = $col;
            endforeach;

            foreach ($importStructure as $col):
                if ((int) ($col['primary_key'] ?? 0) === 1):
                    $primaryKey = $col['name'];
                    $primaryKeyConfig = $tableStructureMap[$primaryKey] ?? $col;
                    break;
                endif;
            endforeach;

            if ( ! $this->validateStagingDefinition($headers, $manifest, $importStructure, $primaryKey)):
                $this->markImportFailed($importId, $manifest, 'Staging definition is inconsistent with the manifest or the current authorized schema.');
                return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
            endif;

            $cursor = $progress['cursor'];

            if ($cursor < 0 || fseek($handle, $cursor) !== 0):
                $this->markImportFailed($importId, $manifest, 'Invalid staging cursor.');
                return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
            endif;

            $hasCreatedAt = in_array('created_at', $tableColumns, true);
            $hasUpdatedAt = in_array('updated_at', $tableColumns, true);

            /*
             * La preview non è un'autorizzazione permanente alla scrittura: prima di ogni chunk
             * ricostruiamo le regole sullo schema corrente e le rieseguiamo riga per riga.
             */
            $csvHeaders = array_values(array_filter(
                $headers,
                static fn(string $header): bool => ! in_array($header, ['__import_action', '__import_snapshot'], true)
            ));
            $csvHeaderMap = array_flip($csvHeaders);
            $fallbackInsertRules = $this->buildDynamicRules($importStructure, 'insert', $mode);
            $fallbackUpdateRules = array_intersect_key(
                $this->buildDynamicRules($importStructure, 'update', $mode),
                $csvHeaderMap
            );
            $validator = \Config\Services::validation();

            if ($this->db->transBegin() === false):
                $this->markImportFailed($importId, $manifest, 'Unable to start the chunk transaction.');
                return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
            endif;

            $transactionStarted = true;
            $inserted = 0;
            $updated = 0;
            $processedInChunk = 0;
            $isFinished = false;

            while ($processedInChunk < self::IMPORT_CHUNK_SIZE):
                $row = fgetcsv($handle, 0, ',');

                if ($row === false):
                    $isFinished = true;
                    break;
                endif;

                $nextCursor = ftell($handle);

                if ($nextCursor === false):
                    $this->db->transRollback();
                    $transactionStarted = false;
                    $this->markImportFailed($importId, $manifest, 'Unable to determine the staging file cursor.');
                    return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                endif;

                $cursor = $nextCursor;

                /* Le righe vuote avanzano il cursore ma non consumano quota del chunk. */
                if ($this->isCsvRowEmpty($row)):
                    continue;
                endif;

                if (count($row) !== count($headers)):
                    $this->db->transRollback();
                    $transactionStarted = false;
                    $this->markImportFailed($importId, $manifest, 'Inconsistent column count in staging data.');
                    return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                endif;

                $data = array_combine($headers, $row);

                $action = $data['__import_action'] ?? null;
                $expectedSnapshot = $data['__import_snapshot'] ?? null;

                if ( ! is_string($action) || ! in_array($action, ['insert', 'update'], true)):
                    $this->db->transRollback();
                    $transactionStarted = false;
                    $this->markImportFailed($importId, $manifest, 'Invalid __import_action directive.');
                    return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                endif;

                unset($data['__import_action'], $data['__import_snapshot']);

                foreach ($data as $key => $value):
                    if (trim((string) $value) === '' || strtolower(trim((string) $value)) === 'null'):
                        $data[$key] = null;
                    endif;
                endforeach;

                $idValue = $data[$primaryKey] ?? null;
                $hasPrimaryKeyValue = $idValue !== null && trim((string) $idValue) !== '';

                if ($action === 'update' && ! $hasPrimaryKeyValue):
                    $this->db->transRollback();
                    $transactionStarted = false;
                    $this->markImportFailed($importId, $manifest, 'Update is missing the Primary Key.');
                    return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                endif;

                /* Ri-validazione applicativa/strutturale immediatamente prima della scrittura. */
                $validator->reset();
                $rules = $action === 'insert' ? $fallbackInsertRules : $fallbackUpdateRules;

                if ($targetModel !== null):
                    if ($action === 'insert'):
                        $specificRules = array_intersect_key(
                            $targetModel->importAddValidationRules(),
                            array_flip(array_column($importStructure, 'name'))
                        );
                    else:
                        $specificRules = array_intersect_key(
                            $targetModel->importEditValidationRules($data),
                            $csvHeaderMap
                        );
                    endif;

                    $rules = array_replace($rules, $specificRules);
                endif;

                if ($rules !== []):
                    $validator->setRules($rules);

                    if ( ! $validator->run($data)):
                        $validationErrors = $validator->getErrors();
                        $firstValidationError = $validationErrors !== []
                            ? (string) reset($validationErrors)
                            : lang('backend/components/import.messages.importationUndone');

                        $this->db->transRollback();
                        $transactionStarted = false;
                        $this->markImportFailed($importId, $manifest, 'Validation failed during execution: ' . implode(' | ', $validationErrors));

                        return [
                            'status' => false,
                            'message' => $firstValidationError,
                            'recoveryRequired' => $progress['processed'] > 0,
                        ];
                    endif;
                endif;

                if ($action === 'update'):
                    if ( ! is_string($expectedSnapshot) || preg_match('/^[a-f0-9]{64}$/', $expectedSnapshot) !== 1):
                        $this->db->transRollback();
                        $transactionStarted = false;
                        $this->markImportFailed($importId, $manifest, 'Concurrency snapshot is missing or invalid.');
                        return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                    endif;

                    /* Usa esattamente le colonne viste in preview, prima di modificare timestamp o payload SQL. */
                    $snapshotColumns = array_values(array_filter(
                        array_keys($data),
                        static fn(string $column): bool => ! (
                            $mode === self::IMPORT_MODE_CRUD
                            && in_array($column, ['created_at', 'updated_at'], true)
                        )
                    ));

                    $currentRowBuilder = $this->db->table($entity)->where($primaryKey, $idValue);

                    if ($mode === self::IMPORT_MODE_DATABASE):
                        $currentRowBuilder->select(array_unique(array_merge([$primaryKey], $snapshotColumns)));
                    endif;

                    $currentRow = $currentRowBuilder->get()->getRowArray();

                    if ($currentRow === null || $this->buildRowSnapshot($currentRow, $snapshotColumns, $mode) !== $expectedSnapshot):
                        $this->db->transRollback();
                        $transactionStarted = false;
                        $this->markImportFailed($importId, $manifest, 'Concurrent conflict detected between preview and update.');

                        return [
                            'status' => false,
                            'message' => lang('backend/components/import.messages.importationUndone'),
                            'recoveryRequired' => $progress['processed'] > 0,
                        ];
                    endif;

                    if ($targetModel !== null):
                        $businessErrors = $targetModel->importBusinessValidationErrors('update', $data, $currentRow);

                        if ($businessErrors !== []):
                            $this->db->transRollback();
                            $transactionStarted = false;
                            $this->markImportFailed($importId, $manifest, 'CRUD domain constraint violation: ' . implode(' | ', $businessErrors));

                            return [
                                'status' => false,
                                'message' => $businessErrors[0],
                                'recoveryRequired' => $progress['processed'] > 0,
                            ];
                        endif;
                    endif;
                endif;

                if ($mode === self::IMPORT_MODE_CRUD):
                    $now = date('Y-m-d H:i:s');

                    if ($action === 'insert'):
                        if ($hasCreatedAt && empty($data['created_at'])):
                            $data['created_at'] = $now;
                        endif;
                    elseif ($hasUpdatedAt):
                        $data['updated_at'] = $now;
                    endif;
                endif;

                if ($action === 'update'):
                    /* La Primary Key identifica il record e non viene mai riscritta. */
                    unset($data[$primaryKey]);

                    /* Nel CRUD created_at è metadato applicativo immutabile; Tools/database rispetta invece il CSV. */
                    if ($mode === self::IMPORT_MODE_CRUD):
                        unset($data['created_at']);
                    endif;
                endif;

                if ($action === 'update'):
                    $queryResult = $this->db->table($entity)->where($primaryKey, $idValue)->update($data);

                    if ($queryResult === false):
                        $this->db->transRollback();
                        $transactionStarted = false;
                        $this->markImportFailed($importId, $manifest, 'SQL error during update.');
                        return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
                    endif;

                    $updated++;
                else:
                    if ($targetModel !== null):
                        $businessErrors = $targetModel->importBusinessValidationErrors('insert', $data, null);

                        if ($businessErrors !== []):
                            $this->db->transRollback();
                            $transactionStarted = false;
                            $this->markImportFailed($importId, $manifest, 'CRUD domain constraint violation: ' . implode(' | ', $businessErrors));

                            return [
                                'status' => false,
                                'message' => $businessErrors[0],
                                'recoveryRequired' => $progress['processed'] > 0,
                            ];
                        endif;
                    endif;

                    if ($hasPrimaryKeyValue):
                        $alreadyExists = $this->db->table($entity)->where($primaryKey, $idValue)->countAllResults() > 0;

                        if ($alreadyExists):
                            $this->db->transRollback();
                            $transactionStarted = false;
                            $this->markImportFailed($importId, $manifest, 'Concurrent conflict detected: the Primary Key expected for insert already exists.');

                            return [
                                'status' => false,
                                'message' => lang('backend/components/import.messages.importationUndone'),
                                'recoveryRequired' => $progress['processed'] > 0,
                            ];
                        endif;
                    endif;

                    if ( ! $hasPrimaryKeyValue):
                        if (($primaryKeyConfig['auto_increment'] ?? false) === true):
                            unset($data[$primaryKey]);
                        elseif ($primaryKeyConfig !== null && $this->isUuidPrimaryKey($primaryKeyConfig)):
                            $data[$primaryKey] = $this->generateUUID();
                        else:
                            $this->db->transRollback();
                            $transactionStarted = false;
                            $this->markImportFailed($importId, $manifest, 'Insert is missing a generatable Primary Key.');
                            return ['status' => false, 'message' => lang('backend/components/import.messages.importationUndone')];
                        endif;
                    endif;

                    /* Lascia al DB l'applicazione dei DEFAULT sulle colonne NOT NULL vuote. */
                    foreach ($data as $fieldName => $value):
                        $fieldConfig = $tableStructureMap[$fieldName] ?? null;

                        if (
                            $value === null
                            && $fieldConfig !== null
                            && ($fieldConfig['nullable'] ?? true) === false
                            && ($fieldConfig['default'] ?? null) !== null
                        ):
                            unset($data[$fieldName]);
                        endif;
                    endforeach;

                    $queryResult = $this->db->table($entity)->insert($data);

                    if ($queryResult === false):
                        $this->db->transRollback();
                        $transactionStarted = false;
                        $this->markImportFailed($importId, $manifest, 'SQL error during insert.');
                        return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
                    endif;

                    $inserted++;
                endif;

                $processedInChunk++;
            endwhile;

            $totalInserted = $progress['inserted'] + $inserted;
            $totalUpdated = $progress['updated'] + $updated;
            $totalProcessed = $progress['processed'] + $processedInChunk;

            /* A EOF il numero delle operazioni deve coincidere con il piano prodotto dalla fase di validazione. */
            if (
                $isFinished
                && (
                    $totalInserted !== (int) ($manifest['plan']['insert'] ?? -1)
                    || $totalUpdated !== (int) ($manifest['plan']['update'] ?? -1)
                )
            ):
                $this->db->transRollback();
                $transactionStarted = false;
                $this->markImportFailed($importId, $manifest, 'The number of executed operations does not match the staging plan.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => $progress['processed'] > 0,
                ];
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                $transactionStarted = false;
                $this->markImportFailed($importId, $manifest, 'The chunk transaction reported an error state.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => $progress['processed'] > 0,
                ];
            endif;

            /*
             * Journal write-ahead: da questo punto fino all'aggiornamento del manifest un arresto anomalo
             * rende ambiguo l'esito del chunk. La presenza del file bloccherà quindi qualsiasi retry cieco.
             */
            if ( ! $this->writeChunkJournal($importId, $progress)):
                $this->db->transRollback();
                $transactionStarted = false;
                $this->markImportFailed($importId, $manifest, 'Unable to create the chunk journal before commit.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => $progress['processed'] > 0,
                ];
            endif;

            if ($this->db->transCommit() === false):
                $transactionStarted = false;
                $this->writeRecoveryFlag($importId, 'The chunk commit outcome cannot be determined with certainty.');
                $this->markImportFailed($importId, $manifest, 'The chunk commit failed or its outcome is ambiguous.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => true,
                ];
            endif;

            $transactionStarted = false;

            /* Il DB è ormai committed: da questo punto un errore di persistenza del manifest richiede recovery. */
            $manifest['progress'] = [
                'cursor' => $cursor,
                'processed' => $totalProcessed,
                'inserted' => $totalInserted,
                'updated' => $totalUpdated,
            ];
            $manifest['status'] = $isFinished ? self::STATUS_COMPLETED : self::STATUS_PROCESSING;
            $manifest['failure'] = null;
            $manifest['expiresAt'] = time() + self::STAGING_TTL;

            if ($isFinished):
                $manifest['completedAt'] = time();
            endif;

            if ( ! $this->persistStagingManifest($importId, $manifest)):
                $this->writeRecoveryFlag($importId, 'Database committed, but manifest progress was not persisted.');
                log_message('critical', 'ImportModel: recovery required for importId ' . $importId . ' after database commit without manifest update.');

                return [
                    'status' => false,
                    'message' => lang('backend/components/import.messages.importTransactionError'),
                    'recoveryRequired' => true,
                ];
            endif;

            /* Manifest e database sono nuovamente sincronizzati: il journal può essere rimosso. */
            $this->deleteChunkJournal($importId);

            if ($isFinished):
                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity(
                    'IMPORT_DATA',
                    $entity,
                    sprintf(
                        lang('backend/components/import.messages.importSuccess'),
                        $entity,
                        $totalInserted,
                        $totalUpdated
                    ),
                    $currentAdmin
                );
            endif;

            return [
                'status' => true,
                'message' => $isFinished
                    ? (($totalInserted + $totalUpdated) === 0
                        ? lang('backend/components/import.messages.importationNoRecordsModified')
                        : sprintf(lang('backend/components/import.messages.importSuccess'), $totalInserted, $totalUpdated))
                    : '',
                'isFinished' => $isFinished,
                'inserted' => $inserted,
                'updated' => $updated,
                'totalInserted' => $totalInserted,
                'totalUpdated' => $totalUpdated,
                'processed' => $totalProcessed,
            ];
        } catch (\Throwable $e) {
            if ($transactionStarted):
                $this->db->transRollback();
            endif;

            if ($manifest !== []):
                $this->markImportFailed($importId, $manifest, 'Exception during executeImport: ' . $e->getMessage());
            endif;

            log_message(
                'error',
                'ImportModel executeImport error [' . $importId . ']: ' . $e->getMessage()
                . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine()
            );

            $progress = $manifest['progress'] ?? [];

            return [
                'status' => false,
                'message' => lang('backend/components/import.messages.importTransactionError'),
                'recoveryRequired' => (int) ($progress['processed'] ?? 0) > 0,
            ];
        } finally {
            if (is_resource($handle)):
                fclose($handle);
            endif;

            $this->releaseImportLock($lockHandle);
        }
    }

    /**
     * Traduce lo schema fisico del database in un set di regole di validazione (Fallback Rules).
     * 
     * Funzione cruciale che entra in azione quando il modulo non ha regole scritte a mano nel Model. 
     * Legge i metadati estratti da `getTableStructure` e mappa i tipi SQL nelle rispettive regole di CodeIgniter: 
     * es. `varchar(255)` diventa `string|max_length[255]`, `int` diventa `integer`, `datetime` diventa `valid_date`.
     * Garantisce che l'importazione rispetti i limiti stringenti del database prevenendo eccezioni PDO.
     *
     * @param array $structure Lo schema delle colonne generato da getTableStructure
     * @param string $action Operazione pianificata per la riga: insert oppure update
     * @return array Dizionario di regole di validazione nel formato nativo di CodeIgniter
     */
    protected function buildDynamicRules(array $structure, string $action, string $mode): array
    {
        $rules = [];

        foreach ($structure as $field):
            $rule = [];
            $type = strtolower($field['type']);
            $isPrimaryKey = (int) $field['primary_key'] === 1;
            $isGeneratedPrimaryKey = $isPrimaryKey
                && (($field['auto_increment'] ?? false) === true || $this->isUuidPrimaryKey($field));
            $isCreatedAt = $field['name'] === 'created_at';
            $isUpdatedAt = $field['name'] === 'updated_at';
            $isManagedCreatedAt = $mode === self::IMPORT_MODE_CRUD && $isCreatedAt;
            $isManagedUpdatedAt = $mode === self::IMPORT_MODE_CRUD && $isUpdatedAt;
            $hasDatabaseDefault = ($field['default'] ?? null) !== null;
            $isNullable = ($field['nullable'] ?? true) === true;

            /*
             * INSERT:
             * - nullable, PK generate e colonne con DEFAULT SQL possono essere vuote;
             * - created_at è generato dal motore di importazione;
             * - le altre colonne NOT NULL sono obbligatorie.
             *
             * UPDATE:
             * - created_at viene ignorato e updated_at viene rigenerato;
             * - una colonna NOT NULL presente nel CSV non può essere svuotata.
             */
            if ($action === 'insert'):
                $canBeEmpty = $isNullable || $isGeneratedPrimaryKey || $hasDatabaseDefault || $isManagedCreatedAt;
            else:
                $canBeEmpty = $isNullable || $isManagedCreatedAt || $isManagedUpdatedAt;
            endif;

            $rule[] = $canBeEmpty ? 'permit_empty' : 'required';

            /* In update la primary key identifica sempre un record esistente e non può essere vuota. */
            if ($action === 'update' && $isPrimaryKey):
                $rule[0] = 'required';
            endif;

            /* Mappatura tipi numerici interi */
            if (strpos($type, 'int') !== false):
                $rule[] = 'integer';

            /* Mappatura tipi numerici con virgola mobile e decimali */
            elseif (in_array($type, ['float', 'double', 'decimal', 'numeric'], true)):
                $rule[] = 'numeric';

            /* Mappatura formati stringa e testo */
            elseif (in_array($type, ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true)):
                $rule[] = 'string';

            /* Mappatura date e orari */
            elseif ($type === 'date'):
                $rule[] = 'valid_date[Y-m-d]';
            elseif ($type === 'datetime' || $type === 'timestamp'):
                $rule[] = 'valid_date[Y-m-d H:i:s]';
            endif;

            /* max_length è semanticamente applicabile alle stringhe a lunghezza dichiarata. */
            if ( ! empty($field['max_length']) && in_array($type, ['varchar', 'char'], true)):
                $rule[] = 'max_length[' . $field['max_length'] . ']';
            endif;

            $rules[$field['name']] = implode('|', $rule);
        endforeach;

        return $rules;
    }

    /**
     * Determina se una primary key testuale può essere generata dal motore di importazione come UUID.
     *
     * @param array $field Metadati della colonna estratti da getTableStructure()
     * @return bool True per PK CHAR/VARCHAR con spazio sufficiente a contenere un UUID standard
     */
    private function isUuidPrimaryKey(array $field): bool
    {
        if ((int) ($field['primary_key'] ?? 0) !== 1):
            return false;
        endif;

        $name = strtolower((string) ($field['name'] ?? ''));
        $type = strtolower((string) ($field['type'] ?? ''));
        $maxLength = (int) ($field['max_length'] ?? 0);
        $isUuidNamed = $name === 'uuid' || str_ends_with($name, '_uuid');

        return $isUuidNamed && in_array($type, ['char', 'varchar'], true) && $maxLength >= 36;
    }

}
