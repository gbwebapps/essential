<?php 

/**
 * Backend Routing Configuration
 *
 * Mappatura completa degli URL, raggruppamento logico dei moduli amministrativi
 * e applicazione chirurgica dei filtri di protezione (guest, authorization, master).
 */

/* REDIRECT BACKEND */
$routes->get('backend', function() {
    return redirect()->to('backend/dashboard');
});

/* BACKEND */
$routes->group('backend', function($routes) {

    /* AUTH GUEST */
    $routes->group('auth', ['filter' => 'guest'], function($routes) {
        $routes->get('/', '\App\Controllers\Backend\AuthController::index');
        $routes->match(['GET', 'POST'], 'login', '\App\Controllers\Backend\AuthController::login');
        $routes->match(['GET', 'POST'], 'resetPassword', '\App\Controllers\Backend\AuthController::resetPassword');
        $routes->get('setPassword/(:token)', '\App\Controllers\Backend\AuthController::setPassword/$1');
        $routes->post('setPassword', '\App\Controllers\Backend\AuthController::setPassword');
        $routes->match(['GET', 'POST'], 'verify', '\App\Controllers\Backend\AuthController::verify');
    });

    /* LOGOUT (AUTHORIZED) */
    $routes->get('auth/logout', '\App\Controllers\Backend\AuthController::logout', ['filter' => 'authorization']);

    /* AUTHORIZED */
    $routes->group('', ['filter' => 'authorization'], function($routes) {
        
        /* DASHBOARD */
        $routes->get('dashboard', '\App\Controllers\Backend\DashboardController::index');
        
        /* ACCOUNT */
        $routes->group('account', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AccountController::index');
            $routes->get('general', '\App\Controllers\Backend\AccountController::general');
            $routes->match(['GET', 'POST'], 'edit', '\App\Controllers\Backend\AccountController::edit');
            $routes->match(['GET', 'POST'], 'permissions', '\App\Controllers\Backend\AccountController::permissions');
            $routes->get('images', '\App\Controllers\Backend\AccountController::images');

            $routes->match(['GET', 'POST'], 'tokens', '\App\Controllers\Backend\AccountController::tokens');
            $routes->post('deleteToken', '\App\Controllers\Backend\AccountController::deleteToken');
            
            $routes->match(['GET', 'POST'], 'resetPassword', '\App\Controllers\Backend\AccountController::resetPassword');

            $routes->get('security', '\App\Controllers\Backend\AccountController::security');
            $routes->post('saveBasicMethod', '\App\Controllers\Backend\AccountController::saveBasicMethod');
            $routes->post('setupTotp', '\App\Controllers\Backend\AccountController::setupTotp');
            $routes->post('confirmTotp', '\App\Controllers\Backend\AccountController::confirmTotp');
        });

        /* MESSAGES */
        $routes->group('messages', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\MessagesController::index', ['filter' => 'permission:users_show']);
            $routes->get('showAll', '\App\Controllers\Backend\MessagesController::showAll', ['filter' => 'permission:users_showAll']);
            $routes->get('show', '\App\Controllers\Backend\MessagesController::show', ['filter' => 'permission:users_show']);
        });

        /* USERS */
        $routes->group('users', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\UsersController::index', ['filter' => 'permission:users_index']);
            $routes->get('showAll', '\App\Controllers\Backend\UsersController::showAll', ['filter' => 'permission:users_showall']);
            $routes->get('show', '\App\Controllers\Backend\UsersController::show', ['filter' => 'permission:users_show']);
        });

        /* IMAGES PREVIEW */
        $routes->group('uploadPreviewImg', ['filter' => 'authorization'], function($routes) {
            $routes->post('saveImages', '\App\Controllers\Backend\Components\UploadPreviewController::saveImages');
        });

        /* GALLERY ONE */
        $routes->group('galleryOneImg', ['filter' => 'authorization'], function($routes) {
            $routes->post('showGallery', '\App\Controllers\Backend\Components\GalleryOneController::showGallery');
            $routes->post('deleteImage', '\App\Controllers\Backend\Components\GalleryOneController::deleteImage');
            $routes->post('removeCover', '\App\Controllers\Backend\Components\GalleryOneController::removeCover');
            $routes->post('setCover', '\App\Controllers\Backend\Components\GalleryOneController::setCover');
        });

        /* EXPORT CSV */
        $routes->group('export', ['filter' => 'authorization'], function($routes) {
            $routes->post('generate', '\App\Controllers\Backend\Components\ExportController::generate');
            $routes->get('download/(:any)', '\App\Controllers\Backend\Components\ExportController::download/$1');
        });

        /* IMPORT CSV */
        $routes->group('import', ['filter' => 'authorization'], function($routes) {
            $routes->post('showModal', '\App\Controllers\Backend\Components\ImportController::showModal');
            $routes->get('download/(:any)', '\App\Controllers\Backend\Components\ImportController::download/$1');
            $routes->post('processCsv', '\App\Controllers\Backend\Components\ImportController::processCsv');
            $routes->post('executeImport', '\App\Controllers\Backend\Components\ImportController::executeImport');
            $routes->post('deleteFile', '\App\Controllers\Backend\Components\ImportController::deleteFile');
        });
    });

    /* MASTER */
    $routes->group('', ['filter' => ['authorization', 'master']], function($routes) {
        
        /* SETTINGS */
        $routes->group('settings', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\SettingsController::index');

            $routes->post('openSettings', '\App\Controllers\Backend\SettingsController::openSettings');
            $routes->post('saveSettings', '\App\Controllers\Backend\SettingsController::saveSettings');
            $routes->post('deleteSettings', '\App\Controllers\Backend\SettingsController::deleteSettings');
            $routes->post('getSettings', '\App\Controllers\Backend\SettingsController::getSettings');
        });

        /* TOOLS */
        $routes->group('tools', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\ToolsController::index');

            $routes->post('openTools', '\App\Controllers\Backend\ToolsController::openTools');

            $routes->post('deleteAudits', '\App\Controllers\Backend\ToolsController::deleteAudits');
            $routes->post('validateAuditsDateRequest', '\App\Controllers\Backend\ToolsController::validateAuditsDateRequest');

            $routes->get('dbMaintenance', '\App\Controllers\Backend\ToolsController::dbMaintenance');
            $routes->post('optimizeTable', '\App\Controllers\Backend\ToolsController::optimizeTable');

            $routes->post('backups', '\App\Controllers\Backend\ToolsController::backups');
            $routes->get('downloadBackups/(:segment)', '\App\Controllers\Backend\ToolsController::downloadBackups/$1');

            $routes->post('cleanFolder', '\App\Controllers\Backend\ToolsController::cleanFolder');
        });

        /* GROUPS */
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

        /* AUDITS */
        $routes->group('audits', function($routes) {
            $routes->match(['GET', 'POST'], '/', '\App\Controllers\Backend\AuditsController::index');
        });

        /* TOKENS */
        $routes->group('tokens', function($routes) {
            $routes->match(['GET', 'POST'], '/', '\App\Controllers\Backend\TokensController::index');
            $routes->post('hardDelete', '\App\Controllers\Backend\TokensController::hardDelete');
        });

        /* ADMINS */
        $routes->group('admins', function($routes) {
            // $routes->get('/', '\App\Controllers\Backend\AdminsController::index');

            $routes->get('/', function() {
                return redirect()->to('backend/admins/showAll');
            });

            $routes->match(['GET', 'POST'], 'showAll', '\App\Controllers\Backend\AdminsController::showAll');

            $routes->match(['GET', 'POST'], 'add', '\App\Controllers\Backend\AdminsController::add');

            $routes->get('edit/(:uuid)', '\App\Controllers\Backend\AdminsController::edit/$1');
            $routes->post('edit', '\App\Controllers\Backend\AdminsController::edit');

            $routes->get('show/(:uuid)', '\App\Controllers\Backend\AdminsController::show/$1');

            $routes->post('hardDelete', '\App\Controllers\Backend\AdminsController::hardDelete');
            $routes->post('softDelete', '\App\Controllers\Backend\AdminsController::softDelete');
            $routes->post('restoreDelete', '\App\Controllers\Backend\AdminsController::restoreDelete');

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