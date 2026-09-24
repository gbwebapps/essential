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
		        'title' => lang('backend/permissions.users.title'),
		        'controller' => 'users',
		        'icon' => '<i class="fa-solid fa-cube"></i>',
		        'perms' => [
		            'users_index' => lang('backend/permissions.users.index'),
		            'users_showall' => lang('backend/permissions.users.showAll'),
		            'users_show' => lang('backend/permissions.users.show'),
		            'users_delete' => lang('backend/permissions.users.delete'),
		        ]
		    ], 
		    [
		        'title' => lang('backend/permissions.messages.title'),
		        'controller' => 'messages',
		        'icon' => '<i class="fa-solid fa-cube"></i>',
		        'perms' => [
		            'messages_index' => lang('backend/permissions.messages.index'),
		            'messages_showall' => lang('backend/permissions.messages.showAll'),
		            'messages_show' => lang('backend/permissions.messages.show'),
		            'messages_delete' => lang('backend/permissions.messages.delete'),
		        ]
		    ]
		];
	}
}
