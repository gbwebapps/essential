<?php declare(strict_types = 1); 

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BackendMenu extends BaseConfig
{
    public array $topRight = [];
    public array $bottomLeft = [];
    public array $bottomRight = [];

    public function __construct()
    {
        /* Richiama il costruttore padre di BaseConfig per non rompere le logiche di CI4 */
        parent::__construct();

        /* Adesso puoi usare liberamente le funzioni perché siamo a runtime */
        $this->topRight = [
            ['label' => lang('backend/global.menu.topRight.admins'), 'route' => 'backend/admins/showAll', 'icon' => '<i class="fa-solid fa-users"></i>', 'controller' => 'admins'],
            ['label' => lang('backend/global.menu.topRight.account'), 'route' => 'backend/account', 'icon' => '<i class="fa-solid fa-user-gear"></i>', 'controller' => 'account'],
            ['label' => lang('backend/global.menu.topRight.logout'), 'route' => 'backend/auth/logout', 'icon' => '<i class="fa-solid fa-right-to-bracket"></i>', 'controller' => 'auth'],
        ];

        $this->bottomLeft = [
            ['label' => lang('backend/global.menu.bottomLeft.dashboard'), 'route' => 'backend/dashboard', 'icon' => '<i class="fa-solid fa-gauge"></i>', 'controller' => 'dashboard'],
            ['label' => lang('backend/global.menu.bottomLeft.users'), 'route' => 'backend/users/showAll', 'icon' => '<i class="fa-solid fa-cube"></i>', 'controller' => 'users'],
            ['label' => lang('backend/global.menu.bottomLeft.messages'), 'route' => 'backend/messages/showAll','icon' => '<i class="fa-solid fa-cube"></i>', 'controller' => 'messages'],
        ];

        $this->bottomRight = [
            ['label' => lang('backend/global.menu.bottomRight.tools'), 'route' => 'backend/tools', 'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>', 'controller' => 'tools'],
            ['label' => lang('backend/global.menu.bottomRight.settings'), 'route' => 'backend/settings', 'icon' => '<i class="fa-solid fa-sliders"></i>', 'controller' => 'settings'],
        ];
    }
}