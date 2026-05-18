<?php declare(strict_types = 1); 

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BackendMenu extends BaseConfig
{
    public array $topRight = [
        ['label' => 'Amministratori', 'route' => 'backend/admins/showAll', 'icon' => '<i class="fa-solid fa-users"></i>', 'controller' => 'admins'],
        ['label' => 'Profilo', 'route' => 'backend/account', 'icon' => '<i class="fa-solid fa-user-gear"></i>', 'controller' => 'account'],
        ['label' => 'Logout', 'route' => 'backend/auth/logout', 'icon' => '<i class="fa-solid fa-right-to-bracket"></i>', 'controller' => 'auth'],
    ];

    public array $bottomLeft = [
        ['label' => 'Dashboard', 'route' => 'backend/dashboard', 'icon' => '<i class="fa-solid fa-gauge"></i>', 'controller' => 'dashboard'],
        ['label' => 'Utenti', 'route' => 'backend/users/showAll', 'icon' => '<i class="fa-solid fa-cube"></i>', 'controller' => 'users'],
        ['label' => 'Messaggi', 'route' => 'backend/messages/showAll','icon' => '<i class="fa-solid fa-cube"></i>', 'controller' => 'messages'],
    ];

    public array $bottomRight = [
        ['label' => 'Strumenti', 'route' => 'backend/tools', 'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>', 'controller' => 'tools'],
        ['label' => 'Impostazioni', 'route' => 'backend/settings', 'icon' => '<i class="fa-solid fa-sliders"></i>', 'controller' => 'settings'],
    ];
}