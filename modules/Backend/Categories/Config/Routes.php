<?php declare(strict_types = 1);

/**
 * Rotte del modulo Categories.
 *
 * Il file viene caricato all'interno dei gruppi backend e authorization.
 *
 * @var \CodeIgniter\Router\RouteCollection $routes
 */
$routes->group('categories', function($routes) {
    
    $routes->get('/', function() {
        return redirect()->to('backend/categories/showAll');
    }, ['filter' => 'permission:categories_show_all']);

    $routes->match(['GET', 'POST'], 'showAll', '\Modules\Backend\Categories\Controllers\CategoriesController::showAll', ['filter' => 'permission:categories_show_all']);
    $routes->match(['GET', 'POST'], 'add', '\Modules\Backend\Categories\Controllers\CategoriesController::add', ['filter' => 'permission:categories_add']);

    $routes->get('edit/(:uuid)', '\Modules\Backend\Categories\Controllers\CategoriesController::edit/$1', ['filter' => 'permission:categories_edit']);
    $routes->post('edit', '\Modules\Backend\Categories\Controllers\CategoriesController::edit', ['filter' => 'permission:categories_edit']);

    $routes->get('show/(:uuid)', '\Modules\Backend\Categories\Controllers\CategoriesController::show/$1', ['filter' => 'permission:categories_show']);

    $routes->post('hardDelete', '\Modules\Backend\Categories\Controllers\CategoriesController::hardDelete', ['filter' => 'permission:categories_delete']);
    $routes->post('softDelete', '\Modules\Backend\Categories\Controllers\CategoriesController::softDelete', ['filter' => 'permission:categories_delete']);
    $routes->post('restoreDelete', '\Modules\Backend\Categories\Controllers\CategoriesController::restoreDelete', ['filter' => 'permission:categories_delete']);

    $routes->post('changeStatus', '\Modules\Backend\Categories\Controllers\CategoriesController::changeStatus', ['filter' => 'permission:categories_change_status']);
    $routes->post('getTree', '\Modules\Backend\Categories\Controllers\CategoriesController::getTree', ['filter' => 'permission:categories_add,categories_edit,categories_show']);
    $routes->post('getGeneralData', '\Modules\Backend\Categories\Controllers\CategoriesController::getGeneralData', ['filter' => 'permission:categories_show,categories_edit']);
    $routes->post('getMetaData', '\Modules\Backend\Categories\Controllers\CategoriesController::getMetaData', ['filter' => 'permission:categories_show,categories_edit']);
});
