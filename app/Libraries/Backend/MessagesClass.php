<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class MessagesClass extends BaseClass  
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
		    ['label' => lang('backend/messages.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione showAll */
	public function getOptionsShowAll()
	{
		return 
		[
		    ['label' => lang('backend/messages.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione show */
	public function getOptionsShow()
	{
		return 
		[
		    ['label' => lang('backend/messages.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/messages.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/* Array della linksBar della sezione index */
	public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/messages.linksBar.showAll'), 'route' => 'backend/messages/showAll'],
        ];
	}

	/* Array della linksBar della sezione showAll */
	public function getLinksBarShowAll()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/messages.linksBar.index'), 'route' => 'backend/messages'],
        ];
	}

	/* Array della linksBar della sezione show */
	public function getLinksBarShow()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/messages.linksBar.index'), 'route' => 'backend/messages'],
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/messages.linksBar.showAll'), 'route' => 'backend/messages/showAll'],
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
