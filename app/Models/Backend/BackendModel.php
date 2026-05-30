<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;

abstract class BackendModel extends BaseModel
{
	protected ?string $module = null;

	protected ?string $getDataQuery = null;
	protected ?string $getUUIDQuery = null;
	protected ?string $getNumRowsQuery = null;
	protected ?string $defaultColumn = null;

	protected array $toCompare = [];

	protected array $showAllAllowedFields = [];
	protected array $addAllowedFields = [];
	protected array $editAllowedFields = [];
	protected array $delAllowedFields = [];
	protected array $changeStatusAllowedFields = [];

	protected array $allowedOrderColumns = [];
	protected array $showAllSearchAllowedFields = [];
	
	protected function initModel(): void 
	{
		parent::initModel();
	}

	public function getData(array $posts): array
	{
		try
		{
			$posts = $this->checkAllowedFields($posts, $this->showAllAllowedFields);

			$paramsFilter = [];

			$params = [];

			$posts['order'] = (isset($posts['order']) && $posts['order'] === 'asc') ? 'desc' : 'asc';

			$posts['column'] = (isset($posts['column']) && in_array($posts['column'], $this->allowedOrderColumns)) ? $posts['column'] : $this->defaultColumn;

			$sql = $this->getDataQuery;

			if($this->module === 'admins'):
			    $params[] = 1;
			    $params[] = service('authorization')->currentAdmin()->uuid;
			endif;

			if ( ! empty(array_filter($posts['searchFields']))):
			    $sql .= $this->buildFilters($posts['searchFields'], $params);
			    $paramsFilter['searchFields'] = $posts['searchFields'];
			endif;

			$sql .= ' order by ' . $posts['column'] . ' ' . $posts['order'];

			$page = (isset($posts['page']) && is_numeric($posts['page']) && $posts['page'] > 0) ? (int)$posts['page'] : 1;

			$recordsPerPage = (isset($posts['rows']) && is_numeric($posts['rows']) && $posts['rows'] > 0) ? min((int)$posts['rows'], 20) : 5;

			$offset = ($page - 1) * $recordsPerPage; 

			$sql .= ' limit ' . $offset . ', ' . $recordsPerPage;

			$records = $this->db->query($sql, $params)->getResult();

			$totalRows = $this->getNumRows($paramsFilter); 

			$lastItemPage = ($totalRows - $offset);

			$pagination = ['page' => $page, 'limit' => $recordsPerPage, 'totalRows' => $totalRows]; 

			return ['result' => true, 'records' => $records, 'pagination' => $pagination, 'lastItemPage' => $lastItemPage];

		} catch (\Throwable $e) {

			log_message('error', lang('backend/global.messages.getDataError') . ' - ' . $e->getMessage());
			return ['result' => false, 'message' => lang('backend/global.messages.getDataError')];

		}
	}

	private function getNumRows(array $paramsFilter): int
	{
		$params = [];

		$sql = $this->getNumRowsQuery;

		if($this->module === 'admins'):
		    $params[] = 1;
		    $params[] = service('authorization')->currentAdmin()->uuid;
		endif;

		if (isset($paramsFilter['searchFields']) && is_array($paramsFilter['searchFields'])):
		    $sql .= $this->buildFilters($paramsFilter['searchFields'], $params);
		endif;

		return (int) $this->db->query($sql, $params)->getRow()->num;
	}

	private function buildFilters(array $searchFields, array &$params): string
	{
		$whereClause = '';

		foreach ($searchFields as $key => $val):
		    if (in_array($key, $this->showAllSearchAllowedFields)):
		        $whereClause .= " and " . $key . " like ?";
		        $params[] = "%$val%";
		    endif;
		endforeach;

		return $whereClause;
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

	protected function hasDataChanged(array $posts, array|object $data): bool {

	}

	protected function insertImages(Array $filenames, String $uuid, String $entity, String $action = 'add'): Void 
	{

	}

	protected function insertDocuments(array $filenames, string $uuid, string $entity): void 
	{

	}

	protected function removeImages(string $entity, string $uuid): void 
	{

	}

	protected function removeImage(string $entity, string $entity_uuid, string $filename): void 
	{

	}

	protected function removeDocuments(string $entity, string $uuid): void 
	{

	}

	protected function rrmdir(string $dir): void 
	{

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