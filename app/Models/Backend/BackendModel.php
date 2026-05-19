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

	}

	protected function getNumRows(array $params_filter): int
	{

	}

	protected function buildFilters(array $search_fields, array &$params): string
	{

	}

	public function getByUUID(string $uuid): array 
	{

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