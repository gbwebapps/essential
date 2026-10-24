<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Libraries;

use Modules\Backend\Warehouse\Config\Package;
use Modules\Backend\Warehouse\Models\WarehouseModel;

/**
 * Applica al pacchetto XLSX il protocollo stateful del componente Export.
 * Lo snapshot è unico per le cinque tabelle; cursore, file e colonne restano server-side.
 */
class WarehouseXlsxExport
{
    private const VERSION = 1;
    private const DIRECTORY = 'exports/warehouse-jobs/';
    private const TTL = 3600;

    /** Associa lettura coerente del pacchetto e generazione XLSX. */
    public function __construct(private readonly WarehouseModel $model, private readonly WarehouseXlsx $writer)
    {
    }

    /** Avvia o continua la preparazione di cinque record per richiesta. */
    public function generate(?string $exportId = null): array
    {
        $exportId ??= $this->create();
        $lock = $this->lock($exportId);

        try
        {
            $manifest = $this->manifest($exportId);
            $directory = $this->directory($exportId);

            if ($manifest['status'] === 'completed'):
                return $this->response($manifest, true);
            endif;

            $snapshot = $this->snapshot($exportId, $manifest);

            if ($manifest['processed'] < $manifest['total']):
                $this->appendChunk($exportId, $manifest, $snapshot);
                return $this->response($manifest, false);
            endif;

            /* Il formato XLSX viene chiuso solo dopo aver preparato tutte le righe. */
            $package = $this->readPreparedPackage($exportId, $manifest, $snapshot);
            $path = $directory . 'package.xlsx';
            $written = $this->writer->writePackage($package, $path . '.tmp');

            if ($written !== $manifest['total'] || ! rename($path . '.tmp', $path)):
                throw new \RuntimeException('Finalizzazione export Warehouse non riuscita.');
            endif;

            $manifest['status'] = 'completed';
            $manifest['fileHash'] = $this->hash($path);
            $manifest['fileSize'] = filesize($path);
            $manifest['expiresAt'] = time() + self::TTL;
            $this->persist($directory . 'manifest.json', $manifest);

            return $this->response($manifest, false);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Export Warehouse ' . $exportId . ': ' . $e->getMessage());
            return ['result' => false, 'exportId' => $exportId, 'message' => lang('backend/warehouse.messages.exportError')];
        }
        finally
        {
            $this->unlock($lock);
        }
    }

    /** Risolve soltanto un XLSX completato, integro e appartenente alla sessione corrente. */
    public function getDownloadFile(string $exportId): ?array
    {
        try
        {
            $manifest = $this->manifest($exportId);
            $path = $this->directory($exportId) . 'package.xlsx';

            if ($manifest['status'] !== 'completed' || ! is_file($path)
                || filesize($path) !== ($manifest['fileSize'] ?? null)
                || ! hash_equals((string) ($manifest['fileHash'] ?? ''), $this->hash($path))):
                return null;
            endif;

            return ['path' => $path, 'fileName' => $manifest['fileName']];
        }
        catch (\Throwable)
        {
            return null;
        }
    }

    /** Annulla un export autorizzato aspettando l'eventuale chunk in scrittura. */
    public function delete(string $exportId): bool
    {
        $lock = $this->lock($exportId, true);
        $directory = $this->directory($exportId);

        try
        {
            $this->manifest($exportId, true);

            foreach (['snapshot.json', 'snapshot.json.tmp', 'prepared.jsonl', 'package.xlsx', 'package.xlsx.tmp', 'manifest.json.tmp', 'manifest.json'] as $file):
                if (is_file($directory . $file) && ! unlink($directory . $file)):
                    return false;
                endif;
            endforeach;
        }
        finally
        {
            $this->unlock($lock);
        }

        return unlink($directory . 'export.lock') && rmdir($directory);
    }

