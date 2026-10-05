<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Test\CIUnitTestCase;

/* Funzione isolata per evitare che i test scrivano audit reali. */
if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
    function log_admin_activity(
        string $action,
        string $section,
        string $details,
        ?object $currentAdmin = null
    ): bool {
        return true;
    }
endif;

class AuthModelTest extends CIUnitTestCase
{
    public function testLoginFailsOnTooManyAttempts(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $mockConfig = 
            (object)[
                'attempts'         => true,
                'twoFactor'        => false,
                'attemptsInterval' => 900,
                'attemptsLimit'    => 5
            ];

        /* 2. MOCK QUERY DB E TRANSAZIONI */
        $mockRow = 
            (object)[
                'uuid'          => '123-abc',
                'firstname'     => 'Mario',
                'lastname'      => 'Rossi',
                'email'         => 'mario@test.com',
                'times'         => 5,
                'password_hash' => 'hash',
                'last_ts'       => '2026-09-25 10:00:00'
            ];

        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockQuery
            ->method('getRow')
            ->willReturn(
                $mockRow
            );

        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb
            ->method('query')
            ->willReturn(
                $mockQuery
            );
            
        /* FIX TYPERROR: Impedisce il crash di PHP restituendo bool ai metodi transazionali */
        $mockDb
            ->method('transBegin')
            ->willReturn(true);
            
        $mockDb
            ->method('transCommit')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);

        /* 3. MOCK REQUEST */
        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');

        /* 4. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);

        /* INIEZIONE DB E CONFIG VIA CLOSURE (INFALLIBILE SUI MOCK) */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. DATI POST */
        $posts = 
            [
                'email'    => 'mario@test.com',
                'password' => 'password'
            ];

