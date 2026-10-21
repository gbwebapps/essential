<?php

namespace Tests\App\Security;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use App\Filters\Backend\SuperAdminFilter;
use App\Libraries\Backend\AuthorizationClass;

class AuthorizationSecurityTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', ENVIRONMENT);
    }

    public function testNonSuperadminIsDenied(): void
    {
        $admin = (object) [
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'superadmin' => 0,
        ];

        $authorization = $this->createMock(AuthorizationClass::class);

        $authorization->method('currentAdmin')->willReturn($admin);

        Services::injectMock('authorization', $authorization);

        $request = service('request');
        $request->setMethod('GET');

        $filter = new SuperAdminFilter();
        $response = $filter->before($request);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $response
        );

        $this->assertSame(
            base_url('backend/dashboard'),
            $response->getHeaderLine('Location')
        );
    }

    public function testSuperadminIsAllowed(): void
    {
        $admin = (object) [
            'superadmin' => 1,
        ];

        $authorization = $this->createMock(AuthorizationClass::class);

        $authorization->method('currentAdmin')->willReturn($admin);

        Services::injectMock('authorization', $authorization);

        $request = service('request');
        $request->setMethod('GET');

        $filter = new SuperAdminFilter();

        $this->assertNull($filter->before($request));
    }


    private function mockAdminPermissions(object $admin): void
    {
        $authorization = $this->createMock(AuthorizationClass::class);

        $authorization->method('currentAdmin')->willReturn($admin);

        Services::injectMock('authorization', $authorization);
    }

    public function testPermissionDenied(): void
    {
        $admin = (object) [
            'permissions' => (object) [],
        ];

        $this->mockAdminPermissions($admin);

        $request = service('request');
        $request->setMethod('GET');

        $filter = new \App\Filters\Backend\PermissionFilter();
        $response = $filter->before($request, ['brands_show_all']);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $response
        );

        $this->assertSame(
            base_url('backend/dashboard'),
            $response->getHeaderLine('Location')
        );
    }

    public function testPermissionGranted(): void
    {
        $admin = (object) [
            'permissions' => (object) [
                'brands_show_all' => true,
            ],
        ];

        $this->mockAdminPermissions($admin);

        $request = service('request');
        $request->setMethod('GET');

        $filter = new \App\Filters\Backend\PermissionFilter();

        $this->assertNull(
            $filter->before($request, ['brands_show_all'])
        );
    }

    public function testSuperadminPermissionBypass(): void
    {
        $admin = (object) [
            'permissions' => (object) [
                'all' => true,
            ],
        ];

        $this->mockAdminPermissions($admin);

        $request = service('request');
        $request->setMethod('GET');

        $filter = new \App\Filters\Backend\PermissionFilter();

        $this->assertNull(
            $filter->before($request, ['brands_show_all'])
        );
    }

    
    public function testPermissionDeniedAjaxPost(): void
    {
        $admin = (object) [
            'permissions' => (object) [],
        ];

        $this->mockAdminPermissions($admin);

        $request = service('request');
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new \App\Filters\Backend\PermissionFilter();
        $response = $filter->before($request, ['brands_show_all']);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $response
        );

        $this->assertFalse(
            json_decode($response->getBody(), true)['result']
        );
    }

    public function testSuperadminDeniedAjaxPost(): void
    {
        $admin = (object) [
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'superadmin' => 0,
        ];

        $this->mockAdminPermissions($admin);

        $request = service('request');
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new SuperAdminFilter();
        $response = $filter->before($request);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $response
        );

        $this->assertFalse(
            json_decode($response->getBody(), true)['result']
        );
    }


}
