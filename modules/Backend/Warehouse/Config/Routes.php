<?php declare(strict_types = 1);

/**
 * Rotte del modulo Warehouse.
 *
 * Il file viene caricato nel gruppo backend protetto dai filtri authorization e superadmin.
 *
 * @var \CodeIgniter\Router\RouteCollection $routes
 */
$routes->group('warehouse', function($routes) {

    $routes->get('/', '\Modules\Backend\Warehouse\Controllers\WarehouseController::index');
    $routes->post('export', '\Modules\Backend\Warehouse\Controllers\WarehouseController::export');
    $routes->post('showExportModal', '\Modules\Backend\Warehouse\Controllers\WarehouseController::showExportModal');
    $routes->post('removeExport', '\Modules\Backend\Warehouse\Controllers\WarehouseController::removeExport');
    $routes->get('download/(:segment)', '\Modules\Backend\Warehouse\Controllers\WarehouseController::download/$1');
    $routes->get('downloadTemplate', '\Modules\Backend\Warehouse\Controllers\WarehouseController::downloadTemplate');
    $routes->post('showImportModal', '\Modules\Backend\Warehouse\Controllers\WarehouseController::showImportModal');
    $routes->post('prepareImport', '\Modules\Backend\Warehouse\Controllers\WarehouseController::prepareImport');
    $routes->post('executeImport', '\Modules\Backend\Warehouse\Controllers\WarehouseController::executeImport');
    $routes->post('deleteImport', '\Modules\Backend\Warehouse\Controllers\WarehouseController::deleteImport');
});
