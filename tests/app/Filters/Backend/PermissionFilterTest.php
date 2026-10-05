<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use App\Libraries\Backend\AuthorizationClass;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Filters\Backend\PermissionFilter;

class PermissionFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('message');
        session()->remove('class');
        session()->remove('icon');

        Services::reset();

        parent::tearDown();
    }

    public function testBeforeReturnsNullWhenAdminIsNotAuthenticated(): void
    {
        $authorization = $this->createAuthorizationMock(null);

        Services::injectMock('authorization', $authorization);

        $request = $this->createMock(IncomingRequest::class);

        $filter = new PermissionFilter();

        $this->assertNull($filter->before($request));
    }

    public function testBeforeReturnsNullForSuperadmin(): void
    {
        $admin = (object) [
            'permissions' => (object) ['all' => true]
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);

        $filter = new PermissionFilter();

        $this->assertNull($filter->before($request, ['users.view']));
    }

    public function testBeforeReturnsNullWhenAdminHasRequiredPermission(): void
    {
        $admin = (object) [
            'permissions' => (object) ['users.view' => true]
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);

        $filter = new PermissionFilter();

        $this->assertNull($filter->before($request, ['users.view']));
    }

    public function testBeforeDeniesAccessWhenPermissionValueIsFalse(): void
    {
        $admin = (object) [
            'permissions' => (object) ['users.view' => false]
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $result = (new PermissionFilter())->before($request, ['users.view']);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
    }

    public function testBeforeDeniesAccessWhenPermissionsObjectIsMissing(): void
    {
        Services::injectMock('authorization', $this->createAuthorizationMock((object) ['uuid' => 'admin-uuid']));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $result = (new PermissionFilter())->before($request, ['users.view']);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
    }

    public function testBeforeReturnsJsonWhenPermissionIsMissingOnAjaxPost(): void
    {
        $admin = (object) [
            'permissions' => new \stdClass()
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

        $filter = new PermissionFilter();

        $result = $filter->before($request, ['users.delete']);

        $this->assertSame($response, $result);
    }

    public function testBeforeRedirectsWhenPermissionIsMissingOnStandardRequest(): void
    {
        $admin = (object) [
            'permissions' => new \stdClass()
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $filter = new PermissionFilter();

        $result = $filter->before($request, ['users.delete']);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
        $this->assertSame(lang('backend/global.messages.permissionDenied'), session()->getFlashdata('message'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-ban"></i>', session()->getFlashdata('icon'));
    }

    public function testBeforeDeniesAccessWhenArgumentsAreEmpty(): void
    {
        $admin = (object) [
            'permissions' => new \stdClass()
        ];

        Services::injectMock('authorization', $this->createAuthorizationMock($admin));

        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $filter = new PermissionFilter();

        $result = $filter->before($request);

        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));
        $this->assertSame(lang('backend/global.messages.permissionDenied'), session()->getFlashdata('message'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-ban"></i>', session()->getFlashdata('icon'));
    }

    public function testAfterReturnsNull(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $filter = new PermissionFilter();

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
}
