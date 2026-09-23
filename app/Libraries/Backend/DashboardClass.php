<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\DashboardModel;

class DashboardClass 
{
	protected DashboardModel $dashboardModel;

	public function __construct(DashboardModel $dashboardModel) 
	{
		$this->dashboardModel = $dashboardModel;
	}

}