        /* 6. ESECUZIONE */
        $result = 
            $model->login(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/auth.messages.tooMAnyAttempts'), 
            $result['message']
        );
    }

    public function testLoginRequiresTwoFactorAuthentication(): void
    {
        /* 0. FIX HELPER GLOBALE E MOCK EMAIL (Previene invii reali e crash) */
        if (
            ! function_exists('setting')
        ):
            /* Proprietà richieste dai servizi OTP email e TOTP. */
            eval("function setting(\$key = null) { return (object)['twoFactorDigits' => 6, 'twoFactorEmailExpiry' => 300, 'twoFactorWindow' => 1]; }");
        endif;
        
        $mockEmail = 
            $this->getMockBuilder(\CodeIgniter\Email\Email::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockEmail
            ->method('send')
            ->willReturn(true);
            
        \CodeIgniter\Config\Services::injectMock(
            'email', 
            $mockEmail
        );

        /* 1. MOCK CONFIGURAZIONE */
        $mockConfig = 
            (object)[
                'attempts'         => true,
                'twoFactor'        => true,
                'attemptsInterval' => 900,
                'attemptsLimit'    => 5
            ];

        /* 2. MOCK QUERY DB */
        $realHash = 
            password_hash('passwordCorretta', PASSWORD_DEFAULT);
            
        $mockAdmin = 
            (object)[
                'uuid'          => '123-abc',
                'firstname'     => 'Mario',
                'lastname'      => 'Rossi',
                'email'         => 'mario@test.com',
                'times'         => 0,
                'password_hash' => $realHash
            ];
            
        $mock2fa = 
            (object)[
                'method' => 'email'
            ];

        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockQuery
            ->method('getRow')
            ->willReturnOnConsecutiveCalls(
                $mockAdmin, 
                $mock2fa
            );

        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb
            ->method('query')
            ->willReturn(
                $mockQuery
            );
            
        $mockDb
            ->method('transBegin')
            ->willReturn(true);
            
        $mockDb
            ->method('transCommit')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);

        /* 3. MOCK REQUEST */
        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');

        /* 4. MOCK MODEL E INIEZIONE */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. DATI POST */
        $posts = 
            [
                'email'    => 'mario@test.com',
                'password' => 'passwordCorretta'
            ];

        /* 6. ESECUZIONE */
        $result = 
            $model->login(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $this->assertEquals(
            '2fa_required', 
            $result['result']
        );
        
        $this->assertEquals(
            'email', 
            $result['method']
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testLoginSuccessWithSessionToken(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $mockConfig = 
            (object)[
                'attempts'         => false,
                'twoFactor'        => false,
                'sessionTime'      => 3600,
                'hashKey'          => 'secret123'
            ];

        /* 2. MOCK QUERY DB */
        $realHash = 
            password_hash('passwordCorretta', PASSWORD_DEFAULT);
            
        $mockAdmin = 
            (object)[
                'uuid'          => '123-abc',
                'firstname'     => 'Mario',
                'lastname'      => 'Rossi',
                'email'         => 'mario@test.com',
                'times'         => 0,
                'password_hash' => $realHash
            ];

        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockQuery
            ->method('getRow')
            ->willReturn(
                $mockAdmin
            );

        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb
            ->method('query')
            ->willReturn(
                $mockQuery
            );
            
        $mockDb
            ->method('transBegin')
            ->willReturn(true);
            
        $mockDb
            ->method('transCommit')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        /* Aggiungiamo il mock per insertID richiesto da innerLogin */
        $mockDb
            ->method('insertID')
            ->willReturn(
                1
            );

        /* 3. MOCK REQUEST E USER AGENT */
        $mockUserAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('Mozilla/Test');

        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        /* Forniamo lo UserAgent fittizio quando innerLogin lo richiede */
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );

        /* 4. MOCK MODEL E INIEZIONE */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. DATI POST */
        $posts = 
            [
                'email'    => 'mario@test.com',
                'password' => 'passwordCorretta'
            ];

        /* 6. ESECUZIONE */
        $result = 
            $model->login(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
    }

    public function testLoginSuccessWithRememberMeCookie(): void
    {
        /* 0. CARICAMENTO HELPER E MOCK SERVIZIO CRYPTO */
        helper('cookie');

        $mockCrypto = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['encrypt'])
                 ->getMock();
                 
        $mockCrypto
            ->method('encrypt')
            ->willReturn('encrypted_token_string');
            
        \CodeIgniter\Config\Services::injectMock(
            'crypto', 
            $mockCrypto
        );

        /* 1. MOCK CONFIGURAZIONE */
        $mockConfig = 
            (object)[
                'attempts'         => false,
                'twoFactor'        => false,
                'rememberMeTime'   => 2592000,
                'hashKey'          => 'secret123'
            ];

        /* 2. MOCK QUERY DB E TRANSAZIONI */
        $realHash = 
            password_hash('passwordCorretta', PASSWORD_DEFAULT);
            
        $mockAdmin = 
            (object)[
                'uuid'          => '123-abc',
                'firstname'     => 'Mario',
                'lastname'      => 'Rossi',
                'email'         => 'mario@test.com',
                'times'         => 0,
                'password_hash' => $realHash
            ];

        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockQuery
            ->method('getRow')
            ->willReturn(
                $mockAdmin
            );

        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb
            ->method('query')
            ->willReturn(
                $mockQuery
            );
            
        $mockDb
            ->method('transBegin')
            ->willReturn(true);
            
        $mockDb
            ->method('transCommit')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        $mockDb
            ->method('insertID')
            ->willReturn(
                1
            );

        /* 3. MOCK REQUEST E USER AGENT */
        $mockUserAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('Mozilla/Test');

        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );

        /* 4. MOCK MODEL E INIEZIONE */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. DATI POST (CON FLAG REMEMBER ME ATTIVO) */
        $posts = 
            [
                'email'      => 'mario@test.com',
                'password'   => 'passwordCorretta',
                'rememberMe' => true
            ];

        /* 6. ESECUZIONE */
        $result = 
            $model->login(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI E PULIZIA MOCK */
        $this->assertTrue(
            $result['result']
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testResetPasswordFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK QUERY DB (Restituisce null, simulando admin non trovato) */
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        /* 2. MOCK CONNESSIONE DB */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 3. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();

        /* 4. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 5. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $posts = 
            ['email' => 'inesistente@test.com'];
            
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $expectedResult = 
            false;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
    }

    public function testResetPasswordSuccessWithEmailSent(): void
    {
        /* 1. MOCK SERVIZIO EMAIL (Intercetta EmailService) */
        $builderEmail = 
            $this->getMockBuilder(\CodeIgniter\Email\Email::class);
            
        $builderEmail->disableOriginalConstructor();
        
        $mockEmail = 
            $builderEmail->getMock();
            
        $mockEmail->method('send')->willReturn(true);
        
        \CodeIgniter\Config\Services::injectMock(
            'email', 
            $mockEmail
        );

        /* 2. MOCK CONFIGURAZIONE */
        $configArr = 
            [
                'hashKey'        => 'secret123',
                'activationTime' => 3600
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB E QUERY */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@test.com'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);

        /* 4. MOCK REQUEST E USER AGENT */
        $builderUA = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderUA->disableOriginalConstructor();
        
        $mockUA = 
            $builderUA->getMock();
            
        $mockUA->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockUA
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 5. MOCK MODEL E INIEZIONE (Db, Config e Module) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
                    
                $this->module = 
                    'auth';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $posts = 
            ['email' => 'mario@test.com'];
            
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI E PULIZIA MOCK */
        $expectedResult = 
            true;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testResetPasswordFailsOnTransactionError(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            [
                'hashKey'        => 'secret123',
                'activationTime' => 3600
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB E TRANSAZIONE FALLITA */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@test.com'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        /* Simuliamo il fallimento della transazione */
        $mockDb->method('transStatus')->willReturn(false);
        
        /* Verifichiamo che il metodo transRollback venga effettivamente chiamato */
        $once = 
            $this->once();
            
        $expects = 
            $mockDb->expects(
                $once
            );
            
        $expects->method('transRollback');

        /* 3. MOCK REQUEST E USER AGENT */
        $builderUA = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderUA->disableOriginalConstructor();
        
        $mockUA = 
            $builderUA->getMock();
            
        $mockUA->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockUA
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['email' => 'mario@test.com'];
            
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );
            
        /* 6. ASSERZIONI */
        $expectedResult = 
            false;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
    }

    public function testResetPasswordFailsWhenEmailNotSent(): void
    {
        /* 1. MOCK SERVIZIO EMAIL (Restituisce false simulando un errore SMTP) */
        $builderEmail = 
            $this->getMockBuilder(\CodeIgniter\Email\Email::class);
            
        $builderEmail->disableOriginalConstructor();
        
        $mockEmail = 
            $builderEmail->getMock();
            
        $mockEmail->method('send')->willReturn(false);
        
        \CodeIgniter\Config\Services::injectMock(
            'email', 
            $mockEmail
        );

        /* 2. MOCK CONFIGURAZIONE */
        $configArr = 
            [
                'hashKey'        => 'secret123',
                'activationTime' => 3600
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB E QUERY */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@test.com'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);

        /* 4. MOCK REQUEST E USER AGENT */
        $builderUA = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderUA->disableOriginalConstructor();
        
        $mockUA = 
            $builderUA->getMock();
            
        $mockUA->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockUA
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 5. MOCK MODEL E INIEZIONE (Db, Config e Module) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
                    
                $this->module = 
                    'auth';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $posts = 
            ['email' => 'mario@test.com'];
            
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI E PULIZIA MOCK */
        $expectedResult = 
            false;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testResetPasswordCatchesExceptionAndRollbacks(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            [
                'hashKey'        => 'secret123',
                'activationTime' => 3600
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB E ECCEZIONE FORZATA */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@test.com'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        /* La prima query per trovare l'utente va a buon fine */
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        /* Forziamo un'eccezione su transBegin per far scattare il blocco catch */
        $exception = 
            new \Exception('Test DB Connection Lost');
            
        $mockDb->method('transBegin')->willThrowException(
            $exception
        );
        
        /* Verifichiamo che il metodo transRollback venga richiamato per sicurezza */
        $once = 
            $this->once();
            
        $expects = 
            $mockDb->expects(
                $once
            );
            
        $expects->method('transRollback');

        /* 3. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['email' => 'mario@test.com'];
            
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );
            
        /* 6. ASSERZIONI */
        $expectedResult = 
            false;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
    }

    public function testSetPasswordFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB */
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 3. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            [
                'token'    => 'dummy_token',
                'password' => 'NewPass123!'
            ];
            
        $result = 
            $model->setPassword(
                $posts
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testSetPasswordSuccess(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);

        /* 3. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            [
                'token'    => 'dummy_token',
                'password' => 'NewPass123!'
            ];
            
        $result = 
            $model->setPassword(
                $posts
            );

        /* 6. ASSERZIONE */
        $expected = 
            true;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testSetPasswordFailsOnTransactionError(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi'
            ];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(false);
        
        $once = 
            $this->once();
            
        $expects = 
            $mockDb->expects(
                $once
            );
            
        $expects->method('transRollback');

        /* 3. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            [
                'token'    => 'dummy_token',
                'password' => 'NewPass123!'
            ];
            
        $result = 
            $model->setPassword(
                $posts
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testSetPasswordCatchesExceptionAndRollbacks(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB CON ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Test DB Error');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );
        
        $once = 
            $this->once();
            
        $expects = 
            $mockDb->expects(
                $once
            );
            
        $expects->method('transRollback');

        /* 3. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            [
                'token'    => 'dummy_token',
                'password' => 'NewPass123!'
            ];
            
        $result = 
            $model->setPassword(
                $posts
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testCheckAuthTokenFailsWhenTokenNotFound(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB */
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 3. MOCK MODEL CORRETTO (Mockiamo solo checkAllowedFields) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'dummy_token';
            
        $result = 
            $model->checkAuthToken(
                $token
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testCheckAuthTokenFailsWhenTokenExpired(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB CON DATA SCADUTA */
        $pastDate = 
            date('Y-m-d H:i:s', strtotime('-1 day'));
            
        $adminArr = 
            ['token_expire' => $pastDate];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 3. MOCK MODEL CORRETTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'dummy_token';
            
        $result = 
            $model->checkAuthToken(
                $token
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testCheckAuthTokenSuccessWhenValid(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB CON DATA FUTURA E CLASSE STANDARD */
        $futureDate = 
            date('Y-m-d H:i:s', strtotime('+1 day'));
            
        $adminArr = 
            ['token_expire' => $futureDate];
            
        $mockAdmin = 
            (object) $adminArr;
            
        $builderQuery = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderQuery->addMethods(['getRow']);
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(
            $mockAdmin
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 3. MOCK MODEL CORRETTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'dummy_token';
            
        $result = 
            $model->checkAuthToken(
                $token
            );

        /* 6. ASSERZIONE */
        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testCheckAuthTokenCatchesException(): void
    {
        /* 1. MOCK CONFIGURAZIONE */
        $configArr = 
            ['hashKey' => 'secret123'];
            
        $mockConfig = 
            (object) $configArr;

        /* 2. MOCK DB CON ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('DB query failed');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );

        /* 3. MOCK MODEL CORRETTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'dummy_token';
            
        $result = 
            $model->checkAuthToken(
                $token
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testVerifyFailsOnEmptySession(): void
    {
        /* 1. MOCK SESSION */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('get')->willReturn(null);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 3. MOCK DB E CONFIG (Necessari per il blocco catch di sicurezza) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $configArr = 
            [];
            
        $mockConfig = 
            (object) $configArr;

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '123456'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifyFailsOnAdminNotFound(): void
    {
        /* 1. MOCK SESSION */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            [
                'admin_uuid' => '123-abc',
                'method'     => 'totp',
                'rememberMe' => false
            ];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 3. MOCK DB CON ESITO NULLO SULL'ADMIN */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 4. MOCK CONFIG E MODEL */
        $configArr = 
            [];
            
        $mockConfig = 
            (object) $configArr;

        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '123456'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifyFailsOnThrottlingLimit(): void
    {
        /* 1. MOCK SESSION E REQUEST */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            [
                'admin_uuid' => '123-abc',
                'method'     => 'email',
                'rememberMe' => false
            ];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. CONFIGURAZIONE LIMITI BRUTE-FORCE */
        $configArr = 
            [
                'twoFactorTime'  => 900,
                'twoFactorLimit' => 3
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB CON SEQUENZA DI RISPOSTE */
        $adminArr = 
            ['firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $mockAdmin = 
            (object) $adminArr;

        $countArr = 
            ['cnt' => 5]; /* Supera il limite di 3 */
            
        $mockCount = 
            (object) $countArr;

        $tsArr = 
            ['last_ts' => '2026-01-01 10:00:00'];
            
        $mockTs = 
            (object) $tsArr;

        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();

        /* Intercettiamo le query in ordine: admin, conteggio fallimenti, timestamp */
        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $mockAdmin, 
            $mockCount, 
            $mockTs
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '123456'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifyFailsOnWrongEmailCode(): void
    {
        /* 1. MOCK SESSION E REQUEST */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            [
                'admin_uuid' => '123-abc',
                'method'     => 'email',
                'rememberMe' => false
            ];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. MOCK CONFIG */
        $configArr = 
            [
                'twoFactorTime'  => 900,
                'twoFactorLimit' => 3
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB CON CODICE ERRATO */
        $adminArr = 
            ['firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $mockAdmin = 
            (object) $adminArr;

        $countArr = 
            ['cnt' => 0];
            
        $mockCount = 
            (object) $countArr;

        $mockCode = 
            null; /* Simuliamo codice inesistente */

        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();

        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $mockAdmin, 
            $mockCount, 
            $mockCode
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 4. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '999999'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifyFailsOnExpiredEmailCode(): void
    {
        /* 1. MOCK SESSION E REQUEST */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            [
                'admin_uuid' => '123-abc',
                'method'     => 'email',
                'rememberMe' => false
            ];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. MOCK CONFIG */
        $configArr = 
            [
                'twoFactorTime'  => 900,
                'twoFactorLimit' => 3
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB CON CODICE SCADUTO */
        $adminArr = 
            ['firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $mockAdmin = 
            (object) $adminArr;

        $countArr = 
            ['cnt' => 0];
            
        $mockCount = 
            (object) $countArr;

        $pastDate = 
            date('Y-m-d H:i:s', strtotime('-1 hour'));
            
        $codeArr = 
            ['expires_at' => $pastDate];
            
        $mockCode = 
            (object) $codeArr;

        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();

        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $mockAdmin, 
            $mockCount, 
            $mockCode
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 4. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '123456'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifySuccessEmailCode(): void
    {
        /* 1. MOCK SESSION, REQUEST E USER AGENT */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            [
                'admin_uuid' => '123-abc',
                'method'     => 'email',
                'rememberMe' => false
            ];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* Mock dello User Agent (richiesto da innerLogin) */
        $builderUA = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderUA->disableOriginalConstructor();
        
        $mockUA = 
            $builderUA->getMock();
            
        $mockUA->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');
        
        $mockRequest->method('getUserAgent')->willReturn(
            $mockUA
        );

        /* 2. MOCK CONFIG (Inclusi i parametri richiesti da innerLogin) */
        $configArr = 
            [
                'twoFactorTime'  => 900,
                'twoFactorLimit' => 3,
                'hashKey'        => 'secret123',
                'sessionTime'    => 7200
            ];
            
        $mockConfig = 
            (object) $configArr;

        /* 3. MOCK DB CON CODICE VALIDO */
        $adminArr = 
            [
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@test.com'
            ];
            
        $mockAdmin = 
            (object) $adminArr;

        $countArr = 
            ['cnt' => 0];
            
        $mockCount = 
            (object) $countArr;

        $futureDate = 
            date('Y-m-d H:i:s', strtotime('+1 hour'));
            
        $codeArr = 
            ['expires_at' => $futureDate];
            
        $mockCode = 
            (object) $codeArr;

        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();

        /* getRow() gestirà in sequenza: utente, conteggio tentativi, verifica codice */
        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $mockAdmin, 
            $mockCount, 
            $mockCode
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);

        /* 4. MOCK MODEL (Senza mockare innerLogin, lasciamo che esegua!) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        $injector = 
            function() use (
                $mockDb, 
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $posts = 
            ['code' => '123456'];
            
        $result = 
            $model->verify(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONE */
        $expected = 
            true;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutBySessionDoesNothingWhenNoSession(): void
    {
        /* 1. MOCK SESSION (Simula sessione inesistente) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('has')
            ->with('backendSession')
            ->willReturn(false);

        $mockSession
            ->expects(
                $this->never()
            )
            ->method('remove');

        $mockSession
            ->expects(
                $this->never()
            )
            ->method('regenerate');
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        /* 3. ESECUZIONE */
        $model->logoutBySession();

        /* 4. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutBySessionDeletesTokenWhenNoLogId(): void
    {
        /* 1. MOCK SESSION (Ha il token, ma manca il login_log_id) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturnMap([
            ['backendSession', true],
            ['login_log_id', false]
        ]);
        
        $fakeSessionVal = 
            'fake_session_string';
            
        $mockSession->method('get')->willReturn(
            $fakeSessionVal
        );
        
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('remove')
            ->with('backendSession')
            ->willReturn(true);
        
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('regenerate')
            ->with(true)
            ->willReturn(true);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK DB CON SUCCESSO (Solo query di delete) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects(
                $this->once()
            )
            ->method('query')
            ->with(
                'delete from admins_tokens where token_hash = ? and token_type = ?',
                $this->callback(
                    static fn(array $params): bool => count($params) === 2 && $params[1] === 'session'
                )
            )
            ->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
        $mockConfig = 
            (object) ['hashKey' => 'secret_key'];

        $injector = 
            function() use (
                $mockDb,
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $model->logoutBySession();

        /* 5. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutBySessionUpdatesLogAndDeletesTokenOnSuccess(): void
    {
        /* 1. MOCK SESSION (Tutto presente) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturn(true);
        
        $fakeSessionVal = 
            'fake_session_string';
            
        $mockSession->method('get')->willReturnMap([
            ['backendSession', $fakeSessionVal],
            ['login_log_id', 99]
        ]);
        
        $removedSessionKeys = [];

        $mockSession
            ->expects(
                $this->exactly(2)
            )
            ->method('remove')
            ->willReturnCallback(
                static function(string $key) use (&$removedSessionKeys): bool {
                    $removedSessionKeys[] = $key;
                    return true;
                }
            );
        
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('regenerate')
            ->with(true)
            ->willReturn(true);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK DB (Simula le select del log e del token) */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeLogRow = 
            (object) ['logout_reason' => null];
            
        $fakeTokenRow = 
            (object) ['last_activity' => '2026-09-27 18:00:00'];
            
        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $fakeLogRow,
            $fakeTokenRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $queries = [];

        $mockDb
            ->expects(
                $this->exactly(4)
            )
            ->method('query')
            ->willReturnCallback(
                static function(string $sql, array $params) use (&$queries, $mockQuery) {
                    $queries[] = [$sql, $params];
                    return $mockQuery;
                }
            );

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
        $mockConfig = 
            (object) ['hashKey' => 'secret_key'];

        $injector = 
            function() use (
                $mockDb,
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE (Passiamo timeout per coprire la condizione specifica) */
        $reason = 
            'timeout';
            
        $model->logoutBySession(
            $reason
        );

        /* 5. ASSERZIONI */
        $this->assertSame(
            [
                'select logout_reason from admins_logs where id = ?',
                'select last_activity from admins_tokens where token_hash = ? and token_type = ?',
                'update admins_logs set logout = ?, logout_reason = ? where id = ?',
                'delete from admins_tokens where token_hash = ? and token_type = ?'
            ],
            array_column($queries, 0)
        );

        $this->assertSame(['login_log_id', 'backendSession'], $removedSessionKeys);
        $this->assertSame('timeout', $queries[2][1][1]);
        $this->assertSame(99, $queries[2][1][2]);
        $this->assertSame('session', $queries[3][1][1]);
        
        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutBySessionCatchesException(): void
    {
        /* 1. MOCK SESSION CHE LANCIA ECCEZIONE FORZATA */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $exception = 
            new \Exception('Session Crash Simulation');
            
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('has')
            ->with('backendSession')
            ->willThrowException(
                $exception
            );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        /* 3. ESECUZIONE */
        $model->logoutBySession();

        /* 4. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutByCookieDeletesTokenWhenNoLogId(): void
    {
        /* 1. MOCK SESSION (Simuliamo l'assenza del login_log_id) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('has')
            ->with('login_log_id')
            ->willReturn(false);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK DB CON SUCCESSO (Verrà eseguita solo la query di delete) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects(
                $this->once()
            )
            ->method('query')
            ->with(
                'delete from admins_tokens where token_hash = ? and token_type = ?',
                $this->callback(
                    static fn(array $params): bool => count($params) === 2 && $params[1] === 'cookie'
                )
            )
            ->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
        $mockConfig = 
            (object) ['hashKey' => 'secret_key'];

        $injector = 
            function() use (
                $mockDb,
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $cookieValue = 
            'fake_cookie_string';
            
        $model->logoutByCookie(
            $cookieValue
        );

        /* 5. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutByCookieUpdatesLogAndDeletesToken(): void
    {
        /* 1. MOCK SESSION (Simuliamo la presenza del login_log_id) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturn(true);
        
        $fakeLogId = 
            99;
            
        $mockSession->method('get')->willReturn(
            $fakeLogId
        );
        
        $mockSession
            ->expects(
                $this->once()
            )
            ->method('remove')
            ->with('login_log_id')
            ->willReturn(true);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK DB (Simula le select e le update) */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeLogRow = 
            (object) ['logout_reason' => null];
            
        $fakeTokenRow = 
            (object) ['last_activity' => '2026-09-27 18:00:00'];
            
        $mockQuery->method('getRow')->willReturnOnConsecutiveCalls(
            $fakeLogRow,
            $fakeTokenRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $queries = [];

        $mockDb
            ->expects(
                $this->exactly(4)
            )
            ->method('query')
            ->willReturnCallback(
                static function(string $sql, array $params) use (&$queries, $mockQuery) {
                    $queries[] = [$sql, $params];
                    return $mockQuery;
                }
            );

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
        $mockConfig = 
            (object) ['hashKey' => 'secret_key'];

        $injector = 
            function() use (
                $mockDb,
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $cookieValue = 
            'fake_cookie_string';
            
        $reason = 
            'timeout';
            
        $model->logoutByCookie(
            $cookieValue, 
            $reason
        );

        /* 5. ASSERZIONI */
        $this->assertSame(
            [
                'select logout_reason from admins_logs where id = ?',
                'select last_activity from admins_tokens where token_hash = ? and token_type = ?',
                'update admins_logs set logout = ?, logout_reason = ? where id = ?',
                'delete from admins_tokens where token_hash = ? and token_type = ?'
            ],
            array_column($queries, 0)
        );

        $this->assertSame('timeout', $queries[2][1][1]);
        $this->assertSame(99, $queries[2][1][2]);
        $this->assertSame('cookie', $queries[3][1][1]);
        
        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogoutByCookieCatchesException(): void
    {
        /* 1. MOCK SESSION */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturn(false);
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb
            ->expects(
                $this->once()
            )
            ->method('query')
            ->willThrowException(
                $exception
            );

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
        $mockConfig = 
            (object) ['hashKey' => 'secret_key'];

        $injector = 
            function() use (
                $mockDb,
                $mockConfig
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->config = 
                    $mockConfig;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AuthModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $cookieValue = 
            'fake_cookie_string';
            
        $model->logoutByCookie(
            $cookieValue
        );

        /* 5. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testInitModelIsCalledAndSetsConfig(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $invoker = 
            function () {
                $this->initModel();
                
                return $this->config;
            };

        $bind = 
            \Closure::bind(
                $invoker,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $config = 
            $bind();

        $this->assertNotNull(
            $config
        );
    }

    public function testValidateLoginRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $fakeConfig = 
            (object)['passwordRegex' => '/.*/'];

        $injector = 
            function () use (
                $fakeConfig
            ) {
                $this->config = 
                    $fakeConfig;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $rules = 
            $model->validateLoginRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'email',
            $rules
        );

        $this->assertArrayHasKey(
            'password',
            $rules
        );
    }

    public function testValidateResetPasswordRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $rules = 
            $model->validateResetPasswordRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'email',
            $rules
        );

        $emailConfig = 
            $rules['email'];

        $emailRulesList = 
            $emailConfig['rules'];

        $this->assertContains(
            'valid_email',
            $emailRulesList
        );
    }

    public function testValidateSetPasswordRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $fakeConfig = 
            (object)['passwordRegex' => '/.*/'];

        $injector = 
            function () use (
                $fakeConfig
            ) {
                $this->config = 
                    $fakeConfig;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $rules = 
            $model->validateSetPasswordRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'password',
            $rules
        );

        $this->assertArrayHasKey(
            'confirmPassword',
            $rules
        );

        $this->assertArrayHasKey(
            'token',
            $rules
        );
    }

    public function testValidateVerifyRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $rules = 
            $model->validateVerifyRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'code',
            $rules
        );

        $codeConfig = 
            $rules['code'];

        $codeRulesList = 
            $codeConfig['rules'];

        $this->assertContains(
            'exact_length[6]',
            $codeRulesList
        );
    }

    public function testLoginFailsIfAdminNotFound(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockResultAdmin = 
            $this->createMock(\CodeIgniter\Database\BaseResult::class);

        $mockResultAdmin->method('getRow')
                        ->willReturn(null);

        $mockDb->method('query')
               ->willReturn(
                   $mockResultAdmin
               );

        $fakeConfig = 
            (object)[
                'attempts' => 1,
                'twoFactor' => 0,
                'attemptsInterval' => 900
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->loginAllowedFields = 
                    ['email', 'password', 'rememberMe'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $posts = 
            [
                'email' => 'test@test.com', 
                'password' => 'secret'
            ];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $result = 
            $model->login(
                $posts,
                $request
            );

        $isResultFalse = 
            $result['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    public function testLoginFailsIfPasswordWrongAndInsertsAttempt(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockResultAdmin = 
            $this->createMock(\CodeIgniter\Database\BaseResult::class);

        $fakeHash = 
            password_hash('wrong', PASSWORD_DEFAULT);

        $mockRow = 
            (object)[
                'uuid' => '1234', 
                'firstname' => 'Test', 
                'lastname' => 'User', 
                'password_hash' => $fakeHash
            ];

        $mockResultAdmin->method('getRow')
                        ->willReturn($mockRow);

        $mockDb->method('query')
               ->willReturn(
                   $mockResultAdmin
               );

        $mockDb->method('transBegin')
               ->willReturn(true);

        $mockDb->method('transCommit')
               ->willReturn(true);

        $fakeConfig = 
            (object)[
                'attempts' => 1,
                'twoFactor' => 0,
                'attemptsInterval' => 900
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->loginAllowedFields = 
                    ['email', 'password', 'rememberMe'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $posts = 
            [
                'email' => 'test@test.com', 
                'password' => 'right'
            ];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $result = 
            $model->login(
                $posts,
                $request
            );

        $isResultFalse = 
            $result['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    public function testLoginSucceedsAndDeletesAttempts(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockResultAdmin = 
            $this->createMock(\CodeIgniter\Database\BaseResult::class);

        $fakeHash = 
            password_hash('correct', PASSWORD_DEFAULT);

        $mockRow = 
            (object)[
                'uuid' => '1234', 
                'firstname' => 'Test', 
                'lastname' => 'User', 
                'email' => 'test@test.com',
                'password_hash' => $fakeHash,
                'times' => 0,
                'status' => 1,
                'deleted_at' => null
            ];

        $mockResultAdmin->method('getRow')
                        ->willReturn($mockRow);

        $queries = [];

        $mockDb->method('query')
               ->willReturnCallback(
                   static function(string $sql, array $params = []) use (&$queries, $mockResultAdmin) {
                       $queries[] = [$sql, $params];
                       return $mockResultAdmin;
                   }
               );

        $mockDb->method('insertID')
               ->willReturnOnConsecutiveCalls(10, 20);

        $mockDb->method('transBegin')
               ->willReturn(true);

        $mockDb->method('transCommit')
               ->willReturn(true);

        $fakeConfig = 
            (object)[
                'attempts' => 1,
                'twoFactor' => 0,
                'attemptsInterval' => 900,
                'attemptsLimit' => 5,
                'sessionTime' => 3600,
                'hashKey' => 'test_hash_key'
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->loginAllowedFields = 
                    ['email', 'password', 'rememberMe'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $posts = 
            [
                'email' => 'test@test.com', 
                'password' => 'correct'
            ];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $userAgent =
            $this->createMock(\CodeIgniter\HTTP\UserAgent::class);

        $userAgent->method('getAgentString')
                  ->willReturn('PHPUnit');

        $request->method('getUserAgent')
                ->willReturn($userAgent);

        $result = 
            $model->login(
                $posts,
                $request
            );

        $this->assertSame(['result' => true], $result);
        $this->assertNotEmpty(session()->get('backendSession'));
        $this->assertSame(20, session()->get('login_log_id'));
        $this->assertContains(
            'delete from admins_attempts where admin_uuid = ?',
            array_column($queries, 0)
        );
    }

    public function testVerifyCatchesExceptionAndRollbacks(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $exception = 
            new \Exception('Forced crash to test catch block');

        /* La primissima query dell'admin scatenerà questo errore */
        $mockDb->method('query')
               ->willThrowException(
                   $exception
               );

        /* Asserzione: ci aspettiamo che il rollback venga richiamato esattamente 1 volta */
        $mockDb->expects(
                   $this->once()
               )
               ->method('transRollback');

        /* Tutte le configurazioni lette prima della query per evitare crash silenti */
        $fakeConfig = 
            (object)[
                'twoFactorTime' => 300,
                'twoFactorLimit' => 5
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->verifyAllowedFields = 
                    ['code'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        /* Iniezione della sessione corretta attesa dal tuo if di sicurezza */
        session()->set(
            'auth_2fa_pending', 
            [
                'admin_uuid' => '1234',
                'method' => 'totp',
                'rememberMe' => false
            ]
        );

        $posts = 
            ['code' => '123456'];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $result = 
            $model->verify(
                $posts,
                $request
            );

        $isResultFalse = 
            $result['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    public function testVerifyReturnsSessionExpiredWhenPendingSessionIsMissing(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockResult = 
            $this->createMock(\CodeIgniter\Database\BaseResult::class);

        $mockRow = 
            (object)[
                'method' => 'totp',
                'secret' => 'JBSWY3DPEHPK3PXP'
            ];

        $mockResult->method('getRow')
                   ->willReturn(
                       $mockRow
                   );

        $mockDb->method('query')
               ->willReturn(
                   $mockResult
               );

        $injector = 
            function () use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        $session = 
            \Config\Services::session();

        $session->set(
            'method', 
            'totp'
        );

        $session->set(
            '2fa_method', 
            'totp'
        );

        $posts = 
            ['code' => '123456'];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $result = 
            $model->verify(
                $posts,
                $request
            );

        $this->assertSame(
            [
                'result' => false,
                'message' => lang('backend/auth.messages.sessionExpired')
            ],
            $result
        );
    }

    public function testVerifyTotpRejectsInvalidCodeAndRecordsAttempt(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockDb->method('transBegin')
               ->willReturn(true);

        $mockDb->method('transCommit')
               ->willReturn(true);

        /* Callback intelligente: risponde in base alla query che il model sta eseguendo */
        $queries = [];

        $queryHandler = 
            function (
                $sql
            ) use (&$queries) {
                $queries[] = $sql;

                $mockResult = 
                    $this->createMock(\CodeIgniter\Database\BaseResult::class);

                $sqlLower = 
                    strtolower($sql);

                $isAdmin = 
                    strpos($sqlLower, 'select * from admins');

                if ($isAdmin !== false):
                    $mockRow = 
                        (object)[
                            'uuid' => '1234',
                            'firstname' => 'Test',
                            'lastname' => 'User'
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $isAttempts = 
                    strpos($sqlLower, 'count(id)');

                if ($isAttempts !== false):
                    $mockRow = 
                        (object)[
                            'cnt' => 0
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $isTotp = 
                    strpos($sqlLower, 'secret from admins_2fa');

                if ($isTotp !== false):
                    $mockRow = 
                        (object)[
                            'secret' => 'JBSWY3DPEHPK3PXP'
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $mockResult->method('getRow')
                           ->willReturn(null);

                return $mockResult;
            };

        $mockDb->method('query')
               ->willReturnCallback(
                   $queryHandler
               );

        $fakeConfig = 
            (object)[
                'twoFactorTime' => 300,
                'twoFactorLimit' => 5
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->verifyAllowedFields = 
                    ['code'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        /* Forziamo il blocco TOTP tramite sessione */
        session()->set(
            'auth_2fa_pending', 
            [
                'admin_uuid' => '1234',
                'method' => 'totp',
                'rememberMe' => false
            ]
        );

        $posts = 
            ['code' => '123456'];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $result = 
            $model->verify(
                $posts,
                $request
            );

        $this->assertSame(
            [
                'result' => false,
                'message' => lang('backend/auth.messages.wrongCode')
            ],
            $result
        );
        $this->assertContains(
            'insert into admins_2fa_attempts (admin_uuid, method, ip_address, timestamp) values (?, ?, ?, ?)',
            $queries
        );
    }

    public function testVerifyEmailCodeCompletesLogin(): void
    {
        $model = 
            new \App\Models\Backend\AuthModel();

        $mockDb = 
            $this->createMock(\CodeIgniter\Database\BaseConnection::class);

        $mockDb->method('transBegin')
               ->willReturn(true);

        $mockDb->method('transCommit')
               ->willReturn(true);

        $mockDb->method('insertID')
               ->willReturnOnConsecutiveCalls(10, 20);

        /* Usiamo lo stesso cervello, ma con la risposta specifica per le Email */
        $queryHandler = 
            function (
                $sql
            ) {
                $mockResult = 
                    $this->createMock(\CodeIgniter\Database\BaseResult::class);

                $sqlLower = 
                    strtolower($sql);

                $isAdmin = 
                    strpos($sqlLower, 'select * from admins');

                if ($isAdmin !== false):
                    $mockRow = 
                        (object)[
                            'uuid' => '1234',
                            'firstname' => 'Test',
                            'lastname' => 'User',
                            'email' => 'test@test.com'
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $isAttempts = 
                    strpos($sqlLower, 'count(id)');

                if ($isAttempts !== false):
                    $mockRow = 
                        (object)[
                            'cnt' => 0
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $isEmail = 
                    strpos($sqlLower, 'expires_at from admins_2fa_codes');

                if ($isEmail !== false):
                    /* Creiamo una data di scadenza nel futuro per validare il controllo temporale */
                    $future = 
                        date('Y-m-d H:i:s', time() + 3600);

                    $mockRow = 
                        (object)[
                            'expires_at' => $future
                        ];
                    $mockResult->method('getRow')
                               ->willReturn($mockRow);
                    return $mockResult;
                endif;

                $mockResult->method('getRow')
                           ->willReturn(null);

                return $mockResult;
            };

        $mockDb->method('query')
               ->willReturnCallback(
                   $queryHandler
               );

        $fakeConfig = 
            (object)[
                'twoFactorTime' => 300,
                'twoFactorLimit' => 5,
                'sessionTime' => 3600,
                'hashKey' => 'test_hash_key'
            ];

        $injector = 
            function () use (
                $mockDb,
                $fakeConfig
            ) {
                $this->db = 
                    $mockDb;

                $this->config = 
                    $fakeConfig;

                $this->verifyAllowedFields = 
                    ['code'];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $model,
                \App\Models\Backend\AuthModel::class
            );

        $bind();

        /* Forziamo il blocco EMAIL tramite sessione */
        session()->set(
            'auth_2fa_pending', 
            [
                'admin_uuid' => '1234',
                'method' => 'email',
                'rememberMe' => false
            ]
        );

        $posts = 
            ['code' => '123456'];

        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('getIPAddress')
                ->willReturn('127.0.0.1');

        $userAgent =
            $this->createMock(\CodeIgniter\HTTP\UserAgent::class);

        $userAgent->method('getAgentString')
                  ->willReturn('PHPUnit');

        $request->method('getUserAgent')
                ->willReturn($userAgent);

        $result = 
            $model->verify(
                $posts,
                $request
            );

        $this->assertSame(['result' => true], $result);
        $this->assertNull(session()->get('auth_2fa_pending'));
        $this->assertNotEmpty(session()->get('backendSession'));
        $this->assertSame(20, session()->get('login_log_id'));
    }
}
