<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Libraries\ImageFileSystemService;

use App\Models\Backend\BackendModel;

class ImportModel extends BackendModel 
{
	private function getTargetModelInstance(string $entity)
    {
        $modelName = 'App\\Models\\Backend\\' . ucfirst($entity) . 'Model';
        
        if (class_exists($modelName)):
            return new $modelName();
        endif;
        
        return null;
    }

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

    public function parseAndValidateCsv($file, string $entity): array
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

        /* Carica i dati esistenti in memoria per un confronto O(1) velocissimo */
        $existingDataMap = [];
        if ($primaryKey !== null && $this->db->tableExists($entity)) {
            $dbRecords = $this->db->table($entity)->get()->getResultArray();
            foreach ($dbRecords as $record) {
                $existingDataMap[$record[$primaryKey]] = $record;
            }
        }

        $plan = ['insert' => 0, 'update' => 0, 'skip' => 0];
        
        $handle = fopen($file->getTempName(), 'r');
        
        if ($handle === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        /* Lettura CSV con virgola (,) e pulizia preventiva di spazi e BOM UTF-8 */
        $csvHeaders = array_map(function($header) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, fgetcsv($handle, 0, ','));

        /* Controllo corrispondenza colonne */
        if ($csvHeaders !== $expectedHeaders):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.headerMismatch')];
        endif;

        $rows = [];
        $errors = [];
        $lineNumber = 2; /* La riga 1 è occupata dalle intestazioni */

        while (($row = fgetcsv($handle, 0, ',')) !== false):
            
            /* Salta le righe totalmente vuote */
            if ( ! array_filter($row)):
                $lineNumber++;
                continue;
            endif;

            /* Controllo di integrità: previene il fatal error di array_combine */
            if (count($row) !== count($csvHeaders)):
                $errors[] = sprintf(lang('backend/components/import.messages.wrongColumnsNumber'), $lineNumber, count($row), count($csvHeaders));
                $lineNumber++;
                continue;
            endif;

            $data = array_combine($csvHeaders, $row);

            /* Pulizia dei dati: converte "null" o stringhe vuote in null nativo */
            foreach ($data as $key => $value):
                if (trim((string)$value) === '' || strtolower(trim((string)$value)) === 'null'):
                    $data[$key] = null;
                endif;
            endforeach;

            /* --- INIZIO MODIFICA: CALCOLO STATO RECORD (Insert, Update, Skip) --- */
            $idValue = $data[$primaryKey] ?? null;

            if ( ! empty($idValue) && array_key_exists($idValue, $existingDataMap)):
                $dbRow = $existingDataMap[$idValue];
                $hasChanges = false;
                foreach ($data as $key => $value):
                    if ($key !== 'updated_at' && array_key_exists($key, $dbRow) && (string)$dbRow[$key] !== (string)$value):
                        $hasChanges = true;
                        break;
                    endif;
                endforeach;

                if ($hasChanges):
                    $plan['update']++;
                else:
                    $plan['skip']++;
                endif;
            else:
                $plan['insert']++;
            endif;
            /* --- FINE MODIFICA --- */

            /* Conserva solo i primi 10 record puliti per l'anteprima nella vista */
            if (count($rows) < 10):
                $rows[] = array_values($data);
            endif;

            /* 
             * VALIDAZIONE IBRIDA: CRUD Model o Fallback Dinamico
             */
            $primaryKey = $csvHeaders[0];
            $idValue = $data[$primaryKey] ?? null;
            
            $targetModel = $this->getTargetModelInstance($entity);
            $validator = \Config\Services::validation();
            
            /* Svuota i dati e gli errori del ciclo precedente */
            $validator->reset(); 
            
            if ($targetModel !== null):
                /* 1. Esiste il CRUD: usiamo le sue regole specifiche */
                if (empty($idValue)):
                    $rules = $targetModel->addValidationRules();
                else:
                    $rules = $targetModel->editValidationRules(['uuid' => $idValue]);
                endif;
            else:
                /* 2. Nessun CRUD (es. tabelle pivot/tools): usiamo le regole universali del DB */
                $rules = $this->buildDynamicRules($structure);
            endif;

            if ( ! empty($rules)):
                $validator->setRules($rules);

                /* Esegue la validazione sui dati della riga corrente */
                if ( ! $validator->run($data)):
                    foreach ($validator->getErrors() as $field => $error):
                        $errors[] = "Riga {$lineNumber} ({$field}): {$error}";
                    endforeach;
                endif;
            endif;

            $lineNumber++;
        endwhile;

        fclose($handle);

        /* Verifica che ci sia almeno una riga di dati validi */
        if (empty($rows)):
            return ['status' => false, 'message' => lang('backend/components/import.messages.noDataFound')];
        endif;

        /* Se sono emersi errori durante il ciclo, blocca tutto e restituisci il report */
        if ( ! empty($errors)):
            $errorMessage = implode('<br>', $errors);
            return ['status' => false, 'message' => $errorMessage];
        endif;

        $csvDir = WRITEPATH . 'uploads/csv/';

        /* Crea la cartella se non esiste */
        if ( ! is_dir($csvDir)):
            mkdir($csvDir, 0777, true);
        endif;

        $tempFilename = $file->getRandomName();
        $file->move($csvDir, $tempFilename);

        return [
            'status'   => true,
            'headers'  => $csvHeaders,
            'rows'     => $rows,
            'tempFile' => $tempFilename,
            'plan'     => $plan /* <--- Aggiunto */
        ];
    }

