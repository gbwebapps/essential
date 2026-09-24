<?php declare(strict_types = 1); 

namespace App\Models\Backend\Components;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato al Componente Globale di Esportazione Dati (ExportModel).
 * 
 * Estende BackendModel. Implementa un motore di estrazione asincrono e scalabile 
 * basato sulla "Keyset Pagination" (o Cursor Pagination). Permette di esportare grandi volumi 
 * di dati (CSV) senza esaurire la memoria (OOM) suddividendo l'estrazione in blocchi sequenziali 
 * e scrivendoli in append sul file temporaneo.
 */
class ExportModel extends BackendModel 
{
    /**
     * Genera le regole di validazione per il form di esportazione.
     * 
     * Verifica che la tabella richiesta (`entity`) esista e sia un nome formattato in sicurezza. 
     * Controlla inoltre la coerenza dei parametri strutturali come l'ordinamento e la colonna base, 
     * prevenendo manipolazioni HTTP.
     *
     * @return array Regole di validazione native
     */
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

    /**
     * Estrae dinamicamente la struttura delle colonne di una specifica tabella.
     * 
     * Interroga il database per ottenere lo schema, ma filtra intenzionalmente le chiavi primarie 
     * fisiche e la colonna logica 'id'. Questo previene che identificatori interni (privi di utilità per l'operatore finale) 
     * inquinino l'esportazione CSV.
     *
     * @param string $table Il nome esatto della tabella nel DB
     * @return array Lista dei nomi delle colonne esportabili
     */
    public function getExportColumns(string $table): array 
    {
        /* Controllo di sicurezza */
        if ( ! $this->db->tableExists($table)):
            return [];
        endif;

        $fields = $this->db->getFieldData($table);
        $columns = [];

        foreach ($fields as $field):

            /* Escludiamo la chiave primaria e l'id dal form */
            if ($field->primary_key !== 1 && $field->name !== 'id'):
                $columns[] = $field->name;
            endif;

        endforeach;

        return $columns;
    }

    /**
     * Interroga il database per individuare il nome esatto della chiave primaria di una tabella.
     * 
     * Metodo fondamentale perché il motore di esportazione necessita di una chiave su cui 
     * agganciare il cursore. Scorre i campi e restituisce il primo contrassegnato come `primary_key`.
     *
     * @param string $table Nome della tabella
     * @return string|null Il nome della colonna PK, o null in caso di assenza
     */
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

    /**
     * Motore ricorsivo asincrono per l'esportazione progressiva in CSV.
     * 
     * Questa funzione è il cuore pulsante.
     * 1. Accetta filtri dinamici (es. estrarre solo utenti attivi di uno specifico gruppo).
     * 2. Riceve le colonne esplicitamente scelte dall'operatore.
     * 3. Forza per sicurezza l'inserimento della colonna `id` nella query SELECT, poiché indispensabile per 
     *    muovere il cursore (limit/offset) al blocco successivo, pur omettendola poi dalla scrittura sul CSV finale.
     * 4. Genera (o appende a) un file CSV in locale inserendo il marcatore BOM per la compatibilità UTF-8.
     * 5. Segnala al Javascript client quando il blocco è concluso e restituisce l'URL di download finale se 
     *    l'estrazione è terminata.
     * 
     * NOTA: Il `$limit` attualmente è forzato a 5 per facilitare i test architetturali sul Chunking, 
     * dovrà essere aumentato (es. 500/1000) per la produzione.
     *
     * @param array $posts I parametri filtrati inviati dal client e i payload di stato
     * @param int|null $lastId L'ID su cui si è fermata l'ultima estrazione (il "Cursore")
     * @param string|null $fileName Il nome del file CSV temporaneo generato (mantenuto tra una chiamata e l'altra)
     * @return array Struttura dati complessa per mantenere in sync Frontend e Backend (esito, blocco completato, url)
     */
    public function generate(array $posts, ?int $lastId = null, ?string $fileName = null): array
    {
        /* Recuperiamo il conteggio precedente o partiamo da zero */
        $processedCount = (int) ($posts['processedCount'] ?? 0);

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
            return ['result' => false, 'message' => lang('backend/components/export.messages.noColumnsSelected')];
        endif;

        /* Intersezione con lo schema reale del DB: scarta spietatamente qualsiasi colonna inesistente */
        $validSelectedColumns = array_intersect($requestedColumns, $allowedColumns);

        if (empty($validSelectedColumns)):
            return ['result' => false, 'message' => 'Le colonne richieste non sono valide.'];
        endif;

        /* FORZATURA DI SICUREZZA: La PK e la colonna 'id' (motore del cursore) DEVONO essere sempre presenti */
        $primaryKey = $this->getPrimaryKey($entity);
        
        /* FORZATURA DI SICUREZZA: La colonna 'id' è il motore del cursore (keyset pagination). Deve essere obbligatoriamente inclusa nella query SELECT per calcolare il lastId del blocco di esportazione successivo, anche se verrà rimossa al volo prima della scrittura nel CSV. */
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

            /* Copiamo la prima riga e rimuoviamo l'id per stampare gli header puliti */
            $firstRow = $records[0];
            unset($firstRow['id']);

            fputcsv($file, array_keys($firstRow), ',');
        else:
            $filePath = $directory . $fileName;
            $file = fopen($filePath, 'a');
        endif;

        if ( ! empty($records)):
            foreach ($records as $row):

                /* Rimuoviamo l'id solo dalla riga da scrivere sul file */
                unset($row['id']);
                fputcsv($file, $row, ',');

            endforeach;
        endif;

        fclose($file);

        /* Unificata e ripulita la logica di chiusura dell'esportazione */
        $chunkSize = count($records);

        /* Aggiorniamo il totale globale delle righe esportate */
        $processedCount += $chunkSize;

        $isFinished = $chunkSize < $limit;

        if ($isFinished):
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EXPORT_DATA', $entity, sprintf(lang('backend/components/export.messages.exportSuccess'), $processedCount, $entity), $currentAdmin);
            
            return [
                'result' => true,
                'isFinished' => true,
                'message' => sprintf(lang('backend/components/export.messages.exportSuccess'), $processedCount, $entity),
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