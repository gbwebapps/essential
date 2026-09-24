<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

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
    /**
     * Tenta di istanziare dinamicamente il modello specifico associato all'entità bersaglio.
     * 
     * Sfrutta il nome della tabella (es. 'admins') per cercare la classe corrispondente 
     * (es. 'App\Models\Backend\AdminsModel'). Questo permette al motore di importazione 
     * di recuperare e utilizzare le regole di validazione esplicite scritte dallo sviluppatore, 
     * ignorando i comportamenti di default se esiste una logica dedicata.
     *
     * @param string $entity Il nome della tabella/modulo (es. 'users', 'logs')
     * @return \CodeIgniter\Model|null L'istanza del modello se esiste, altrimenti null
     */
    private function getTargetModelInstance(string $entity)
    {
        $modelName = 'App\\Models\\Backend\\' . ucfirst($entity) . 'Model';
        
        if (class_exists($modelName)):
            return new $modelName();
        endif;
        
        return null;
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

            $structure[] = [
                'name' => $field->name,
                'type' => $field->type,
                'max_length' => $field->max_length,
                'primary_key' => $field->primary_key,
                'is_index' => $isIndex
            ];

        endforeach;

        return $structure;
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
     * @return array Struttura dati con l'esito, le statistiche dell'operazione (plan) e i primi record da mostrare in anteprima
     */
    public function parseAndValidateCsv(\CodeIgniter\HTTP\Files\UploadedFile $file, string $entity): array
    {
        $structure = $this->getTableStructure($entity);
        $expectedHeaders = array_column($structure, 'name');

        /* Trova la chiave primaria reale dallo schema */
        $primaryKey = null;
        foreach ($structure as $col):
            if ($col['primary_key'] == 1):
                $primaryKey = $col['name'];
                break;
            endif;
        endforeach;

        $plan = ['insert' => 0, 'update' => 0, 'skip' => 0];
        
        $handle = fopen($file->getTempName(), 'r');
        
        if ($handle === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        /* Lettura CSV con virgola e pulizia preventiva di spazi e BOM UTF-8 */
        $csvHeaders = array_map(function($header) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, fgetcsv($handle, 0, ','));

        /* Controllo validità colonne CSV (Minimo 2 colonne) */
        if (count($csvHeaders) < 2):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.insufficientColumns')];
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
        if ($primaryKey !== null && $this->db->tableExists($entity)):
            $pkIndex = array_search($primaryKey, $csvHeaders, true);
            if ($pkIndex !== false):
                $csvIds = [];
                while (($row = fgetcsv($handle, 0, ',')) !== false):
                    if (isset($row[$pkIndex]) && trim((string)$row[$pkIndex]) !== ''):
                        $csvIds[] = trim((string)$row[$pkIndex]);
                    endif;
                endwhile;

                rewind($handle);
                fgetcsv($handle, 0, ',');

                if ( ! empty($csvIds)):
                    $chunks = array_chunk(array_unique($csvIds), 1000);
                    foreach ($chunks as $chunk):
                        $dbRecords = $this->db->table($entity)->whereIn($primaryKey, $chunk)->get()->getResultArray();
                        foreach ($dbRecords as $record):
                            $existingDataMap[$record[$primaryKey]] = $record;
                        endforeach;
                    endforeach;
                endif;
            endif;
        endif;

        $stagingDir = WRITEPATH . 'uploads/staging/';
        if ( ! is_dir($stagingDir)):
            mkdir($stagingDir, 0755, true);
        endif;
        
        $tempFilename = $entity . '_staging_' . time() . '_' . bin2hex(random_bytes(4)) . '.csv';
        $stagingPath = $stagingDir . $tempFilename;
        $stagingHandle = fopen($stagingPath, 'w');
        
        $stagingHeaders = $csvHeaders;
        $stagingHeaders[] = '__import_action';
        fputcsv($stagingHandle, $stagingHeaders, ',');

        $rows = [];
        $errors = [];
        $lineNumber = 2;
        
        $targetModel = $this->getTargetModelInstance($entity);
        $validator = \Config\Services::validation();
        
        /* Genera le regole e le filtra mantenendo SOLO quelle delle colonne presenti nel CSV */
        $fallbackRules = array_intersect_key(
            $this->buildDynamicRules($structure), 
            array_flip($csvHeaders)
        );

        while (($row = fgetcsv($handle, 0, ',')) !== false):
            
            if ( ! array_filter($row)):
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
            $action = 'insert';
            
            /* NUOVA LOGICA: Tracciamento delle singole colonne modificate */
            $changedColumns = [];

            if ( ! empty($idValue) && array_key_exists($idValue, $existingDataMap)):
                $dbRow = $existingDataMap[$idValue];
                
                foreach ($data as $key => $value):
                    /* Confronto esatto: se c'è una differenza, salviamo il nome della colonna nell'array */
                    if ($key !== 'updated_at' && array_key_exists($key, $dbRow) && (string)$dbRow[$key] !== (string)$value):
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
            
            if ($targetModel !== null):
                $rules = empty($idValue) ? $targetModel->addValidationRules() : $targetModel->editValidationRules(['uuid' => $idValue]);
            else:
                $rules = $fallbackRules;
            endif;

            if ( ! empty($rules)):
                $rules = array_intersect_key($rules, array_flip($csvHeaders));
            endif;

            if ( ! empty($rules)):
                $validator->setRules($rules);
                if ( ! $validator->run($data)):
                    foreach ($validator->getErrors() as $field => $error):
                        $errors[] = "Riga {$lineNumber} ({$field}): {$error}";
                    endforeach;
                endif;
            endif;

            if (empty($errors)):
                $stagingData = $data;
                $stagingData['__import_action'] = $action;
                fputcsv($stagingHandle, array_values($stagingData), ',');

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
            if (file_exists($stagingPath)):
                unlink($stagingPath);
            endif;
            return ['status' => false, 'message' => lang('backend/components/import.messages.emptyFile') ?? 'Il file CSV non contiene dati validi da elaborare.'];
        endif;

        if ( ! empty($errors)):
            if (file_exists($stagingPath)):
                unlink($stagingPath);
            endif;

            $maxErrors = 500;
            $totalErrors = count($errors);
            
            if ($totalErrors > $maxErrors):
                $errors = array_slice($errors, 0, $maxErrors);
                $errors[] = '... e altri ' . ($totalErrors - $maxErrors) . ' errori non mostrati per limiti di memoria.';
            endif;

            return ['status' => false, 'validationErrors' => $errors];
        endif;

        if ($plan['insert'] === 0 && $plan['update'] === 0):
            if (file_exists($stagingPath)):
                unlink($stagingPath);
            endif;
        endif;

        return [
            'status'   => true,
            'headers'  => $csvHeaders,
            'rows'     => $rows,
            'tempFile' => $tempFilename,
            'plan'     => $plan
        ];
    }

    /**
     * Fase 2: Esecuzione materiale dell'importazione leggendo il file di Staging.
     * 
     * Opera in modalità asincrona (Chunking) e Transazionale. Scorre il file temporaneo 
     * leggendo la direttiva `__import_action` per decidere l'operazione SQL (Insert o Update).
     * Inietta in automatico i timestamp di sistema (`created_at` e `updated_at`), ignorando eventuali 
     * date fornite nel CSV per preservare l'integrità dello storico. Al termine del file, 
     * effettua il commit della transazione, cancella il file di staging e registra l'audit.
     *
     * @param string $entity Nome della tabella
     * @param string $tempFile Il nome del file CSV di staging generato dal metodo parseAndValidateCsv
     * @param int $offset L'indice della riga da cui partire per il blocco corrente
     * @return array Esito strutturato con il totale dei record inseriti/aggiornati e il flag di completamento
     */
    public function executeImport(string $entity, string $tempFile, int $offset = 0): array
    {
        /* FIX SICUREZZA: Prevenzione Directory Traversal */
        $tempFile = basename($tempFile);
        $filePath = WRITEPATH . 'uploads/staging/' . $tempFile;

        if ( ! file_exists($filePath)):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileNotFoundError')];
        endif;

        $handle = fopen($filePath, 'r');
        
        if ($handle === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;
        
        /* 
         * AVVIO TRANSAZIONE: 
         * Se un solo record fallisce, tutto viene annullato (Rollback) 
         */
        $this->db->transStart();

        /* Estrazione prima riga (nomi colonne, inclusa la colonna di sistema __import_action) */
        $headers = array_map(function($header) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, fgetcsv($handle, 0, ','));
        
        /* Recupero rigoroso della Primary Key e mappatura colonne dallo schema del database */
        $structure = $this->getTableStructure($entity);
        $primaryKey = null;
        $tableColumns = [];
        
        foreach ($structure as $col):
            $tableColumns[] = $col['name'];
            if ($col['primary_key'] == 1):
                $primaryKey = $col['name'];
            endif;
        endforeach;

        /* Prepariamo i flag booleani fuori dal ciclo per massimizzare le performance */
        $hasCreatedAt = in_array('created_at', $tableColumns, true);
        $hasUpdatedAt = in_array('updated_at', $tableColumns, true);

        if ($primaryKey === null):
            $this->db->transRollback();
            fclose($handle);
            return ['status' => false, 'message' => sprintf(lang('backend/components/import.messages.notDeterminedPrimaryKey'), $entity)];
        endif;

        $inserted = 0;
        $updated = 0;

        $chunkSize = 5;
        $currentRow = 0;
        $processedInChunk = 0;
        $isFinished = false;

        while (true):
            $row = fgetcsv($handle, 0, ',');

            /* Se fgetcsv restituisce false, il file è finito */
            if ($row === false):
                $isFinished = true;
                break;
            endif;

            $currentRow++;

            /* Salta le righe già elaborate nei blocchi precedenti */
            if ($currentRow <= $offset):
                continue;
            endif;

            /* Interrompe il ciclo se è stato raggiunto il limite di questo blocco */
            if ($processedInChunk >= $chunkSize):
                break;
            endif;
            
            /* Salta le righe totalmente vuote */
            if ( ! array_filter($row)):
                continue;
            endif;

            /* Controllo di integrità: il numero di colonne deve corrispondere all'header dello staging */
            if (count($row) !== count($headers)):
                $this->db->transRollback();
                fclose($handle);
                return ['status' => false, 'message' => lang('backend/components/messages.importationUndone')];
            endif;

            /* Combina intestazione e riga */
            $data = array_combine($headers, $row);
            
            /* Estrae l'azione dettata dallo staging e la rimuove dai dati da persistere */
            $action = $data['__import_action'] ?? 'insert';
            unset($data['__import_action']);
            
            /* 1. Pulizia dei dati: converte la stringa "null" o i campi vuoti nel vero null di PHP */
            foreach ($data as $key => $value):
                if (trim((string)$value) === '' || strtolower(trim((string)$value)) === 'null'):
                    $data[$key] = null;
                endif;
            endforeach;

            /* 2. FIX ENTERPRISE: INIEZIONE TIMESTAMP FORZATA IN BASE ALLO SCHEMA REALE */
            $now = date('Y-m-d H:i:s');

            if ($action === 'insert'):
                if ($hasCreatedAt && empty($data['created_at'])):
                    $data['created_at'] = $now;
                endif;
                
            elseif ($action === 'update'):
                if ($hasUpdatedAt):
                    $data['updated_at'] = $now; // Forza SEMPRE la data attuale, ignorando il CSV
                endif;
                
                /* Sicurezza: impediamo che un update alteri la data di creazione originale */
                if (array_key_exists('created_at', $data)):
                    unset($data['created_at']);
                endif;
            endif;
            
            $idValue = $data[$primaryKey] ?? null;

            /* 
             * ESECUZIONE CIECA E OTTIMIZZATA
             * Ci fidiamo ciecamente del file di staging, azzerando il carico di lettura
             */
            if ($action === 'update'):
                $this->db->table($entity)->where($primaryKey, $idValue)->update($data);
                $updated++;
            else:
                /* Insert: genera l'UUID in caso di assenza */
                if (empty($idValue)):
                    $data[$primaryKey] = $this->generateUUID();
                endif;
                
                $this->db->table($entity)->insert($data);
                $inserted++;
            endif;

            $processedInChunk++;

        endwhile;

        fclose($handle);

        /* CHIUSURA TRANSAZIONE */
        $this->db->transComplete();

        /* Verifica esito della transazione */
        if ($this->db->transStatus() === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
        endif;

        /* Operazioni finali di pulizia e logging SOLO se il file è concluso */
        if ($isFinished):

            /* Pulizia fisica del file temporaneo di staging */
            if (file_exists($filePath)):
                unlink($filePath);
            endif;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('IMPORT_DATA', $entity, sprintf(lang('Importazione dati nella tabella %s. Inseriti %d records, aggiornati %d records.'), $entity, $inserted, $updated), $currentAdmin);
        endif;

        /* Risposta finale aggregata per il controller */
        if (($inserted + $updated) === 0):
            return [
                'status' => true,
                'message' => lang('backend/components/import.messages.importationNoRecordsModified'),
                'nextOffset' => $offset + $processedInChunk,
                'isFinished' => $isFinished,
                'inserted' => $inserted,
                'updated' => $updated
            ];
        endif;

        return [
            'status' => true, 
            'message' => sprintf(lang('backend/components/import.messages.importSuccess'), $inserted, $updated), 
            'nextOffset' => $offset + $processedInChunk,
            'isFinished' => $isFinished,
            'inserted' => $inserted,
            'updated' => $updated
        ];
    }

    /**
     * Genera un backup SQL completo della tabella bersaglio prima di avviare l'importazione.
     * 
     * Implementa uno scudo contro sovrascritture accidentali o importazioni distruttive. 
     * Crea un file .sql contenente la struttura TRUNCATE e le query di INSERT relative a 
     * tutti i record attuali. Utilizza l'estrazione a blocchi (Limit/Offset) per non 
     * saturare la memoria RAM (Memory Leak) in caso di tabelle molto capienti.
     *
     * @param string $entity Nome della tabella da salvaguardare
     * @return bool True se il backup viene completato con successo (o se la tabella è vuota), false in caso di errore di scrittura
     */
    public function backupTableBeforeImport(string $entity): bool
    {
        $builder = $this->db->table($entity);
        $totalRows = $builder->countAllResults(false);

        /* Salta la creazione se la tabella è vuota */
        if ($totalRows === 0):
            return true;
        endif;

        $backupDir = WRITEPATH . 'backups/imports/';
        
        if ( ! is_dir($backupDir)):
            mkdir($backupDir, 0755, true);
        endif;

        $filename = $entity . '_backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . $filename;

        /* Creazione e intestazione file (sovrascrive se esiste) */
        $handle = fopen($filepath, 'w');
        if ($handle === false):
            return false;
        endif;

        fwrite($handle, "/* Backup tabella: {$entity} - Data: " . date('Y-m-d H:i:s') . " */\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "TRUNCATE TABLE `{$entity}`;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n\n");

        /* Chunking per prevenire Memory Leak */
        $chunkSize = 1000;
        $offset = 0;

        while ($offset < $totalRows):
            $chunk = $builder->limit($chunkSize, $offset)->get()->getResultArray();
            
            foreach ($chunk as $row):
                $escapedValues = array_map([$this->db, 'escape'], array_values($row));
                $columns = implode('`, `', array_keys($row));
                $values = implode(', ', $escapedValues);
                
                fwrite($handle, "INSERT INTO `{$entity}` (`{$columns}`) VALUES ({$values});\n");
            endforeach;
            
            $offset += $chunkSize;
        endwhile;

        fclose($handle);
        return true;
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
     * @return array Dizionario di regole di validazione nel formato nativo di CodeIgniter
     */
    protected function buildDynamicRules(array $structure): array
    {
        $rules = [];

        foreach ($structure as $field):
            $rule = [];
            $type = strtolower($field['type']);

            /* 
             * Manteniamo permit_empty per consentire la creazione di nuovi record 
             * (con ID vuoto nel CSV che verrà generato dopo la validazione).
             * Se vuoi forzare l'esistenza dell'ID, cambia in 'required'.
             */
            $rule[] = 'permit_empty';

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

            /* Aggiunge max_length ignorando i campi in cui non è applicabile (es. text, date, timestamp) */
            if ( ! empty($field['max_length']) && strpos($type, 'text') === false && strpos($type, 'date') === false && $type !== 'timestamp'):
                $rule[] = 'max_length[' . $field['max_length'] . ']';
            endif;

            $rules[$field['name']] = implode('|', $rule);
        endforeach;

        return $rules;
    }
}