<?php

namespace Tests\App\Security;

use CodeIgniter\Test\CIUnitTestCase;

class AuthenticationSecurityTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Config\Services::resetSingle('authorization');
    }

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('authorization');

        parent::tearDown();
    }

    public function testUnauthenticatedUser(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        session()->remove('backendSession');

        service('authorization')->refresh();

        $this->assertNull(
            service('authorization')->currentAdmin()
        );
    }

    
    public function testUnauthenticatedGetRedirect(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        session()->remove('backendSession');
        session()->remove('login_log_id');

        service('authorization')->refresh();

        $request = service('request');

        $request->setMethod('GET');

        $filter = new \App\Filters\Backend\AuthorizationFilter();
        $response = $filter->before($request);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $response
        );

        $this->assertSame(
            base_url('backend/auth'),
            $response->getHeaderLine('Location')
        );
    }

    public function testUnauthenticatedAjaxPost(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        session()->remove('backendSession');
        session()->remove('login_log_id');

        service('authorization')->refresh();

        $request = service('request');

        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new \App\Filters\Backend\AuthorizationFilter();
        $response = $filter->before($request);

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $response
        );

        $this->assertSame(
            'no_current_user_logged',
            json_decode($response->getBody(), true)['result'] ?? null
        );
    }

}
