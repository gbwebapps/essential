<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Test\CIUnitTestCase;

class AccountModelTest extends CIUnitTestCase
{
	public function testGetGroupPermissionsReturnsEmptyArrayWhenNoResults(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $emptyArray = 
            [];
            
        $mockQuery->method('getResultObject')->willReturn(
            $emptyArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select permission from admins_groups_permissions where group_id = ?',
                [1]
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $groupId = 
            1;
            
        $result = 
            $model->getGroupPermissions(
                $groupId
            );

        /* 4. ASSERZIONE */
        $expected = 
            [];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetGroupPermissionsReturnsFlattenedArray(): void
    {
        /* 1. MOCK DB CON RISULTATI VALIDI */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $perm1 = 
            (object) ['permission' => 'users.read'];
            
        $perm2 = 
            (object) ['permission' => 'users.write'];
            
        $resultsArray = 
            [
                $perm1, 
                $perm2
            ];
            
        $mockQuery->method('getResultObject')->willReturn(
            $resultsArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select permission from admins_groups_permissions where group_id = ?',
                [1]
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $groupId = 
            1;
            
        $result = 
            $model->getGroupPermissions(
                $groupId
            );

        /* 4. ASSERZIONE */
        $expected = 
            [
                'users.read', 
                'users.write'
            ];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetAdminExceptionsReturnsEmptyArrayWhenNoResults(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $emptyArray = 
            [];
            
        $mockQuery->method('getResultObject')->willReturn(
            $emptyArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select permission, allow from admins_permissions where admin_uuid = ?',
                ['123-abc']
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getAdminExceptions(
                $uuid
            );

        /* 4. ASSERZIONE */
        $expected = 
            [];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetAdminExceptionsReturnsAssociativeArray(): void
    {
        /* 1. MOCK DB CON RISULTATI VALIDI (Misti tra stringhe e interi per testare il casting) */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $exception1 = 
            (object) ['permission' => 'users.delete', 'allow' => '1'];
            
        $exception2 = 
            (object) ['permission' => 'settings.view', 'allow' => 0];
            
        $resultsArray = 
            [
                $exception1, 
                $exception2
            ];
            
        $mockQuery->method('getResultObject')->willReturn(
            $resultsArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select permission, allow from admins_permissions where admin_uuid = ?',
                ['123-abc']
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getAdminExceptions(
                $uuid
            );

        /* 4. ASSERZIONE (Ci aspettiamo gli interi 1 e 0 a causa del casting (int)) */
        $expected = 
            [
                'users.delete'  => 1,
                'settings.view' => 0
            ];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetTokensReturnsEmptyArrayWhenNoResults(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $emptyArray = 
            [];
            
        $mockQuery->method('getResult')->willReturn(
            $emptyArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select * from admins_tokens where admin_uuid = ?',
                ['123-abc']
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getTokens(
                $uuid
            );

        /* 4. ASSERZIONE */
        $expected = 
            [];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetTokensReturnsArrayOfTokens(): void
    {
        /* 1. MOCK DB CON RISULTATI VALIDI */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $tokenObj = 
            (object) ['id' => 1, 'token' => 'abc_123'];
            
        $tokensArray = 
            [
                $tokenObj
            ];
            
        $mockQuery->method('getResult')->willReturn(
            $tokensArray
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb
            ->expects($this->once())
            ->method('query')
            ->with(
                'select * from admins_tokens where admin_uuid = ?',
                ['123-abc']
            )
            ->willReturn($mockQuery);

        /* 2. MOCK MODEL (AccountModel) E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getTokens(
                $uuid
            );

        /* 4. ASSERZIONE */
        $expected = 
            $tokensArray;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testEditReturnsFalseWhenNoDataChanged(): void
    {
        /* 1. MOCK MODEL (Isoliamo hasDataChanged) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields', 'hasDataChanged']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);
        
        $model->method('hasDataChanged')->willReturn(false);

        /* 2. ESECUZIONE */
        $posts = 
            [];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $result = 
            $model->edit(
                $posts, 
                $currentAdmin
            );

        /* 3. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testEditReturnsFalseOnTransactionFailure(): void
    {
        /* 1. MOCK DB CON FALLIMENTO TRANSAZIONE */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(false);
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields', 'hasDataChanged']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);
        
        $model->method('hasDataChanged')->willReturn(true);
        
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $posts = 
            [
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'test@test.com',
                'phone'     => '123456',
                'note'      => ''
            ];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $result = 
            $model->edit(
                $posts, 
                $currentAdmin
            );

        /* 4. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testEditReturnsFalseOnException(): void
    {
        /* 1. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields', 'hasDataChanged']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);
        
        $model->method('hasDataChanged')->willReturn(true);
        
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $posts = 
            [
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'test@test.com',
                'phone'     => '123456',
                'note'      => 'Test note'
            ];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $result = 
            $model->edit(
                $posts, 
                $currentAdmin
            );

        /* 4. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testEditSuccessReturnsTrueAndRefreshesAdmin(): void
    {
        /* 0. SALVAGENTE: DEFINIZIONE DELLA FUNZIONE CUSTOM SE MANCANTE */
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity() 
            {
                return true;
            }
        endif;

        /* 1. MOCK DB CON SUCCESSO COMPLETO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 2. MOCK DEL SERVIZIO DI AUTHORIZATION */
        $builderAuth = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderAuth->addMethods(['refresh', 'currentAdmin']);
        
        $mockAuth = 
            $builderAuth->getMock();
            
        $mockAuth->method('refresh')->willReturnSelf();
        
        $updatedAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Luigi', 'lastname' => 'Verdi'];
            
        $mockAuth->method('currentAdmin')->willReturn(
            $updatedAdmin
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'authorization', 
            $mockAuth
        );

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields', 'hasDataChanged']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);
        
        $model->method('hasDataChanged')->willReturn(true);
        
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE (Testiamo anche il trim automatico della nota) */
        $posts = 
            [
                'firstname' => 'Luigi',
                'lastname'  => 'Verdi',
                'email'     => 'luigi@test.com',
                'phone'     => '456789',
                'note'      => '   '
            ];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $result = 
            $model->edit(
                $posts, 
                $currentAdmin
            );

        /* 5. ASSERZIONI */
        $expectedResult = 
            true;
            
        $actualResult = 
            $result['result'];
            
        $this->assertEquals(
            $expectedResult, 
            $actualResult
        );
        
        $actualAdmin = 
            $result['currentAdmin'];
            
        $this->assertSame(
            $updatedAdmin, 
            $actualAdmin
        );
        
        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testGetCurrentTokenIdReturnsNullWhenNoSession(): void
    {
        /* 1. MOCK SESSIONE (Simula assenza della chiave) */
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

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        /* 3. ESECUZIONE */
        $result = 
            $model->getCurrentTokenId();

        /* 4. ASSERZIONE */
        $this->assertNull(
            $result
        );

        /* 5. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testGetCurrentTokenIdReturnsNullWhenNoDbResult(): void
    {
        /* 1. MOCK SESSIONE (Simula presenza della chiave) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturn(true);
        
        $fakeSessionVal = 
            'fake_session_string';
            
        $mockSession->method('get')->willReturn(
            $fakeSessionVal
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK CONFIGURAZIONE (Fornisce la hashKey) */
        $builderConfig = 
            $this->getMockBuilder(\stdClass::class);
            
        $mockConfig = 
            $builderConfig->getMock();
            
        $mockConfig->hashKey = 
            'fake_hash_key';
            
        /* Inietto la config sia con lo slash che col namespace per coprire il tracciamento del core */
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend/Auth', 
            $mockConfig
        );
        
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Auth::class, 
            $mockConfig
        );

        /* 3. MOCK DB CON RISULTATO VUOTO */
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

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $model->getCurrentTokenId();

        /* 6. ASSERZIONE */
        $this->assertNull(
            $result
        );

        /* 7. PULIZIA SERVIZI E FACTORIES */
        \CodeIgniter\Config\Services::reset();
        
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testGetCurrentTokenIdReturnsIntOnSuccess(): void
    {
        /* 1. MOCK SESSIONE */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->method('has')->willReturn(true);
        
        $fakeSessionVal = 
            'fake_session_string';
            
        $mockSession->method('get')->willReturn(
            $fakeSessionVal
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 2. MOCK CONFIGURAZIONE */
        $builderConfig = 
            $this->getMockBuilder(\stdClass::class);
            
        $mockConfig = 
            $builderConfig->getMock();
            
        $mockConfig->hashKey = 
            'fake_hash_key';
            
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend/Auth', 
            $mockConfig
        );
        
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Auth::class, 
            $mockConfig
        );

        /* 3. MOCK DB CON RISULTATO VALIDO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['id' => '42'];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();
            
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $model->getCurrentTokenId();

        /* 6. ASSERZIONE (Verifichiamo che restituisca l'intero 42 castato) */
        $expected = 
            42;
            
        $this->assertSame(
            $expected, 
            $result
        );

        /* 7. PULIZIA SERVIZI E FACTORIES */
        \CodeIgniter\Config\Services::reset();
        
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testDeleteTokenFailsWhenIdMatchesCurrentTokenId(): void
    {
        /* 1. MOCK MODEL (Isoliamo il controllo dei campi consentiti) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 2. ESECUZIONE (Simuliamo l'id in post identico al token di sessione corrente) */
        $posts = 
            ['id' => '42'];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $currentTokenId = 
            42;
            
        $result = 
            $model->deleteToken(
                $posts, 
                $currentAdmin, 
                $currentTokenId
            );

        /* 3. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testDeleteTokenSucceedsAndUpdatesLog(): void
    {
        /* 0. SALVAGENTE: DEFINIZIONE FUNZIONE CUSTOM SE MANCANTE */
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity() 
            {
                return true;
            }
        endif;

        /* 1. MOCK DB CON ESITO POSITIVO E RISULTATO FAKE */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['id' => 1, 'token_type' => 'session', 'last_activity' => ''];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('affectedRows')->willReturn(1);

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 3. INIEZIONE */
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $posts = 
            ['id' => '10'];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $currentTokenId = 
            99;
            
        $result = 
            $model->deleteToken(
                $posts, 
                $currentAdmin, 
                $currentTokenId
            );

        /* 5. ASSERZIONE */
        $expected = 
            true;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testDeleteTokenFailsWhenZeroAffectedRows(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO DA DELETE */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['id' => 1, 'token_type' => 'api', 'last_activity' => ''];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );
        
        $mockDb->method('affectedRows')->willReturn(0);

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 3. INIEZIONE */
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $posts = 
            ['id' => '10'];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $currentTokenId = 
            99;
            
        $result = 
            $model->deleteToken(
                $posts, 
                $currentAdmin, 
                $currentTokenId
            );

        /* 5. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testDeleteTokenReturnsFalseOnException(): void
    {
        /* 1. MOCK DB CHE LANCIA ECCEZIONE */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Query crash simulation');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['checkAllowedFields']);
        
        $model = 
            $builderModel->getMock();
            
        $model->method('checkAllowedFields')->willReturnArgument(0);

        /* 3. INIEZIONE */
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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $posts = 
            ['id' => '10'];
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $currentTokenId = 
            99;
            
        $result = 
            $model->deleteToken(
                $posts, 
                $currentAdmin, 
                $currentTokenId
            );

        /* 5. ASSERZIONE */
        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testResetPasswordReturnsFalseOnTransactionFailure(): void
    {
        /* 0. DEFINIZIONE FUNZIONI CUSTOM E OVERRIDE HELPER SETTING */
        if ( ! function_exists(__NAMESPACE__ . '\setting')):
            function setting($key)
            {
                $mockConfig = 
                    new \stdClass();
                    
                $mockConfig->hashKey = 
                    'secret123';
                    
                $mockConfig->activationTime = 
                    3600;
                    
                return $mockConfig;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK REQUEST E USER AGENT */
        $builderAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderAgent->disableOriginalConstructor();
        
        $mockAgent = 
            $builderAgent->getMock();
            
        $mockAgent->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockAgent
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. MOCK DB CON FALLIMENTO TRANSAZIONE */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(false);
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->module = 
                    'account';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];

        $result = 
            $model->resetPassword(
                $currentAdmin, 
                $mockRequest
            );

        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testResetPasswordReturnsFalseOnException(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists(__NAMESPACE__ . '\setting')):
            function setting($key)
            {
                $mockConfig = 
                    new \stdClass();
                    
                $mockConfig->hashKey = 
                    'secret123';
                    
                $mockConfig->activationTime = 
                    3600;
                    
                return $mockConfig;
            }
        endif;

        /* 1. MOCK REQUEST E USER AGENT */
        $builderAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderAgent->disableOriginalConstructor();
        
        $mockAgent = 
            $builderAgent->getMock();
            
        $mockAgent->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockAgent
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $exception = 
            new \Exception('Query error');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->module = 
                    'account';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];

        $result = 
            $model->resetPassword(
                $currentAdmin, 
                $mockRequest
            );

        $expected = 
            false;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );
    }

    public function testResetPasswordReturnsDbCommittedNoEmailWhenEmailFails(): void
    {
        /* 0. DEFINIZIONE HELPER (Per evitare crash mascherati dal blocco catch) */
        if ( ! function_exists('App\Models\Backend\setting')):
            function setting($key)
            {
                $mockConfig = 
                    new \stdClass();
                    
                $mockConfig->hashKey = 
                    'secret123';
                    
                $mockConfig->activationTime = 
                    3600;
                    
                return $mockConfig;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK SERVICES (Email + Renderer) PER EVITARE CRASH IN EMAILSERVICE */
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

        $builderView = 
            $this->getMockBuilder(\CodeIgniter\View\View::class);
            
        $builderView->disableOriginalConstructor();
        
        $mockView = 
            $builderView->getMock();
            
        $mockView->method('setData')->willReturnSelf();
        
        $mockView->method('render')->willReturn('mock_html');
        
        \CodeIgniter\Config\Services::injectMock(
            'renderer', 
            $mockView
        );

        /* 2. MOCK REQUEST E USER AGENT */
        $builderAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderAgent->disableOriginalConstructor();
        
        $mockAgent = 
            $builderAgent->getMock();
            
        $mockAgent->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockAgent
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 3. MOCK DB CON SUCCESSO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->module = 
                    'account';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE (Esito: db_committed_no_email) */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi', 'email' => 'gbwebapps@gmail.com'];

        $result = 
            $model->resetPassword(
                $currentAdmin, 
                $mockRequest
            );

        $expected = 
            'db_committed_no_email';
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testResetPasswordReturnsTrueOnSuccess(): void
    {
        /* 0. DEFINIZIONE HELPER (Per evitare crash mascherati dal blocco catch) */
        if ( ! function_exists('App\Models\Backend\setting')):
            function setting($key)
            {
                $mockConfig = 
                    new \stdClass();
                    
                $mockConfig->hashKey = 
                    'secret123';
                    
                $mockConfig->activationTime = 
                    3600;
                    
                return $mockConfig;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK SERVICES CON INVIO EMAIL SUCCESSO */
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

        $builderView = 
            $this->getMockBuilder(\CodeIgniter\View\View::class);
            
        $builderView->disableOriginalConstructor();
        
        $mockView = 
            $builderView->getMock();
            
        $mockView->method('setData')->willReturnSelf();
        
        $mockView->method('render')->willReturn('mock_html');
        
        \CodeIgniter\Config\Services::injectMock(
            'renderer', 
            $mockView
        );

        /* 2. MOCK REQUEST E USER AGENT */
        $builderAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class);
            
        $builderAgent->disableOriginalConstructor();
        
        $mockAgent = 
            $builderAgent->getMock();
            
        $mockAgent->method('getAgentString')->willReturn('TestAgent');

        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getUserAgent')->willReturn(
            $mockAgent
        );
        
        $mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 3. MOCK DB CON SUCCESSO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transStatus')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 4. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->module = 
                    'account';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE (Esito: true) */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi', 'email' => 'gbwebapps@gmail.com'];

        $result = 
            $model->resetPassword(
                $currentAdmin, 
                $mockRequest
            );

        $expected = 
            true;
            
        $actual = 
            $result['result'];
            
        $this->assertEquals(
            $expected, 
            $actual
        );

        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testGetExpiringDateReturnsEmptyStringWhenNoToken(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
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

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];

        $result = 
            $model->getExpiringDate(
                $currentAdmin
            );

        $expected = 
            '';
            /* 0. DEFINIZIONE HELPER */
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetExpiringDateReturnsDangerSpanWhenTokenExpired(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists('App\Models\Backend\convertDate')):
            function convertDate(
                $date,
                $format = null
            ) {
                return 'formatted_' . $date;
            }
        endif;

        /* 1. MOCK DB CON DATA PASSATA */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $pastDate = 
            date('Y-m-d H:i:s', strtotime('-1 day'));
            
        $fakeRow = 
            (object) ['token_expire' => $pastDate];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE (Base64 bypassa l'interferenza del Markdown) */
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];

        $result = 
            $model->getExpiringDate(
                $currentAdmin
            );

        $b64Format = 
            'PHNwYW4gY2xhc3M9InRleHQtZGFuZ2VyIj48cz5mb3JtYXR0ZWRfJXM8L3M+PC9zcGFuPg==';
            
        $expectedFormat = 
            base64_decode($b64Format);
            
        $expected = 
            sprintf(
                $expectedFormat, 
                $pastDate
            );
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetExpiringDateReturnsSuccessSpanWhenTokenValid(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists('App\Models\Backend\convertDate')):
            function convertDate(
                $date,
                $format = null
            ) {
                return 'formatted_' . $date;
            }
        endif;

        /* 1. MOCK DB CON DATA FUTURA */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $futureDate = 
            date('Y-m-d H:i:s', strtotime('+1 day'));
            
        $fakeRow = 
            (object) ['token_expire' => $futureDate];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE (Base64 bypassa l'interferenza del Markdown) */
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];

        $result = 
            $model->getExpiringDate(
                $currentAdmin
            );

        $b64Format = 
            'PHNwYW4gY2xhc3M9InRleHQtc3VjY2VzcyI+Zm9ybWF0dGVkXyVzPC9zcGFuPg==';
            
        $expectedFormat = 
            base64_decode($b64Format);
            
        $expected = 
            sprintf(
                $expectedFormat, 
                $futureDate
            );
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetActiveMethodReturnsNoneWhenNoRowFound(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
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

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';

        $result = 
            $model->getActiveMethod(
                $adminUuid
            );

        $expected = 
            'none';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetActiveMethodReturnsMethodString(): void
    {
        /* 1. MOCK DB CON RISULTATO POSITIVO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['method' => 'totp'];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';

        $result = 
            $model->getActiveMethod(
                $adminUuid
            );

        $expected = 
            'totp';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetBasicMethodReturnsTrueOnEmailMethod(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK DB CON SUCCESSO (Eseguirà entrambe le query) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $method = 
            'email';

        $result = 
            $model->setBasicMethod(
                $currentAdmin, 
                $method
            );

        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetBasicMethodReturnsTrueOnOtherMethod(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK DB CON SUCCESSO (Salterà la query upsert) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];
            
        $method = 
            'none';

        $result = 
            $model->setBasicMethod(
                $currentAdmin, 
                $method
            );

        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetBasicMethodReturnsFalseOnException(): void
    {
        /* 1. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];
            
        $method = 
            'email';

        $result = 
            $model->setBasicMethod(
                $currentAdmin, 
                $method
            );

        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSaveTemporarySecretReturnsTrueOnSuccess(): void
    {
        /* 1. MOCK DB CON SUCCESSO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';
            
        $secret = 
            'secret_string';
            
        $result = 
            $model->saveTemporarySecret(
                $adminUuid, 
                $secret
            );
            
        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSaveTemporarySecretReturnsFalseOnException(): void
    {
        /* 1. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';
            
        $secret = 
            'secret_string';
            
        $result = 
            $model->saveTemporarySecret(
                $adminUuid, 
                $secret
            );
            
        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetTemporarySecretReturnsNullWhenNoRowFound(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
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

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';

        $result = 
            $model->getTemporarySecret(
                $adminUuid
            );

        $this->assertNull(
            $result
        );
    }

    public function testGetTemporarySecretReturnsSecretString(): void
    {
        /* 1. MOCK DB CON ESITO POSITIVO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['secret' => 'ABCDEF123456'];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';

        $result = 
            $model->getTemporarySecret(
                $adminUuid
            );

        $expected = 
            'ABCDEF123456';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testActivateTotpMethodReturnsTrueOnSuccess(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists(__NAMESPACE__ . '\log_admin_activity')):
            function log_admin_activity()
            {
                return true;
            }
        endif;

        /* 1. MOCK DB CON SUCCESSO (Eseguirà entrambe le query e il commit) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $mockDb->method('query')->willReturn(true);
        
        $mockDb->method('transCommit')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc', 'firstname' => 'Mario', 'lastname' => 'Rossi'];

        $result = 
            $model->activateTotpMethod(
                $adminUuid, 
                $currentAdmin
            );

        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testActivateTotpMethodReturnsFalseOnException(): void
    {
        /* 1. MOCK DB CHE LANCIA ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('transBegin')->willReturn(true);
        
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );
        
        $mockDb->method('transRollback')->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AccountModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $adminUuid = 
            '123-abc';
            
        $currentAdmin = 
            (object) ['uuid' => '123-abc'];

        $result = 
            $model->activateTotpMethod(
                $adminUuid, 
                $currentAdmin
            );

        $expected = 
            false;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testInitModelIsCalledAndExecutesParent(): void
    {
        $model = 
            new \App\Models\Backend\AccountModel();

        $invoker = 
            function () {
                $this->initModel();
            };

        $bind = 
            \Closure::bind(
                $invoker,
                $model,
                \App\Models\Backend\AccountModel::class
            );

        $bind();

        $this->assertInstanceOf(
            \App\Models\Backend\AccountModel::class,
            $model
        );
    }

    public function testEditValidationRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AccountModel();

        $uuid = 
            'fake-uuid-1234';

        $rules = 
            $model->editValidationRules(
                $uuid
            );

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'firstname',
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

        $expectedString = 
            sprintf(
                'is_unique[admins.email,uuid,%s]', 
                $uuid
            );

        $this->assertContains(
            $expectedString,
            $emailRulesList
        );
    }

    public function testDeleteTokenValidationRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AccountModel();

        $rules = 
            $model->deleteTokenValidationRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'id',
            $rules
        );

        $idConfig = 
            $rules['id'];

        $idRulesList = 
            $idConfig['rules'];

        $this->assertContains(
            'required',
            $idRulesList
        );
    }
}
