<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;

abstract class BackendModel extends BaseModel
{
	protected ?string $module = null;

	protected bool $hasSoftDelete = false;

	protected ?string $getDataQuery = null;

	protected ?string $getUUIDQuery = null;

	protected ?string $getNumRowsQuery = null;

	protected ?string $defaultColumn = null;

	protected array $toCompare = [];

	protected array $showAllAllowedFields = [];

	protected array $showAllAllowedDates = [];

	protected array $addAllowedFields = [];

	protected array $editAllowedFields = [];

	protected array $delAllowedFields = [];

	protected array $changeStatusAllowedFields = [];

	protected array $allowedOrderColumns = [];

	protected array $showAllSearchAllowedFields = [];

	protected function initModel(): void 
	{
		parent::initModel();
		
		helper('audits');
	}

	public function getData(array $posts): array
	{
		try
		{
			/* Whitelist estesa: aggiunto 'trash_filter' ai parametri consentiti */
			$posts = $this->checkAllowedFields($posts, array_merge($this->showAllAllowedFields, ['searchDates', 'trash_filter']));

			$params = [];
			$paramsFilter = [];

			$posts['order'] = (isset($posts['order']) && $posts['order'] === 'desc') ? 'asc' : 'desc';
			$posts['column'] = (isset($posts['column']) && in_array($posts['column'], $this->allowedOrderColumns)) ? $posts['column'] : $this->defaultColumn;

			$sql = $this->getDataQuery;

			/* Filtro Cestino basato sulla proprietà module */
			$trashStatus = (isset($posts['trash_filter']) && in_array($posts['trash_filter'], ['active', 'trashed', 'all'])) ? $posts['trash_filter'] : 'active';
			$sql .= $this->buildTrashFilter($trashStatus, $this->module, $this->hasSoftDelete);
			$paramsFilter['trash_filter'] = $trashStatus;

			/* 1. Filtri di testo standard */
			if ( ! empty(array_filter($posts['searchFields']))):
				$sql .= $this->buildFilters($posts['searchFields'], $params);
				$paramsFilter['searchFields'] = $posts['searchFields'];
			endif;

			/* 2. Filtri per i range di date */
			if ( ! empty(array_filter($posts['searchDates']))):
				$sql .= $this->buildDateFilters($posts['searchDates'], $params);
				$paramsFilter['searchDates'] = $posts['searchDates'];
			endif;

			$sql .= ' order by ' . $posts['column'] . ' ' . $posts['order'];

			$page = (isset($posts['page']) && is_numeric($posts['page']) && $posts['page'] > 0) ? (int)$posts['page'] : 1;
			$recordsPerPage = (isset($posts['rows']) && is_numeric($posts['rows']) && $posts['rows'] > 0) ? min((int)$posts['rows'], 20) : 5;
			$offset = ($page - 1) * $recordsPerPage; 

			$sql .= ' limit ' . $offset . ', ' . $recordsPerPage;

			$records = $this->db->query($sql, $params)->getResult();

			/* Passaggio dei parametri completi al metodo di conteggio */
			$totalRows = $this->getNumRows($paramsFilter); 

			$lastItemPage = ($totalRows - $offset);

			$pagination = ['page' => $page, 'limit' => $recordsPerPage, 'totalRows' => $totalRows]; 

			return ['result' => true, 'records' => $records, 'pagination' => $pagination, 'lastItemPage' => $lastItemPage];

		} catch (\Throwable $e) {

			log_message('error', lang('backend/global.messages.getDataError') . ' - ' . $e);
			return ['result' => false, 'message' => lang('backend/global.messages.getDataError')];

		}
	}

	private function getNumRows(array $paramsFilter): int
	{
		$params = [];
		$sql = $this->getNumRowsQuery;

		/* 0. Filtro Cestino basato sulla proprietà module (Sincronizzato) */
		$trashStatus = isset($paramsFilter['trash_filter']) ? $paramsFilter['trash_filter'] : 'active';
		$sql .= $this->buildTrashFilter($trashStatus, $this->module, $this->hasSoftDelete);

		if (isset($paramsFilter['searchFields']) && is_array($paramsFilter['searchFields'])):
			$sql .= $this->buildFilters($paramsFilter['searchFields'], $params);
		endif;

		if (isset($paramsFilter['searchDates']) && is_array($paramsFilter['searchDates'])):
			$sql .= $this->buildDateFilters($paramsFilter['searchDates'], $params);
		endif;

		return (int) $this->db->query($sql, $params)->getRow()->count;
	}

	private function buildFilters(array $searchFields, array &$params): string
	{
		$whereClause = '';

		foreach ($searchFields as $key => $val):
		    if (in_array($key, $this->showAllSearchAllowedFields)):
		        $whereClause .= " and " . $this->module . '.' . $key . " like ?";
		        $params[] = "%$val%";
		    endif;
		endforeach;

		return $whereClause;
	}

	private function buildDateFilters(array $searchDates, array &$params): string
	{
		$whereClause = '';

		foreach ($this->showAllSearchAllowedDates as $dbColumn):
			/* 1. Controllo e binding per il limite inferiore (Da / >=) */
			$fromKey = $dbColumn . '-from';
			if (isset($searchDates[$fromKey]) && trim($searchDates[$fromKey]) !== ''):
				$whereClause .= " and " . $this->module . '.' . $dbColumn . " >= ?";
				$params[] = $searchDates[$fromKey];
			endif;

			/* 2. Controllo e binding per il limite superiore (A / <=) */
			$toKey = $dbColumn . '-to';
			if (isset($searchDates[$toKey]) && trim($searchDates[$toKey]) !== ''):
				$whereClause .= " and " . $this->module . '.' . $dbColumn . " <= ?";
				$params[] = $searchDates[$toKey];
			endif;
		endforeach;

		return $whereClause;
	}

