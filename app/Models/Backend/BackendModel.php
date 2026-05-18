<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;

abstract class BackendModel extends BaseModel
{
	protected ?string $module = null;
	protected ?string $getDataQuery = null;
	protected ?string $getIdQuery = null;
	protected ?string $getNumRowsQuery = null;
	protected ?string $defaultColumns = null;

	protected array $allowedFields = [];
	protected array $allowedColumns = [];
	protected array $allowedSearchFields = [];
	protected array $toCompare = [];
	
	protected function initModel(): void 
	{
		parent::initModel();
	}
}