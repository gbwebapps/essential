<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AccountModel;

class AccountClass 
{
	protected AccountModel $accountModel;

	public function __construct(AccountModel $accountModel) 
	{
		$this->accountModel = $accountModel;
	}

	/*public function getJsIndex()
	{
		return [
	        ['id' => 'datatables-js', 'path' => 'assets/js/backend/datatables.js', 'position' => 'before', 'target' => 'backend-js']
	    ];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/*public function getCssIndex()
	{
		return [
		    ['id' => 'datatables-css', 'path' => 'assets/css/backend/datatables.css', 'position' => 'before', 'target' => 'backend-css']
		];
	}*/
}
