<?php declare(strict_types = 1);

/**
 * Rotte del modulo Brands.
 *
 * Il file viene caricato all'interno dei gruppi backend e authorization.
 *
 * @var \CodeIgniter\Router\RouteCollection $routes
 */
$routes->group('brands', function($routes) {
    
    $routes->get('/', function() {
        return redirect()->to('backend/brands/showAll');
    }, ['filter' => 'permission:brands_show_all']);

    $routes->match(['GET', 'POST'], 'showAll', '\Modules\Backend\Brands\Controllers\BrandsController::showAll', ['filter' => 'permission:brands_show_all']);
    $routes->match(['GET', 'POST'], 'add', '\Modules\Backend\Brands\Controllers\BrandsController::add', ['filter' => 'permission:brands_add']);

    $routes->get('edit/(:uuid)', '\Modules\Backend\Brands\Controllers\BrandsController::edit/$1', ['filter' => 'permission:brands_edit']);
    $routes->post('edit', '\Modules\Backend\Brands\Controllers\BrandsController::edit', ['filter' => 'permission:brands_edit']);

    $routes->get('show/(:uuid)', '\Modules\Backend\Brands\Controllers\BrandsController::show/$1', ['filter' => 'permission:brands_show']);

    $routes->post('hardDelete', '\Modules\Backend\Brands\Controllers\BrandsController::hardDelete', ['filter' => 'permission:brands_delete']);
    $routes->post('softDelete', '\Modules\Backend\Brands\Controllers\BrandsController::softDelete', ['filter' => 'permission:brands_delete']);
    $routes->post('restoreDelete', '\Modules\Backend\Brands\Controllers\BrandsController::restoreDelete', ['filter' => 'permission:brands_delete']);

    $routes->post('changeStatus', '\Modules\Backend\Brands\Controllers\BrandsController::changeStatus', ['filter' => 'permission:brands_change_status']);
    $routes->post('getGeneralData', '\Modules\Backend\Brands\Controllers\BrandsController::getGeneralData', ['filter' => 'permission:brands_show,brands_edit']);
    $routes->post('getMetaData', '\Modules\Backend\Brands\Controllers\BrandsController::getMetaData', ['filter' => 'permission:brands_show,brands_edit']);
});
