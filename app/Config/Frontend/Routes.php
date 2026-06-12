<?php 

/* Rotte Frontend */
$routes->get('/', '\App\Controllers\Frontend\HomeController::index');
$routes->get('contacts', '\App\Controllers\Frontend\ContactsController::index');
$routes->get('users', '\App\Controllers\Frontend\UsersController::index');