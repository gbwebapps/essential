<?php declare(strict_types = 1); 

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BackendPermissions extends BaseConfig
{
	public array $permissions = [];

	public function __construct()
    {
        /* Richiama il costruttore padre di BaseConfig per non rompere le logiche di CI4 */
        parent::__construct();
	}

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