    /** Congela dati e intestazioni senza date tecniche prima di iniziare i chunk. */
    private function create(): string
    {
        $owner = $this->owner();

        if ($owner === null):
            throw new \DomainException(lang('backend/warehouse.messages.sessionUnavailable'));
        endif;

        $package = $this->model->getPackageData();
        $total = array_sum(array_map(static fn(array $sheet): int => count($sheet['rows']), $package));

        if ($total === 0):
            throw new \DomainException(lang('backend/components/export.messages.noDataFound'));
        endif;

        $exportId = bin2hex(random_bytes(32));
        $directory = $this->directory($exportId);

        if ( ! mkdir($directory, 0750, true) && ! is_dir($directory)):
            throw new \RuntimeException('Staging export Warehouse non disponibile.');
        endif;

        $this->persist($directory . 'snapshot.json', $package);

        if (file_put_contents($directory . 'prepared.jsonl', '', LOCK_EX) !== 0):
            throw new \RuntimeException('Preparazione export Warehouse non riuscita.');
        endif;

        $this->persist($directory . 'manifest.json', [
            'version' => self::VERSION, 'exportId' => $exportId, 'owner' => $owner,
            'status' => 'processing', 'processed' => 0, 'total' => $total,
            'fileName' => 'warehouse_' . date('Y-m-d_H-i-s') . '.xlsx',
            'sourceHash' => $this->hash($directory . 'snapshot.json'),
            'preparedSize' => 0, 'preparedHash' => hash('sha256', ''),
            'createdAt' => time(), 'expiresAt' => time() + self::TTL,
        ]);

        return $exportId;
    }

    /** Appende al file di lavoro un massimo di cinque record, con rollback dei byte non confermati. */
    private function appendChunk(string $exportId, array &$manifest, array $snapshot): void
    {
        $directory = $this->directory($exportId);
        $path = $directory . 'prepared.jsonl';
        $file = fopen($path, 'c+b');

        if ($file === false):
            throw new \RuntimeException('File di preparazione Warehouse non disponibile.');
        endif;

        $expected = $manifest['preparedSize'];

        try
        {
            $size = fstat($file)['size'];

            if ($size < $expected || ($size > $expected && ! ftruncate($file, $expected))
                || ! hash_equals($manifest['preparedHash'], $this->hash($path)) || fseek($file, $expected) !== 0):
                throw new \RuntimeException('File di preparazione Warehouse non coerente con il cursore.');
            endif;

            $position = 0;
            $processed = 0;

            foreach ($snapshot as $table => $sheet):
                foreach ($sheet['rows'] as $record):
                    if ($position++ < $manifest['processed']):
                        continue;
                    endif;

                    $this->write($file, json_encode(['table' => $table, 'record' => $record], JSON_THROW_ON_ERROR) . "\n");
                    $processed++;

                    if ($processed === Package::CHUNK_SIZE):
                        break 2;
                    endif;
                endforeach;
            endforeach;

            if ($processed === 0 || ! fflush($file)):
                throw new \RuntimeException('Chunk export Warehouse non completato.');
            endif;

            $next = $manifest;
            $next['processed'] += $processed;
            $next['preparedSize'] = ftell($file);
            $next['preparedHash'] = $this->hash($path);
            $next['expiresAt'] = time() + self::TTL;
            $this->persist($directory . 'manifest.json', $next);
            $manifest = $next;
        }
        catch (\Throwable $e)
        {
            ftruncate($file, $expected);
            fflush($file);
            throw $e;
        }
        finally
        {
            fclose($file);
        }
    }

