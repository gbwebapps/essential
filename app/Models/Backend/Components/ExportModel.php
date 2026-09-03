<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Models\Backend\BackendModel;

class ExportModel extends BackendModel 
{
    public function generateValidationRules(): array 
    {
        return [
            'entity' => [
                'label' => lang('backend/components/export.labels.entity'),
                'rules' => ['required', 'alpha_dash'],
            ],
            'order' => [
                'label' => lang('backend/components/export.labels.order'),
                'rules' => ['permit_empty', 'in_list[asc,desc,ASC,DESC]'],
            ],
            'column' => [
                'label' => lang('backend/components/export.labels.column'),
                'rules' => ['permit_empty', 'alpha_dash'],
            ],
            'trash_filter' => [
                'label' => lang('backend/components/export.labels.trash_filter'),
                'rules' => ['permit_empty', 'in_list[active,trashed,all]'],
            ],
            'page' => [
                'label' => lang('backend/components/export.labels.page'),
                'rules' => ['permit_empty', 'is_natural_no_zero'],
            ],
        ];
    }

    public function getExportColumns(string $table): array 
    {
        /* Controllo di sicurezza */
        if ( ! $this->db->tableExists($table)):
            return [];
        endif;

        $fields = $this->db->getFieldData($table);
        $columns = [];

        foreach ($fields as $field):
            /* Escludiamo la chiave primaria dall'elenco selezionabile dall'utente */
            if ($field->primary_key !== 1):
                $columns[] = $field->name;
            endif;
        endforeach;

        return $columns;
    }

    public function getPrimaryKey(string $table): ?string
    {
        if ( ! $this->db->tableExists($table)):
            return null;
        endif;

        $fields = $this->db->getFieldData($table);
        
        foreach ($fields as $field):
            if ($field->primary_key == 1):
                return $field->name;
            endif;
        endforeach;

        return null;
    }

