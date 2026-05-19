<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class UsersClass extends BaseClass  
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
		    ['label' => lang('backend/users.options.showAll'),  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.edit'), 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.delete'),  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione showAll */
	public function getOptionsShowAll()
	{
		return 
		[
		    ['label' => lang('backend/users.options.showAll'),  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.edit'), 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.delete'),  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione show */
	public function getOptionsShow()
	{
		return 
		[
		    ['label' => lang('backend/users.options.showAll'),  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.edit'), 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/users.options.delete'),  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/* Array della linksBar della sezione index */
	public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/users.linksBar.showAll'), 'route' => 'backend/users/showAll'],
            ['icon' => '<i class="fa-solid fa-circle-info"></i>', 'label' => lang('backend/users.linksBar.show'), 'route' => 'backend/users/show/12'],
        ];
	}

	/* Array della linksBar della sezione showAll */
	public function getLinksBarShowAll()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/users.linksBar.index'), 'route' => 'backend/users'],
            ['icon' => '<i class="fa-solid fa-circle-info"></i>', 'label' => lang('backend/users.linksBar.show'), 'route' => 'backend/users/show/12'],
        ];
	}

	/* Array della linksBar della sezione show */
	public function getLinksBarShow()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/users.linksBar.index'), 'route' => 'backend/users'],
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/users.linksBar.showAll'), 'route' => 'backend/users/showAll'],
        ];
	}

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
