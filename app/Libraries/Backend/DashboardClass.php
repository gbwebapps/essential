<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class DashboardClass extends BaseClass 
{
	protected function initClass(): void
	{
		parent::initClass();
	}

	/* Array delle options della sezione index */
	/*public function getOptionsIndex()
	{
		return 
		[
		    ['label' => lang('backend/dashboard.options.index'),  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/dashboard.options.add'), 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/dashboard.options.delete'),  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/* Array della linksBar della sezione index */
	/*public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-tachometer"></i>', 'label' => lang('backend/dashboard.options.performances'), 'route' => 'backend/dashboard'],
        ];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/*public function getJsIndex(): array
	{
	    return [
	        ['id' => 'datatables-js', 'path' => 'assets/js/backend/datatables.js', 'position' => 'before', 'target' => 'backend-js']
	    ];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/*public function getCssIndex(): array
	{
	    return [
	        ['id' => 'datatables-css', 'path' => 'assets/css/backend/datatables.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}*/
}
