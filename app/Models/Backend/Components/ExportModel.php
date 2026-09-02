<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Libraries\ImageFileSystemService;

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
		return $this->db->getFieldNames($table);
	}

	public function generate(array $posts, int $offset = 0, ?string $fileName = null): array
    {
        $entity = $posts['entity'] ?? '';

        if (empty($entity) || ! $this->db->tableExists($entity)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')];
        endif;

        /* 1. Recuperiamo le colonne reali della tabella */
        $allowedColumns = $this->db->getFieldNames($entity);

        /* 2. Prepariamo i suffissi per le ricerche temporali */
        $dateKeys = [];
        foreach ($allowedColumns as $col):
            $dateKeys[] = $col . '-from';
            $dateKeys[] = $col . '-to';
        endforeach;

        /* Chiavi di sistema inviate dal JS da ignorare come filtri DB */
        $systemKeys = ['entity', 'column', 'order', 'page', 'rows', 'trash_filter', 'search_bar_visible', 'offset', 'fileName'];

        /* 3. Uniamo tutto: colonne reali, suffissi data e chiavi di sistema */
        $allowedFields = array_merge(
            $allowedColumns, 
            $dateKeys, 
            $systemKeys
        );

        /* 4. Filtriamo i post */
        $posts = $this->checkAllowedFields($posts, $allowedFields);
                
        /* Forza l'esportazione integrale e rigorosa di tutte le colonne della tabella */
        $selectFields = implode(', ', $allowedColumns);
        $sql = "select {$selectFields} from {$entity} where 1 = 1";

        /* Se è la tabella admins escludo l'esportazione del master. */
        if($entity === 'admins'):
            $sql .= ' and master <> 1';
        endif;
        
        $bindings = [];

        /* 1. Applicazione Dinamica Filtri */
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

        /* 2. Applicazione stato Cestino */
        if (isset($posts['trash_filter']) && in_array('deleted_at', $allowedColumns)):
            if ($posts['trash_filter'] === 'active'):
                $sql .= " and deleted_at is null";
            elseif ($posts['trash_filter'] === 'trashed'):
                $sql .= " and deleted_at is not null";
            endif;
        endif;

        /* 3. Applicazione ordinamento */
        $column = $posts['column'] ?? 'created_at';
        $order = $posts['order'] ?? 'desc';
        if (in_array($column, $allowedColumns) && in_array(strtolower($order), ['asc', 'desc'])):
            $sql .= " order by {$column} " . strtoupper($order);
        endif;

        /* Impostazioni per i Chunk */
        $limit = 10;
        $chunkSql = $sql . " LIMIT {$limit} OFFSET {$offset}";
        $records = $this->db->query($chunkSql, $bindings)->getResultArray();

        /* Preparazione cartella */
        $directory = WRITEPATH . 'exports/';
        if ( ! is_dir($directory)):
            mkdir($directory, 0755, true);
        endif;

        /* Gestione File: Creazione (w) al primo chunk, Append (a) ai successivi */
        if ($offset === 0 || empty($fileName)):
            $fileName = 'export_' . $entity . '_' . date('d_m_Y_H_i_s') . '.csv';
            $filePath = $directory . $fileName;
            $file = fopen($filePath, 'w');
            
            if (empty($records)):
                fclose($file);
                unlink($filePath);
                return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
            endif;

            /* Intestazioni solo al primo ciclo */
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, array_keys($records[0]), ',');
        else:
            $filePath = $directory . $fileName;
            $file = fopen($filePath, 'a');
        endif;

        /* Se il blocco è vuoto negli offset successivi, abbiamo finito */
        if (empty($records)):
            fclose($file);
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EXPORT_DATA', $entity, sprintf(lang('Esportazione completata: %s'), $entity), $currentAdmin);
            return [
                'result' => true,
                'isFinished' => true,
                'message' => sprintf(lang('backend/components/export.messages.exportSuccess'), $entity),
                'downloadUrl' => base_url('backend/export/download/' . $fileName)
            ];
        endif;

        /* Scrittura dati del blocco corrente */
        foreach ($records as $row):
            fputcsv($file, $row, ',');
        endforeach;

        fclose($file);

        /* Controllo di fine: se estratte meno righe del limite, era l'ultimo blocco */
        $isFinished = count($records) < $limit;
        $nextOffset = $offset + count($records);

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

        return [
            'result' => true,
            'isFinished' => false,
            'nextOffset' => $nextOffset,
            'fileName' => $fileName
        ];
    }
}