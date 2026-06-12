<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Class Permissions
 *
 * Configurazione centrale per la definizione e la mappatura dei permessi
 * di accesso (RBAC) associati ai singoli moduli amministrativi del Backend.
 */
class Permissions extends BaseConfig
{
	/**
	 * @var array Elenco strutturato dei permessi di sicurezza configurati nell'applicazione.
	 */
	public array $permissions = [];

	public function __construct()
    {
        parent::__construct();
	}

	/**
	 * Restituisce la matrice completa dei permessi del backend organizzati per modulo,
	 * traducendo dinamicamente le etichette descrittive tramite il servizio lang().
	 *
	 * @return array Struttura gerarchica dei moduli con i relativi permessi atomici verificabili.
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
		            'users_add' => lang('backend/permissions.users.add'),
		            'users_edit' => lang('backend/permissions.users.edit'),
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
		            'messages_add' => lang('backend/permissions.messages.add'),
		            'messages_edit' => lang('backend/permissions.messages.edit'),
		            'messages_show' => lang('backend/permissions.messages.show'),
		            'messages_delete' => lang('backend/permissions.messages.delete'),
		        ]
		    ]
		];
	}
}
