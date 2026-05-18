<?php declare(strict_types = 1); 

namespace App\Libraries;

use App\Models\BaseModel;

abstract class BaseClass 
{
	protected BaseModel $model;

	public function __construct()
	{
		$this->initClass();
	}

	protected function initClass() {}

	public function withModel(BaseModel $model): self
	{
		$this->model = $model;
		return $this;
	}
}