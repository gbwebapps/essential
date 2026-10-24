<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione e la struttura dei menu di navigazione del pannello di controllo.
 */
class Menu extends BaseConfig
{
    /**
     * Elenco delle voci di menu posizionate nell'area superiore destra
     * 
     * @var array 
     */
    public array $topRight = [];

    /**
     * Elenco delle voci di menu posizionate nell'area inferiore sinistra
     * 
     * @var array 
     */
    public array $bottomLeft = [];

    /**
     * Elenco delle voci di menu posizionate nell'area inferiore destra
     * 
     * @var array 
     */
    public array $bottomRight = [];

    /** Tabelle abilitate ai badge e permessi di consultazione delle rispettive liste. */
    public array $counterPermissions = [
        'admins' => null,
        'brands' => 'brands_show_all',
        'categories' => 'categories_show_all',
        'products' => 'products_show_all',
    ];

    /**
     * Inizializza la configurazione dei menu popolando le sezioni di navigazione con le rispettive voci localizzate e rotte.
     * 
     */
    public function __construct()
    {
        parent::__construct();

        $this->topRight = [
            ['label' => lang('backend/global.menu.topRight.admins'), 'route' => 'backend/admins/showAll', 'icon' => '<i class="fa-solid fa-users"></i>', 'controller' => 'admins'],
            ['label' => lang('backend/global.menu.topRight.groups'), 'route' => 'backend/groups', 'icon' => '<i class="fa-solid fa-user-shield"></i>', 'controller' => 'groups'],
            ['label' => lang('backend/global.menu.topRight.audits'), 'route' => 'backend/audits', 'icon' => '<i class="fa-solid fa-clock-rotate-left"></i>', 'controller' => 'audits'],
            ['label' => lang('backend/global.menu.topRight.tokens'), 'route' => 'backend/tokens', 'icon' => '<i class="fa-solid fa-chain"></i>', 'controller' => 'tokens'],
            ['label' => lang('backend/global.menu.topRight.logs'), 'route' => 'backend/logs', 'icon' => '<i class="fa-solid fa-user-clock"></i>', 'controller' => 'logs'],
            ['label' => lang('backend/global.menu.topRight.account'), 'route' => 'backend/account', 'icon' => '<i class="fa-solid fa-user-gear"></i>', 'controller' => 'account'],
            ['label' => lang('backend/global.menu.topRight.logout'), 'route' => 'backend/auth/logout', 'icon' => '<i class="fa-solid fa-right-to-bracket"></i>', 'controller' => 'auth', 'method' => 'post'],
        ];

        $this->bottomLeft = [
            ['label' => lang('backend/global.menu.bottomLeft.dashboard'), 'route' => 'backend/dashboard', 'icon' => '<i class="fa-solid fa-gauge"></i>', 'controller' => 'dashboard'],
            ['label' => lang('backend/global.menu.bottomLeft.brands'), 'route' => 'backend/brands/showAll', 'icon' => '<i class="fa-solid fa-tags"></i>', 'controller' => 'brands'],
            ['label' => lang('backend/global.menu.bottomLeft.categories'), 'route' => 'backend/categories/showAll', 'icon' => '<i class="fa-solid fa-folder-tree"></i>', 'controller' => 'categories'],
            ['label' => lang('backend/global.menu.bottomLeft.products'), 'route' => 'backend/products/showAll', 'icon' => '<i class="fa-solid fa-boxes-stacked"></i>', 'controller' => 'products'],
        ];

        $this->bottomRight = [
            ['label' => lang('backend/global.menu.bottomRight.warehouse'), 'route' => 'backend/warehouse', 'icon' => '<i class="fa-solid fa-warehouse"></i>', 'controller' => 'warehouse'],
            ['label' => lang('backend/global.menu.bottomRight.tools'), 'route' => 'backend/tools', 'icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>', 'controller' => 'tools'],
            ['label' => lang('backend/global.menu.bottomRight.settings'), 'route' => 'backend/settings', 'icon' => '<i class="fa-solid fa-sliders"></i>', 'controller' => 'settings'],
        ];
    }
}
