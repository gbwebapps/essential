<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\GroupsModel;

class GroupsClass 
{
	protected GroupsModel $groupsModel;

	public function __construct(GroupsModel $groupsModel) 
	{
		$this->groupsModel = $groupsModel;
	}

}
