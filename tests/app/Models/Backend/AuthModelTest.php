<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Config\Factories;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

if (! function_exists(__NAMESPACE__ . '\log_admin_activity')) {
    function log_admin_activity(string $action, string $section, string $details, ?object $currentAdmin = null): bool
    {
        return true;
    }
}

final class AuthModelTest extends CIUnitTestCase
{
    use MocksSettings;

    private const EMAIL = 'admin@example.test';
    private const UUID = 'admin-uuid-1';
    private const PASSWORD = 'CorrectPassword123!';
    private const IP = '127.0.0.1';

    /** @var array<string, mixed> */
    private array $defaultConfig;

    protected function setUp(): void
    {
        parent::setUp();

        Factories::reset();
        Services::reset();
        helper(['settings', 'cookie']);

        $this->defaultConfig = [
            'attempts' => true,
            'attemptsInterval' => 900,
            'attemptsLimit' => 5,
            'attemptsIpMultiplier' => 20,
            'activationTime' => 3600,
            'hashKey' => 'phpunit-auth-hash-key',
            'passwordRegex' => '/.*/',
            'rememberMeTime' => 2592000,
            'resetAttempts' => true,
            'resetAttemptsInterval' => 900,
            'resetAttemptsLimit' => 5,
            'resetPasswordInterval' => 900,
            'resetPasswordLimit' => 5,
            'sessionTime' => 3600,
            'twoFactor' => false,
            'twoFactorAttempts' => true,
            'twoFactorAttemptsInterval' => 300,
            'twoFactorDigits' => 6,
            'twoFactorEmailExpiry' => 300,
            'twoFactorLimit' => 5,
            'twoFactorTime' => 300,
            'twoFactorWindow' => 1,
        ];

        $this->mockSettings(['Backend\\Auth' => $this->defaultConfig]);

        $crypto = $this->getMockBuilder(\App\Libraries\CryptoService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['encrypt', 'decrypt'])
            ->getMock();
        $crypto->method('encrypt')->willReturn('encrypted-token');
        $crypto->method('decrypt')->willReturnCallback(static fn (string $value): string => $value);
        Services::injectMock('crypto', $crypto);

        session()->remove(['backendSession', 'login_log_id', 'auth_2fa_pending']);
    }

    protected function tearDown(): void
    {
        session()->remove(['backendSession', 'login_log_id', 'auth_2fa_pending']);
        $this->resetSettingsMocks();
        Factories::reset();
        Services::reset();

        parent::tearDown();
    }

