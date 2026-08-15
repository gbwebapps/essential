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

	public function generate(array $posts): array
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
        $systemKeys = ['entity', 'column', 'order', 'page', 'rows', 'trash_filter', 'search_bar_visible'];

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

        /* 1. Applicazione Dinamica Filtri (Testo e Date misti nell'array piatto) */
        foreach ($posts as $key => $value):
            
            /* Salta le chiavi di sistema o vuote */
            if (empty($value) || in_array($key, $systemKeys)) continue;

            /* Gestione Date: suffisso -from */
            if (str_ends_with($key, '-from')):
                $realField = str_replace('-from', '', $key);
                if (in_array($realField, $allowedColumns)):
                    $sql .= " and {$realField} >= ?";
                    $bindings[] = $value; 
                endif;
            
            /* Gestione Date: suffisso -to */
            elseif (str_ends_with($key, '-to')):
                $realField = str_replace('-to', '', $key);
                if (in_array($realField, $allowedColumns)):
                    $sql .= " and {$realField} <= ?";
                    $bindings[] = $value;
                endif;

            /* Gestione Testo: la chiave è esattamente una colonna */
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

        $records = $this->db->query($sql, $bindings)->getResultArray();

        if (empty($records)):
            return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
        endif;

        /* Preparazione cartella e file CSV */
        $directory = WRITEPATH . 'exports/';
        if ( ! is_dir($directory)):
            mkdir($directory, 0755, true);
        endif;

        $fileName = 'export_' . $entity . '_' . date('d_m_Y_H_i_s') . '.csv';
        $filePath = $directory . $fileName;
        $file = fopen($filePath, 'w');

        /* Impostazioni per i Chunk */
        $limit = 500;
        $offset = 0;
        $isFirstChunk = true;

        /* Estrazione a blocchi */
        while (true):
            
            /* Accodiamo limite e offset alla query base */
            $chunkSql = $sql . " LIMIT {$limit} OFFSET {$offset}";
            $records = $this->db->query($chunkSql, $bindings)->getResultArray();

            /* Se il blocco è vuoto, valutiamo cosa fare */
            if (empty($records)):
                
                /* Se è il primo in assoluto, significa che non ci sono dati */
                if ($isFirstChunk):
                    fclose($file);
                    unlink($filePath); /* Eliminiamo il file vuoto appena creato */
                    return ['result' => false, 'message' => lang('backend/components/export.messages.noDataFound')];
                endif;
                
                /* Altrimenti siamo semplicemente arrivati alla fine dei dati */
                break;
            endif;

            /* Scriviamo le intestazioni solo al primo ciclo */
            if ($isFirstChunk):
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, array_keys($records[0]), ',');
                $isFirstChunk = false;
            endif;

            /* Scriviamo tutte le righe del blocco corrente */
            foreach ($records as $row):
                fputcsv($file, $row, ',');
            endforeach;

            /* Se abbiamo estratto meno record del limite, significa che è l'ultimo blocco */
            if (count($records) < $limit):
                break;
            endif;

            /* Prepariamo l'inizio del blocco successivo */
            $offset += $limit;

        endwhile;

        fclose($file);

        /* Log e risposta finale */
        $currentAdmin = service('authorization')->currentAdmin();
        log_admin_activity('EXPORT_DATA', $entity, sprintf(lang('Esportazione dati dalla tabella: %s'), $entity), $currentAdmin);

        return [
            'result' => true,
            'message' => lang('backend/components/export.messages.exportSuccess'),
            'downloadUrl' => base_url('backend/export/download/' . $fileName),
        ];
    }
}