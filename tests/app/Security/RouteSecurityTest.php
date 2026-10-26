<?php

namespace Tests\App\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FilterTestTrait;

class RouteSecurityTest extends CIUnitTestCase
{
    use FilterTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection->setHTTPVerb('GET');
        $this->collection->loadRoutes();
    }

    public function testProtectedRoutes(): void
    {
        $routes = [
            'backend/dashboard'             => ['authorization'],
            'backend/account/security'      => ['authorization'],
            'backend/brands/showAll'        => ['authorization', 'permission:brands_show_all'],
            'backend/products/showAll'      => ['authorization', 'permission:products_show_all'],
            'backend/admins/showAll'        => ['authorization', 'superadmin'],
            'backend/groups'                => ['authorization', 'superadmin'],
            'backend/tools'                 => ['authorization', 'superadmin'],
            'backend/warehouse'             => ['authorization', 'superadmin'],
            'backend/import/database/showModal' => ['authorization', 'superadmin'],
        ];

        foreach ($routes as $route => $filters):
            $method = $route === 'backend/import/database/showModal' ? 'POST' : 'GET';

            $this->collection->setHTTPVerb($method);
            $this->collection->loadRoutes();

            foreach ($filters as $filter):
                $this->assertFilter($route, 'before', $filter);
            endforeach;
        endforeach;
    }

    public function testProtectedPostRoutes(): void
    {
        $routes = [
            'backend/interface/change'            => ['authorization', 'csrf'],
            'backend/admins/add'                  => ['authorization', 'superadmin', 'csrf'],
            'backend/admins/edit'                 => ['authorization', 'superadmin', 'csrf'],
            'backend/admins/hardDelete'           => ['authorization', 'superadmin', 'csrf'],
            'backend/products/add'                 => ['authorization', 'permission:products_add', 'csrf'],
            'backend/products/edit'                => ['authorization', 'permission:products_edit', 'csrf'],
            'backend/products/hardDelete'          => ['authorization', 'permission:products_delete', 'csrf'],
            'backend/products/softDelete'          => ['authorization', 'permission:products_delete', 'csrf'],
            'backend/products/restoreDelete'       => ['authorization', 'permission:products_delete', 'csrf'],
            'backend/products/changeStatus'        => ['authorization', 'permission:products_change_status', 'csrf'],
            'backend/groups/add'                  => ['authorization', 'superadmin', 'csrf'],
            'backend/settings/saveSettings'       => ['authorization', 'superadmin', 'csrf'],
            'backend/logs/disconnectSession'      => ['authorization', 'superadmin', 'csrf'],
            'backend/tokens/hardDelete'            => ['authorization', 'superadmin', 'csrf'],
            'backend/tools/cleanFolder'           => ['authorization', 'superadmin', 'csrf'],
            'backend/warehouse/export'            => ['authorization', 'superadmin', 'csrf'],
            'backend/warehouse/prepareImport'     => ['authorization', 'superadmin', 'csrf'],
            'backend/warehouse/executeImport'     => ['authorization', 'superadmin', 'csrf'],
            'backend/import/database/processCsv'  => ['authorization', 'superadmin', 'csrf'],
            'backend/import/executeImport'        => ['authorization', 'superadmin', 'csrf'],
            'backend/export/database/generate'    => ['authorization', 'superadmin', 'csrf'],
            'backend/account/saveBasicMethod'     => ['authorization', 'csrf'],
            'backend/auth/login'                  => ['guest', 'csrf'],
            'backend/auth/logout'                 => ['authorization', 'csrf'],
        ];

        $this->collection->setHTTPVerb('POST');
        $this->collection->loadRoutes();

        foreach ($routes as $route => $filters):
            foreach ($filters as $filter):
                $this->assertFilter($route, 'before', $filter);
            endforeach;
        endforeach;
    }

    public function testGuestRoutes(): void
    {
        $this->assertFilter('backend/auth/login', 'before', 'guest');
        $this->assertFilter('backend/auth/resetPassword', 'before', 'guest');
    }

    public function testCsrfProtection(): void
    {
        $this->assertFilter('backend/admins/add', 'before', 'csrf');
        $this->assertFilter('backend/groups/add', 'before', 'csrf');
    }

    public function testDebugFilters(): void
    {
        $routes = [
            'backend/dashboard',
            'backend/auth/login',
            'backend/admins/showAll',
        ];

        foreach ($routes as $route):
        	fwrite(STDOUT, $route . ': ' . json_encode($this->getFiltersForRoute($route, 'before')) . PHP_EOL);
            $this->assertNotEmpty(
                $this->getFiltersForRoute($route, 'before'),
                $route . ': nessun filtro rilevato'
            );
        endforeach;
    }

    public function testDebugRouteCollection(): void
    {
        $routes = [
            'backend/dashboard',
            'backend/auth/login',
            'backend/admins/showAll',
        ];

        foreach ($routes as $route):
            fwrite(STDOUT, $route . ': ' . json_encode(
                $this->collection->getFiltersForRoute($route)
            ) . PHP_EOL);
        endforeach;

        $this->assertNotEmpty($routes);
    }

    public function testDebugLoadedRoutes(): void
    {
        $routes = $this->collection->getRoutes('GET');

        foreach ([
            'backend/dashboard',
            'backend/auth/login',
            'backend/admins/showAll',
        ] as $route):
            fwrite(STDOUT, $route . ': ' . (
                array_key_exists($route, $routes) ? 'PRESENTE' : 'ASSENTE'
            ) . PHP_EOL);
        endforeach;

        $this->assertNotEmpty($routes);
    }
}
