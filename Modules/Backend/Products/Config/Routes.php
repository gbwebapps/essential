<?php declare(strict_types = 1);

/**
 * Rotte del modulo Products.
 *
 * Il file viene caricato all'interno dei gruppi backend e authorization.
 *
 * @var \CodeIgniter\Router\RouteCollection $routes
 */
$routes->group('products', function($routes) {
    
    $routes->get('/', function() {
        return redirect()->to('backend/products/showAll');
    }, ['filter' => 'permission:products_show_all']);

    $routes->match(['GET', 'POST'], 'showAll', '\Modules\Backend\Products\Controllers\ProductsController::showAll', ['filter' => 'permission:products_show_all']);
    $routes->match(['GET', 'POST'], 'add', '\Modules\Backend\Products\Controllers\ProductsController::add', ['filter' => 'permission:products_add']);

    $routes->get('edit/(:uuid)', '\Modules\Backend\Products\Controllers\ProductsController::edit/$1', ['filter' => 'permission:products_edit']);
    $routes->post('edit', '\Modules\Backend\Products\Controllers\ProductsController::edit', ['filter' => 'permission:products_edit']);

    $routes->get('show/(:uuid)', '\Modules\Backend\Products\Controllers\ProductsController::show/$1', ['filter' => 'permission:products_show']);

    $routes->post('hardDelete', '\Modules\Backend\Products\Controllers\ProductsController::hardDelete', ['filter' => 'permission:products_delete']);
    $routes->post('softDelete', '\Modules\Backend\Products\Controllers\ProductsController::softDelete', ['filter' => 'permission:products_delete']);
    $routes->post('restoreDelete', '\Modules\Backend\Products\Controllers\ProductsController::restoreDelete', ['filter' => 'permission:products_delete']);

    $routes->post('changeStatus', '\Modules\Backend\Products\Controllers\ProductsController::changeStatus', ['filter' => 'permission:products_change_status']);
    $routes->post('getGeneralData', '\Modules\Backend\Products\Controllers\ProductsController::getGeneralData', ['filter' => 'permission:products_show,products_edit']);
    $routes->post('getMetaData', '\Modules\Backend\Products\Controllers\ProductsController::getMetaData', ['filter' => 'permission:products_show,products_edit']);
});
