<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use App\Libraries\Backend\AuthorizationClass;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Filters\Backend\GuestFilter;

class GuestFilterTest extends CIUnitTestCase
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
        $db = $this->createMock(ConnectionInterface::class);

        $authorization = $this->getMockBuilder(AuthorizationClass::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['currentAdmin'])
            ->getMock();

        $authorization->method('currentAdmin')->willReturn(null);

        Services::injectMock('authorization', $authorization);

        $request = $this->createMock(IncomingRequest::class);

        $filter = new GuestFilter();

        $result = $filter->before($request);

        $this->assertNull($result);
    }

    public function testBeforeReturnsJsonResponseForAuthenticatedAdminOnAjaxPost(): void
    {
        $currentAdmin = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi'
        ];

        $db = $this->createMock(ConnectionInterface::class);

        $authorization = $this->getMockBuilder(AuthorizationClass::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['currentAdmin'])
            ->getMock();

        $authorization->method('currentAdmin')->willReturn($currentAdmin);

        Services::injectMock('authorization', $authorization);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('isAJAX')->willReturn(true);
        $request->method('is')->with('post')->willReturn(true);

        $message = sprintf(
            lang('backend/auth.messages.currentSessionOn'),
            esc($currentAdmin->firstname),
            esc($currentAdmin->lastname)
        );

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('setJSON')
            ->with([
                'result' => false,
                'message' => $message
            ])
            ->willReturnSelf();

        Services::injectMock('response', $response);

        $filter = new GuestFilter();

        $result = $filter->before($request);

        $this->assertSame($response, $result);
        $this->assertSame($message, session()->getFlashdata('message'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-triangle-exclamation"></i>', session()->getFlashdata('icon'));
    }

    public function testBeforeRedirectsAuthenticatedAdminToDashboard(): void
    {
        $currentAdmin = (object) [
            'firstname' => 'Mario',
            'lastname' => 'Rossi'
        ];

        $db = $this->createMock(ConnectionInterface::class);

        $authorization = $this->getMockBuilder(AuthorizationClass::class)
            ->setConstructorArgs([$db])
            ->onlyMethods(['currentAdmin'])
            ->getMock();

        $authorization->method('currentAdmin')->willReturn($currentAdmin);

        Services::injectMock('authorization', $authorization);

        $request = $this->createMock(IncomingRequest::class);

        $request->method('isAJAX')->willReturn(false);

        $filter = new GuestFilter();

        $result = $filter->before($request);

        $message = sprintf(
            lang('backend/auth.messages.currentSessionOn'),
            esc($currentAdmin->firstname),
            esc($currentAdmin->lastname)
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame(base_url('backend/dashboard'), $result->getHeaderLine('Location'));

        $this->assertSame($message, session()->getFlashdata('message'));
        $this->assertSame('light text-danger fw-bold', session()->getFlashdata('class'));
        $this->assertSame('<i class="fa-solid fa-triangle-exclamation"></i>', session()->getFlashdata('icon'));
    }

    public function testAfterReturnsNull(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $response = $this->createMock(ResponseInterface::class);

        $filter = new GuestFilter();

        $result = $filter->after($request, $response);

        $this->assertNull($result);
    }
}