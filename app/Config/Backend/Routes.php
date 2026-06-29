<?php 

/**
 * Backend Routing Configuration
 *
 * Mappatura completa degli URL, raggruppamento logico dei moduli amministrativi
 * e applicazione chirurgica dei filtri di protezione (guest, authorization, master).
 */

/* Reindirizzamento automatico della radice di backend verso la dashboard */
$routes->get('backend', function() {
    return redirect()->to('backend/dashboard');
});

/* Inizio del raggruppamento globale per l'intero perimetro del Backend */
$routes->group('backend', function($routes) {

    /* Gestione dell'autenticazione: accesso consentito solo agli utenti non loggati (Filtro: guest) */
    $routes->group('auth', ['filter' => 'guest'], function($routes) {
        $routes->get('/', '\App\Controllers\Backend\AuthController::index');
        $routes->match(['GET', 'POST'], 'login', '\App\Controllers\Backend\AuthController::login');
        $routes->match(['GET', 'POST'], 'resetPassword', '\App\Controllers\Backend\AuthController::resetPassword');
        $routes->get('setPassword/(:token)', '\App\Controllers\Backend\AuthController::setPassword/$1');
        $routes->match(['GET', 'POST'], 'setPassword', '\App\Controllers\Backend\AuthController::setPassword');
    });

    /* Endpoint di disconnessione sicuro: accessibile solo ad amministratori autenticati */
    $routes->get('auth/logout', '\App\Controllers\Backend\AuthController::logout', ['filter' => 'authorization']);

    /* Area riservata standard: richiede l'autenticazione obbligatoria dell'operatore (Filtro: authorization) */
    $routes->group('', ['filter' => 'authorization'], function($routes) {
        
        $routes->get('dashboard', '\App\Controllers\Backend\DashboardController::index');
        
        /* Modulo Account: gestione del profilo personale, sicurezza, permessi personali e token dell'amministratore corrente */
        $routes->group('account', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AccountController::index');
            $routes->get('general', '\App\Controllers\Backend\AccountController::general');
            $routes->match(['GET', 'POST'], 'edit', '\App\Controllers\Backend\AccountController::edit');
            $routes->match(['GET', 'POST'], 'permissions', '\App\Controllers\Backend\AccountController::permissions');
            $routes->get('images', '\App\Controllers\Backend\AccountController::images');
            $routes->get('tokens', '\App\Controllers\Backend\AccountController::tokens');
            $routes->get('resetPassword', '\App\Controllers\Backend\AccountController::resetPassword');
            $routes->get('security', '\App\Controllers\Backend\AccountController::security');
        });

        /* Modulo Messages: visualizzazione e consultazione della messaggistica di sistema */
        $routes->group('messages', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\MessagesController::index', ['filter' => 'permission:messages_show']);
            $routes->get('showAll', '\App\Controllers\Backend\MessagesController::showAll', ['filter' => 'permission:messages_show']);
            $routes->get('show', '\App\Controllers\Backend\MessagesController::show', ['filter' => 'permission:messages_show']);
        });

        /* Modulo Users: consultazione ed elenco degli utenti della piattaforma pubblica */
        $routes->group('users', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\UsersController::index', ['filter' => 'permission:users_index']);
            $routes->get('showAll', '\App\Controllers\Backend\UsersController::showAll', ['filter' => 'permission:users_showall']);
            $routes->get('show', '\App\Controllers\Backend\UsersController::show', ['filter' => 'permission:users_show']);
        });
    });

    /* Pannello di controllo avanzato: accesso limitato esclusivamente ai Superadmin (Filtri: authorization + master) */
    $routes->group('', ['filter' => ['authorization', 'master']], function($routes) {
        
        $routes->get('settings', '\App\Controllers\Backend\SettingsController::index');
        $routes->get('tools', '\App\Controllers\Backend\ToolsController::index');

        $routes->group('groups', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\GroupsController::index');
            $routes->post('getGroups', '\App\Controllers\Backend\GroupsController::getGroups');
            $routes->post('getGroup', '\App\Controllers\Backend\GroupsController::getGroup'); 
            $routes->post('openAdd', '\App\Controllers\Backend\GroupsController::openAdd'); 
            $routes->post('add', '\App\Controllers\Backend\GroupsController::add'); 
            $routes->post('edit', '\App\Controllers\Backend\GroupsController::edit'); 
            $routes->post('del', '\App\Controllers\Backend\GroupsController::del'); 
            $routes->post('openExceptions', '\App\Controllers\Backend\GroupsController::openExceptions'); 
            $routes->post('getDropdownAdmins', '\App\Controllers\Backend\GroupsController::getDropdownAdmins'); 
            $routes->post('getAdminPermissions', '\App\Controllers\Backend\GroupsController::getAdminPermissions'); 
            $routes->post('saveExceptions', '\App\Controllers\Backend\GroupsController::saveExceptions'); 
        });

        /* Modulo Admins: gestione totale (CRUD), stati operativi e configurazione granulare dei permessi degli amministratori */
        $routes->group('admins', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AdminsController::index');

            $routes->match(['GET', 'POST'], 'showAll', '\App\Controllers\Backend\AdminsController::showAll');

            $routes->match(['GET', 'POST'], 'add', '\App\Controllers\Backend\AdminsController::add');

            $routes->get('edit/(:uuid)', '\App\Controllers\Backend\AdminsController::edit/$1');
            $routes->post('edit', '\App\Controllers\Backend\AdminsController::edit');

            $routes->get('show/(:uuid)', '\App\Controllers\Backend\AdminsController::show/$1');

            $routes->post('delete', '\App\Controllers\Backend\AdminsController::delete');
            $routes->post('changeStatus', '\App\Controllers\Backend\AdminsController::changeStatus');
            $routes->post('resetPassword', '\App\Controllers\Backend\AdminsController::resetPassword');

            $routes->post('getGeneralData', '\App\Controllers\Backend\AdminsController::getGeneralData');
            $routes->post('getMetaData', '\App\Controllers\Backend\AdminsController::getMetaData');
            $routes->post('getPermissions', '\App\Controllers\Backend\AdminsController::getPermissions');
            $routes->post('getTokens', '\App\Controllers\Backend\AdminsController::getTokens');
            
            $routes->post('changePermission', '\App\Controllers\Backend\AdminsController::changePermission');
            $routes->post('changeGroup', '\App\Controllers\Backend\AdminsController::changeGroup');
            $routes->post('deleteToken', '\App\Controllers\Backend\AdminsController::deleteToken');
        });
    });
});