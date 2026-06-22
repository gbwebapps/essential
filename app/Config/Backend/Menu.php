<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Class Menu
 *
 * Configurazione centrale per la struttura, la localizzazione e la mappatura 
 * dei menu di navigazione utilizzati all'interno del pannello di amministrazione.
 */
class Menu extends BaseConfig
{
    /**
     * Elenco delle voci di menu configurate per la sezione superiore destra (es. utilità utente).
     * 
     * @var array 
     */
    public array $topRight = [];

    /**
     * Elenco delle voci di menu configurate per la sezione inferiore sinistra (es. navigazione principale).
     * 
     * @var array 
     */
    public array $bottomLeft = [];

    /**
     * Elenco delle voci di menu configurate per la sezione inferiore destra (es. strumenti e impostazioni).
     * 
     * @var array 
     */
    public array $bottomRight = [];

    /**
     * Costruttore della classe.
     * Inizializza gli array dei menu traducendo dinamicamente le etichette tramite il servizio lang()
     * e definendo rotte, elementi grafici (icone) e controller associati a ciascuna voce.
     */
    public function __construct()
    {
        parent::__construct();

        $this->topRight = [
            ['label' => lang('backend/global.menu.topRight.admins'), 'route' => 'backend/admins/showAll', 'icon' => '<i class="fa-solid fa-users"></i>', 'controller' => 'admins'],
            ['label' => lang('backend/global.menu.topRight.groups'), 'route' => 'backend/groups', 'icon' => '<i class="fa-solid fa-user-shield"></i>', 'controller' => 'groups'],
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