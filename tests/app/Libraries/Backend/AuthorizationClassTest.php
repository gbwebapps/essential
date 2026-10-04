<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

use App\Libraries\Backend\AuthorizationClass;

class AuthorizationClassTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('backendSession');
        Services::reset();

        parent::tearDown();
    }

    public function testRefreshClearsCacheAndReturnsSameInstance(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $admin = (object) ['uuid' => 'admin-uuid'];

        $this->setPrivatePropertyValue($authorization, 'currentAdminCache', $admin);

        $result = $authorization->refresh();

        $this->assertSame($authorization, $result);
        $this->assertNull($this->getPrivatePropertyValue($authorization, 'currentAdminCache'));
    }

    public function testCurrentAdminReturnsCachedAdmin(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $admin = (object) ['uuid' => 'admin-uuid'];

        $this->setPrivatePropertyValue($authorization, 'currentAdminCache', $admin);

        $result = $authorization->currentAdmin();

        $this->assertSame($admin, $result);
    }

    public function testCurrentAdminReturnsNullWhenSessionAndCookieAreMissing(): void
    {
        session()->remove('backendSession');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn(null);

        Services::injectMock('request', $request);

        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $result = $authorization->currentAdmin();

        $this->assertNull($result);
    }

    public function testGetAdminFromSessionReturnsNullWhenSessionIsMissing(): void
    {
        session()->remove('backendSession');

        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromSession');

        $this->assertNull($result);
    }

    public function testGetAdminFromSessionReturnsNullWhenTokenDoesNotExistInDatabase(): void
    {
        $plainToken = 'phpunit-session-token';

        session()->set('backendSession', $plainToken);

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn(null);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromSession');

        $this->assertNull($result);
    }

    public function testGetAdminFromSessionReturnsNullWhenSessionIsExpired(): void
    {
        $plainToken = 'phpunit-expired-session';

        session()->set('backendSession', $plainToken);

        $sessionTime = (int) setting('Backend\Auth')->sessionTime;

        $row = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'last_activity' => Time::now()->subSeconds($sessionTime + 60)->format('Y-m-d H:i:s')
        ];

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn($row);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromSession');

        $this->assertNull($result);
    }

    public function testGetAdminFromSessionReturnsAdminAndUpdatesSlidingExpiration(): void
    {
        $plainToken = 'phpunit-valid-session';

        session()->set('backendSession', $plainToken);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'last_activity' => Time::now()->format('Y-m-d H:i:s')
        ];

        $adminRow = $this->createAdminRow(true);

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($adminRow);

        $queries = 0;

        $db = $this->createMock(ConnectionInterface::class);
        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use (&$queries, $tokenResult, $adminResult) {
            $queries++;

            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromSession');

        $this->assertSame($adminRow, $result);
        $this->assertTrue($result->permissions->all);
        $this->assertSame(3, $queries);
    }

    public function testGetAdminFromSessionReturnsNullWhenAdminCannotBeResolved(): void
    {
        $plainToken = 'phpunit-valid-session-invalid-admin';

        session()->set('backendSession', $plainToken);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'missing-admin',
            'last_activity' => Time::now()->format('Y-m-d H:i:s')
        ];

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn(null);

        $db = $this->createMock(ConnectionInterface::class);
        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($tokenResult, $adminResult) {
            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromSession');

        $this->assertNull($result);
    }

    public function testGetAdminFromCookieReturnsNullWhenCookieIsMissing(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn(null);

        Services::injectMock('request', $request);

        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testGetAdminReturnsNullWhenAdminDoesNotExist(): void
    {
        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn(null);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdmin', ['missing-admin']);

        $this->assertNull($result);
    }

    public function testGetAdminReturnsUniversalPermissionForSuperadmin(): void
    {
        $admin = $this->createAdminRow(true);

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn($admin);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdmin', ['admin-uuid']);

        $this->assertSame($admin, $result);
        $this->assertTrue($result->permissions->all);
    }

    public function testGetAdminReturnsEmptyPermissionsWhenGroupAndUserHaveNoPermissions(): void
    {
        $admin = $this->createAdminRow(false);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($admin);

        $groupResult = $this->createMock(BaseResult::class);
        $groupResult->method('getResultObject')->willReturn([]);

        $userResult = $this->createMock(BaseResult::class);
        $userResult->method('getResultObject')->willReturn([]);

        $db = $this->createMock(ConnectionInterface::class);
        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($adminResult, $groupResult, $userResult) {
            if (str_contains($sql, 'from admins ')):
                return $adminResult;
            endif;

            if (str_contains($sql, 'admins_groups_permissions')):
                return $groupResult;
            endif;

            if (str_contains($sql, 'admins_permissions')):
                return $userResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdmin', ['admin-uuid']);

        $this->assertInstanceOf(\stdClass::class, $result->permissions);
        $this->assertSame([], get_object_vars($result->permissions));
    }

    public function testGetAdminMergesGroupPermissionsAndUserOverrides(): void
    {
        $admin = $this->createAdminRow(false);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($admin);

        $groupResult = $this->createMock(BaseResult::class);
        $groupResult->method('getResultObject')->willReturn([
            (object) ['permission' => 'users.view'],
            (object) ['permission' => 'users.delete'],
            (object) ['permission' => 'messages.view']
        ]);

        $userResult = $this->createMock(BaseResult::class);
        $userResult->method('getResultObject')->willReturn([
            (object) ['permission' => 'users.create', 'allow' => 1],
            (object) ['permission' => 'users.delete', 'allow' => 0]
        ]);

        $db = $this->createMock(ConnectionInterface::class);
        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($adminResult, $groupResult, $userResult) {
            if (str_contains($sql, 'from admins ')):
                return $adminResult;
            endif;

            if (str_contains($sql, 'admins_groups_permissions')):
                return $groupResult;
            endif;

            if (str_contains($sql, 'admins_permissions')):
                return $userResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdmin', ['admin-uuid']);

        $this->assertTrue($result->permissions->{'users.view'});
        $this->assertTrue($result->permissions->{'messages.view'});
        $this->assertTrue($result->permissions->{'users.create'});
        $this->assertFalse(property_exists($result->permissions, 'users.delete'));
    }

    private function createAdminRow(bool $superadmin): object
    {
        return (object) [
            'uuid' => 'admin-uuid',
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => null,
            'status' => 1,
            'note' => null,
            'superadmin' => $superadmin ? 1 : 0,
            'group_id' => 1,
            'created_at' => null,
            'updated_at' => null,
            'suspended_at' => null,
            'resetted_at' => null,
            'deleted_at' => null
        ];
    }

    private function invokePrivateMethod(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    private function setPrivatePropertyValue(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    private function getPrivatePropertyValue(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    public function testGetAdminFromCookieReturnsNullWhenCookieCannotBeDecrypted(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn('invalid-cookie');

        Services::injectMock('request', $request);

        $db = $this->createMock(ConnectionInterface::class);
        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testGetAdminFromCookieReturnsNullWhenTokenDoesNotExist(): void
    {
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-cookie-token');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn(null);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testGetAdminFromCookieReturnsNullWhenTokenIsExpired(): void
    {
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-expired-cookie');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'token_expire' => Time::now()->subSeconds(60)->format('Y-m-d H:i:s')
        ];

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn($tokenRow);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testGetAdminFromCookieReturnsNullWhenTokenHasNoExpiration(): void
    {
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-cookie-without-expiration');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid'
        ];

        $resultSet = $this->createMock(BaseResult::class);
        $resultSet->method('getRow')->willReturn($tokenRow);

        $db = $this->createMock(ConnectionInterface::class);
        $db->expects($this->once())->method('query')->willReturn($resultSet);

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testGetAdminFromCookieReturnsAdminWhenCookieIsValid(): void
    {
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-valid-cookie');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'token_expire' => Time::now()->addSeconds(3600)->format('Y-m-d H:i:s')
        ];

        $adminRow = $this->createAdminRow(true);

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($adminRow);

        $db = $this->createMock(ConnectionInterface::class);

        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($tokenResult, $adminResult) {
            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertSame($adminRow, $result);
        $this->assertTrue($result->permissions->all);
    }

    public function testGetAdminFromCookieReturnsNullWhenCookieIsValidButAdminDoesNotExist(): void
    {
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-valid-cookie-invalid-admin');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'missing-admin',
            'token_expire' => Time::now()->addSeconds(3600)->format('Y-m-d H:i:s')
        ];

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn(null);

        $db = $this->createMock(ConnectionInterface::class);

        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($tokenResult, $adminResult) {
            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $this->invokePrivateMethod($authorization, 'getAdminFromCookie');

        $this->assertNull($result);
    }

    public function testCurrentAdminReturnsAndCachesAdminFromSession(): void
    {
        session()->set('backendSession', 'phpunit-current-admin-session');

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'last_activity' => Time::now()->format('Y-m-d H:i:s')
        ];

        $adminRow = $this->createAdminRow(true);

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($adminRow);

        $db = $this->createMock(ConnectionInterface::class);

        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($tokenResult, $adminResult) {
            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $authorization->currentAdmin();

        $this->assertSame($adminRow, $result);
        $this->assertSame($adminRow, $this->getPrivateProperty($authorization, 'currentAdminCache'));
    }

    public function testCurrentAdminReturnsAndCachesAdminFromCookie(): void
    {
        session()->remove('backendSession');

        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $cookie = $crypto->encrypt('phpunit-current-admin-cookie');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getCookie')->with('backendRememberMe')->willReturn($cookie);

        Services::injectMock('request', $request);

        $tokenRow = (object) [
            'token_hash' => 'hash',
            'admin_uuid' => 'admin-uuid',
            'token_expire' => Time::now()->addSeconds(3600)->format('Y-m-d H:i:s')
        ];

        $adminRow = $this->createAdminRow(true);

        $tokenResult = $this->createMock(BaseResult::class);
        $tokenResult->method('getRow')->willReturn($tokenRow);

        $adminResult = $this->createMock(BaseResult::class);
        $adminResult->method('getRow')->willReturn($adminRow);

        $db = $this->createMock(ConnectionInterface::class);

        $db->method('query')->willReturnCallback(function(string $sql, array $params = []) use ($tokenResult, $adminResult) {
            if (str_contains($sql, 'select * from admins_tokens')):
                return $tokenResult;
            endif;

            if (str_contains($sql, 'update admins_tokens')):
                return true;
            endif;

            if (str_contains($sql, 'from admins')):
                return $adminResult;
            endif;

            return false;
        });

        $authorization = new AuthorizationClass($db);

        $result = $authorization->currentAdmin();

        $this->assertSame($adminRow, $result);
        $this->assertSame($adminRow, $this->getPrivateProperty($authorization, 'currentAdminCache'));
    }
}