    public function generate(array $posts, ?int $lastId = null, ?string $fileName = null): array
    {
        /* Sanificazione preventiva per evitare Directory Traversal */
        $fileName = $fileName !== null ? basename($fileName) : null;

        $entity = $posts['entity'] ?? '';

        if (empty($entity) || ! $this->db->tableExists($entity)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')];
        endif;

        $allowedColumns = $this->db->getFieldNames($entity);

        /* Controllo di sicurezza: la tabella deve avere la colonna id numerica per il cursore (keyset pagination) */
        if ( ! in_array('id', $allowedColumns)):
            return ['result' => false, 'message' => 'Colonna id mancante. Esportazione a cursore impossibile.'];
        endif;

        /* Validazione server-side delle colonne scelte (Sicurezza contro manomissioni lato client) */
        $requestedColumns = $posts['selected_columns'] ?? [];
        if (empty($requestedColumns) || ! is_array($requestedColumns)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.noColumnsSelected') ?? 'Nessuna colonna selezionata per l\'esportazione.'];
        endif;

        /* Intersezione con lo schema reale del DB: scarta spietatamente qualsiasi colonna inesistente */
        $validSelectedColumns = array_intersect($requestedColumns, $allowedColumns);

        if (empty($validSelectedColumns)):
            return ['result' => false, 'message' => 'Le colonne richieste non sono valide.'];
        endif;

        /* FORZATURA DI SICUREZZA: La PK e la colonna 'id' (motore del cursore) DEVONO essere sempre presenti */
        $primaryKey = $this->getPrimaryKey($entity);
        $mandatoryColumns = ['id'];
        
        if ($primaryKey !== null):
            $mandatoryColumns[] = $primaryKey;
        endif;

        foreach ($mandatoryColumns as $mandatoryCol):
            if ( ! in_array($mandatoryCol, $validSelectedColumns)):
                /* Mettiamo le colonne obbligatorie forzatamente all'inizio dell'array */
                array_unshift($validSelectedColumns, $mandatoryCol);
            endif;
        endforeach;
        
        /* Rimuoviamo eventuali duplicati logici (se id e primaryKey coincidono) */
        $validSelectedColumns = array_unique($validSelectedColumns);

        $dateKeys = [];
        foreach ($allowedColumns as $col):
            $dateKeys[] = $col . '-from';
            $dateKeys[] = $col . '-to';
        endforeach;

        /* Aggiungiamo 'selected_columns' tra le chiavi di sistema per bypassare il checkAllowedFields e il generatore di WHERE */
        $systemKeys = ['entity', 'column', 'order', 'page', 'rows', 'trash_filter', 'search_bar_visible', 'lastId', 'fileName', 'processedCount', 'selected_columns'];

        $allowedFields = array_merge($allowedColumns, $dateKeys, $systemKeys);
        $posts = $this->checkAllowedFields($posts, $allowedFields);
                
        /* Costruiamo la query limitandola rigorosamente alle sole colonne richieste e validate */
        $selectFields = implode(', ', $validSelectedColumns);
        $sql = "select {$selectFields} from {$entity} where 1 = 1";
        $bindings = [];

        foreach ($posts as $key => $value):
            if (empty($value) || in_array($key, $systemKeys)) continue;

            if (str_ends_with($key, '-from')):
                $realField = str_replace('-from', '', $key);
                if (in_array($realField, $allowedColumns)):
                    $sql .= " and {$realField} >= ?";
                    $bindings[] = $value; 
                endif;
            elseif (str_ends_with($key, '-to')):
                $realField = str_replace('-to', '', $key);
                if (in_array($realField, $allowedColumns)):
                    $sql .= " and {$realField} <= ?";
                    $bindings[] = $value;
                endif;
            elseif (in_array($key, $allowedColumns)):
                $sql .= " and {$key} like ?";
                $bindings[] = "%{$value}%";
            endif;
        endforeach;

        if (isset($posts['trash_filter']) && in_array('deleted_at', $allowedColumns)):
            if ($posts['trash_filter'] === 'active'):
                $sql .= " and deleted_at is null";
            elseif ($posts['trash_filter'] === 'trashed'):
                $sql .= " and deleted_at is not null";
            endif;
        endif;

        /* KEYSET PAGINATION PURA CON ID NUMERICO */
        if ($lastId !== null && $lastId > 0):
            $sql .= " and id > ?";
            $bindings[] = $lastId;
        endif;

        /* Limit impostato basso esclusivamente per finalità di testing */
        $limit = 5;
        $sql .= " order by id ASC LIMIT {$limit}";
        
        $records = $this->db->query($sql, $bindings)->getResultArray();

        $directory = WRITEPATH . 'exports/';
        if ( ! is_dir($directory)):
            mkdir($directory, 0755, true);
        endif;

        if ($lastId === null || empty($fileName)):
            $fileName = 'export_' . $entity . '_' . date('d_m_Y_H_i_s') . '.csv';
            $filePath = $directory . $fileName;
            $file = fopen($filePath, 'w');
            
            if (empty($records)):
                fclose($file);
                unlink($filePath);
                return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
            endif;

            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, array_keys($records[0]), ',');
        else:
            $filePath = $directory . $fileName;
            $file = fopen($filePath, 'a');
        endif;

        if ( ! empty($records)):
            foreach ($records as $row):
                fputcsv($file, $row, ',');
            endforeach;
        endif;

        fclose($file);

        /* Unificata e ripulita la logica di chiusura dell'esportazione */
        $chunkSize = count($records);
        $isFinished = $chunkSize < $limit;

        if ($isFinished):
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EXPORT_DATA', $entity, sprintf(lang('Esportazione completata: %s'), $entity), $currentAdmin);
            
            return [
                'result' => true,
                'isFinished' => true,
                'message' => sprintf(lang('backend/components/export.messages.exportSuccess'), $entity),
                'downloadUrl' => base_url('backend/export/download/' . $fileName)
            ];
        endif;

        $lastRecord = end($records);

        return [
            'result' => true,
            'isFinished' => false,
            'lastId' => (int) $lastRecord['id'],
            'chunkSize' => $chunkSize,
            'fileName' => $fileName
        ];
    }
}