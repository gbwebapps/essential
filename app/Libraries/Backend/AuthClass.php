<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AuthModel;

class AuthClass 
{
	protected AuthModel $authModel;

	public function __construct(AuthModel $authModel) 
	{
		$this->authModel = $authModel;
	}
	
}
