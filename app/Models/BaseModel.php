<?php declare(strict_types = 1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

abstract class BaseModel 
{
	protected BaseConnection $db;

	public function __construct()
	{
		$this->initModel();
	}

	protected function initModel(): void 
	{
		$this->db = Database::connect();
	}
}