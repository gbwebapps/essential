<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/* Definizione del placeholder personalizzato per UUID v1-v5 */
$routes->addPlaceholder('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}');

/* Definizione del placeholder personalizzato per token setPassword */
$routes->addPlaceholder('token', '[0-9a-f]{32}');

/* Rotte Frontend */
$routes->get('/', '\App\Controllers\Frontend\HomeController::index');
$routes->get('contacts', '\App\Controllers\Frontend\ContactsController::index');
$routes->get('users', '\App\Controllers\Frontend\UsersController::index');

/* Redirect base per il backend */
$routes->get('backend', function() {
    return redirect()->to('backend/dashboard');
});

/* Gruppo principale Backend */
$routes->group('backend', function($routes) {

    /* Area Accesso (Filtro: guest) */
    $routes->group('auth', ['filter' => 'guest'], function($routes) {
        $routes->get('/', '\App\Controllers\Backend\AuthController::index');
        $routes->match(['GET', 'POST'], 'login', '\App\Controllers\Backend\AuthController::login');
        $routes->match(['GET', 'POST'], 'resetPassword', '\App\Controllers\Backend\AuthController::resetPassword');
        $routes->get('setPassword/(:token)', '\App\Controllers\Backend\AuthController::setPassword/$1');
        $routes->match(['GET', 'POST'], 'setPassword', '\App\Controllers\Backend\AuthController::setPassword');
    });

    /* Rotta di Logout (Filtro: auth - deve poter uscire solo chi è loggato) */
    $routes->get('auth/logout', '\App\Controllers\Backend\AuthController::logout', ['filter' => 'authorization']);

    /* Area Riservata Base (Filtro: authorization) */
    $routes->group('', ['filter' => 'authorization'], function($routes) {
        
        $routes->get('dashboard', '\App\Controllers\Backend\DashboardController::index');
        
        /* Account */
        $routes->group('account', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AccountController::index');
            $routes->get('general', '\App\Controllers\Backend\AccountController::general');
            $routes->get('edit', '\App\Controllers\Backend\AccountController::edit');
            $routes->get('permissions', '\App\Controllers\Backend\AccountController::permissions');
            $routes->get('images', '\App\Controllers\Backend\AccountController::images');
            $routes->get('tokens', '\App\Controllers\Backend\AccountController::tokens');
            $routes->get('resetPassword', '\App\Controllers\Backend\AccountController::resetPassword');
            $routes->get('security', '\App\Controllers\Backend\AccountController::security');
        });

        /* Messages */
        $routes->group('messages', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\MessagesController::index');
            $routes->get('showAll', '\App\Controllers\Backend\MessagesController::showAll');
            $routes->get('show', '\App\Controllers\Backend\MessagesController::show');
        });

        /* Users */
        $routes->group('users', ['filter' => 'authorization'], function($routes) {
            $routes->get('/', '\App\Controllers\Backend\UsersController::index');
            $routes->get('showAll', '\App\Controllers\Backend\UsersController::showAll');
            $routes->get('show', '\App\Controllers\Backend\UsersController::show');
        });
    });

    /* Area Superadmin (Filtro multiplo: authorization + master) */
    $routes->group('', ['filter' => ['authorization', 'master']], function($routes) {
        
        $routes->get('settings', '\App\Controllers\Backend\SettingsController::index');
        $routes->get('tools', '\App\Controllers\Backend\ToolsController::index');

        /* Admins */
        $routes->group('admins', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AdminsController::index');
            $routes->match(['GET', 'POST'], 'showAll', '\App\Controllers\Backend\AdminsController::showAll');
            $routes->match(['GET', 'POST'], 'add', '\App\Controllers\Backend\AdminsController::add');
            $routes->get('edit/(:uuid)', '\App\Controllers\Backend\AdminsController::edit/$1');
            $routes->get('show/(:uuid)', '\App\Controllers\Backend\AdminsController::show/$1');
        });
    });
});