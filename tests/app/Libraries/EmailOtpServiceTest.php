<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class EmailOtpServiceTest extends CIUnitTestCase
{
	public function testSendReturnsFalseOnDbException()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method(
                'query'
            )
            ->will(
                $this
                ->throwException(
                    new \Exception(
                        'DB Error'
                    )
                )
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $service
            ->send(
                'uuid-123'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSendReturnsFalseWhenAdminNotFound()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $resultClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resultClass
            );

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                null
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $service
            ->send(
                'uuid-123'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testVerifyReturnsTrueWhenCodeExists()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $resultClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resultClass
            );

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                (object) [
                    'id'
                    =>
                    1
                ]
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $service
            ->verify(
                'uuid-123',
                '123456'
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testVerifyReturnsFalseWhenCodeNotFound()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $resultClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resultClass
            );

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                null
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $service
            ->verify(
                'uuid-123',
                '999999'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSendReturnsFalseWhenEmailSendFails()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $resultClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resultClass
            );

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->email
            =
            'admin@test.com';

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $emailClass
            =
            \CodeIgniter\Email\Email::class;

        $emailMock
            =
            $this
            ->createMock(
                $emailClass
            );

        $emailMock
            ->method(
                'send'
            )
            ->willReturn(
                false
            );

        \Config\Services::injectMock(
            'email',
            $emailMock
        );

        $result
            =
            $service
            ->send(
                'uuid-123'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSendReturnsTrueOnSuccessfulEmailSend()
    {
        $serviceClass
            =
            \App\Libraries\EmailOtpService::class;

        $service
            =
            new $serviceClass();

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $resultClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resultClass
            );

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->email
            =
            'admin@test.com';

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $service,
                $serviceClass
            );

        $binder(
            $dbMock
        );

        $emailClass
            =
            \CodeIgniter\Email\Email::class;

        $emailMock
            =
            $this
            ->createMock(
                $emailClass
            );

        $emailMock
            ->method(
                'send'
            )
            ->willReturn(
                true
            );

        \Config\Services::injectMock(
            'email',
            $emailMock
        );

        $result
            =
            $service
            ->send(
                'uuid-123'
            );

        $this
            ->assertTrue(
                $result
            );
    }
}