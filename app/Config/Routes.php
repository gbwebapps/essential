<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', '\App\Controllers\Frontend\HomeController::index');
$routes->get('contacts', '\App\Controllers\Frontend\ContactsController::index');
$routes->get('users', '\App\Controllers\Frontend\UsersController::index');

$routes->get('backend/dashboard', '\App\Controllers\Backend\DashboardController::index');
