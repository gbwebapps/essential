<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', '\App\Controllers\Frontend\HomeController::index');
$routes->get('contacts', '\App\Controllers\Frontend\ContactsController::index');
$routes->get('users', '\App\Controllers\Frontend\UsersController::index');

$routes->get('backend/auth', '\App\Controllers\Backend\AuthController::index');
$routes->get('backend/auth/login', '\App\Controllers\Backend\AuthController::login');
$routes->get('backend/auth/resetPassword', '\App\Controllers\Backend\AuthController::resetPassword');
$routes->get('backend/auth/setPassword', '\App\Controllers\Backend\AuthController::setPassword');
$routes->get('backend/auth/logout', '\App\Controllers\Backend\AuthController::logout');

$routes->get('backend/dashboard', '\App\Controllers\Backend\DashboardController::index');
$routes->get('backend/settings', '\App\Controllers\Backend\SettingsController::index');
$routes->get('backend/tools', '\App\Controllers\Backend\ToolsController::index');

$routes->get('backend/account', '\App\Controllers\Backend\AccountController::index');
$routes->get('backend/account/general', '\App\Controllers\Backend\AccountController::general');
$routes->get('backend/account/edit', '\App\Controllers\Backend\AccountController::edit');
$routes->get('backend/account/permissions', '\App\Controllers\Backend\AccountController::permissions');
$routes->get('backend/account/images', '\App\Controllers\Backend\AccountController::images');
$routes->get('backend/account/tokens', '\App\Controllers\Backend\AccountController::tokens');
$routes->get('backend/account/resetPassword', '\App\Controllers\Backend\AccountController::resetPassword');
$routes->get('backend/account/security', '\App\Controllers\Backend\AccountController::security');

$routes->get('backend/admins', '\App\Controllers\Backend\AdminsController::index');
$routes->get('backend/admins/showAll', '\App\Controllers\Backend\AdminsController::showAll');
$routes->get('backend/admins/add', '\App\Controllers\Backend\AdminsController::add');
$routes->get('backend/admins/edit', '\App\Controllers\Backend\AdminsController::edit');
$routes->get('backend/admins/show', '\App\Controllers\Backend\AdminsController::show');

$routes->get('backend/messages', '\App\Controllers\Backend\MessagesController::index');
$routes->get('backend/messages/showAll', '\App\Controllers\Backend\MessagesController::showAll');
$routes->get('backend/messages/show', '\App\Controllers\Backend\MessagesController::show');

$routes->get('backend/users', '\App\Controllers\Backend\UsersController::index');
$routes->get('backend/users/showAll', '\App\Controllers\Backend\UsersController::showAll');
$routes->get('backend/users/show', '\App\Controllers\Backend\UsersController::show');
