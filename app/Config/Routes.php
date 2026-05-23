<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

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
        $routes->get('resetPassword', '\App\Controllers\Backend\AuthController::resetPassword');
        $routes->get('setPassword', '\App\Controllers\Backend\AuthController::setPassword');
    });

    /* Rotta di Logout (Filtro: auth - deve poter uscire solo chi è loggato) */
    $routes->get('auth/logout', '\App\Controllers\Backend\AuthController::logout', ['filter' => 'auth']);

    /* Area Riservata Base (Filtro: auth) */
    $routes->group('', ['filter' => 'auth'], function($routes) {
        
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
        $routes->group('messages', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\MessagesController::index');
            $routes->get('showAll', '\App\Controllers\Backend\MessagesController::showAll');
            $routes->get('show', '\App\Controllers\Backend\MessagesController::show');
        });

        /* Users */
        $routes->group('users', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\UsersController::index');
            $routes->get('showAll', '\App\Controllers\Backend\UsersController::showAll');
            $routes->get('show', '\App\Controllers\Backend\UsersController::show');
        });
    });

    /* Area Superadmin (Filtro multiplo: auth + master) */
    $routes->group('', ['filter' => ['auth', 'master']], function($routes) {
        
        $routes->get('settings', '\App\Controllers\Backend\SettingsController::index');
        $routes->get('tools', '\App\Controllers\Backend\ToolsController::index');

        /* Admins */
        $routes->group('admins', function($routes) {
            $routes->get('/', '\App\Controllers\Backend\AdminsController::index');
            $routes->get('showAll', '\App\Controllers\Backend\AdminsController::showAll');
            $routes->get('add', '\App\Controllers\Backend\AdminsController::add');
            $routes->get('edit', '\App\Controllers\Backend\AdminsController::edit');
            $routes->get('show', '\App\Controllers\Backend\AdminsController::show');
        });
    });
});