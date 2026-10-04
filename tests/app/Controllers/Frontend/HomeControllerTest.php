<?php declare(strict_types = 1);

namespace App\Controllers\Frontend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class HomeControllerTest extends CIUnitTestCase
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

        $log
            =
            $this
            ->createMock(
                $logClass
            );

        $this
            ->mockLogger
            =
            $log;

        $ctrlClass
            =
            \App\Controllers\Frontend\HomeController::class;

        $ctrl
            =
            new $ctrlClass();

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

        $log
            =
            $this
            ->mockLogger;

        $ctrl
            ->initController(
                $req,
                $res,
                $log
            );

        $ctrlClass
            =
            \App\Controllers\Frontend\HomeController::class;

        $refReq
            =
            new \ReflectionProperty(
                $ctrlClass,
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
                $ctrlClass,
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
                $ctrlClass,
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
                $log,
                $actualLog
            );
    }

    public function testIndexReturnsString()
    {
        $ctrl
            =
            $this
            ->controller;

        /* La funzione view() restituisce una stringa, verifichiamo il tipo di ritorno */
        $result
            =
            $ctrl
            ->index();

        $this
            ->assertIsString(
                $result
            );

        $this
            ->assertStringContainsString(
                '<title>Welcome to CodeIgniter 4!</title>',
                $result
            );
    }
}