    /** Ricostruisce il workbook dalle righe realmente preparate, non da dati inviati dal browser. */
    private function readPreparedPackage(string $exportId, array $manifest, array $snapshot): array
    {
        $path = $this->directory($exportId) . 'prepared.jsonl';

        if (filesize($path) !== $manifest['preparedSize'] || ! hash_equals($manifest['preparedHash'], $this->hash($path))):
            throw new \RuntimeException('Integrità del pacchetto preparato non valida.');
        endif;

        $package = array_map(static fn(array $sheet): array => ['headers' => $sheet['headers'], 'rows' => []], $snapshot);
        $file = fopen($path, 'rb');

        if ($file === false):
            throw new \RuntimeException('Pacchetto preparato non disponibile.');
        endif;

        $total = 0;

        try
        {
            while (($line = fgets($file)) !== false):
                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                if ( ! isset($package[$row['table'] ?? '']) || ! is_array($row['record'] ?? null)):
                    throw new \RuntimeException('Riga del pacchetto preparato non valida.');
                endif;

                $package[$row['table']]['rows'][] = $row['record'];
                $total++;
            endwhile;

            if ( ! feof($file) || $total !== $manifest['total']):
                throw new \RuntimeException('Pacchetto preparato incompleto.');
            endif;
        }
        finally
        {
            fclose($file);
        }

        return $package;
    }

    /** Verifica struttura e proprietà dello stato, senza accettare cursori client-side. */
    private function manifest(string $exportId, bool $allowExpired = false): array
    {
        $path = $this->directory($exportId) . 'manifest.json';
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : null;
        $owner = $this->owner();

        if ( ! is_array($manifest) || ($manifest['version'] ?? null) !== self::VERSION || ($manifest['exportId'] ?? null) !== $exportId
            || $owner === null || ! is_string($manifest['owner'] ?? null) || ! hash_equals($owner, $manifest['owner'])
            || ! in_array($manifest['status'] ?? null, ['processing', 'completed'], true)
            || ! is_int($manifest['processed'] ?? null) || ! is_int($manifest['total'] ?? null)
            || $manifest['processed'] < 0 || $manifest['total'] <= 0 || $manifest['processed'] > $manifest['total']
            || ! is_int($manifest['preparedSize'] ?? null) || $manifest['preparedSize'] < 0
            || ! is_int($manifest['expiresAt'] ?? null) || (! $allowExpired && $manifest['expiresAt'] < time())
            || ! is_string($manifest['fileName'] ?? null) || preg_match('/^warehouse_[0-9_-]+\.xlsx$/', $manifest['fileName']) !== 1):
            throw new \DomainException(lang('backend/components/export.messages.invalidEntity'));
        endif;

        foreach (['sourceHash', 'preparedHash'] as $field):
            if ( ! is_string($manifest[$field] ?? null) || preg_match('/^[a-f0-9]{64}$/', $manifest[$field]) !== 1):
                throw new \DomainException(lang('backend/components/export.messages.invalidEntity'));
            endif;
        endforeach;

        if ($manifest['status'] === 'completed'
            && ($manifest['processed'] !== $manifest['total'] || ! is_int($manifest['fileSize'] ?? null) || $manifest['fileSize'] <= 0
                || ! is_string($manifest['fileHash'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $manifest['fileHash']) !== 1)):
            throw new \DomainException(lang('backend/components/export.messages.invalidEntity'));
        endif;

        return $manifest;
    }

    /** Controlla lo snapshot congelato e le colonne autorizzate per tutti i cinque fogli. */
    private function snapshot(string $exportId, array $manifest): array
    {
        $path = $this->directory($exportId) . 'snapshot.json';

        if ( ! hash_equals($manifest['sourceHash'], $this->hash($path))):
            throw new \RuntimeException('Snapshot export Warehouse alterato.');
        endif;

        $snapshot = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $template = $this->model->getPackageTemplate();

        if ( ! is_array($snapshot) || array_keys($snapshot) !== Package::TABLES):
            throw new \RuntimeException('Snapshot export Warehouse non valido.');
        endif;

        $total = 0;

        foreach ($snapshot as $table => $sheet):
            if (($sheet['headers'] ?? null) !== $template[$table]['headers'] || ! is_array($sheet['rows'] ?? null)):
                throw new \RuntimeException('Schema export Warehouse modificato.');
            endif;
            $total += count($sheet['rows']);
        endforeach;

        if ($total !== $manifest['total']):
            throw new \RuntimeException('Conteggio snapshot Warehouse non valido.');
        endif;

        return $snapshot;
    }

    /** Restituisce lo stesso contratto progresso/download del componente Export. */
    private function response(array $manifest, bool $alreadyCompleted): array
    {
        $finished = $manifest['status'] === 'completed';
        $response = [
            'result' => true, 'exportId' => $manifest['exportId'], 'isFinished' => $finished,
            'processedCount' => $manifest['processed'], 'alreadyCompleted' => $alreadyCompleted,
            'progressMessage' => sprintf(lang('backend/warehouse.messages.exportProgress'), $manifest['processed'], $manifest['total']),
        ];

        if ( ! $finished && $manifest['processed'] === $manifest['total']):
            $response['progressMessage'] = sprintf(lang('backend/warehouse.messages.exportReady'), $manifest['processed']);
        endif;

        if ($finished):
            $response['message'] = sprintf(lang('backend/warehouse.messages.exportSuccess'), $manifest['processed']);
            $response['downloadUrl'] = base_url('backend/warehouse/download/' . $manifest['exportId']);
        endif;

        return $response;
    }

    /** Pubblica JSON soltanto dopo la scrittura completa del file temporaneo. */
    private function persist(string $path, array $data): void
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($path . '.tmp', $json, LOCK_EX) !== strlen($json)):
            throw new \RuntimeException('Scrittura stato export Warehouse incompleta.');
        endif;

