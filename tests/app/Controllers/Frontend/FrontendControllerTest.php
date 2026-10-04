<?php declare(strict_types = 1);

namespace App\Controllers\Frontend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class FrontendControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $reqClass
            =
            \CodeIgniter\HTTP\RequestInterface::class;

        $req
            =
            $this
            ->createMock(
                $reqClass
            );

        $this
            ->mockRequest
            =
            $req;

        $resClass
            =
            \CodeIgniter\HTTP\ResponseInterface::class;

        $res
            =
            $this
            ->createMock(
                $resClass
            );

        $this
            ->mockResponse
            =
            $res;

        $logClass
            =
            \Psr\Log\LoggerInterface::class;

        $logger
            =
            $this
            ->createMock(
                $logClass
            );

        $this
            ->mockLogger
            =
            $logger;

        $ctrlClass
            =
            \App\Controllers\Frontend\FrontendController::class;

        $ctrl
            =
            $this
            ->getMockForAbstractClass(
                $ctrlClass
            );

        $this
            ->controller
            =
            $ctrl;
    }

    public function testInitControllerSetsPropertiesCorrectly()
    {
        $ctrl
            =
            $this
            ->controller;

        $req
            =
            $this
            ->mockRequest;

        $res
            =
            $this
            ->mockResponse;

        $logger
            =
            $this
            ->mockLogger;

        $ctrl
            ->initController(
                $req,
                $res,
                $logger
            );

        $refReq
            =
            new \ReflectionProperty(
                $ctrl,
                'request'
            );

        $refReq
            ->setAccessible(true);

        $actualReq
            =
            $refReq
            ->getValue(
                $ctrl
            );

        $this
            ->assertSame(
                $req,
                $actualReq
            );

        $refRes
            =
            new \ReflectionProperty(
                $ctrl,
                'response'
            );

        $refRes
            ->setAccessible(true);

        $actualRes
            =
            $refRes
            ->getValue(
                $ctrl
            );

        $this
            ->assertSame(
                $res,
                $actualRes
            );

        $refLog
            =
            new \ReflectionProperty(
                $ctrl,
                'logger'
            );

        $refLog
            ->setAccessible(true);

        $actualLog
            =
            $refLog
            ->getValue(
                $ctrl
            );

        $this
            ->assertSame(
                $logger,
                $actualLog
            );
    }
}