<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use App\Models\Backend\AuthModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Filters\Backend\AuthorizationFilter;

class AuthorizationFilterTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('intended_url');
        session()->remove('message');
        session()->remove('class');
        session()->remove('icon');

        Services::reset();
        Factories::reset();

        parent::tearDown();
    }

    public function testBeforeReturnsNullWhenAdminIsAlreadyAuthenticated(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);

        $authorization->method('currentAdmin')->willReturn((object) [
            'uuid' => 'admin-uuid'
        ]);

        Services::injectMock('authorization', $authorization);

        $request = $this->createMock(IncomingRequest::class);

        $filter = new AuthorizationFilter();

        $result = $filter->before($request);

        $this->assertNull($result);
    }

    public function testBeforeLogsOutBySessionWhenRememberMeCookieIsMissing(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);

        $authModel->expects($this->once())
            ->method('logoutBySession')
            ->with('timeout');

        $authModel->expects($this->never())
            ->method('logoutByCookie');

        Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->with('backendRememberMe')->willReturn(null);
        $request->method('isAJAX')->willReturn(false);
        $request->method('is')->with('get')->willReturn(false);

        $filter = new AuthorizationFilter();

        $filter->before($request);
    }

    public function testBeforeLogsOutByCookieWhenRememberMeCookieExists(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);

        $authModel->expects($this->never())
            ->method('logoutBySession');

        $authModel->expects($this->once())
            ->method('logoutByCookie')
            ->with('remember-me-cookie', 'timeout');

        Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->with('backendRememberMe')->willReturn('remember-me-cookie');
        $request->method('isAJAX')->willReturn(false);
        $request->method('is')->with('get')->willReturn(false);

        $filter = new AuthorizationFilter();

        $filter->before($request);
    }

    public function testBeforeSetsFlashdataWhenUserIsNotAuthenticated(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);
        $authModel->method('logoutBySession');

        Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->willReturn(null);
        $request->method('isAJAX')->willReturn(false);
        $request->method('is')->with('get')->willReturn(false);

        $filter = new AuthorizationFilter();

        $filter->before($request);

        $this->assertSame(lang('backend/auth.messages.loginNeeded'), session()->getFlashdata('message'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-triangle-exclamation"></i>', session()->getFlashdata('icon'));
    }

    public function testBeforeReturnsJsonResponseForAjaxPostRequest(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);
        $authModel->method('logoutBySession');

        Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->willReturn(null);
        $request->method('isAJAX')->willReturn(true);

        $request->method('is')->willReturnCallback(function(string $method) {
            return $method === 'post';
        });

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('setJSON')
            ->with([
                'result' => 'no_current_user_logged'
            ])
            ->willReturnSelf();

        Services::injectMock('response', $response);

        $filter = new AuthorizationFilter();

        $result = $filter->before($request);

        $this->assertSame($response, $result);
    }

    public function testBeforeReturnsRedirectForStandardRequest(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);
        $authModel->method('logoutBySession');

        Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->willReturn(null);
        $request->method('isAJAX')->willReturn(false);
        $request->method('is')->with('get')->willReturn(false);

        $filter = new AuthorizationFilter();

        $result = $filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/auth'), $result->getHeaderLine('Location'));
    }

    public function testBeforeStoresIntendedUrlForStandardGetRequest(): void
    {
        $authorization = $this->createMock(\App\Libraries\Backend\AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $authModel = $this->createMock(AuthModel::class);
        $authModel->method('logoutBySession');

        \CodeIgniter\Config\Factories::injectMock('models', AuthModel::class, $authModel);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('getCookie')->with('backendRememberMe')->willReturn(null);
        $request->method('isAJAX')->willReturn(false);
        $request->method('is')->with('get')->willReturn(true);

        $filter = new AuthorizationFilter();

        $filter->before($request);

        $this->assertSame(current_url(), session()->get('intended_url'));
    }

    public function testAfterReturnsNull(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $response = $this->createMock(ResponseInterface::class);

        $filter = new AuthorizationFilter();

        $result = $filter->after($request, $response);

        $this->assertNull($result);
    }
}