        if ( ! @rename($path . '.tmp', $path)):
            if (file_put_contents($path, $json, LOCK_EX) !== strlen($json)):
                throw new \RuntimeException('Pubblicazione stato export Warehouse non riuscita.');
            endif;
            unlink($path . '.tmp');
        endif;
    }

    /** Scrive integralmente una riga, gestendo le write parziali. */
    private function write($file, string $bytes): void
    {
        while ($bytes !== ''):
            $written = fwrite($file, $bytes);

            if ($written === false || $written === 0):
                throw new \RuntimeException('Scrittura chunk export Warehouse non riuscita.');
            endif;
            $bytes = substr($bytes, $written);
        endwhile;
    }

    /** Firma un artefatto privato esistente. */
    private function hash(string $path): string
    {
        $hash = is_file($path) ? hash_file('sha256', $path) : false;

        if ($hash === false):
            throw new \RuntimeException('Artefatto export Warehouse non disponibile.');
        endif;

        return $hash;
    }

    /** Acquisisce un lock relativo esclusivamente a una directory server-side già esistente. */
    private function lock(string $exportId, bool $blocking = false)
    {
        $directory = $this->directory($exportId);
        $lock = is_dir($directory) ? fopen($directory . 'export.lock', 'c+') : false;

        if ($lock === false || ! flock($lock, $blocking ? LOCK_EX : LOCK_EX | LOCK_NB)):
            if (is_resource($lock)):
                fclose($lock);
            endif;
            throw new \DomainException(lang('backend/warehouse.messages.exportBusy'));
        endif;

        return $lock;
    }

    /** Rilascia il lock anche quando il chunk termina con un errore. */
    private function unlock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /** Mantiene il percorso entro il perimetro Warehouse senza usare nomi forniti dall'utente. */
    private function directory(string $exportId): string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $exportId) !== 1):
            throw new \DomainException(lang('backend/components/export.messages.invalidEntity'));
        endif;

        return WRITEPATH . self::DIRECTORY . $exportId . DIRECTORY_SEPARATOR;
    }

    /** Calcola la proprietà del job senza conservare il token di sessione in chiaro. */
    private function owner(): ?string
    {
        $session = session()->get('backendSession');
        return is_string($session) && $session !== '' ? hash('sha256', $session) : null;
    }
}