    private function createStatefulSessionMock(array $initialState = []): \CodeIgniter\Session\Session
    {
        $state = $initialState;

        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'has', 'remove', 'regenerate', 'setFlashdata'])
            ->getMock();

        $session->method('get')->willReturnCallback(static function (?string $key = null) use (&$state) {
            return $key === null ? $state : ($state[$key] ?? null);
        });

        $session->method('has')->willReturnCallback(static fn (string $key): bool => array_key_exists($key, $state));

        $session->method('set')->willReturnCallback(static function ($key, $value = null) use (&$state): void {
            if (is_array($key)):
                foreach ($key as $name => $item):
                    $state[$name] = $item;
                endforeach;
                return;
            endif;

            $state[$key] = $value;
        });

        $session->method('remove')->willReturnCallback(static function ($key) use (&$state): void {
            foreach ((array) $key as $name):
                unset($state[$name]);
            endforeach;
        });

        $session->method('regenerate')->willReturnCallback(static function (bool $destroy = false): void {
        });

        $session->method('setFlashdata')->willReturnCallback(static function ($data, $value = null): void {
        });

        Services::injectMock('session', $session);

        return $session;
    }

    public function testValidateLoginRulesExposeEmailAndPasswordSecurityRules(): void
    {
        $passwordRegex = '/^PasswordSicura1!$/';
        $model = $this->createModel(
            $this->createMock(BaseConnection::class),
            ['passwordRegex' => $passwordRegex]
        );

        $rules = $model->validateLoginRules();

        $this->assertSame(['email', 'password'], array_keys($rules));
        $this->assertContains('valid_email', $rules['email']['rules']);
        $this->assertContains('required', $rules['password']['rules']);
        $this->assertContains("regex_match[{$passwordRegex}]", $rules['password']['rules']);
        $this->assertNotContains('max_length[255]', $rules['password']['rules']);
    }

    public function testValidateResetPasswordRulesRequireValidEmail(): void
    {
        $model = $this->createModel($this->createMock(BaseConnection::class));

        $rules = $model->validateResetPasswordRules();

        $this->assertSame(['email'], array_keys($rules));
        $this->assertSame(['required', 'valid_email', 'trim'], $rules['email']['rules']);
    }

    public function testValidateSetPasswordRulesRequirePasswordConfirmationAndToken(): void
    {
        $model = $this->createModel($this->createMock(BaseConnection::class));

        $rules = $model->validateSetPasswordRules();

        $this->assertSame(['password', 'confirmPassword', 'token'], array_keys($rules));
        $this->assertContains('regex_match[/.*/]', $rules['password']['rules']);
        $this->assertContains('matches[password]', $rules['confirmPassword']['rules']);
        $this->assertSame('required|checkTokenRule', $rules['token']['rules']);
    }

    public function testValidateVerifyRulesRequireExactlySixDigitsIncludingLeadingZeroes(): void
    {
        $model = $this->createModel($this->createMock(BaseConnection::class));

        $rules = $model->validateVerifyRules();

        $this->assertSame(['required', 'regex_match[/^[0-9]{6}$/]'], $rules['code']['rules']);

        $validation = Services::validation();
        $validation->setRules($rules);
        $this->assertTrue($validation->run(['code' => '012345']));

        $validation->reset()->setRules($rules);
        $this->assertFalse($validation->run(['code' => '12345']));

        $validation->reset()->setRules($rules);
        $this->assertFalse($validation->run(['code' => '12345a']));
    }

    public function testLoginRecordsUnknownEmailWithoutRevealingAccountExistence(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(4))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult(null);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.loginFailed')], $result);
        $attempt = $this->findQuery($queries, 'insert into admins_attempts');
        $this->assertNull($attempt[1][0]);
        $this->assertSame(hash_hmac('sha256', self::EMAIL, $this->defaultConfig['hashKey']), $attempt[1][1]);
        $this->assertSame(self::IP, $attempt[1][2]);
    }

    public function testLoginThrottlingRenewsIdentifierWindowAndRejectsAccess(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 5, 'ip_attempts' => 1]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
        $this->assertTrue($this->queriesContain($queries, 'update admins_attempts set timestamp = ? where timestamp > ? and email_hash = ?'));
    }

    public function testLoginThrottlingAlsoBlocksIpWideCredentialSpraying(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 1, 'ip_attempts' => 100]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
        $this->assertFalse($this->queriesContain($queries, 'select uuid, firstname, lastname, email, password_hash from admins'));
        $this->assertTrue($this->queriesContain($queries, 'update admins_attempts set timestamp = ? where timestamp > ? and ip_address = ?'));
    }

    public function testLoginThrottlingRenewsBothWindowsWhenBothLimitsAreReached(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 5, 'ip_attempts' => 100]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
        $this->assertTrue($this->queriesContain($queries, 'update admins_attempts set timestamp = ? where timestamp > ? and (email_hash = ? or ip_address = ?)'));
    }

    public function testLoginWrongPasswordRecordsAttemptInsideTransaction(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult($this->admin(['password_hash' => password_hash('different', PASSWORD_DEFAULT)]));
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertFalse($result['result']);
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_attempts where timestamp <= ?'));
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_attempts'));
        $attempt = $this->findQuery($queries, 'insert into admins_attempts');
        $this->assertSame(self::UUID, $attempt[1][0]);
        $this->assertSame(hash_hmac('sha256', self::EMAIL, $this->defaultConfig['hashKey']), $attempt[1][1]);
        $this->assertSame(self::IP, $attempt[1][2]);
    }

    public function testLoginWrongPasswordDoesNotWriteAttemptWhenAttemptsAreDisabled(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult($this->admin(['password_hash' => password_hash('different', PASSWORD_DEFAULT)]));
            endif;

            return true;
        });
        $db->expects($this->never())->method('transBegin');
        $db->expects($this->never())->method('transCommit');

        $result = $this->createModel($db, ['attempts' => false])->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertFalse($result['result']);
        $this->assertFalse($this->queriesContain($queries, 'insert into admins_attempts'));
    }

    public function testLoginWithTotpTwoFactorStoresOnlyPendingServerSessionAndDoesNotCreateLoginToken(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'select method from admins_2fa')):
                return $this->dbResult((object) ['method' => 'totp']);
            endif;

            return true;
        });
        $db->expects($this->never())->method('transBegin');

        $result = $this->createModel($db, ['twoFactor' => true])->login(['email' => self::EMAIL, 'password' => self::PASSWORD, 'rememberMe' => true], $this->request());

        $this->assertSame(['result' => '2fa_required', 'method' => 'totp'], $result);
        $pending = session()->get('auth_2fa_pending');
        $this->assertSame(self::UUID, $pending['admin_uuid']);
        $this->assertTrue($pending['rememberMe']);
        $this->assertSame('totp', $pending['method']);
        $this->assertIsInt($pending['created_at']);
        $this->assertSame(base_url('backend/dashboard'), $pending['redirect']);
        $this->assertNull(session()->get('backendSession'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_attempts'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_2fa_codes'));
        $this->assertFalse($this->queriesContain($queries, 'insert into admins_tokens'));
    }

    public function testLoginSuccessfulSessionCreatesTokenAndLogAndClearsAttempts(): void
    {
        $this->createStatefulSessionMock();

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->method('insertID')->willReturnOnConsecutiveCalls(10, 20);
        $db->expects($this->exactly(2))->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => true], $result);
        $this->assertIsString(session()->get('backendSession'));
        $this->assertNotSame('', session()->get('backendSession'));
        $this->assertSame(20, session()->get('login_log_id'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_attempts'));
        $this->assertTrue($this->queriesContain($queries, 'update admins_logs as l join admins_tokens as t'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens where admin_uuid = ? and token_type = ?'));
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_tokens'));
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_logs'));
    }

    public function testLoginRememberMeCreatesCookieTokenWithoutServerSessionToken(): void
    {
        $this->createStatefulSessionMock();
        
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email, password_hash from admins')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->method('insertID')->willReturnOnConsecutiveCalls(11, 21);
        $db->expects($this->exactly(2))->method('transCommit')->willReturn(true);

        $crypto = $this->getMockBuilder(\stdClass::class)->addMethods(['encrypt'])->getMock();
        $crypto->expects($this->once())->method('encrypt')->with($this->isType('string'))->willReturn('encrypted-remember-token');
        Services::injectMock('crypto', $crypto);

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD, 'rememberMe' => true], $this->request());

        $this->assertSame(['result' => true], $result);
        $this->assertNull(session()->get('backendSession'));
        $this->assertSame(21, session()->get('login_log_id'));

        $tokenInsert = $this->findQuery($queries, 'insert into admins_tokens');
        $this->assertSame('cookie', $tokenInsert[1][5]);
        $this->assertFalse($this->queriesContain($queries, 'delete from admins_tokens where admin_uuid = ? and token_type = ?'));
    }

    public function testLoginFailsClosedWithoutRollbackBeforeTransaction(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willThrowException(new \RuntimeException('database failure'));
        $db->expects($this->never())->method('transRollback');

        $result = $this->createModel($db)->login(['email' => self::EMAIL, 'password' => self::PASSWORD], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.loginFailed')], $result);
    }

    public function testResetPasswordDoesNotRevealUnknownAdmin(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(4))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select uuid, firstname, lastname, email from admins')):
                return $this->dbResult(null);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame(['result' => true, 'message' => lang('backend/auth.messages.resetPasswordSuccess')], $result);
        $attempt = $this->findQuery($queries, 'insert into admins_reset_attempts');
        $this->assertSame(hash_hmac('sha256', self::EMAIL, $this->defaultConfig['hashKey']), $attempt[1][0]);
        $this->assertSame(self::IP, $attempt[1][1]);
    }

    public function testResetPasswordBypassesAttemptsTableWhenControlIsDisabled(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->with(
            $this->callback(static fn (string $sql): bool => str_contains($sql, 'select uuid, firstname, lastname, email from admins')),
            [self::EMAIL]
        )->willReturn($this->dbResult(null));

        $result = $this->createModel($db, ['resetAttempts' => false])
            ->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame(['result' => true, 'message' => lang('backend/auth.messages.resetPasswordSuccess')], $result);
    }

    public function testResetPasswordBlocksRequestWhenAttemptsControlFails(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willThrowException(new \RuntimeException('Attempts table unavailable'));

        $result = $this->createModel($db)->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')], $result);
    }

    public function testResetPasswordRateLimitReturnsGenericResponseWithoutAdminLookup(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 5, 'ip_attempts' => 1]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertFalse($this->queriesContain($queries, 'select uuid, firstname, lastname, email from admins'));
        $this->assertFalse($this->queriesContain($queries, 'insert into admins_reset_attempts'));
        $this->assertTrue($this->queriesContain($queries, 'update admins_reset_attempts set timestamp'));
        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
    }

    public function testResetPasswordRateLimitAlsoBlocksExcessiveIpRequests(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 1, 'ip_attempts' => 100]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertFalse($this->queriesContain($queries, 'select uuid, firstname, lastname, email from admins'));
        $this->assertTrue($this->queriesContain($queries, 'update admins_reset_attempts set timestamp = ? where timestamp > ? and ip_address = ?'));
        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
    }

    public function testResetPasswordSuccessPersistsActivationTokenAndSendsEmail(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select uuid, firstname, lastname, email from admins')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transStatus')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $email = $this->getMockBuilder(\CodeIgniter\Email\Email::class)->disableOriginalConstructor()->onlyMethods(['setTo', 'setSubject', 'setMessage', 'send'])->getMock();
        $email->method('setTo')->willReturnSelf();
        $email->method('setSubject')->willReturnSelf();
        $email->method('setMessage')->willReturnSelf();
        $email->expects($this->once())->method('send')->willReturn(true);
        Services::injectMock('email', $email);

        $result = $this->createModel($db, ['resetAttempts' => false])->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertTrue($result['result']);
        $this->assertTrue($this->queriesContain($queries, 'update admins set resetted_at'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens'));
        $insert = $this->findQuery($queries, 'insert into admins_tokens');
        $this->assertSame(self::UUID, $insert[1][0]);
        $this->assertSame('activation', $insert[1][5]);
    }

    public function testResetPasswordReportsEmailFailureAfterSuccessfulDatabaseCommit(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql) {
            if (str_contains($sql, 'select uuid, firstname, lastname, email from admins')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->expects($this->exactly(2))->method('transCommit')->willReturn(true);

        $email = $this->getMockBuilder(\CodeIgniter\Email\Email::class)->disableOriginalConstructor()->onlyMethods(['setTo', 'setSubject', 'setMessage', 'send', 'printDebugger'])->getMock();
        $email->method('setTo')->willReturnSelf();
        $email->method('setSubject')->willReturnSelf();
        $email->method('setMessage')->willReturnSelf();
        $email->expects($this->once())->method('send')->willReturn(false);
        $email->method('printDebugger')->willReturn('smtp failure');
        Services::injectMock('email', $email);

        $result = $this->createModel($db, ['resetAttempts' => false])->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame([
            'result' => true,
            'message' => lang('backend/auth.messages.resetPasswordSuccess'),
        ], $result);
    }

    public function testResetPasswordRollsBackWhenTransactionStatusFails(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select uuid, firstname, lastname, email from admins')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transStatus')->willReturn(false);
        $db->expects($this->once())->method('transRollback')->willReturn(true);
        $db->expects($this->never())->method('transCommit');

        $result = $this->createModel($db, ['resetAttempts' => false])->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')], $result);
        $this->assertTrue($this->queriesContain($queries, 'update admins set resetted_at'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens'));
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_tokens'));
    }

    public function testResetPasswordCatchesDatabaseExceptionAndRollsBack(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql) {
            if (str_contains($sql, 'select uuid, firstname, lastname, email from admins')):
                return $this->dbResult($this->admin());
            endif;

            throw new \RuntimeException('write failure');
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transRollback')->willReturn(true);

        $result = $this->createModel($db, ['resetAttempts' => false])->resetPassword(['email' => self::EMAIL], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')], $result);
    }

    public function testSetPasswordRejectsUnknownActivationToken(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willReturn($this->dbResult(null));
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transRollback')->willReturn(true);

        $result = $this->createModel($db)->setPassword(['token' => 'activation-token', 'password' => self::PASSWORD]);

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.setPasswordFailed')], $result);
    }

    public function testSetPasswordUpdatesPasswordAndConsumesActivationToken(): void
    {
        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'from admins as u')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transStatus')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);
        $db->expects($this->never())->method('transRollback');

        $result = $this->createModel($db)->setPassword(['token' => 'activation-token', 'password' => self::PASSWORD]);

        $this->assertTrue($result['result']);
        $update = $this->findQuery($queries, 'update admins set password_hash');
        $this->assertTrue(password_verify(self::PASSWORD, $update[1][0]));
        $this->assertNull($update[1][1]);
        $this->assertSame(self::UUID, $update[1][2]);
        $this->assertTrue($this->queriesContain($queries, 'update admins_logs as l join admins_tokens as t'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens'));
    }

    public function testSetPasswordRollsBackAndDoesNotCommitWhenTransactionFails(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql) {
            if (str_contains($sql, 'from admins as u')):
                return $this->dbResult($this->admin());
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transStatus')->willReturn(false);
        $db->expects($this->once())->method('transRollback')->willReturn(true);
        $db->expects($this->never())->method('transCommit');

        $result = $this->createModel($db)->setPassword(['token' => 'activation-token', 'password' => self::PASSWORD]);

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.setPasswordError')], $result);
    }

    public function testCheckAuthTokenReturnsFalseWhenTokenDoesNotExist(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willReturn($this->dbResult(null));

        $this->assertFalse($this->createModel($db)->checkAuthToken('activation-token'));
    }

    public function testCheckAuthTokenReturnsFalseWhenTokenIsExpired(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willReturn($this->dbResult((object) ['token_expire' => date('Y-m-d H:i:s', time() - 60)]));

        $this->assertFalse($this->createModel($db)->checkAuthToken('activation-token'));
    }

    public function testCheckAuthTokenReturnsTrueOnlyForFutureExpiry(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willReturn($this->dbResult((object) ['token_expire' => date('Y-m-d H:i:s', time() + 3600)]));

        $this->assertTrue($this->createModel($db)->checkAuthToken('activation-token'));
    }

    public function testCheckAuthTokenFailsClosedOnDatabaseException(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willThrowException(new \RuntimeException('read failure'));

        $this->assertFalse($this->createModel($db)->checkAuthToken('activation-token'));
    }

    public function testGetEmailOtpExpiresAtReturnsStoredExpiration(): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + 60);
        $db = $this->createMock(BaseConnection::class);

        $db->expects($this->once())->method('query')->with(
            'select expires_at from admins_2fa_codes where admin_uuid = ? order by id desc limit 1',
            [self::UUID]
        )->willReturn($this->dbResult((object) ['expires_at' => $expiresAt]));

        $result = $this->createModel($db)->getEmailOtpExpiresAt(self::UUID);

        $this->assertSame(strtotime($expiresAt), $result);
    }

    public function testGetEmailOtpExpiresAtReturnsNullWithoutCode(): void
    {
        $db = $this->createMock(BaseConnection::class);

        $db->expects($this->once())->method('query')->willReturn($this->dbResult(null));

        $result = $this->createModel($db)->getEmailOtpExpiresAt(self::UUID);

        $this->assertNull($result);
    }

    public function testVerifyFailsBeforeDatabaseAccessWhenPendingSessionIsMissing(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->never())->method('query');

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.sessionExpired')], $result);
    }

    public function testVerifyRejectsExpiredPendingSessionAndDeletesEmailCode(): void
    {
        $this->createStatefulSessionMock([
            'auth_2fa_pending' => [
                'admin_uuid' => self::UUID,
                'method' => 'email',
                'rememberMe' => false,
                'created_at' => time() - 301,
            ],
        ]);

        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->with(
            'delete from admins_2fa_codes where admin_uuid = ?',
            [self::UUID]
        )->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.sessionExpired')], $result);
        $this->assertNull(session()->get('auth_2fa_pending'));
    }

    public function testVerifyRejectsMissingOrDisabledAdmin(): void
    {
        $this->setPendingTwoFactor('email');

        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->with(
            $this->callback(static fn (string $sql): bool => str_contains($sql, 'status = 1') && str_contains($sql, 'suspended_at is null') && str_contains($sql, 'deleted_at is null')),
            [self::UUID]
        )->willReturn($this->dbResult(null));

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.verifyFailed')], $result);
    }

    public function testVerifyThrottlingExtendsLatestAttemptAndRejectsCode(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 5, 'ip_attempts' => 1]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
        $this->assertTrue($this->queriesContain($queries, 'update admins_2fa_attempts set timestamp = ? where timestamp > ? and admin_uuid = ? and method = ?'));
    }

    public function testVerifyThrottlingAlsoBlocksIpWideOtpSpraying(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 1, 'ip_attempts' => 100]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')], $result);
        $this->assertFalse($this->queriesContain($queries, 'select expires_at from admins_2fa_codes'));
        $this->assertTrue($this->queriesContain($queries, 'update admins_2fa_attempts set timestamp = ? where timestamp > ? and ip_address = ?'));
    }

    public function testVerifyWrongEmailCodeRecordsSecurityAttempt(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select expires_at from admins_2fa_codes')):
                return $this->dbResult(null);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.wrongCode')], $result);
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_2fa_attempts where timestamp <= ?'));
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_2fa_attempts'));
    }

    public function testVerifyDoesNotUseAttemptsTableWhenControlIsDisabled(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'select expires_at from admins_2fa_codes')):
                return $this->dbResult(null);
            endif;

            return true;
        });
        $db->expects($this->never())->method('transBegin');
        $db->expects($this->never())->method('transCommit');

        $result = $this->createModel($db, ['twoFactorAttempts' => false])->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.wrongCode')], $result);
        $this->assertFalse($this->queriesContain($queries, 'admins_2fa_attempts'));
    }

    public function testVerifyExpiredEmailCodeIsRejectedAndCountsAsAttempt(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select expires_at from admins_2fa_codes')):
                return $this->dbResult((object) ['expires_at' => date('Y-m-d H:i:s', time() - 60)]);
            endif;

            return true;
        });
        $db->expects($this->once())->method('transBegin')->willReturn(true);
        $db->expects($this->once())->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.expiredCode')], $result);
        $this->assertTrue($this->queriesContain($queries, 'insert into admins_2fa_attempts'));
    }

    public function testVerifyValidEmailCodeCleansTemporaryStateAndCompletesSessionLogin(): void
    {
        $this->setPendingTwoFactor('email');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select * from admins where uuid')):
                return $this->dbResult($this->admin());
            endif;

            if (str_contains($sql, 'as identifier_attempts')):
                return $this->dbResult((object) ['identifier_attempts' => 0, 'ip_attempts' => 0]);
            endif;

            if (str_contains($sql, 'select expires_at from admins_2fa_codes')):
                return $this->dbResult((object) ['expires_at' => date('Y-m-d H:i:s', time() + 300)]);
            endif;

            return true;
        });
        $db->method('transBegin')->willReturn(true);
        $db->method('transStatus')->willReturn(true);
        $db->method('insertID')->willReturnOnConsecutiveCalls(30, 40);
        $db->expects($this->exactly(2))->method('transCommit')->willReturn(true);

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => true, 'redirect' => base_url('backend/dashboard')], $result);
        $this->assertNull(session()->get('auth_2fa_pending'));
        $this->assertIsString(session()->get('backendSession'));
        $this->assertSame(40, session()->get('login_log_id'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_2fa_attempts'));
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_2fa_codes'));
    }

    public function testVerifyFailsClosedWithoutRollbackBeforeTransaction(): void
    {
        $this->setPendingTwoFactor('email');

        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->once())->method('query')->willThrowException(new \RuntimeException('2fa failure'));
        $db->expects($this->never())->method('transRollback');

        $result = $this->createModel($db)->verify(['code' => '123456'], $this->request());

        $this->assertSame(['result' => false, 'message' => lang('backend/auth.messages.verifyError')], $result);
    }

    public function testLogoutBySessionDoesNothingWithoutAuthenticatedSession(): void
    {
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->never())->method('query');

        $this->createModel($db)->logoutBySession();

        $this->assertNull(session()->get('backendSession'));
    }

    public function testLogoutBySessionDeletesTokenAndClearsLocalSessionWithoutLogId(): void
    {
        session()->set('backendSession', 'raw-session-token');

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $lastActivity = '2026-10-08 10:11:12';
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries, $lastActivity) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select id, last_activity')):
                return $this->dbResult((object) ['id' => 55, 'last_activity' => $lastActivity]);
            endif;

            return true;
        });

        $this->createModel($db)->logoutBySession();

        $this->assertNull(session()->get('backendSession'));
        $update = $this->findQuery($queries, 'update admins_logs set logout');
        $this->assertSame('manual', $update[1][1]);
        $this->assertSame(55, $update[1][2]);
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens'));
        $delete = $this->findQuery($queries, 'delete from admins_tokens');
        $this->assertSame('session', $delete[1][1]);
    }

    public function testLogoutBySessionClosesOpenLogAndUsesTokenActivityForTimeout(): void
    {
        session()->set('backendSession', 'raw-session-token');
        session()->set('login_log_id', 99);

        $queries = [];
        $lastActivity = '2026-10-08 10:11:12';
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries, $lastActivity) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select id, last_activity')):
                return $this->dbResult((object) ['id' => 55, 'last_activity' => $lastActivity]);
            endif;

            return true;
        });

        $this->createModel($db)->logoutBySession('timeout');

        $update = $this->findQuery($queries, 'update admins_logs set logout');
        $this->assertSame([$lastActivity, 'timeout', 99], $update[1]);
        $this->assertNull(session()->get('backendSession'));
        $this->assertNull(session()->get('login_log_id'));
    }

    public function testLogoutBySessionUpdatesOnlyAnOpenLog(): void
    {
        session()->set('backendSession', 'raw-session-token');
        session()->set('login_log_id', 99);

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select id, last_activity')):
                return $this->dbResult((object) ['id' => 55, 'last_activity' => null]);
            endif;

            return true;
        });

        $this->createModel($db)->logoutBySession();

        $update = $this->findQuery($queries, 'update admins_logs set logout');
        $this->assertStringContainsString('where id = ? and logout is null', $update[0]);
        $this->assertTrue($this->queriesContain($queries, 'delete from admins_tokens'));
    }

    public function testLogoutByCookieAlwaysDeletesCookieToken(): void
    {
        $crypto = $this->getMockBuilder(\App\Libraries\CryptoService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['decrypt'])
            ->getMock();
        $crypto->expects($this->once())->method('decrypt')->with('encrypted-cookie-token')->willReturn('raw-cookie-token');
        Services::injectMock('crypto', $crypto);

        $queries = [];
        $db = $this->createMock(BaseConnection::class);
        $lastActivity = '2026-10-08 09:08:07';
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries, $lastActivity) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select id, last_activity')):
                return $this->dbResult((object) ['id' => 77, 'last_activity' => $lastActivity]);
            endif;

            return true;
        });

        $this->createModel($db)->logoutByCookie('encrypted-cookie-token');

        $update = $this->findQuery($queries, 'update admins_logs set logout');
        $this->assertSame('manual', $update[1][1]);
        $this->assertSame(77, $update[1][2]);
        $delete = $this->findQuery($queries, 'delete from admins_tokens');
        $this->assertSame(hash_hmac('sha256', 'raw-cookie-token', $this->defaultConfig['hashKey']), $delete[1][0]);
        $this->assertSame('cookie', $delete[1][1]);
    }

    public function testLogoutByCookieClosesOpenLogAndUsesTokenActivityForTimeout(): void
    {
        session()->set('login_log_id', 101);

        $queries = [];
        $lastActivity = '2026-10-08 09:08:07';
        $db = $this->createMock(BaseConnection::class);
        $db->expects($this->exactly(3))->method('query')->willReturnCallback(function (string $sql, array $params = []) use (&$queries, $lastActivity) {
            $queries[] = [$sql, $params];

            if (str_contains($sql, 'select id, last_activity')):
                return $this->dbResult((object) ['id' => 77, 'last_activity' => $lastActivity]);
            endif;

            return true;
        });

        $this->createModel($db)->logoutByCookie('raw-cookie-token', 'timeout');

        $update = $this->findQuery($queries, 'update admins_logs set logout');
        $this->assertSame([$lastActivity, 'timeout', 101], $update[1]);
        $this->assertNull(session()->get('login_log_id'));
    }

    public function testLogoutMethodsFailClosedWhenDatabaseThrows(): void
    {
        session()->set('backendSession', 'raw-session-token');

        $db = $this->createMock(BaseConnection::class);
        $db->method('query')->willThrowException(new \RuntimeException('logout failure'));

        $model = $this->createModel($db);
        $model->logoutBySession();
        $model->logoutByCookie('raw-cookie-token');

        $this->addToAssertionCount(1);
    }

    /**
     * Crea un'istanza unit-test dell'AuthModel senza eseguire il costruttore del BaseModel.
     *
     * Il model mantiene integralmente i metodi di produzione; vengono sostituite soltanto le
     * dipendenze esterne (database e configurazione) per impedire accessi al DB di sviluppo.
     *
     * @param BaseConnection $db Connessione database mockata.
     * @param array<string, mixed> $overrides Override della configurazione Auth.
     * @return AuthModel Istanza pronta per il test.
     */
    private function createModel(BaseConnection $db, array $overrides = []): AuthModel
    {
        $model = $this->getMockBuilder(AuthModel::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $config = (object) array_replace($this->defaultConfig, $overrides);

        $inject = function (BaseConnection $connection, object $config): void {
            $this->db = $connection;
            $this->config = $config;
        };

        $binder = \Closure::bind($inject, $model, AuthModel::class);
        $binder($db, $config);

        return $model;
    }

    /**
     * Crea una request deterministica con IP e User-Agent controllati.
     *
     * @return IncomingRequest Request mockata.
     */
    private function request(): IncomingRequest
    {
        $agent = $this->createMock(UserAgent::class);
        $agent->method('getAgentString')->willReturn('PHPUnit AuthModel');

        $request = $this->createMock(IncomingRequest::class);
        $request->method('getIPAddress')->willReturn(self::IP);
        $request->method('getUserAgent')->willReturn($agent);

        return $request;
    }

    /**
     * Crea un risultato DB con una singola riga.
     *
     * @param object|null $row Riga restituita da getRow().
     * @return BaseResult Result mockato.
     */
    private function dbResult(?object $row): BaseResult
    {
        $result = $this->createMock(BaseResult::class);
        $result->method('getRow')->willReturn($row);

        return $result;
    }

    /**
     * Restituisce un admin valido sovrascrivibile per scenari specifici.
     *
     * @param array<string, mixed> $overrides Proprietà da sovrascrivere.
     * @return object Admin fixture.
     */
    private function admin(array $overrides = []): object
    {
        return (object) array_replace([
            'uuid' => self::UUID,
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'email' => self::EMAIL,
            'password_hash' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'times' => 0,
            'status' => 1,
            'deleted_at' => null,
        ], $overrides);
    }

    /**
     * Configura lo stato temporaneo server-side richiesto dal secondo fattore.
     *
     * @param string $method Metodo 2FA.
     * @param bool $rememberMe Stato remember-me da preservare.
     * @return void
     */
    private function setPendingTwoFactor(string $method, bool $rememberMe = false): void
    {
        $this->createStatefulSessionMock([
            'auth_2fa_pending' => [
                'admin_uuid' => self::UUID,
                'method' => $method,
                'rememberMe' => $rememberMe,
                'created_at' => time(),
                'redirect' => base_url('backend/dashboard'),
            ],
        ]);
    }

    /**
     * Verifica se una query SQL è stata emessa durante lo scenario.
     *
     * @param array<int, array{0:string,1:array}> $queries Query catturate.
     * @param string $needle Frammento SQL cercato.
     * @return bool True quando almeno una query contiene il frammento.
     */
    private function queriesContain(array $queries, string $needle): bool
    {
        foreach ($queries as [$sql]):
            if (str_contains($sql, $needle)):
                return true;
            endif;
        endforeach;

        return false;
    }

    /**
     * Recupera la prima query che contiene il frammento richiesto.
     *
     * @param array<int, array{0:string,1:array}> $queries Query catturate.
     * @param string $needle Frammento SQL cercato.
     * @return array{0:string,1:array}
     */
    private function findQuery(array $queries, string $needle): array
    {
        foreach ($queries as $query):
            if (str_contains($query[0], $needle)):
                return $query;
            endif;
        endforeach;

        $this->fail('Query non trovata: ' . $needle);
    }
}
