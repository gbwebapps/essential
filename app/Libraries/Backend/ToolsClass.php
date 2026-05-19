<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class ToolsClass extends BaseClass  
{
	protected function initClass(): void
	{
		parent::initClass();
	}

	/* Array delle options della sezione index */
	public function getOptionsIndex()
	{
		return 
		[
		    ['label' => lang('backend/tools.options.showAll'),  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/tools.options.edit'), 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/tools.options.delete'),  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/* Array della linksBar della sezione index */
	public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-sliders"></i>', 'label' => lang('backend/tools.linksBar.settings'), 'route' => 'backend/settings'],
        ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	// public function getJsIndex(): array
	// {
	//     return [
	//         ['id' => 'datatables-js', 'path' => 'assets/js/backend/datatables.js', 'position' => 'before', 'target' => 'backend-js']
	//     ];
	// }

	/* ------------------------------------------------------------------------------------------------- */

	/*public function getCssIndex(): array
	{
	    return [
	        ['id' => 'datatables-css', 'path' => 'assets/css/backend/datatables.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}*/
}
