<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use App\Libraries\Backend\AuthorizationClass;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Filters\Backend\SuperAdminFilter;

class SuperAdminFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('message');
        session()->remove('class');
        session()->remove('icon');

        Services::reset();

        parent::tearDown();
    }

    public function testBeforeReturnsNullForSuperadmin(): void
    {
        $admin = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'superadmin' => 1
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);

        $filter = new SuperAdminFilter();

        $this->assertNull($filter->before($request));
    }

    public function testBeforeReturnsJsonForNonSuperadminAjaxPost(): void
    {
        $admin = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'superadmin' => 0
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(true);
        $request->method('is')->with('post')->willReturn(true);

        $message = lang('backend/global.messages.permissionDenied');

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('setJSON')
            ->with([
                'result' => false,
                'message' => $message
            ])
            ->willReturnSelf();

        Services::injectMock('response', $response);

        $filter = new SuperAdminFilter();

        $result = $filter->before($request);

        $this->assertSame($response, $result);
    }

    public function testBeforeRedirectsNonSuperadminToDashboard(): void
    {
        $admin = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'superadmin' => 0
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $filter = new SuperAdminFilter();

        $result = $filter->before($request);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-ban"></i>', session()->getFlashdata('icon'));
    }

    public function testAfterReturnsNull(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $filter = new SuperAdminFilter();

        $this->assertNull($filter->after($request, $response));
    }

    private function createAuthorizationMock(?object $admin): AuthorizationClass
    {
        $db = $this->createMock(ConnectionInterface::class);

        $authorization = $this->getMockBuilder(AuthorizationClass::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['currentAdmin'])
            ->getMock();

        $authorization->method('currentAdmin')->willReturn($admin);

        return $authorization;
    }

    public function testBeforeRedirectsWhenAdminIsNotAuthenticated(): void
    {
        Services::injectMock('authorization', $this->createAuthorizationMock(null));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $filter = new SuperAdminFilter();

        $result = $filter->before($request);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
        $this->assertSame(lang('backend/auth.messages.loginNeeded'), session()->getFlashdata('message'));
    }
}