	private function buildTrashFilter(string $filter, string $table, bool $hasSoftDelete = true): string
	{
	    /* Sanitizzazione rigorosa del nome tabella per prevenire SQL injection strutturali */
	    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

	    /* Scudo strutturale: se la tabella non possiede la colonna deleted_at, la logica viene aggirata */
	    if ($hasSoftDelete === false):
	        return "";
	    endif;

	    if ($filter === 'trashed'):
	        return " and {$table}.deleted_at IS NOT NULL";
	    endif;

	    if ($filter === 'all'):
	        return ""; /* Nessuna restrizione, restituisce l'intero storico */
	    endif;

	    /* Comportamento enterprise di default (active): blocco rigoroso dei record cestinati */
	    return " and {$table}.deleted_at IS NULL";
	}

	public function getByUUID(string $uuid): array 
	{
	    try 
	    {
	        $row = $this->db->query($this->getUUIDQuery, [$uuid])->getRow();

	        if ( ! $row):
	            return ['result' => false, 'message' => lang('backend/global.messages.UUIDNotFound')];
	        endif;

	        /* Struttura di ritorno fissa, coerente e affidabile per tutti i moduli */
	        return ['result' => true, 'row' => $row];

	    } catch(\Throwable $e) {
	        log_message('error', lang('backend/global.messages.getUUIDError') . ' - ' . $e->getMessage());
	        return ['result' => false, 'message' => lang('backend/global.messages.getUUIDError')];
	    }
	}

	protected function hasDataChanged(array $posts, object $original): bool
	{
	    /* 1. Controllo dei campi nativi della tabella (Valido per TUTTI i moduli) */
        foreach ($this->toCompare as $field):
            
            /* Se il campo è presente nel POST, normalizziamo il confronto a stringa */
            if (isset($posts[$field])):
                
                /* Recuperiamo il valore originale gestendo il possibile NULL dal DB */
                $originalValue = $original->$field ?? '';

                /* Il cast a (string) azzera la differenza tra null e "" senza toccare i dati reali */
                if ((string)$posts[$field] !== (string)$originalValue):
                    return true;
                endif;

            endif;
        endforeach;

	    /* 2. Controllo dei file caricati (Valido per TUTTI i moduli che accettano allegati) */
	    foreach (['images'] as $type):
	        if (isset($posts[$type]) && is_array($posts[$type])):
	            foreach ($posts[$type] as $file):
	                if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile && $file->isValid() && ! $file->hasMoved()):
	                    return true;
	                endif;
	            endforeach;
	        endif;
	    endforeach;

	    return false;
	}

	protected function insertImages(array $filenames, string $uuid, string $entity, string $action = 'add'): void
	{
	    $dataImage = [];
	    $flag = false;

	    if ($action === 'edit'):
	        $sql = "select 1 from images where entity_uuid = ? and is_cover = ? and entity = ? limit 1";
	        $result = $this->db->query($sql, [$uuid, '1', $entity])->getRow();
	        $flag = $result ? true : false;
	    endif;

	    foreach ($filenames as $k => $v):
	        $dataImage[$k]['entity'] = $entity;
	        $dataImage[$k]['entity_uuid'] = $uuid;
	        $dataImage[$k]['filename'] = $v;
	        
	        if ($flag):
	            $dataImage[$k]['is_cover'] = '0';
	        else:
	            $dataImage[$k]['is_cover'] = ($k === 0) ? '1' : '0';
	        endif;
	    endforeach;

	    $placeholders = [];
	    $bind = [];
	    $now = date('Y-m-d H:i:s');

	    foreach ($dataImage as $row):
	        $placeholders[] = "(?, ?, ?, ?, ?)";
	        $bind[] = $row['entity'];
	        $bind[] = $row['entity_uuid'];
	        $bind[] = $row['filename'];
	        $bind[] = $row['is_cover'];
	        $bind[] = $now;
	    endforeach;

	    $sql = "insert into images (entity, entity_uuid, filename, is_cover, created_at) values " . implode(", ", $placeholders);
	    $this->db->query($sql, $bind);
	}

    protected function rrmdir(string $dir): void
    {
        if ( ! is_dir($dir)):
            return;
        endif;

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file):
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full)):
                $this->rrmdir($full);
            else:
                @unlink($full);
            endif;
        endforeach;

        @rmdir($dir);
    }

	protected function generateUUID(): string
	{
	    $data = random_bytes(16);

	    $data[6] = chr((ord($data[6]) & 0x0f) | 0b01000000);
	    $data[8] = chr((ord($data[8]) & 0x3f) | 0b10000000);

	    $hex = bin2hex($data);

	    return vsprintf('%08s-%04s-%04s-%04s-%12s', sscanf($hex, '%8s%4s%4s%4s%12s'));
	}

	protected function checkAllowedFields(array $posts, array $allowedFields): array
	{
	    foreach (array_keys($posts) as $key):
	        /* Rimuove il campo se non è presente nei campi consentiti */
	        if ( ! in_array($key, $allowedFields, true)):
	            unset($posts[$key]);
	        endif;
	    endforeach;

	    return $posts;
	}
}