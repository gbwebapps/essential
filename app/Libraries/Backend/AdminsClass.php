<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class AdminsClass extends BaseClass  
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
		    ['label' => 'Elenco',  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Aggiorna', 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Elimina',  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione showAll */
	public function getOptionsShowAll()
	{
		return 
		[
		    ['label' => 'Elenco',  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Aggiorna', 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Elimina',  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione add */
	public function getOptionsAdd()
	{
		return 
		[
		    ['label' => 'Elenco',  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Aggiorna', 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Elimina',  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione edit */
	public function getOptionsEdit()
	{
		return 
		[
		    ['label' => 'Elenco',  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Aggiorna', 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Elimina',  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* Array delle options della sezione show */
	public function getOptionsShow()
	{
		return 
		[
		    ['label' => 'Elenco',  'route' => 'backend/admins', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Aggiorna', 'route' => 'backend/admins/edit/12', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => 'Elimina',  'route' => 'backend/admins/delete/12', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/* Array della linksBar della sezione index */
	public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => 'Elenco amministratori', 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => 'Aggiungi amministratore', 'route' => 'backend/admins/add'], 
        ];
	}

	/* Array della linksBar della sezione showAll */
	public function getLinksBarShowAll()
	{
		return 
		[
		    ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => 'Dati amministratori', 'route' => 'backend/admins'],
		    ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => 'Aggiungi amministratore', 'route' => 'backend/admins/add'], 
		];
	}

	/* Array della linksBar della sezione add */
	public function getLinksBarAdd()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => 'Dati amministratori', 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => 'Elenco amministratori', 'route' => 'backend/admins/showAll'], 
        ];
	}

	/* Array della linksBar della sezione edit */
	public function getLinksBarEdit(?string $uuid = null)
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => 'Dati amministratori', 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => 'Elenco amministratori', 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => 'Aggiungi amministratore', 'route' => 'backend/admins/add'],
            ['icon' => '<i class="fa-solid fa-user"></i>', 'label' => 'Dettaglio amministratore', 'route' => "backend/admins/show/{$uuid}"],
        ];
	}

	/* Array della linksBar della sezione show */
	public function getLinksBarShow(?string $uuid = null)
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return [
	        ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => 'Dati amministratori', 'route' => 'backend/admins'],
	        ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => 'Elenco amministratori', 'route' => 'backend/admins/showAll'],
	        ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => 'Aggiungi amministratore', 'route' => 'backend/admins/add'],
	        ['icon' => '<i class="fa-solid fa-user-pen"></i>', 'label' => 'Aggiorna amministratore', 'route' => "backend/admins/edit/{$uuid}"],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	public function getJsShowAll(): array
	{
	    return [
	        ['id' => 'jquery', 'path' => 'assets/vendor/jquery/jquery.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	        ['id' => 'datatables-js', 'path' => 'assets/vendor/datatables/js/dataTables.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	        ['id' => 'datatables-bs5-js', 'path' => 'assets/vendor/datatables/js/dataTables.bootstrap5.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	public function getCssShowAll(): array
	{
	    return [
	        ['id' => 'datatables-bs5-css', 'path' => 'assets/vendor/datatables/css/dataTables.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css'],
	    ];
	}
}
