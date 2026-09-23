<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AdminsModel;

class AdminsClass 
{
	protected AdminsModel $adminsModel;

	public function __construct(AdminsModel $adminsModel) 
	{
		$this->adminsModel = $adminsModel;
	}

	/* ------------------------------------------------------------------------------------------------- */

	public function getLinksBarShowAll(): array
	{
		return 
		[
		    // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
		    ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'], 
		];
	}

	public function getLinksBarAdd(): array
	{
		return 
		[
            // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'], 
        ];
	}

	public function getLinksBarEdit(?string $uuid = null): array
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return 
		[
            // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
            ['icon' => '<i class="fa-solid fa-user"></i>', 'label' => lang('backend/admins.linksBar.show'), 'route' => "backend/admins/show/{$uuid}"],
        ];
	}

	public function getLinksBarShow(?string $uuid = null): array
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return [
	        // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
	        ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
	        ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
	        ['icon' => '<i class="fa-solid fa-user-pen"></i>', 'label' => lang('backend/admins.linksBar.edit'), 'route' => "backend/admins/edit/{$uuid}"],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	public function getJsShowAll(): array
	{
		$locale = setting('Backend\General')->language;
		
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'admins-js'], 
	        ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	public function getJsShow(): array
	{
	    return [
			['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'admins-js'],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	public function getCssShowAll(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