    /* --- INIZIO MODIFICA CHUNKING: Aggiunto parametro $offset (default 0) --- */
    public function executeImport(string $entity, string $tempFile, int $offset = 0): array
    /* --- FINE MODIFICA CHUNKING --- */
    {
        $filePath = WRITEPATH . 'uploads/csv/' . $tempFile;

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

        /* Estrazione prima riga (nomi colonne) e pulizia massiva BOM e spazi */
        $headers = array_map(function($header) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, fgetcsv($handle, 0, ','));
        
        /* Recupero rigoroso della Primary Key dallo schema del database */
        $structure = $this->getTableStructure($entity);
        $primaryKey = null;
        
        foreach ($structure as $col):
            if ($col['primary_key'] == 1):
                $primaryKey = $col['name'];
                break;
            endif;
        endforeach;

        if ($primaryKey === null):
            $this->db->transRollback();
            fclose($handle);
            return ['status' => false, 'message' => sprintf(lang('backend/components/import.messages.notDeterminedPrimaryKey'), $entity)];
        endif;

        $inserted = 0;
        $updated = 0;

        /* --- INIZIO MODIFICA CHUNKING: Impostazione variabili di controllo --- */
        $chunkSize = 500;
        $currentRow = 0;
        $processedInChunk = 0;
        $isFinished = false;
        /* --- FINE MODIFICA CHUNKING --- */

        /* --- INIZIO MODIFICA CHUNKING: Sostituzione condizione while per gestire offset e EOF --- */
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

            /* Interrompe il ciclo se è stato raggiunto il limite di questo blocco (500) */
            if ($processedInChunk >= $chunkSize):
                break;
            endif;
        /* --- FINE MODIFICA CHUNKING --- */
            
            /* Salta le righe totalmente vuote */
            if ( ! array_filter($row)):
                continue;
            endif;

            /* Controllo di integrità critico prima di unire i dati */
            if (count($row) !== count($headers)):
                $this->db->transRollback();
                fclose($handle);
                return ['status' => false, 'message' => lang('backend/components/messages.importationUndone')];
            endif;

            /* Combina intestazione e riga per avere l'array associativo colonna => valore */
            $data = array_combine($headers, $row);
            
            /* 1. Pulizia dei dati: converte la stringa "null" o i campi vuoti nel vero null di PHP */
            foreach ($data as $key => $value):
                if (trim((string)$value) === '' || strtolower(trim((string)$value)) === 'null'):
                    $data[$key] = null;
                endif;
            endforeach;

            /* 2. Forza created_at con la data attuale se è nullo */
            if (array_key_exists('created_at', $data) && $data['created_at'] === null):
                $data['created_at'] = date('Y-m-d H:i:s');
            endif;
            
            $idValue = $data[$primaryKey] ?? null;

            if ( ! empty($idValue)):
                
                /* Estrae il record esistente per il confronto */
                $existingRecord = $this->db->table($entity)->where($primaryKey, $idValue)->get()->getRowArray();
                
                if ($existingRecord !== null):
                    /* Confronta i dati in arrivo con quelli nel DB */
                    $hasChanges = false;
                    foreach ($data as $key => $value):
                        /* Escludiamo updated_at dal confronto per evitare falsi positivi */
                        if ($key !== 'updated_at' && array_key_exists($key, $existingRecord) && (string)$existingRecord[$key] !== (string)$value):
                            $hasChanges = true;
                            break;
                        endif;
                    endforeach;

                    /* UPDATE solo se rileva differenze reali */
                    if ($hasChanges):
                        
                        /* Forza la data di aggiornamento attuale se la colonna esiste */
                        if (array_key_exists('updated_at', $data)):
                            $data['updated_at'] = date('Y-m-d H:i:s');
                        endif;

                        $this->db->table($entity)->where($primaryKey, $idValue)->update($data);
                        $updated++;
                    endif;
                else:
                    /* INSERT con UUID fornito dal CSV */
                    $this->db->table($entity)->insert($data);
                    $inserted++;
                endif;

            else:
                /* INSERT (Nuovo record senza UUID nel CSV, deleghiamo al DB l'auto-generazione se prevista) */
                $data[$primaryKey] = $this->generateUUID();
                $this->db->table($entity)->insert($data);
                $inserted++;
            endif;

            /* --- INIZIO MODIFICA CHUNKING: Incremento contatore locale --- */
            $processedInChunk++;
            /* --- FINE MODIFICA CHUNKING --- */

        endwhile;

        fclose($handle);

        /* CHIUSURA TRANSAZIONE */
        $this->db->transComplete();

        /* Verifica esito della transazione */
        if ($this->db->transStatus() === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
        endif;

        /* --- INIZIO MODIFICA CHUNKING: Operazioni finali SOLO se il file è concluso --- */
        if ($isFinished):
            /* Pulizia del file temporaneo */
            if (file_exists($filePath)):
                unlink($filePath);
            endif;

            /* Log e risposta finale */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('IMPORT_DATA', $entity, sprintf(lang('Importazione dati dalla tabella: %s'), $entity), $currentAdmin);
        endif;
        /* --- FINE MODIFICA CHUNKING --- */


        /* Se zero record modificati/inseriti, è un successo: i dati a database sono già identici al CSV */
        if (($inserted + $updated) === 0):
            return [
                'status' => true,
                'message' => lang('backend/components/import.messages.importationNoRecordsModified'),
                /* --- INIZIO MODIFICA CHUNKING: Aggiunta dati di stato --- */
                'nextOffset' => $offset + $processedInChunk,
                'isFinished' => $isFinished,
                'inserted' => $inserted,
                'updated' => $updated
                /* --- FINE MODIFICA CHUNKING --- */
            ];
        endif;

        return [
            'status' => true, 
            'message' => sprintf(lang('backend/components/import.messages.importSuccess'), $inserted, $updated), 
            /* --- INIZIO MODIFICA CHUNKING: Aggiunta dati di stato --- */
            'nextOffset' => $offset + $processedInChunk,
            'isFinished' => $isFinished,
            'inserted' => $inserted,
            'updated' => $updated
            /* --- FINE MODIFICA CHUNKING --- */
        ];
    }

    public function backupTableBeforeImport(string $entity): bool
    {
        $builder = $this->db->table($entity);
        $data = $builder->get()->getResultArray();

        $backupDir = WRITEPATH . 'backups/imports/';
        
        /* Crea la cartella se non esiste */
        if ( ! is_dir($backupDir)):
            mkdir($backupDir, 0777, true);
        endif;

        /* Salta la creazione se la tabella è vuota */
        if (empty($data)):
            return true;
        endif;

        $filename = $entity . '_backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . $filename;

        /* Intestazione e svuotamento tabella con bypass delle chiavi esterne */
        $sqlContent = "/* Backup tabella: {$entity} - Data: " . date('Y-m-d H:i:s') . " */\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sqlContent .= "TRUNCATE TABLE `{$entity}`;\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";

        /* Generazione query di insert */
        foreach ($data as $row):
            /* Utilizza il metodo nativo di CI4 per sfuggire e quotare i valori in modo sicuro */
            $escapedValues = array_map([$this->db, 'escape'], array_values($row));
            
            $columns = implode('`, `', array_keys($row));
            $values = implode(', ', $escapedValues);
            
            $sqlContent .= "INSERT INTO `{$entity}` (`{$columns}`) VALUES ({$values});\n";
        endforeach;

        return (file_put_contents($filepath, $sqlContent) !== false);
    }

    /* Genera regole di validazione dinamiche basate sullo schema reale del DB */
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