<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Libraries\ImageFileSystemService;

use App\Models\Backend\BackendModel;

class ImportModel extends BackendModel 
{
	public function showModalValidationRules(): array 
    {
        return [
            'entity' => [
                'label' => lang('backend/components/import.labels.entity'),
                'rules' => ['required', 'alpha_dash'],
            ],
        ];
    }

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
        
        $handle = fopen($file->getTempName(), 'r');
        
        if ($handle === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.fileReadError')];
        endif;

        $csvHeaders = fgetcsv($handle, 0, ';');

        if ($csvHeaders !== $expectedHeaders):
            fclose($handle);
            return ['status' => false, 'message' => lang('backend/components/import.messages.headerMismatch')];
        endif;

        $rows = [];
        $errors = [];
        $lineNumber = 2; /* La riga 1 è occupata dalle intestazioni */

        while (($row = fgetcsv($handle, 0, ';')) !== false):
            
            /* Salta le righe totalmente vuote */
            if ( ! array_filter($row)):
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

            /* Conserva solo i primi 10 record puliti per l'anteprima nella vista */
            if (count($rows) < 10):
                $rows[] = array_values($data);
            endif;

            /* 
             * VALIDAZIONE DINAMICA TRAMITE CI4
             */
            $primaryKey = $csvHeaders[0];
            $idValue = $data[$primaryKey] ?? null;
            
            $targetModel = $this->getTargetModelInstance($entity);
            
            if ($targetModel !== null):
                $validator = \Config\Services::validation();
                
                /* Svuota i dati e gli errori del ciclo precedente */
                $validator->reset(); 
                
                /* Seleziona le regole corrette in base alla presenza dell'UUID */
                if (empty($idValue)):
                    $rules = $targetModel->addValidationRules();
                else:
                    $rules = $targetModel->editValidationRules(['uuid' => $idValue]);
                endif;

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

        /* Se sono emersi errori durante il ciclo, blocca tutto e restituisci il report */
        if ( ! empty($errors)):
            $errorMessage = implode('<br>', $errors);
            return ['status' => false, 'message' => $errorMessage];
        endif;

        $tempFilename = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/csv/', $tempFilename);

        return [
            'status'   => true,
            'headers'  => $csvHeaders,
            'rows'     => $rows,
            'tempFile' => $tempFilename
        ];
    }

    public function executeImport(string $entity, string $tempFile): array
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

        /* Estrazione prima riga (nomi colonne) */
        $headers = fgetcsv($handle, 0, ';');
        $primaryKey = $headers[0]; /* Assumiamo che la prima colonna sia l'UUID / Chiave Primaria */

        $inserted = 0;
        $updated = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false):
            
            /* Salta le righe totalmente vuote */
            if ( ! array_filter($row)) continue;

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
                
                /* Verifica se il record esiste già */
                $exists = $this->db->table($entity)->where($primaryKey, $idValue)->countAllResults();
                
                if ($exists > 0):
                    /* UPDATE */
                    $this->db->table($entity)->where($primaryKey, $idValue)->update($data);
                    $updated++;
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

        endwhile;

        fclose($handle);

        /* CHIUSURA TRANSAZIONE */
        $this->db->transComplete();

        /* Pulizia del file temporaneo */
        if (file_exists($filePath)):
            unlink($filePath);
        endif;

        /* Verifica esito della transazione */
        if ($this->db->transStatus() === false):
            return ['status' => false, 'message' => lang('backend/components/import.messages.importTransactionError')];
        endif;

        return [
            'status' => true,
            'inserted' => $inserted,
            'updated' => $updated
        ];
    }
}