<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

class EmailOtpServiceTest extends CIUnitTestCase
{
	use MocksSettings;

	protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettings([
            'Backend\Auth' => [
                'twoFactorDigits' => 6,
                'twoFactorEmailExpiry' => 300,
                'twoFactorEmailFrom' => 'security@essential.test',
                'twoFactorIssuer' => 'Essential'
            ]
        ]);
    }

	protected function tearDown(): void
    {
        Services::reset();
        $this->resetSettingsMocks();

        parent::tearDown();
    }

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

    public function testVerifyUsesUuidCodeAndCurrentExpiryConstraint(): void
    {
        $service = new EmailOtpService();
        $resultSet = $this->createMock(\CodeIgniter\Database\BaseResult::class);
        $resultSet->method('getRow')->willReturn((object) ['id' => 1]);

        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        $db->expects($this->once())
            ->method('query')
            ->with(
                'select id from admins_2fa_codes where admin_uuid = ? and code = ? and expires_at >= ? limit 1',
                $this->callback(function(array $params): bool {
                    $this->assertSame('admin-uuid', $params[0]);
                    $this->assertSame('123456', $params[1]);
                    $this->assertLessThanOrEqual(2, abs(time() - strtotime($params[2])));

                    return true;
                })
            )
            ->willReturn($resultSet);

        $this->injectDatabase($service, $db);

        $this->assertTrue($service->verify('admin-uuid', '123456'));
    }

    public function testSendPersistsConfiguredOtpAndBuildsExpectedEmail(): void
    {
        $service = new EmailOtpService();
        $adminResult = $this->createMock(\CodeIgniter\Database\BaseResult::class);
        $adminResult->method('getRow')->willReturn((object) [
            'email' => 'admin@example.com',
            'firstname' => 'Mario',
            'lastname' => 'Rossi'
        ]);

        $persistedCode = null;
        $queryNumber = 0;
        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        $db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function(string $sql, array $params) use (&$persistedCode, &$queryNumber, $adminResult) {
                $queryNumber++;

                if ($queryNumber === 1):
                    $this->assertSame('insert into admins_2fa_codes (admin_uuid, code, expires_at) values (?, ?, ?)', $sql);
                    $this->assertSame('admin-uuid', $params[0]);
                    $this->assertMatchesRegularExpression('/^\d{6}$/', $params[1]);
                    $this->assertLessThanOrEqual(2, abs((time() + 300) - strtotime($params[2])));
                    $persistedCode = $params[1];

                    return true;
                endif;

                $this->assertSame('select email, firstname, lastname from admins where uuid = ? and status = 1 limit 1', $sql);
                $this->assertSame(['admin-uuid'], $params);

                return $adminResult;
            });

        $this->injectDatabase($service, $db);

        $email = $this->createMock(\CodeIgniter\Email\Email::class);
        $email->expects($this->once())->method('setFrom')->with('security@essential.test', 'Essential');
        $email->expects($this->once())->method('setTo')->with('admin@example.com');
        $email->expects($this->once())->method('setSubject')->with($this->isType('string'));
        $email->expects($this->once())
            ->method('setMessage')
            ->with($this->callback(static function(string $message) use (&$persistedCode): bool {
                return $persistedCode !== null && str_contains($message, $persistedCode);
            }));
        $email->expects($this->once())->method('send')->willReturn(true);

        Services::injectMock('email', $email);

        $this->assertTrue($service->send('admin-uuid'));
    }

    private function injectDatabase(EmailOtpService $service, \CodeIgniter\Database\BaseConnection $db): void
    {
        $property = new \ReflectionProperty(EmailOtpService::class, 'db');
        $property->setAccessible(true);
        $property->setValue($service, $db);
    }
}
