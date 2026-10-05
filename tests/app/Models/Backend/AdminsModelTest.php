<?php declare(strict_types = 1);

namespace App\Models\Backend; // Adatta il namespace se si trova in App\Models\Backend

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use App\Models\Backend\AdminsModel; // Adatta il path se necessario

/* 1. Mock della funzione setting() nel namespace del Model */
if ( ! function_exists('App\Models\Backend\setting')):
    function setting($item) {
        $config = new \stdClass();
        $config->hashKey        = 'test_hash_key_123';
        $config->activationTime = 86400; // 24 ore fittizie
        return $config;
    }
endif;

/* 2. Mock della funzione log_admin_activity() nel namespace del Model */
if ( ! function_exists('App\Models\Backend\log_admin_activity')):
    function log_admin_activity($action, $module, $message, $admin) {
        return true; 
    }
endif;

class AdminsModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /* Disabilitiamo le migrazioni poiché usi il DDL manuale */
    protected $migrate = false;

    public function testAddAdminSuccessfully(): void
    {
        /* Carichiamo esplicitamente l'helper mancante nell'ambiente di test */
        helper('setting');

        /* 1. Preparazione: Creiamo un gruppo fittizio con un ID altissimo per evitare conflitti */
        $db = db_connect();
        $db->query("insert ignore into admins_groups (id, name, description) values (9999, 'Test Group', 'Gruppo generato da PHPUnit')");

        /* 2. Mock del Request per simulare User Agent e IP */
        $request = \Config\Services::request();

        /* 3. Mock del servizio di Autorizzazione per evitare blocchi nel log_admin_activity() */
        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)['id' => 1, 'firstname' => 'System', 'lastname' => 'Test']);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. Preparazione del payload aderente alle tue regole addValidationRules */
        $data = [
            'firstname' => 'Mario',
            'lastname'  => 'Rossi',
            'email'     => 'mario.rossi@phpunit.local',
            'phone'     => '+393331234567',
            'status'    => '1',
            'group_id'  => '9999',
            'note'      => 'Test di inserimento automatizzato',
            'images'    => []
        ];

        /* 5. Esecuzione del metodo reale */
        $model = new AdminsModel();
        $result = $model->add($data, $request);

        /* 6. Asserzioni: verifichiamo che la transazione abbia scritto fisicamente nelle tre tabelle */
        $this->seeInDatabase('admins', [
            'email'     => 'mario.rossi@phpunit.local',
            'firstname' => 'Mario'
        ]);

        $createdAdmin = $db
            ->table('admins')
            ->where('email', 'mario.rossi@phpunit.local')
            ->get()
            ->getRow();

        $this->assertTrue($result['result']);
        $this->assertNotNull($createdAdmin);

        $this->seeInDatabase('admins_2fa', [
            'admin_uuid' => $createdAdmin->uuid,
            'method'     => 'email',
            'enabled'    => 1
        ]);

        $this->seeInDatabase('admins_tokens', [
            'admin_uuid' => $createdAdmin->uuid,
            'token_type' => 'activation'
        ]);

        /* Pulizia dei servizi Mock per non interferire con eventuali test successivi */
        \Config\Services::reset(true);
    }

    public function testAddAdminEmailSuccess(): void
    {
        /* 1. Inizializzazione base come nel test precedente */
        helper('setting');
        $db = db_connect();
        $db->query("insert ignore into admins_groups (id, name, description) values (9999, 'Test Group', 'Gruppo generato da PHPUnit')");

        $request = \Config\Services::request();

        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)['id' => 1, 'firstname' => 'System', 'lastname' => 'Test']);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL SERVIZIO EMAIL */
        $mockEmail = $this->getMockBuilder(\CodeIgniter\Email\Email::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['send'])
                          ->getMock();
        
        /* Istruiamo il Mock: il metodo "send" deve essere chiamato esattamente 1 volta e deve restituire TRUE */
        $mockEmail->expects($this->once())
                  ->method('send')
                  ->willReturn(true);
                  
        /* Sostituiamo il motore email reale di CI4 con il nostro Mock inerte */
        \Config\Services::injectMock('email', $mockEmail);

        /* 3. Dati di test (usiamo un'email diversa per variare) */
        $data = [
            'firstname' => 'Luigi',
            'lastname'  => 'Verdi',
            'email'     => 'luigi.verdi@phpunit.local',
            'phone'     => '+393339876543',
            'status'    => '1',
            'group_id'  => '9999',
            'note'      => '',
            'images'    => []
        ];

        /* 4. Esecuzione */
        $model = new AdminsModel();
        $result = $model->add($data, $request);

        /* 5. Asserzioni */
        /* Verifichiamo che il Model restituisca "result" = true (flusso andato a buon fine) */
        $this->assertTrue($result['result']);
        
        /* Verifichiamo che il messaggio finale sia quello di successo completo e non quello di "successo senza email" */
        $this->assertStringContainsString('Luigi', $result['message']);
        $this->assertStringContainsString('Verdi', $result['message']);
    }

    public function testAddAdminEmailFailure(): void
    {
        helper('setting');
        
        /* Re-inseriamo il gruppo perché il tearDown precedente ha svuotato tutto */
        $db = db_connect();
        $db->query("insert ignore into admins_groups (id, name, description) values (9999, 'Test Group', 'Gruppo generato da PHPUnit')");

        $request = \Config\Services::request();

        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)['id' => 1, 'firstname' => 'System', 'lastname' => 'Test']);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* MOCK EMAIL: Questa volta forziamo la restituzione di FALSE per simulare un errore SMTP */
        $mockEmail = $this->getMockBuilder(\CodeIgniter\Email\Email::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['send'])
                          ->getMock();
        
        $mockEmail->expects($this->once())
                  ->method('send')
                  ->willReturn(false); // Simulazione errore
                  
        \Config\Services::injectMock('email', $mockEmail);

        $data = [
            'firstname' => 'Paolo',
            'lastname'  => 'Bianchi',
            'email'     => 'paolo.bianchi@phpunit.local',
            'phone'     => '+393331112233',
            'status'    => '1',
            'group_id'  => '9999',
            'note'      => '',
            'images'    => []
        ];

        $model = new AdminsModel();
        $result = $model->add($data, $request);

        /* Asserzioni: L'utente deve essere nel DB, ma il risultato del controller deve essere FALSE */
        $this->seeInDatabase('admins', ['email' => 'paolo.bianchi@phpunit.local']);
        $this->assertFalse($result['result']);
    }

    public function testAddAdminRollbackOnDatabaseError(): void
    {
        helper('setting');
        $db = db_connect();

        $request = \Config\Services::request();

        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)['id' => 1, 'firstname' => 'System', 'lastname' => 'Test']);
        \Config\Services::injectMock('authorization', $mockAuth);

        $data = [
            'firstname' => 'Errore',
            'lastname'  => 'Critico',
            'email'     => 'errore.critico@phpunit.local',
            'phone'     => '+393330000000',
            'status'    => '1',
            'group_id'  => '88888', // ID gruppo inesistente per forzare il blocco SQL
            'note'      => '',
            'images'    => []
        ];

        $model = new AdminsModel();
        
        /* Eseguiamo il metodo che ora andrà inevitabilmente in errore SQL */
        $result = $model->add($data, $request);

        /* Asserzione 1: Il controller deve ricevere un esito negativo */
        $this->assertFalse($result['result']);

        /* Asserzione 2: La tabella admins NON deve contenere questo utente (Rollback avvenuto con successo) */
        $this->dontSeeInDatabase('admins', ['email' => 'errore.critico@phpunit.local']);
        
        /* Asserzione 3: Verifichiamo che venga restituito il messaggio generico per l'utente, mascherando l'errore tecnico */
        $this->assertEquals(lang('backend/admins.messages.addError'), $result['message']);
    }

    public function testGetDataReturnsDataOnSuccess(): void
    {
        /* 1. Istanza del Model */
        $model = model(\App\Models\Backend\AdminsModel::class);

        /* 2. Dati di simulazione */
        $posts = [
            'trash_filter' => 'active',
            'searchFields' => [],
            'searchDates'  => [],
            'page'         => 1,
            'rows'         => 5,
            'column'       => 'id',
            'order'        => 'desc'
        ];

        /* 3. Esecuzione del metodo */
        $result = $model->getData($posts);

        /* 4. Asserzioni */
        $this->assertTrue($result['result']);
        $this->assertIsArray($result['records']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('lastItemPage', $result);
    }

    public function testGetDataReturnsErrorOnException(): void
    {
        /* 1. Mock del Database per forzare un'eccezione */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();
        
        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception'));

        /* 2. Iniezione del mock nel Model tramite costruttore */
        $model = 
            new \App\Models\Backend\AdminsModel(
                $mockDb
            );

        /* 3. Esecuzione del metodo con array vuoto */
        $result = 
            $model->getData([]);

        /* 4. Asserzioni */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertArrayHasKey(
            'message', 
            $result
        );
    }

    public function testEditFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL: Isoliamo il Model dal database */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        /* Simula il transito della whitelist */
        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* 2. Simuliamo un record che si trova nel cestino */
        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. Esecuzione */
        $result = 
            $model->edit(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testEditFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* 2. Simuliamo un record di un superadmin attivo */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. Esecuzione */
        $result = 
            $model->edit(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testEditFailsOnNoDataChanged(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'hasAdminChanged'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* 2. Simuliamo un record normale e vulnerabile alle modifiche */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* Simuliamo che il form non abbia apportato alcuna variazione reale */
        $model->method('hasAdminChanged')
              ->willReturn(false);

        /* 3. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. Esecuzione */
        $result = 
            $model->edit(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.noDataChanged'),
            $result['message']
        );
    }

    public function testEditSucceeds(): void
    {
        /* 0. MOCK HELPER LOG ATTIVITA' */
        if ( ! function_exists('App\Models\Backend\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d = null
            ) {
            }
        endif;

        /* 1. MOCK DATABASE: Creiamo il mock prima per poterlo iniettare */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('transStatus')
               ->willReturn(true);
               
        $mockDb->method('query')
               ->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE TRAMITE CLOSURE */
        $bld = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class);

        $bld->disableOriginalConstructor();

        $bld->onlyMethods([
            'checkAllowedFields', 
            'getByUUID', 
            'hasAdminChanged', 
            'deletePermissions', 
            'getGroupPermissions'
        ]);

        $model = 
            $bld->getMock();

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
                \App\Models\Backend\AdminsModel::class
            );

        $bind();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record valido esistente */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'firstname'  => 'Vecchio',
                'lastname'   => 'Nome',
                'email'      => 'vecchio@example.com',
                'phone'      => '111111',
                'status'     => 1,
                'group_id'   => 1,
                'note'       => ''
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        $model->method('hasAdminChanged')
              ->willReturn(true);

        $model->method('getGroupPermissions')
              ->willReturn([]);

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'        => 1, 
                     'firstname' => 'System', 
                     'lastname'  => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. Dati della richiesta in ingresso */
        $posts = [
            'uuid'      => '123-abc',
            'firstname' => 'Nuovo',
            'lastname'  => 'Nome',
            'email'     => 'nuovo@example.com',
            'phone'     => '222222',
            'status'    => 1,
            'group_id'  => 2,
            'note'      => 'Test Note'
        ];

        /* 5. Esecuzione */
        $result = 
            $model->edit(
                $posts
            );

        /* 6. Asserzioni */
        $this->assertTrue(
            $result['result'],
            'Il Model avrebbe dovuto restituire result => true'
        );
        
        $this->assertEquals(
            'Nuovo',
            $result['row']->firstname,
            'L\'oggetto in memoria non è stato aggiornato con i nuovi dati'
        );
        
        $this->assertArrayHasKey(
            'message', 
            $result
        );
    }

    public function testEditRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE: Forziamo il crash per testare il catch */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Edit'));

        /* 2. MOCK MODEL: Iniettiamo il mockDb tramite setConstructorArgs */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->setConstructorArgs([$mockDb])
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'hasAdminChanged'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record valido esistente */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        $model->method('hasAdminChanged')
              ->willReturn(true);

        /* 3. Dati fittizi */
        $posts = [
            'uuid'      => '123-abc',
            'firstname' => 'Nuovo',
            'lastname'  => 'Nome',
            'email'     => 'nuovo@example.com',
            'phone'     => '222222',
            'status'    => 1,
            'group_id'  => 2,
            'note'      => ''
        ];

        /* 4. Esecuzione */
        $result = 
            $model->edit(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.editError'),
            $result['message']
        );
    }

    /* Metodo di PHPUnit eseguito automaticamente alla fine di ogni singolo test */
    protected function tearDown(): void
    {
        $db = db_connect();

        $db->resetTransStatus();

        $testEmails = [
            'mario.rossi@phpunit.local',
            'luigi.verdi@phpunit.local',
            'paolo.bianchi@phpunit.local',
            'errore.critico@phpunit.local'
        ];

        $testAdmins = $db
            ->table('admins')
            ->select('uuid')
            ->whereIn('email', $testEmails)
            ->get()
            ->getResultArray();

        $testUuids = array_column($testAdmins, 'uuid');

        if ($testUuids):
            $db->table('admins_tokens')->whereIn('admin_uuid', $testUuids)->delete();
            $db->table('admins_2fa')->whereIn('admin_uuid', $testUuids)->delete();
            $db->table('admins')->whereIn('uuid', $testUuids)->delete();
        endif;

        $db->table('admins_groups')->where('id', 9999)->delete();

        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset();

        parent::tearDown();
    }

    public function testSoftDeleteFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL: Isoliamo il Model dal database */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record che si trova già nel cestino */
        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 3. Esecuzione */
        $result = 
            $model->softDelete(
                $posts
            );

        /* 4. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testSoftDeleteFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record di un superadmin attivo */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 3. Esecuzione */
        $result = 
            $model->softDelete(
                $posts
            );

        /* 4. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testSoftDeleteSucceeds(): void
    {
        /* 0. MOCK HELPER LOG ATTIVITA' */
        if ( ! function_exists('App\Models\Backend\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d = null
            ) {
            }
        endif;

        /* 1. MOCK DATABASE: Creiamo il mock per intercettare le query di pulizia */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('transStatus')
               ->willReturn(true);
               
        $mockDb->method('query')
               ->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE TRAMITE CLOSURE */
        $bld = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class);

        $bld->disableOriginalConstructor();

        $bld->onlyMethods([
            'checkAllowedFields', 
            'getByUUID'
        ]);

        $model = 
            $bld->getMock();

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
                \App\Models\Backend\AdminsModel::class
            );

        $bind();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record valido esistente e vulnerabile */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'firstname'  => 'Utente',
                'lastname'   => 'DaCancellare'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'        => 1, 
                     'firstname' => 'System', 
                     'lastname'  => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 5. Esecuzione */
        $result = 
            $model->softDelete(
                $posts
            );

        /* 6. Asserzioni */
        $this->assertTrue(
            $result['result'],
            'Il Model avrebbe dovuto restituire result => true'
        );
        
        $this->assertArrayHasKey(
            'message', 
            $result
        );
    }

    public function testSoftDeleteRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE: Forziamo il crash per testare il catch e il rollback */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Soft Delete'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->setConstructorArgs([$mockDb])
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. Esecuzione */
        $result = 
            $model->softDelete(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.softDeleteError'),
            $result['message']
        );
    }

    public function testHardDeleteFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record di un superadmin */
        $mockRow = 
            (object)[
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 3. Esecuzione */
        $result = 
            $model->hardDelete(
                $posts
            );

        /* 4. Asserzioni */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testHardDeleteSucceeds(): void
    {
        /* 0. MOCK HELPER LOG ATTIVITA' */
        if ( ! function_exists('App\Models\Backend\log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d = null
            ) {
            }
        endif;

        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('transStatus')
               ->willReturn(true);
               
        $mockDb->method('query')
               ->willReturn(true);

        /* 2. MOCK MODEL E INIEZIONE TRAMITE CLOSURE */
        $bld = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class);

        $bld->disableOriginalConstructor();

        $bld->onlyMethods([
            'checkAllowedFields', 
            'getByUUID'
        ]);

        $model = 
            $bld->getMock();

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
                \App\Models\Backend\AdminsModel::class
            );

        $bind();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Simuliamo un record valido esistente e vulnerabile */
        $mockRow = 
            (object)[
                'superadmin' => 0,
                'firstname'  => 'Utente',
                'lastname'   => 'DaRimuovere'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'        => 1, 
                     'firstname' => 'System', 
                     'lastname'  => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 5. Esecuzione */
        $result = 
            $model->hardDelete(
                $posts
            );

        /* 6. Asserzioni */
        $this->assertTrue(
            $result['result'],
            'Il Model avrebbe dovuto restituire result => true'
        );
        
        $this->assertArrayHasKey(
            'message', 
            $result
        );
    }

    public function testHardDeleteRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE: Forziamo il crash per testare il catch e il rollback */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Hard Delete'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->setConstructorArgs([$mockDb])
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 3. Dati della richiesta */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. Esecuzione */
        $result = 
            $model->hardDelete(
                $posts
            );

        /* 5. Asserzioni */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.hardDeleteError'),
            $result['message']
        );
    }

    public function testRestoreDeleteFailsOnAdminNotFound(): void
    {
        /* 1. MOCK RESULT: Simuliamo una query che non trova nulla */
        $mockResult = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getRow'])
                 ->getMockForAbstractClass();

        $mockResult->method('getRow')
                   ->willReturn(null);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockResult);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->setConstructorArgs([$mockDb])
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* 4. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 5. ESECUZIONE */
        $result = 
            $model->restoreDelete(
                $posts
            );

        /* 6. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.notFound'),
            $result['message']
        );
    }

    public function testRestoreDeleteSucceedsNoConflict(): void
    {
        /* 1. MOCK RECORD TROVATO */
        $mockAdmin = 
            (object)[
                'firstname' => 'Utente',
                'lastname'  => 'Recuperato',
                'email'     => 'test@example.com.deleted.123456789'
            ];

        /* 2. MOCK RESULT */
        $mockResult = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getRow'])
                 ->getMockForAbstractClass();

        $mockResult->method('getRow')
                   ->willReturnOnConsecutiveCalls(
                       $mockAdmin, 
                       null
                   );

        /* 3. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockResult);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 4. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* INIEZIONE FORZATA DEL DB TRAMITE REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 5. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 6. ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->restoreDelete(
                $posts
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );

        $this->assertEquals(
            sprintf(lang('backend/admins.messages.restoreDeleteSuccess'), 'Utente', 'Recuperato'),
            $result['message']
        );
    }

    public function testRestoreDeleteSucceedsWithConflict(): void
    {
        /* 1. MOCK RECORD TROVATO */
        $mockAdmin = 
            (object)[
                'firstname' => 'Utente',
                'lastname'  => 'Conflitto',
                'email'     => 'test@example.com'
            ];

        $mockConflict = 
            (object)[
                'uuid' => '999-xyz'
            ];

        /* 2. MOCK RESULT */
        $mockResult = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getRow'])
                 ->getMockForAbstractClass();

        $mockResult->method('getRow')
                   ->willReturnOnConsecutiveCalls(
                       $mockAdmin, 
                       $mockConflict
                   );

        /* 3. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockResult);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 4. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* INIEZIONE FORZATA DEL DB TRAMITE REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 5. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 6. ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->restoreDelete(
                $posts
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.restoreDeleteConflict'),
            $result['message']
        );
    }

    public function testRestoreDeleteRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Restore'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* INIEZIONE FORZATA DEL DB TRAMITE REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->restoreDelete(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.restoreDeleteError'),
            $result['message']
        );
    }

    public function testResetPasswordFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. MOCK REQUEST */
        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        /* 3. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. ESECUZIONE */
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testResetPasswordFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. MOCK REQUEST */
        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        /* 3. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. ESECUZIONE */
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testResetPasswordRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Reset Password'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK REQUEST & USER AGENT */
        $mockUserAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockUserAgent->method('getAgentString')
                      ->willReturn('TestAgent');

        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRequest->method('getUserAgent')
                    ->willReturn($mockUserAgent);
                    
        $mockRequest->method('getIPAddress')
                    ->willReturn('127.0.0.1');

        /* 4. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 5. ESECUZIONE */
        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 6. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.resetPasswordError'),
            $result['message']
        );
    }

    public function testResetPasswordSuccessWithEmail(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'firstname'  => 'Utente',
                'lastname'   => 'Reset',
                'email'      => 'test@example.com'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK REQUEST & USER AGENT */
        $mockUserAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockUserAgent->method('getAgentString')
                      ->willReturn('TestAgent');

        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRequest->method('getUserAgent')
                    ->willReturn($mockUserAgent);
                    
        $mockRequest->method('getIPAddress')
                    ->willReturn('127.0.0.1');

        /* 4. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 5. MOCK EMAIL (Per prevenire invii reali e restituire successo) */
        $mockEmail = 
            $this->getMockBuilder(\CodeIgniter\Email\Email::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockEmail->method('send')
                  ->willReturn(true);
                  
        \Config\Services::injectMock('email', $mockEmail);

        /* 6. DATI POST E ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result['result'],
            'Il test dell\'Happy Path (invio email riuscito) è fallito'
        );
        
        $this->assertEquals(
            sprintf(lang('backend/admins.messages.resetPasswordSuccess'), 'Utente', 'Reset'),
            $result['message']
        );
    }

    public function testResetPasswordSuccessNoEmail(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'firstname'  => 'Utente',
                'lastname'   => 'SenzaMail',
                'email'      => 'test@example.com'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK REQUEST & USER AGENT */
        $mockUserAgent = 
            $this->getMockBuilder(\CodeIgniter\HTTP\UserAgent::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockUserAgent->method('getAgentString')
                      ->willReturn('TestAgent');

        $mockRequest = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRequest->method('getUserAgent')
                    ->willReturn($mockUserAgent);
                    
        $mockRequest->method('getIPAddress')
                    ->willReturn('127.0.0.1');

        /* 4. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 5. MOCK EMAIL (Simuliamo il fallimento del server SMTP) */
        $mockEmail = 
            $this->getMockBuilder(\CodeIgniter\Email\Email::class)
                 ->disableOriginalConstructor()
                 ->getMock();
                 
        $mockEmail->method('send')
                  ->willReturn(false);
                  
        \Config\Services::injectMock('email', $mockEmail);

        /* 6. DATI POST E ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->resetPassword(
                $posts, 
                $mockRequest
            );

        /* 7. ASSERZIONI */
        $this->assertFalse(
            $result['result'],
            'Il test avrebbe dovuto restituire result => false a causa del fallimento dell\'email'
        );
        
        $this->assertEquals(
            sprintf(lang('backend/admins.messages.resetPasswordSuccessNoEmail'), 'Utente', 'SenzaMail'),
            $result['message']
        );
    }

    public function testChangeStatusFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Utente nel cestino */
        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 3. ESECUZIONE */
        $result = 
            $model->changeStatus(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testChangeStatusFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Superadmin (ma non nel cestino) */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 3. ESECUZIONE */
        $result = 
            $model->changeStatus(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testChangeStatusRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Change Status'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Utente valido */
        $mockRow = 
            (object)[
                'deleted_at'   => null,
                'superadmin'   => 0,
                'status'       => 1,
                'suspended_at' => null
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. DATI POST */
        $posts = 
            ['uuid' => '123-abc'];

        /* 4. ESECUZIONE */
        $result = 
            $model->changeStatus(
                $posts
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.changeStatusError'),
            $result['message']
        );
    }

    public function testChangeStatusSucceedsActivating(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Utente correntemente SOSPESO (status = 0) */
        $mockRow = 
            (object)[
                'deleted_at'   => null,
                'superadmin'   => 0,
                'firstname'    => 'Utente',
                'lastname'     => 'DaAttivare',
                'status'       => 0,
                'suspended_at' => '2026-09-20 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. DATI POST & ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->changeStatus(
                $posts
            );

        /* 5. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
        
        $this->assertEquals(
            1, 
            $result['admin']->status,
            'Lo status avrebbe dovuto essere convertito a 1 (Attivo)'
        );
        
        $this->assertNull(
            $result['admin']->suspended_at,
            'La data di sospensione avrebbe dovuto essere annullata (null)'
        );
        
        $this->assertEquals(
            sprintf(lang('backend/admins.messages.changeStatusSuccess'), 'Utente', 'DaAttivare'),
            $result['message']
        );
    }

    public function testChangeStatusSucceedsSuspending(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        /* Utente correntemente ATTIVO (status = 1) */
        $mockRow = 
            (object)[
                'deleted_at'   => null,
                'superadmin'   => 0,
                'firstname'    => 'Utente',
                'lastname'     => 'DaSospendere',
                'status'       => 1,
                'suspended_at' => null
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. DATI POST & ESECUZIONE */
        $posts = 
            ['uuid' => '123-abc'];

        $result = 
            $model->changeStatus(
                $posts
            );

        /* 5. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
        
        $this->assertEquals(
            0, 
            $result['admin']->status,
            'Lo status avrebbe dovuto essere convertito a 0 (Sospeso)'
        );
        
        $this->assertNotNull(
            $result['admin']->suspended_at,
            'La data di sospensione avrebbe dovuto essere valorizzata'
        );
        
        $this->assertEquals(
            sprintf(lang('backend/admins.messages.changeStatusSuccess'), 'Utente', 'DaSospendere'),
            $result['message']
        );
    }

    public function testChangePermissionFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 3. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testChangePermissionFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 3. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testChangePermissionRollbackOnDatabaseError(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulated DB Exception on Change Permission'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'group_id'   => 1,
                'uuid'       => '123-abc'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        $model->method('getGroupPermissions')
              ->willReturn([]);

        $model->method('getAdminExceptions')
              ->willReturn([]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 4. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.changePermissionError'),
            $result['message']
        );
    }

    public function testChangePermissionCreatesNegativeException(): void
    {
        /* RAMO: Permesso DI GRUPPO, NO eccezioni -> Crea eccezione a 0 */
        
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'group_id'   => 1,
                'uuid'       => '123-abc',
                'firstname'  => 'Mario',
                'lastname'   => 'Rossi'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* Simuliamo che il permesso richiesto APPARTENGA al gruppo */
        $model->method('getGroupPermissions')
              ->willReturn(['CAN_EDIT']);

        /* Simuliamo che NON ci siano eccezioni per questo utente */
        $model->method('getAdminExceptions')
              ->willReturn([]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 5. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
        
        $this->assertEquals(
            sprintf(lang('backend/admins.messages.changePermissionSuccess'), 'Mario', 'Rossi'),
            $result['message']
        );
    }

    public function testChangePermissionCreatesPositiveException(): void
    {
        /* RAMO: Permesso NON DI GRUPPO, NO eccezioni -> Crea eccezione a 1 */
        
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'group_id'   => 1,
                'uuid'       => '123-abc',
                'firstname'  => 'Luigi',
                'lastname'   => 'Verdi'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* Simuliamo che il permesso richiesto NON appartenga al gruppo */
        $model->method('getGroupPermissions')
              ->willReturn(['OTHER_PERMISSION']);

        /* Nessuna eccezione presente */
        $model->method('getAdminExceptions')
              ->willReturn([]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 5. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
    }

    public function testChangePermissionRemovesExistingException(): void
    {
        /* RAMO: Eccezione ESISTENTE (indipendentemente dal gruppo) -> Cancella eccezione */
        
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn(true);

        $mockDb->method('transStatus')
               ->willReturn(true);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'group_id'   => 1,
                'uuid'       => '123-abc',
                'firstname'  => 'Giulia',
                'lastname'   => 'Bianchi'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* Il gruppo non importa, l'importante è che ci sia l'eccezione */
        $model->method('getGroupPermissions')
              ->willReturn([]);

        /* Simuliamo un'eccezione esistente */
        $model->method('getAdminExceptions')
              ->willReturn(['CAN_EDIT' => 1]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 4. DATI POST */
        $posts = [
            'uuid'       => '123-abc',
            'permission' => 'CAN_EDIT'
        ];

        /* 5. ESECUZIONE */
        $result = 
            $model->changePermission(
                $posts
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );
    }

    public function testDeleteTokenFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = [
            'uuid' => '123-abc',
            'id'   => 10
        ];

        /* 3. ESECUZIONE */
        $result = 
            $model->deleteToken(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.cannotModifyDeleted'),
            $result['message']
        );
    }

    public function testDeleteTokenFailsOnSuperadminShield(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* 2. DATI POST */
        $posts = [
            'uuid' => '123-abc',
            'id'   => 10
        ];

        /* 3. ESECUZIONE */
        $result = 
            $model->deleteToken(
                $posts
            );

        /* 4. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.protectedAdmin'),
            $result['message']
        );
    }

    public function testDeleteTokenException(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willThrowException(new \Exception('Simulazione Eccezione DB su deleteToken'));

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. DATI POST */
        $posts = [
            'uuid' => '123-abc',
            'id'   => 10
        ];

        /* 4. ESECUZIONE */
        $result = 
            $model->deleteToken(
                $posts
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );
        
        $this->assertEquals(
            lang('backend/admins.messages.deleteTokenError'),
            $result['message']
        );
    }

    public function testDeleteTokenFailsOnZeroAffectedRows(): void
    {
        /* 1. MOCK QUERY (Simuliamo nessun token trovato nella prima query select) */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockQuery->method('getRow')
                  ->willReturn(null);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        $mockDb->method('affectedRows')
               ->willReturn(0);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. DATI POST */
        $posts = [
            'uuid' => '123-abc',
            'id'   => 10
        ];

        /* 5. ESECUZIONE */
        $result = 
            $model->deleteToken(
                $posts
            );

        /* 6. ASSERZIONI */
        $this->assertFalse(
            $result['result']
        );

        $this->assertEquals(
            lang('backend/admins.messages.deleteTokenError'),
            $result['message']
        );
    }

    public function testDeleteTokenSucceeds(): void
    {
        /* 1. MOCK QUERY (Simuliamo di trovare un token di sessione) */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockTokenRow = 
            (object)[
                'id'            => 10,
                'last_activity' => '2026-09-25 15:00:00',
                'token_type'    => 'session'
            ];

        $mockQuery->method('getRow')
                  ->willReturn($mockTokenRow);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        $mockDb->method('affectedRows')
               ->willReturn(1);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields', 'getByUUID'])
                 ->getMock();

        $model->method('checkAllowedFields')
              ->willReturnArgument(0);

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 0,
                'firstname'  => 'Mario',
                'lastname'   => 'Rossi'
            ];

        $model->method('getByUUID')
              ->willReturn([
                  'result' => true,
                  'row'    => $mockRow
              ]);

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. MOCK AUTENTICAZIONE (Necessario per log_admin_activity) */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test'
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 5. DATI POST */
        $posts = [
            'uuid' => '123-abc',
            'id'   => 10
        ];

        /* 6. ESECUZIONE */
        $result = 
            $model->deleteToken(
                $posts
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result['result']
        );

        $this->assertEquals(
            sprintf(lang('backend/admins.messages.deleteTokenSuccess'), 'Mario', 'Rossi'),
            $result['message']
        );
    }

    public function testGetPermissionsReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockPermission = 
            (object)[
                'id'         => 1, 
                'permission' => 'CAN_EDIT',
                'allow'      => 1
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockPermission]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getPermissions('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            'CAN_EDIT', 
            $result[0]->permission
        );
    }

    public function testGetTokensReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockToken = 
            (object)[
                'id'         => 1, 
                'token_type' => 'session'
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockToken]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields']) /* Mockiamo un metodo a caso solo per bypassare il costruttore originale */
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getTokens('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            'session', 
            $result[0]->token_type
        );
    }

    public function testGetAttemptsReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockAttempt = 
            (object)[
                'id'         => 1, 
                'ip_address' => '192.168.1.100',
                'success'    => 0
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockAttempt]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getAttempts('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            '192.168.1.100', 
            $result[0]->ip_address
        );
    }

    public function testGetTwoFaAttemptsReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockAttempt = 
            (object)[
                'id'         => 1, 
                'ip_address' => '192.168.1.100',
                'success'    => 1
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockAttempt]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getTwoFaAttempts('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            '192.168.1.100', 
            $result[0]->ip_address
        );
    }

    public function testGetTwoFaCodesReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockCode = 
            (object)[
                'id'       => 1, 
                'code'     => '123456',
                'consumed' => 0
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockCode]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getTwoFaCodes('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            '123456', 
            $result[0]->code
        );
    }

    public function testGetTwoFaReturnsObject(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getRow'])
                 ->getMock();
                 
        $mockRow = 
            (object)[
                'id'         => 1, 
                'admin_uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'secret'     => 'ABCDEF1234567890'
            ];
                 
        $mockQuery->method('getRow')
                  ->willReturn($mockRow);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getTwoFa('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsObject(
            $result
        );
        
        $this->assertEquals(
            'ABCDEF1234567890', 
            $result->secret
        );
    }

    public function testGetGroupsReturnsArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResult'])
                 ->getMock();
                 
        $mockGroup = 
            (object)[
                'id'   => 1, 
                'name' => 'Superadmin'
            ];
                 
        $mockQuery->method('getResult')
                  ->willReturn([$mockGroup]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getGroups();

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            1, 
            $result
        );
        
        $this->assertEquals(
            'Superadmin', 
            $result[0]->name
        );
    }

    public function testGetGroupPermissionsReturnsEmptyArrayWhenNoResults(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResultObject'])
                 ->getMock();
                 
        $mockQuery->method('getResultObject')
                  ->willReturn([]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getGroupPermissions(99);

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertEmpty(
            $result
        );
    }

    public function testGetGroupPermissionsReturnsFlattenedArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResultObject'])
                 ->getMock();
                 
        $mockPerm1 = 
            (object)[
                'permission' => 'CAN_EDIT'
            ];

        $mockPerm2 = 
            (object)[
                'permission' => 'CAN_DELETE'
            ];
                 
        $mockQuery->method('getResultObject')
                  ->willReturn([
                      $mockPerm1,
                      $mockPerm2
                  ]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getGroupPermissions(1);

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            2, 
            $result
        );
        
        $this->assertEquals(
            'CAN_EDIT', 
            $result[0]
        );
        
        $this->assertEquals(
            'CAN_DELETE', 
            $result[1]
        );
    }

    public function testGetAdminExceptionsReturnsEmptyArrayWhenNoResults(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResultObject'])
                 ->getMock();
                 
        $mockQuery->method('getResultObject')
                  ->willReturn([]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getAdminExceptions('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertEmpty(
            $result
        );
    }

    public function testGetAdminExceptionsReturnsMappedArray(): void
    {
        /* 1. MOCK QUERY */
        $mockQuery = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['getResultObject'])
                 ->getMock();
                 
        $mockException1 = 
            (object)[
                'permission' => 'CAN_EDIT',
                'allow'      => '1' /* Passiamo stringhe per simulare il DB che poi il cast porta a int */
            ];

        $mockException2 = 
            (object)[
                'permission' => 'CAN_DELETE',
                'allow'      => '0'
            ];
                 
        $mockQuery->method('getResultObject')
                  ->willReturn([
                      $mockException1,
                      $mockException2
                  ]);

        /* 2. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockDb->method('query')
               ->willReturn($mockQuery);

        /* 3. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 4. ESECUZIONE */
        $result = 
            $model->getAdminExceptions('123e4567-e89b-12d3-a456-426614174000');

        /* 5. ASSERZIONI */
        $this->assertIsArray(
            $result
        );
        
        $this->assertCount(
            2, 
            $result
        );
        
        $this->assertArrayHasKey(
            'CAN_EDIT', 
            $result
        );
        
        $this->assertEquals(
            1, 
            $result['CAN_EDIT']
        );
        
        $this->assertArrayHasKey(
            'CAN_DELETE', 
            $result
        );
        
        $this->assertEquals(
            0, 
            $result['CAN_DELETE']
        );
    }

    public function testHasAdminChangedReturnsTrueWhenBaseDataChanged(): void
    {
        /* 1. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['hasDataChanged'])
                 ->getMock();

        /* Simuliamo che i campi base del record siano cambiati */
        $model->method('hasDataChanged')
              ->willReturn(true);

        /* 2. DATI FITTIZI */
        $posts = 
            [];
            
        $original = 
            (object)[
                'group_id' => 1,
                'uuid'     => '123-abc'
            ];

        /* 3. ESECUZIONE */
        $result = 
            $model->hasAdminChanged(
                $posts, 
                $original
            );

        /* 4. ASSERZIONI */
        $this->assertTrue(
            $result,
            'Il metodo avrebbe dovuto restituire true tramite l\'uscita anticipata di hasDataChanged'
        );
    }

    public function testHasAdminChangedReturnsTrueWhenPermissionsDiffer(): void
    {
        /* 1. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        /* Mappiamo 3 permessi globali possibili */
        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'TestGroup' => [
                           'perms' => [
                               'PERM_1' => 'Permesso 1',
                               'PERM_2' => 'Permesso 2',
                               'PERM_3' => 'Permesso 3'
                           ]
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['hasDataChanged', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        /* I dati base sono identici, forziamo il controllo sui permessi */
        $model->method('hasDataChanged')
              ->willReturn(false);

        /* L'utente eredita PERM_1 dal gruppo */
        $model->method('getGroupPermissions')
              ->willReturn([
                  'PERM_1'
              ]);

        /* L'utente possiede PERM_2 come eccezione aggiunta */
        $model->method('getAdminExceptions')
              ->willReturn([
                  'PERM_2' => 1
              ]);

        /* Il calcolo interno atteso genererà: ['PERM_1', 'PERM_2'] */

        /* 3. DATI FITTIZI */
        /* Inviamo dal POST una lista diversa, omettendo PERM_2 e aggiungendo PERM_3 */
        $posts = [
            'permissions' => ['PERM_1', 'PERM_3']
        ];
            
        $original = 
            (object)[
                'group_id' => 1,
                'uuid'     => '123-abc'
            ];

        /* 4. ESECUZIONE */
        $result = 
            $model->hasAdminChanged(
                $posts, 
                $original
            );

        /* 5. ASSERZIONI */
        $this->assertTrue(
            $result,
            'Il metodo avrebbe dovuto restituire true perché i permessi elaborati sono diversi'
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testHasAdminChangedReturnsFalseWhenNothingChanged(): void
    {
        /* 1. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        /* Mappiamo 3 permessi globali possibili */
        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'TestGroup' => [
                           'perms' => [
                               'PERM_1' => 'Permesso 1',
                               'PERM_2' => 'Permesso 2',
                               'PERM_3' => 'Permesso 3'
                           ]
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['hasDataChanged', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $model->method('hasDataChanged')
              ->willReturn(false);

        /* L'utente eredita PERM_1 e PERM_3 dal gruppo */
        $model->method('getGroupPermissions')
              ->willReturn([
                  'PERM_1',
                  'PERM_3'
              ]);

        /* L'utente possiede PERM_2 come eccezione aggiunta, ma ha PERM_3 revocato (valore 0) */
        $model->method('getAdminExceptions')
              ->willReturn([
                  'PERM_2' => 1,
                  'PERM_3' => 0
              ]);

        /* Il calcolo interno atteso genererà: ['PERM_1', 'PERM_2'] */

        /* 3. DATI FITTIZI */
        /* Passiamo esattamente gli stessi permessi finali, ma disordinati per testare la funzione sort() */
        $posts = [
            'permissions' => ['PERM_2', 'PERM_1']
        ];
            
        $original = 
            (object)[
                'group_id' => 1,
                'uuid'     => '123-abc'
            ];

        /* 4. ESECUZIONE */
        $result = 
            $model->hasAdminChanged(
                $posts, 
                $original
            );

        /* 5. ASSERZIONI */
        $this->assertFalse(
            $result,
            'Il metodo avrebbe dovuto restituire false poiché i permessi attuali combaciano perfettamente con quelli passati in POST'
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testDeletePermissionsExecutesQuery(): void
    {
        /* 1. MOCK DATABASE */
        $mockDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $expectedSql = 
            "delete from admins_permissions where admin_uuid = ?";

        /* Impostiamo l'aspettativa: il metodo query DEVE essere chiamato esattamente una volta con questi parametri */
        $mockDb
            ->expects(
                $this->once()
            )
            ->method('query')
            ->with(
                $expectedSql, 
                ['123e4567-e89b-12d3-a456-426614174000']
            );

        /* 2. MOCK MODEL */
        $model = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['checkAllowedFields'])
                 ->getMock();

        /* INIEZIONE DB VIA REFLECTION */
        $reflection = 
            new \ReflectionClass($model);
            
        $property = 
            $reflection->getProperty('db');
            
        $property->setAccessible(true);
        
        $property->setValue(
            $model, 
            $mockDb
        );

        /* 3. ESECUZIONE */
        $model->deletePermissions('123e4567-e89b-12d3-a456-426614174000');

        /* 4. L'aspettativa sul mock verifica query e parametri. */
    }

    public function testShowAllValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione */
        $rules 
            = 
            $model
            ->showAllValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'column' => [
                'rules' => ['required', 'alpha_dash']
            ],
            'order' => [
                'rules' => ['required', 'in_list[asc,desc]']
            ],
            'page' => [
                'rules' => ['required', 'is_natural_no_zero']
            ],
            'rows' => [
                'rules' => ['required', 'is_natural_no_zero']
            ],
            'trash_filter' => [
                'rules' => ['in_list[active,trashed,all]']
            ]
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testShowAllSearchValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per la ricerca */
        $rules 
            = 
            $model
            ->showAllSearchValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'searchFields.firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'searchFields.lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'searchFields.email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'],
            ],
            'searchFields.phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'],
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/admins.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/admins.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this->assertEquals($expected, $rules);
    }

    public function testEditValidationRules()
    {
        /* Crea il mock per la configurazione dei permessi */
        $mockConfig 
            = 
            $this
            ->createMock(\Config\Backend\Permissions::class);

        $mockConfig
            ->method('getPermissions')
            ->willReturn([
                'test_group' => [
                    'perms' => [
                        'perm1' => 'Descrizione 1',
                        'perm2' => 'Descrizione 2'
                    ]
                ]
            ]);

        /* Inietta il mock tramite le Factories di CI4 */
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Permissions::class, 
            $mockConfig
        );

        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Definisce i dati in input */
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];

        /* Ottiene l'array delle regole di validazione */
        $rules 
            = 
            $model
            ->editValidationRules(
                $posts
            );

        /* Stringa attesa per le eccezioni dei permessi */
        $inListString 
            = 'perm1,perm2';

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', "is_unique[admins.uuid,uuid,123e4567-e89b-12d3-a456-426614174000]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', "is_unique[admins.email,uuid,123e4567-e89b-12d3-a456-426614174000]"],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'regex_match[/^\+[0-9]{9,15}$/]'],
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'Caratteri non ammessi.'
                ]
            ],
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'permissions.*' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . 
                $inListString 
                . ']'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.permission')
                ]
            ],
            'images' => [
                'label' => lang('backend/admins.labels.images'),
                'rules' => ['permit_empty', 'checkImages']
            ]
        ];

        /* Verifica che il risultato coincida con le attese */
        $this->assertEquals($expected,$rules);

        /* Pulisce la Factory per non interferire con i prossimi test */
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testChangeGroupValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione */
        $rules 
            = 
            $model
            ->changeGroupValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ]
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testDelValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per l'eliminazione */
        $rules 
            = 
            $model
            ->delValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testResetPasswordValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per il reset della password */
        $rules 
            = 
            $model
            ->resetPasswordValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testChangeStatusValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per il cambio di stato */
        $rules 
            = 
            $model
            ->changeStatusValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.context'),
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testGetTokensValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per i token */
        $rules 
            = 
            $model
            ->getTokensValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testGeneralDataValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per i dati generali */
        $rules 
            = 
            $model
            ->generalDataValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'),
                    'in_list' => lang('Backend/admins.errors.context')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testMetaDataValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per i metadati */
        $rules 
            = 
            $model
            ->metaDataValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testGetPermissionsValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per i permessi */
        $rules 
            = 
            $model
            ->getPermissionsValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'),
                    'in_list' => lang('Backend/admins.errors.context')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testDeleteTokenValidationRules()
    {
        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per l'eliminazione del token */
        $rules 
            = 
            $model
            ->deleteTokenValidationRules();

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'id' => [
                'label' => lang('backend/admins.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.id'),
                    'is_natural_no_zero' => lang('Backend/admins.errors.id')
                ]
            ],
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );
    }

    public function testChangePermissionValidationRules()
    {
        /* Crea il mock per la configurazione dei permessi */
        $mockConfig 
            = 
            $this
            ->createMock(\Config\Backend\Permissions::class);

        $mockConfig
            ->method('getPermissions')
            ->willReturn([
                'test_group' => [
                    'perms' => [
                        'perm1' => 'Descrizione 1',
                        'perm2' => 'Descrizione 2'
                    ]
                ]
            ]);

        /* Inietta il mock tramite le Factories di CI4 */
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Permissions::class, 
            $mockConfig
        );

        /* Inizializza il model */
        $model 
            = new AdminsModel();

        /* Ottiene l'array delle regole di validazione per la modifica dei permessi */
        $rules 
            = 
            $model
            ->changePermissionValidationRules();

        /* Stringa attesa per le eccezioni dei permessi */
        $inListString 
            = 'perm1,perm2';

        /* Definisce l'array atteso */
        $expected 
            = [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'),
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'permission' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['required', 'in_list[' . 
                $inListString 
                . ']'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.permission'),
                    'in_list' => lang('Backend/admins.errors.permission')
                ]
            ]
        ];

        /* Verifica che il risultato coincida con le attese */
        $this
            ->assertEquals(
                $expected
                , 
                $rules
            );

        /* Pulisce la Factory per non interferire con i prossimi test */
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testAddGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'generateUUID', 
                'getByUUID'
            ])
            ->getMock();
            
        $uuid 
            = '123e4567-e89b-12d3-a456-426614174000';
            
        $model
            ->method('generateUUID')
            ->willReturn(
                $uuid
            );
            
        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        /* DB Mock */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();
            
        /* Request Mock */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        \Config\Services::injectMock('request', 
            $mockRequest
        );
        
        $posts 
            = [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 1,
            'note' => ''
        ];
        
        $result 
            = 
            $model
            ->add(
                $posts
                , 
                $mockRequest
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
            
        \Config\Services::reset();
    }

    public function testAddThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'generateUUID'
            ])
            ->getMock();
            
        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $uuid 
            = '123e4567-e89b-12d3-a456-426614174000';
            
        $model
            ->method('generateUUID')
            ->willReturn(
                $uuid
            );
            
        /* DB Mock che forza il catch lanciando un'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Transazione');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();
            
        /* Request Mock */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        \Config\Services::injectMock('request', 
            $mockRequest
        );
        
        $posts 
            = [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 1,
            'note' => ''
        ];
        
        /* Blocca l'output a schermo del var_dump presente nel catch */
        ob_start();
        
        $result 
            = 
            $model
            ->add(
                $posts
                , 
                $mockRequest
            );
            
        ob_end_clean();
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.addError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
            
        \Config\Services::reset();
    }

    public function testEditGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
        ];
        
        $result 
            = 
            $model
            ->edit(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testEditWithExtraPermissions()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID',
                'hasAdminChanged',
                'deletePermissions',
                'getGroupPermissions',
                'insertImages'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->firstname 
            = 'VecchioNome';
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        $model
            ->method('hasAdminChanged')
            ->willReturn(true);
            
        $model
            ->expects(
                $this
                ->once()
            )
            ->method('deletePermissions');
            
        /* Gruppo ha permesso 'A', form manda 'A' e 'B' (extra) */
        $model
            ->method('getGroupPermissions')
            ->willReturn(['perm_A']);
            
        $model
            ->expects(
                $this
                ->never()
            )
            ->method('insertImages');

        /* Mock DB */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        /* Ci aspettiamo 2 query: 
           - 1 per update admin
           - 1 per l'inserimento dell'eccezione extra (perm_B)
        */
        $mockDb
            ->expects(
                $this
                ->exactly(2)
            )
            ->method('query')
            ->willReturnCallback(function ($sql) {
                return true;
            });
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        /* Mock Authorization per evtare eccezioni su currentAdmin() */
        $mockAuth 
            = 
            $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['currentAdmin'])
            ->getMock();
            
        $mockAuth
            ->method('currentAdmin')
            ->willReturn(new \stdClass());
            
        \Config\Services::injectMock('authorization', 
            $mockAuth
        );
        
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 2,
            'note' => '',
            'permissions' => ['perm_A', 'perm_B'] /* perm_B è l'eccezione extra */
        ];
        
        $result 
            = 
            $model
            ->edit(
                $posts
            );
            
        $this
            ->assertTrue(
                $result
                ['result']
            );
            
        \Config\Services::reset();
    }

    public function testEditWithImages()
    {
        /* Inizializza parzialmente il model sbloccando il wrapper getUploadService */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID',
                'insertImages',
                'getUploadService'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $uuid 
            = '123e4567-e89b-12d3-a456-426614174000';

        /* Simula il ritorno positivo di getByUUID */
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->uuid 
            = 
            $uuid;
            
        $fakeUser
            ->firstname 
            = 'Mario';
            
        $fakeUser
            ->lastname 
            = 'Rossi';
            
        $fakeUser
            ->email 
            = 'mario@example.com';
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        /* MOCK DEL SERVIZIO UPLOAD */
        $mockUpload 
            = 
            $this
            ->createMock(\App\Libraries\Backend\UploadClass::class);
            
        $mockUpload
            ->method('doUpload')
            ->willReturn(['dummy_file.jpg']);
            
        $model
            ->method('getUploadService')
            ->willReturn(
                $mockUpload
            );

        /* Ora l'IF ($filenames) è VERO, ci aspettiamo che insertImages venga chiamato */
        $model
            ->expects(
                $this
                ->once()
            )
            ->method('insertImages');

        /* DB Mock Avanzato con BaseResult per prevenire errori fatali su ->getRow() */
        $mockResult 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseResult::class);
            
        $mockResult
            ->method('getRow')
            ->willReturn(null);
            
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        $mockDb
            ->method('query')
            ->willReturn(
                $mockResult
            );
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();
            
        /* Request Mock inietatto direttamente nella chiamata */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        \Config\Services::injectMock('request', 
            $mockRequest
        );

        /* Mock Authorization per i log delle attività */
        $mockAuth 
            = 
            $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['currentAdmin'])
            ->getMock();
            
        $mockAuth
            ->method('currentAdmin')
            ->willReturn(new \stdClass());
            
        \Config\Services::injectMock('authorization', 
            $mockAuth
        );
        
        /* MOCK RENDERER: Previene l'apertura di buffer */
        $mockView 
            = 
            $this
            ->createMock(\CodeIgniter\View\View::class);
            
        $mockView
            ->method('render')
            ->willReturn('mocked view content');
            
        $mockView
            ->method('setData')
            ->willReturnSelf();
            
        \Config\Services::injectMock('renderer', 
            $mockView
        );

        $posts 
            = [
            'uuid' => 
            $uuid
            ,
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 1,
            'note' => '',
            'images' => [
                'dummy_file.jpg'
            ]
        ];
        
        $result 
            = 
            $model
            ->edit(
                $posts
                , 
                $mockRequest
            );
            
        $this->assertTrue($result['result']);
        $this->assertSame(
            sprintf(
                lang('backend/admins.messages.editSuccess'),
                'Mario',
                'Rossi'
            ),
            $result['message']
        );
        $this->assertInstanceOf(\stdClass::class, $result['row']);
        $this->assertSame($uuid, $result['row']->uuid);
            
        \Config\Services::reset();
    }

    public function testEditThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID',
                'hasAdminChanged'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->firstname 
            = 'VecchioNome';
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        $model
            ->method('hasAdminChanged')
            ->willReturn(true);
            
        /* DB Mock che forza il catch lanciando un'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Edit');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 1,
            'note' => ''
        ];
        
        $result 
            = 
            $model
            ->edit(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.editError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testHardDeleteGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->hardDelete(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testHardDeleteThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        /* DB Mock che forza il catch lanciando un'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Hard Delete');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->hardDelete(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.hardDeleteError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testSoftDeleteGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->softDelete(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testSoftDeleteThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        /* DB Mock che forza il catch lanciando un'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Soft Delete');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->softDelete(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.softDeleteError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testResetPasswordGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $result 
            = 
            $model
            ->resetPassword(
                $posts
                ,
                $mockRequest
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testResetPasswordThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        /* Mock DB che forza il catch lanciando un'eccezione su transBegin */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Reset Password');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        /* Request Mock per superare i controlli su userAgent e IP prima della transazione */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->resetPassword(
                $posts
                ,
                $mockRequest
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.resetPasswordError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testChangeStatusGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->changeStatus(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testChangeStatusThrowsException()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->status 
            = 1;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);
            
        /* Mock DB che forza il catch lanciando un'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $exception 
            = new \Exception('Test Eccezione Change Status');
            
        $mockDb
            ->method('transBegin')
            ->willThrowException(
                $exception
            );
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->changeStatus(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.changeStatusError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testRestoreDeleteTransStatusFalse()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        /* Mock BaseResult per query simulata */
        $mockResult 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseResult::class);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->email 
            = 'mario@example.com';
            
        $fakeUser
            ->firstname 
            = 'Mario';
            
        $fakeUser
            ->lastname 
            = 'Rossi';
            
        $mockResult
            ->method('getRow')
            ->willReturn(
                $fakeUser
            );
            
        /* Mock DB che restituisce transStatus() = false */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('query')
            ->willReturn(
                $mockResult
            );
            
        $mockDb
            ->method('transStatus')
            ->willReturn(false);
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->restoreDelete(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.restoreDeleteError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testResetPasswordTransStatusFalse()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        /* Mock DB che restituisce transStatus() = false */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('query')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(false);
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        /* Mock Request */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->resetPassword(
                $posts
                ,
                $mockRequest
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.resetPasswordError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testChangeStatusTransStatusFalse()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->status 
            = 1;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        /* Mock DB che restituisce transStatus() = false */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('query')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(false);
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $result 
            = 
            $model
            ->changeStatus(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.changeStatusError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testChangePermissionExceptionHandling()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID',
                'getGroupPermissions',
                'getAdminExceptions'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->uuid 
            = '123e4567-e89b-12d3-a456-426614174000';
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->group_id 
            = 1;
            
        /* Proprietà obbligatorie per i metodi sprintf ed esc a fine elaborazione */
        $fakeUser
            ->firstname 
            = 'Mario';
            
        $fakeUser
            ->lastname 
            = 'Rossi';
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        /* Simuliamo che il permesso appartenga al gruppo */
        $model
            ->method('getGroupPermissions')
            ->willReturn(['test_permission']);
            
        /* Simuliamo che ci sia un'eccezione (negativa, per bloccarlo) */
        $model
            ->method('getAdminExceptions')
            ->willReturn(['test_permission' => 0]);

        /* Mock DB che simula il successo per la delete dell'eccezione */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        /* Ci aspettiamo la delete e poi l'update su admins, quindi query ritorna true */
        $mockDb
            ->method('query')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        /* Mock Authorization per evtare eccezioni su currentAdmin() */
        $mockAuth 
            = 
            $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['currentAdmin'])
            ->getMock();
            
        $mockAuth
            ->method('currentAdmin')
            ->willReturn(new \stdClass());
            
        \Config\Services::injectMock('authorization', 
            $mockAuth
        );

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'permission' => 'test_permission'
        ];
        
        $result 
            = 
            $model
            ->changePermission(
                $posts
            );
            
        $this
            ->assertTrue(
                $result
                ['result']
            );
            
        \Config\Services::reset();
    }

    public function testChangePermissionTransStatusFalse()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID',
                'getGroupPermissions',
                'getAdminExceptions'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->uuid 
            = '123e4567-e89b-12d3-a456-426614174000';
            
        $fakeUser
            ->deleted_at 
            = null;
            
        $fakeUser
            ->superadmin 
            = 0;
            
        $fakeUser
            ->group_id 
            = 1;
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        $model
            ->method('getGroupPermissions')
            ->willReturn([]);
            
        $model
            ->method('getAdminExceptions')
            ->willReturn([]);

        /* Mock DB che restituisce transStatus() = false */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('query')
            ->willReturn(true);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(false);
            
        $mockDb
            ->expects(
                $this
                ->once()
            )
            ->method('transRollback');
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();

        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'permission' => 'test_permission'
        ];
        
        $result 
            = 
            $model
            ->changePermission(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $expectedMsg 
            = lang('backend/admins.messages.changePermissionError');
            
        $this
            ->assertEquals(
                $expectedMsg
                , 
                $result
                ['message']
            );
    }

    public function testChangePermissionGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'permission' => 'test_permission'
        ];
        
        $result 
            = 
            $model
            ->changePermission(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testDeleteTokenGetByUuidFails()
    {
        /* Inizializza parzialmente il model */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'getByUUID'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $errorMsg 
            = 'Errore recupero utente';
            
        /* Simula il ritorno negativo di getByUUID */
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => false, 
                'message' => 
                $errorMsg
            ]);
            
        $posts 
            = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'id' => 1
        ];
        
        $result 
            = 
            $model
            ->deleteToken(
                $posts
            );
            
        $this
            ->assertFalse(
                $result
                ['result']
            );
            
        $this
            ->assertEquals(
                $errorMsg
                , 
                $result
                ['message']
            );
    }

    public function testAddWithImages()
    {
        /* Inizializza parzialmente il model sbloccando il wrapper getUploadService */
        $model 
            = 
            $this
            ->getMockBuilder(AdminsModel::class)
            ->onlyMethods([
                'checkAllowedFields', 
                'generateUUID', 
                'getByUUID',
                'insertImages',
                'getUploadService'
            ])
            ->getMock();

        $model
            ->method('checkAllowedFields')
            ->willReturnArgument(0);
            
        $uuid 
            = '123e4567-e89b-12d3-a456-426614174000';
            
        $model
            ->method('generateUUID')
            ->willReturn(
                $uuid
            );

        /* Simula il ritorno positivo di getByUUID (usato per l'invio mail a fine add) */
        $fakeUser 
            = new \stdClass();
            
        $fakeUser
            ->firstname 
            = 'Mario';
            
        $fakeUser
            ->lastname 
            = 'Rossi';

        $fakeUser
            ->email 
            = 'aaa@aaa.com';
            
        $fakeUser
            ->email 
            = 'mario@example.com';
            
        $model
            ->method('getByUUID')
            ->willReturn([
                'result' => true, 
                'row' => 
                $fakeUser
            ]);

        /* MOCK DEL SERVIZIO UPLOAD */
        $mockUpload 
            = 
            $this
            ->createMock(\App\Libraries\Backend\UploadClass::class);
            
        $mockUpload
            ->method('doUpload')
            ->willReturn(['dummy_file.jpg']);
            
        $model
            ->method('getUploadService')
            ->willReturn(
                $mockUpload
            );

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('insertImages');

        /* DB Mock Avanzato */
        $mockDb 
            = 
            $this
            ->createMock(\CodeIgniter\Database\BaseConnection::class);
            
        $mockDb
            ->method('transStatus')
            ->willReturn(true);
            
        $mockDb
            ->method('query')
            ->willReturn(true);
            
        $mockDb
            ->method('insertID')
            ->willReturn(1);
            
        /* Bind DB */
        $closure 
            = function () use (
                $mockDb
            ) {
            $this
                ->db 
                = 
                $mockDb;
        };
        
        $closure
            ->bindTo(
                $model
                , 
                get_class(
                    $model
                )
            )();
            
        /* Request Mock */
        $mockRequest 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $mockUserAgent 
            = 
            $this
            ->createMock(\CodeIgniter\HTTP\UserAgent::class);
            
        $mockUserAgent
            ->method('getAgentString')
            ->willReturn('TestAgent');
            
        $mockRequest
            ->method('getUserAgent')
            ->willReturn(
                $mockUserAgent
            );
            
        $mockRequest
            ->method('getIPAddress')
            ->willReturn('127.0.0.1');
            
        \Config\Services::injectMock('request', 
            $mockRequest
        );

        /* Mock Authorization */
        $mockAuth 
            = 
            $this
            ->getMockBuilder(\stdClass::class)
            ->addMethods(['currentAdmin'])
            ->getMock();
            
        $mockAuth
            ->method('currentAdmin')
            ->willReturn(new \stdClass());
            
        \Config\Services::injectMock('authorization', 
            $mockAuth
        );
        
        /* MOCK RENDERER: Previene l'apertura di buffer (ob_start) durante la generazione delle email */
        $mockView 
            = 
            $this
            ->createMock(\CodeIgniter\View\View::class);
            
        $mockView
            ->method('render')
            ->willReturn('mocked view content');
            
        $mockView
            ->method('setData')
            ->willReturnSelf();
            
        \Config\Services::injectMock('renderer', 
            $mockView
        );

        $posts 
            = [
            'firstname' => 'Mario',
            'lastname' => 'Rossi',
            'email' => 'mario@example.com',
            'phone' => '123456789',
            'status' => 1,
            'group_id' => 1,
            'note' => '',
            'images' => [
                'dummy_file.jpg'
            ]
        ];
        
        $result 
            = 
            $model
            ->add(
                $posts
                , 
                $mockRequest
            );
            
        $this
            ->assertTrue(
                $result['result']
            );

        $this->assertStringContainsString('Mario', $result['message']);
        $this->assertStringContainsString('Rossi', $result['message']);
            
        \Config\Services::reset();
    }
}
