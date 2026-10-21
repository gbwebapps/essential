<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione e la mappatura dei permessi di accesso alle sezioni e funzionalità del backend.
 */
class Permissions extends BaseConfig
{
	/**
	 * Elenco dei permessi di sistema configurati
	 * 
	 * @var array 
	 */
	public array $permissions = [];

	/**
	 * Inizializza la configurazione dei permessi di accesso.
	 */
	public function __construct()
    {
        parent::__construct();
	}

	/**
	 * Restituisce la struttura completa dei permessi suddivisi per controller e relative azioni consentite.
	 *
	 * @return array Array multidimensionale contenente i titoli, i controller, le icone e l'elenco dei permessi localizzati
	 */
	public function getPermissions(): array
	{
		return 
		[
		    [
		        'title' => lang('backend/permissions.brands.title'),
		        'controller' => 'brands',
		        'icon' => '<i class="fa-solid fa-tags"></i>',
		        'perms' => [
		            'brands_add' => lang('backend/permissions.brands.add'),
		            'brands_edit' => lang('backend/permissions.brands.edit'),
		            'brands_show' => lang('backend/permissions.brands.show'),
		            'brands_show_all' => lang('backend/permissions.brands.showAll'),
		            'brands_delete' => lang('backend/permissions.brands.delete'),
		            'brands_change_status' => lang('backend/permissions.brands.changeStatus'),
		        ]
		    ],
            [
                'title' => lang('backend/permissions.categories.title'),
                'controller' => 'categories',
                'icon' => '<i class="fa-solid fa-folder-tree"></i>',
                'perms' => [
                    'categories_add' => lang('backend/permissions.categories.add'),
                    'categories_edit' => lang('backend/permissions.categories.edit'),
                    'categories_show' => lang('backend/permissions.categories.show'),
                    'categories_show_all' => lang('backend/permissions.categories.showAll'),
                    'categories_delete' => lang('backend/permissions.categories.delete'),
                    'categories_change_status' => lang('backend/permissions.categories.changeStatus'),
                ]
            ],
            [
                'title' => lang('backend/permissions.products.title'),
                'controller' => 'products',
                'icon' => '<i class="fa-solid fa-boxes-stacked"></i>',
                'perms' => [
                    'products_add' => lang('backend/permissions.products.add'),
                    'products_edit' => lang('backend/permissions.products.edit'),
                    'products_show' => lang('backend/permissions.products.show'),
                    'products_show_all' => lang('backend/permissions.products.showAll'),
                    'products_delete' => lang('backend/permissions.products.delete'),
                    'products_change_status' => lang('backend/permissions.products.changeStatus'),
                ]
            ]
		];
	}
}
