<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AdminsControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $migrate = false;

    public function testAddDisplaysFormOnGetRequest(): void
    {
        /* 1. Prepariamo un mock del Request per simulare una chiamata GET non-AJAX */
        $request = Services::request();
        $request->setMethod('get');

        /* 2. Esecuzione: invochiamo direttamente il metodo add() del Controller */
        $result = $this->withRequest($request)
                       ->controller(\App\Controllers\Backend\AdminsController::class)
                       ->execute('add');

        /* 3. Asserzioni */
        $this->assertTrue($result->isOK());
        $this->assertTrue($result->see('<i class="fa-solid fa-user-plus"></i>'));
    }

    public function testAddFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)[
            'id'         => 1, 
            'firstname'  => 'System', 
            'lastname'   => 'Test',
            'superadmin' => 1
        ]);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. DATI SCORRETTI */
        $postData = [
            'firstname' => '',             
            'email'     => 'non-una-mail', 
            'group_id'  => '9999'
        ];
        
        $_POST = $postData;
        $_FILES = [];

        /* 3. REQUEST REALE */
        $config = config('App');
        $uri    = new \CodeIgniter\HTTP\SiteURI($config, 'http://essential.test');
        
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            $config, 
            $uri, 
            null, 
            new \CodeIgniter\HTTP\UserAgent()
        );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', $postData);

        /* 4. ESECUZIONE */
        $result = $this->withRequest($request)
                       ->controller(\App\Controllers\Backend\AdminsController::class)
                       ->execute('add');

        $body = strip_tags($result->getBody());
        $json = json_decode($body, true);

        /* 5. ASSERZIONI */
        $this->assertIsArray($json, 'Il parsing del JSON è fallito.');
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('message', $json);
        
        $this->assertArrayHasKey('firstname', $json['errors']);
        $this->assertArrayHasKey('email', $json['errors']);

        /* Pulizia */
        $_POST = [];
        $_FILES = [];
        \Config\Services::reset(true);
    }

    public function testAddSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)[
            'id'         => 1, 
            'firstname'  => 'System', 
            'lastname'   => 'Test',
            'superadmin' => 1
        ]);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL MODEL */
        $mockModel = $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['getGroups', 'addValidationRules', 'add'])
                          ->getMock();

        $mockModel->method('getGroups')->willReturn([]);
        
        $mockModel->method('addValidationRules')->willReturn([
            'firstname' => 'required'
        ]);
        
        $mockModel->method('add')->willReturn(['result' => true]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI VALIDI */
        $postData = [
            'firstname' => 'Mario',
            'lastname'  => 'Rossi',
            'email'     => 'mario.rossi@example.com',
            'phone'     => '123456789',
            'status'    => '1',
            'group_id'  => '1'
        ];
        
        $_POST = $postData;
        $_FILES = [];

        /* 4. REQUEST REALE */
        $config = config('App');
        $uri    = new \CodeIgniter\HTTP\SiteURI($config, 'http://essential.test');
        
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            $config, 
            $uri, 
            null, 
            new \CodeIgniter\HTTP\UserAgent()
        );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', $postData);

        /* 5. ESECUZIONE */
        $result = $this->withRequest($request)
                       ->controller(\App\Controllers\Backend\AdminsController::class)
                       ->execute('add');

        $body = strip_tags($result->getBody());
        $json = json_decode($body, true);

        /* 6. ASSERZIONI */
        $this->assertIsArray($json, 'Il parsing del JSON è fallito.');
        $this->assertTrue($json['result'], 'Il Controller ha restituito result => false invece di true.');
        $this->assertArrayHasKey('output', $json);

        /* Pulizia */
        $_POST = [];
        $_FILES = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testAddResetsFormOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)[
            'id'         => 1, 
            'firstname'  => 'System', 
            'lastname'   => 'Test',
            'superadmin' => 1
        ]);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL MODEL */
        $mockModel = $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['getGroups']) 
                          ->getMock();
        $mockModel->method('getGroups')->willReturn([]);
        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST (Solo azione reset) */
        $postData = ['action' => 'reset'];
        
        $_POST = $postData;
        $_FILES = [];

        /* 4. REQUEST REALE */
        $config = config('App');
        $uri    = new \CodeIgniter\HTTP\SiteURI($config, 'http://essential.test');
        
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            $config, 
            $uri, 
            null, 
            new \CodeIgniter\HTTP\UserAgent()
        );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', $postData);

        /* 5. ESECUZIONE */
        $result = $this->withRequest($request)
                       ->controller(\App\Controllers\Backend\AdminsController::class)
                       ->execute('add');

        $body = strip_tags($result->getBody());
        $json = json_decode($body, true);

        /* 6. ASSERZIONI */
        $this->assertIsArray($json, 'Il parsing del JSON è fallito.');
        $this->assertTrue($json['result'], 'Il reset non ha restituito result => true.');
        $this->assertArrayHasKey('output', $json, 'Il reset non ha restituito la vista rigenerata.');

        /* Pulizia */
        $_POST = [];
        $_FILES = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testAddFailsOnModelErrorAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = $this->getMockBuilder(\stdClass::class)
                         ->addMethods(['currentAdmin'])
                         ->getMock();
        $mockAuth->method('currentAdmin')->willReturn((object)[
            'id'         => 1, 
            'firstname'  => 'System', 
            'lastname'   => 'Test',
            'superadmin' => 1
        ]);
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL MODEL */
        $mockModel = $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['getGroups', 'addValidationRules', 'add'])
                          ->getMock();

        $mockModel->method('getGroups')->willReturn([]);
        
        $mockModel->method('addValidationRules')->willReturn([
            'firstname' => 'required'
        ]);
        
        /* Forziamo il model a restituire un fallimento */
        $mockModel->method('add')->willReturn([
            'result'  => false, 
            'message' => 'Errore interno simulato del database.'
        ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $postData = [
            'firstname' => 'Mario',
            'email'     => 'mario@example.com'
        ];
        
        $_POST = $postData;
        $_FILES = [];

        /* 4. REQUEST REALE */
        $config = config('App');
        $uri    = new \CodeIgniter\HTTP\SiteURI($config, 'http://essential.test');
        
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            $config, 
            $uri, 
            null, 
            new \CodeIgniter\HTTP\UserAgent()
        );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('post', $postData);

        /* 5. ESECUZIONE */
        $result = $this->withRequest($request)
                       ->controller(\App\Controllers\Backend\AdminsController::class)
                       ->execute('add');

        $body = strip_tags($result->getBody());
        $json = json_decode($body, true);

        /* 6. ASSERZIONI */
        $this->assertIsArray($json);
        $this->assertFalse($json['result']);
        $this->assertEquals('Errore interno simulato del database.', $json['message']);

        /* Pulizia */
        $_POST = [];
        $_FILES = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testShowAllDisplaysPageOnGetRequest(): void
    {
        /* 1. Prepariamo un mock del Request per simulare chiamata GET */
        $request = 
            \Config\Services::request();
            
        $request->setMethod('get');

        /* 2. Esecuzione diretta */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('showAll');

        /* 3. Asserzioni */
        $this->assertTrue(
            $result->isOK(), 
            'ERRORE: isOK() ha fallito.'
        );
        
        /* 4. Verifichiamo la presenza del titolo della pagina anziché dell'icona */
        $expectedTitle = 
            lang('backend/admins.titles.showAll');
            
        $this->assertTrue(
            $result->see(
                $expectedTitle
            ), 
            'ERRORE: Il titolo della pagina non è stato trovato nella vista.'
        );
    }

    public function testShowAllFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'         => 1, 
                     'firstname'  => 'System', 
                     'lastname'   => 'Test',
                     'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['showAllValidationRules'])
                 ->getMock();
        
        $mockModel->method('showAllValidationRules')
                  ->willReturn([
                      'page' => 'required|numeric'
                  ]);
                  
        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI SCORRETTI */
        $_POST = [
            'page' => 'stringa-non-valida'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
        
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('showAll');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json, 
            'Il parsing del JSON è fallito.'
        );
        
        $this->assertFalse(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'message', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testShowAllSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'         => 1, 
                     'firstname'  => 'System', 
                     'lastname'   => 'Test',
                     'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK DEL MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['showAllValidationRules', 'showAllSearchValidationRules', 'getData'])
                 ->getMock();

        /* Inseriamo regole minime valide per far passare validateData */
        $mockModel->method('showAllValidationRules')
                  ->willReturn([
                      'page' => 'required|numeric'
                  ]);
                  
        $mockModel->method('showAllSearchValidationRules')
                  ->willReturn([
                      'searchFields' => 'permit_empty'
                  ]);
                  
        /* Forniamo la struttura completa attesa dalla view partial per evitare crash nel rendering */
        $mockModel->method('getData')
                  ->willReturn([
                      'result'       => true,
                      'records'      => [],
                      'pagination'   => ['page' => 1, 'limit' => 10, 'totalRows' => 0],
                      'lastItemPage' => 0
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI VALIDI */
        $_POST = [
            'page' => '1',
            'rows' => '10'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
        
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
        
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('showAll');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json, 
            'Il parsing del JSON è fallito.'
        );
        
        $this->assertTrue(
            $json['result'], 
            'Il Controller ha restituito result => false invece di true.'
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testEditRedirectsOnInvalidUuidGet(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id'         => 1, 
                     'firstname'  => 'System', 
                     'lastname'   => 'Test',
                     'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. REQUEST GET */
        $request = 
            \Config\Services::request();
            
        $request->setMethod('get');

        /* 3. ESECUZIONE (Forniamo un UUID palesemente falso) */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('edit', 'uuid-falso-123');

        /* 4. ASSERZIONI */
        $this->assertTrue(
            $result->isRedirect(),
            'Il Controller non ha effettuato il redirect di fronte a un UUID non valido.'
        );
    }

    public function testEditDisplaysPageOnValidGet(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getGroups', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('getGroups')
                  ->willReturn([]);

        /* Oggetto completo inclusi i campi data per metaDataPartial */
        $mockRow = 
            (object)[
                'uuid'       => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'  => 'Mario',
                'lastname'   => 'Rossi',
                'email'      => 'mario@example.com',
                'phone'      => '123456789',
                'status'     => 1,
                'superadmin' => 0,
                'group_id'   => 1,
                'note'       => '',
                'created_at' => '2026-09-20 10:00:00',
                'updated_at' => '2026-09-25 15:00:00',
                'suspended_at' => '2026-09-22 15:00:00',
                'resetted_at' => '2026-09-21 15:00:00',
                'deleted_at' => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn(['result' => true, 'row' => $mockRow]);
                  
        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);
                  
        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. MOCK GALLERY MODEL */
        $mockGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getImages'])
                 ->getMock();

        $mockGallery->method('getImages')
                    ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\Components\GalleryOneModel::class, $mockGallery);

        /* 4. REQUEST GET */
        $request = 
            \Config\Services::request();
            
        $request->setMethod('get');

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('edit', '123e4567-e89b-12d3-a456-426614174000'); 

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK(),
            'ERRORE: isOK() ha fallito.'
        );

        $expectedTitle = 
            lang('backend/admins.titles.edit');

        $this->assertTrue(
            $result->see(
                $expectedTitle
            ),
            'ERRORE: Titolo della pagina non trovato.'
        );
        
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testEditSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getGroups', 'getByUUID', 'editValidationRules', 'edit', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('getGroups')
                  ->willReturn([]);

        /* Oggetto completo inclusi i campi data per metaDataPartial */
        $mockRow = 
            (object)[
                'uuid'       => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'  => 'Mario',
                'lastname'   => 'Rossi',
                'email'      => 'mario@example.com',
                'phone'      => '123456789',
                'status'     => 1,
                'superadmin' => 0,
                'group_id'   => 1,
                'note'       => '',
                'created_at' => '2026-09-20 10:00:00',
                'updated_at' => '2026-09-25 15:00:00',
                'suspended_at' => '2026-09-22 15:00:00',
                'resetted_at' => '2026-09-21 15:00:00',
                'deleted_at' => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn(['result' => true, 'row' => $mockRow]);

        $mockModel->method('editValidationRules')
                  ->willReturn(['uuid' => 'required']);

        $mockModel->method('edit')
                  ->willReturn(['result' => true, 'message' => 'Success', 'row' => $mockRow]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);
                  
        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. MOCK GALLERY MODEL */
        $mockGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getImages'])
                 ->getMock();

        $mockGallery->method('getImages')
                    ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\Components\GalleryOneModel::class, $mockGallery);

        /* 4. DATI POST */
        $_POST = [
            'uuid'      => '123e4567-e89b-12d3-a456-426614174000',
            'firstname' => 'Test'
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('edit');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK(), 
            'ERRORE: La chiamata HTTP ha restituito un codice di errore. Probabile crash interno.'
        );

        $this->assertIsArray(
            $json,
            'ERRORE: La risposta non è un JSON valido.'
        );

        $this->assertTrue(
            $json['result'],
            'Il Controller ha restituito un JSON con result => false'
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json,
            'La vista parziale non è stata iniettata nel JSON'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testEditFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getGroups', 'getByUUID', 'editValidationRules'])
                 ->getMock();

        $mockModel->method('getGroups')
                  ->willReturn([]);

        $mockRow = 
            (object)[
                'uuid'       => '123e4567-e89b-12d3-a456-426614174000',
                'superadmin' => 0,
                'deleted_at' => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn(['result' => true, 'row' => $mockRow]);

        /* Impostiamo una regola ferrea che il POST non rispetterà */
        $mockModel->method('editValidationRules')
                  ->willReturn(['firstname' => 'required|min_length[3]']);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI (firstname vuoto) */
        $_POST = [
            'uuid'      => '123e4567-e89b-12d3-a456-426614174000',
            'firstname' => ''
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('edit');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertArrayHasKey(
            'errors', 
            $json,
            'Il Controller avrebbe dovuto restituire l\'array degli errori di validazione'
        );
        
        $this->assertArrayHasKey(
            'firstname',
            $json['errors'],
            'L\'errore specifico sul campo firstname non è stato catturato'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testSoftDeleteFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['delValidationRules'])
                 ->getMock();

        /* Impostiamo una regola stretta che il POST non rispetterà */
        $mockModel->method('delValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI (nessun uuid inviato) */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('softDelete');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testSoftDeleteSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['delValidationRules', 'softDelete'])
                 ->getMock();

        /* Inseriamo una regola minima valida per ingannare il validatore di CI4 */
        $mockModel->method('delValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Bypassiamo il vero metodo softDelete restituendo esito positivo */
        $mockModel->method('softDelete')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Eliminazione riuscita'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI (devono matchare con la regola inserita sopra) */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('softDelete');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertTrue(
            $json['result'],
            'Il Controller ha restituito result => false invece di true'
        );
        
        $this->assertEquals(
            'Eliminazione riuscita',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testHardDeleteFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['delValidationRules'])
                 ->getMock();

        /* Impostiamo una regola stretta che il POST non rispetterà */
        $mockModel->method('delValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI (nessun uuid inviato) */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('hardDelete');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testHardDeleteSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['delValidationRules', 'hardDelete'])
                 ->getMock();

        /* Inseriamo una regola minima valida per ingannare il validatore di CI4 */
        $mockModel->method('delValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Bypassiamo il vero metodo hardDelete restituendo esito positivo */
        $mockModel->method('hardDelete')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Eliminazione definitiva riuscita'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('hardDelete');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertTrue(
            $json['result'],
            'Il Controller ha restituito result => false invece di true'
        );
        
        $this->assertEquals(
            'Eliminazione definitiva riuscita',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testResetPasswordFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['resetPasswordValidationRules'])
                 ->getMock();

        /* Impostiamo una regola stretta che il POST vuoto non rispetterà */
        $mockModel->method('resetPasswordValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI (nessun uuid inviato) */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('resetPassword');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testResetPasswordSucceedsOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['resetPasswordValidationRules', 'resetPassword'])
                 ->getMock();

        /* Inseriamo una regola minima valida per ingannare il validatore di CI4 */
        $mockModel->method('resetPasswordValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Bypassiamo il vero metodo resetPassword del Model restituendo esito positivo */
        $mockModel->method('resetPassword')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Reset della password riuscito e mail inviata'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('resetPassword');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertTrue(
            $json['result'],
            'Il Controller ha restituito result => false invece di true'
        );
        
        $this->assertEquals(
            'Reset della password riuscito e mail inviata',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangeStatusFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeStatusValidationRules'])
                 ->getMock();

        /* Regola stringente non rispettata dal POST vuoto */
        $mockModel->method('changeStatusValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI (nessun uuid inviato) */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeStatus');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangeStatusFailsFromModelOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeStatusValidationRules', 'changeStatus'])
                 ->getMock();

        $mockModel->method('changeStatusValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Simuliamo un blocco di sicurezza da parte del Model (es. scudo superadmin) */
        $mockModel->method('changeStatus')
                  ->willReturn([
                      'result'  => false, 
                      'message' => 'Errore di protezione dal Model'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeStatus');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto propagare il false ricevuto dal Model'
        );
        
        $this->assertEquals(
            'Errore di protezione dal Model',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangeStatusSucceedsWithoutContextOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeStatusValidationRules', 'changeStatus'])
                 ->getMock();

        $mockModel->method('changeStatusValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Simuliamo un utente ritornato dal Model */
        $mockRow = 
            (object)[
                'status' => 1
            ];

        $mockModel->method('changeStatus')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Status aggiornato',
                      'admin'   => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SENZA CONTEXT SHOW */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeStatus');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $json['result'],
            'Il Controller ha fallito nonostante il Model abbia restituito true'
        );
        
        $this->assertArrayNotHasKey(
            'statusView',
            $json,
            'La vista non doveva essere caricata in assenza del context show'
        );

        $this->assertArrayNotHasKey(
            'admin',
            $json,
            'La chiave admin doveva essere distrutta tramite unset'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangeStatusSucceedsWithContextShowOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeStatusValidationRules', 'changeStatus'])
                 ->getMock();

        $mockModel->method('changeStatusValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto completo inclusi i campi anagrafici per la vista parziale */
        $mockRow = 
            (object)[
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'    => 'Mario',
                'lastname'     => 'Rossi',
                'email'        => 'mario@example.com',
                'status'       => 1,
                'suspended_at' => null,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'resetted_at'   => '2026-09-23 15:00:00',
                'deleted_at'   => null
            ];

        $mockModel->method('changeStatus')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Status aggiornato',
                      'admin'   => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST CON CONTEXT SHOW */
        $_POST = [
            'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
            'context' => 'show'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeStatus');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK(), 
            'ERRORE: La chiamata HTTP ha restituito un codice di errore.'
        );

        $this->assertIsArray(
            $json,
            'ERRORE: La risposta non è un JSON valido.'
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'statusView', 
            $json,
            'La vista statusView non è stata iniettata nel JSON'
        );

        $this->assertArrayHasKey(
            'metaView', 
            $json,
            'La vista metaView non è stata iniettata nel JSON'
        );

        $this->assertArrayNotHasKey(
            'admin',
            $json,
            'La chiave admin doveva essere distrutta tramite unset'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }   

    public function testChangePermissionFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changePermissionValidationRules'])
                 ->getMock();

        /* Impostiamo una regola stretta che il POST vuoto non rispetterà */
        $mockModel->method('changePermissionValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changePermission');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangePermissionFailsFromModelOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changePermissionValidationRules', 'changePermission'])
                 ->getMock();

        $mockModel->method('changePermissionValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Simuliamo un blocco di sicurezza ritornato dal Model (es. scudo superadmin) */
        $mockModel->method('changePermission')
                  ->willReturn([
                      'result'  => false, 
                      'message' => 'Errore dal Model (es. Superadmin non modificabile)'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid'       => '123e4567-e89b-12d3-a456-426614174000',
            'permission' => 'CAN_EDIT'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changePermission');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto propagare il false ricevuto dal Model'
        );
        
        $this->assertEquals(
            'Errore dal Model (es. Superadmin non modificabile)',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangePermissionSucceedsAndRendersViews(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI (Corretto con la chiave "perms") */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        /* Simula la struttura reale del file Config con title, icon e perms */
        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title' => 'Gestione Amministratori',
                           'icon'  => 'fas fa-users',
                           'perms' => [
                               'CAN_EDIT' => 'Modifica Contenuti'
                           ]
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changePermissionValidationRules', 'changePermission', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('changePermissionValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto completo inclusi i campi per metaDataPartial e permissionsPartial */
        $mockRow = 
            (object)[
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'    => 'Mario',
                'lastname'     => 'Rossi',
                'email'        => 'mario@example.com',
                'group_id'     => 1,
                'status'       => 1,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'resetted_at'   => '2026-09-25 15:00:00',
                'suspended_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null
            ];

        $mockModel->method('changePermission')
                  ->willReturn([
                      'result'  => true, 
                      'message' => 'Permesso aggiornato',
                      'admin'   => $mockRow
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST */
        $_POST = [
            'uuid'       => '123e4567-e89b-12d3-a456-426614174000',
            'permission' => 'CAN_EDIT'
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changePermission');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK(), 
            'ERRORE: La chiamata HTTP ha fallito (probabile crash nella vista parziale).'
        );

        $this->assertIsArray(
            $json
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'permissionsView', 
            $json,
            'La vista permissionsView non è stata iniettata nel JSON'
        );

        $this->assertArrayHasKey(
            'metaView', 
            $json,
            'La vista metaView non è stata iniettata nel JSON'
        );

        $this->assertArrayNotHasKey(
            'admin',
            $json,
            'La chiave admin doveva essere distrutta tramite unset'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testChangeGroupFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeGroupValidationRules'])
                 ->getMock();

        $mockModel->method('changeGroupValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeGroup');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testChangeGroupSucceedsSameGroupOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        /* Aggiunta la chiave "controller" richiesta dalla vista parziale */
        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeGroupValidationRules', 'getGroupPermissions', 'getByUUID', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('changeGroupValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([
                      'CAN_EDIT'
                  ]);

        $mockRow = 
            (object)[
                'group_id' => 2, 
                'uuid'     => '123-abc'
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([
                      'OTHER_PERM' => 1
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST */
        $_POST = [
            'uuid'     => '123-abc',
            'group_id' => 2
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeGroup');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );
        
        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testChangeGroupSucceedsDifferentGroupOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        /* Aggiunta la chiave "controller" richiesta dalla vista parziale */
        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['changeGroupValidationRules', 'getGroupPermissions', 'getByUUID'])
                 ->getMock();

        $mockModel->method('changeGroupValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([
                      'CAN_EDIT'
                  ]);

        $mockRow = 
            (object)[
                'group_id' => 1, 
                'uuid'     => '123-abc'
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST */
        $_POST = [
            'uuid'     => '123-abc',
            'group_id' => 3
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('changeGroup');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );
        
        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testShowFailsOnInvalidUUID(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. ESECUZIONE (Passiamo un UUID volutamente non formattato correttamente) */
        $result = 
            $this->controller(\App\Controllers\Backend\AdminsController::class)
                 ->execute('show', 'uuid-non-valido-123');

        /* 3. ASSERZIONI */
        $this->assertTrue(
            $result->isRedirect(),
            'Il Controller avrebbe dovuto reindirizzare a causa dell\'UUID non valido'
        );
        
        $result->assertSessionHas(
            'message', 
            lang('backend/admins.errors.uuid')
        );
    }

    public function testShowFailsOnAdminNotFound(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getByUUID'])
                 ->getMock();

        /* Simuliamo un UUID formalmente corretto ma non presente nel database */
        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result'  => false, 
                      'message' => 'Admin non trovato'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. ESECUZIONE */
        $result = 
            $this->controller(\App\Controllers\Backend\AdminsController::class)
                 ->execute('show', '123e4567-e89b-12d3-a456-426614174000');

        /* 4. ASSERZIONI */
        $this->assertTrue(
            $result->isRedirect()
        );
        
        $result->assertSessionHas(
            'message', 
            'Admin non trovato'
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testShowFailsOnTrashedAdmin(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getByUUID'])
                 ->getMock();

        /* Utente presente ma cestinato */
        $mockRow = 
            (object)[
                'deleted_at' => '2026-09-25 10:00:00'
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true, 
                      'row'    => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. ESECUZIONE */
        $result = 
            $this->controller(\App\Controllers\Backend\AdminsController::class)
                 ->execute('show', '123e4567-e89b-12d3-a456-426614174000');

        /* 4. ASSERZIONI */
        $this->assertTrue(
            $result->isRedirect()
        );
        
        $result->assertSessionHas(
            'message', 
            lang('backend/admins.messages.cannotModifyDeleted')
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testShowFailsOnSuperadminShield(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getByUUID'])
                 ->getMock();

        /* Utente attivo ma protetto dal flag superadmin */
        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true, 
                      'row'    => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. ESECUZIONE */
        $result = 
            $this->controller(\App\Controllers\Backend\AdminsController::class)
                 ->execute('show', '123e4567-e89b-12d3-a456-426614174000');

        /* 4. ASSERZIONI */
        $this->assertTrue(
            $result->isRedirect()
        );
        
        $result->assertSessionHas(
            'message', 
            lang('backend/admins.messages.protectedAdmin')
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testShowSucceedsAndRendersView(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK GALLERY MODEL */
        $mockGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getImages'])
                 ->getMock();

        $mockGallery->method('getImages')
                    ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\Components\GalleryOneModel::class, $mockGallery);

        /* 4. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getByUUID', 'getGroupPermissions', 'getAdminExceptions', 'getTokens'])
                 ->getMock();

        /* Oggetto completo con groupName per soddisfare generalDataPartial */
        $mockRow = 
            (object)[
                'id'           => 1,
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'    => 'Mario',
                'lastname'     => 'Rossi',
                'email'        => 'mario@example.com',
                'phone'        => '123',
                'group_id'     => 1,
                'groupName'    => 'Amministratori',
                'status'       => 1,
                'superadmin'   => 0,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null,
                'resetted_at'  => null,
                'suspended_at' => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true, 
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn(['CAN_EDIT']);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        $mockModel->method('getTokens')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 5. ESECUZIONE */
        $result = 
            $this->controller(\App\Controllers\Backend\AdminsController::class)
                 ->execute('show', '123e4567-e89b-12d3-a456-426614174000');

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK(),
            'La renderizzazione della vista ha fallito restituendo un errore HTTP.'
        );

        $body = 
            $result->getBody();

        $this->assertStringContainsString(
            'Mario', 
            $body, 
            'La vista non ha stampato i dati dell\'admin.'
        );

        /* Pulizia */
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testGetGeneralDataFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['generalDataValidationRules'])
                 ->getMock();

        $mockModel->method('generalDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getGeneralData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'message',
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetGeneralDataFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['generalDataValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('generalDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result'  => false,
                      'message' => 'Record non trovato'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getGeneralData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertEquals(
            'Record non trovato',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetGeneralDataSucceedsWithContextShow(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['generalDataValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('generalDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto sufficiente per superare la vista generalDataPartial in context show */
        $mockRow = 
            (object)[
                'uuid'      => '123-abc',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'email'     => 'mario@example.com',
                'groupName' => 'Amministratori',
                'status'    => 1, 
                'group_id' => 1,
                'phone' => '123456'
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST CON CONTEXT SHOW */
        $_POST = [
            'uuid'    => '123-abc',
            'context' => 'show'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getGeneralData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );
        
        $this->assertArrayNotHasKey(
            'permissions_output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetGeneralDataSucceedsWithContextEdit(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI (necessaria per il pannello permessi in context edit) */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['generalDataValidationRules', 'getByUUID', 'getGroups', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('generalDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto completo richiesto dal context edit (incluso group_id per i permessi) */
        $mockRow = 
            (object)[
                'uuid'      => '123-abc',
                'firstname' => 'Luigi',
                'lastname'  => 'Verdi',
                'email'     => 'luigi@example.com',
                'group_id'  => 1,
                'status'    => 1, 
                'phone' => '123456', 
                'note'      => ''
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getGroups')
                  ->willReturn([]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST CON CONTEXT EDIT */
        $_POST = [
            'uuid'    => '123-abc',
            'context' => 'edit'
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getGeneralData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );
        
        $this->assertArrayHasKey(
            'permissions_output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testGetMetaDataFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['metaDataValidationRules'])
                 ->getMock();

        $mockModel->method('metaDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getMetaData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'message',
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetMetaDataFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['metaDataValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('metaDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result'  => false,
                      'message' => 'Admin non trovato'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getMetaData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertEquals(
            'Admin non trovato',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetMetaDataSucceedsAndRendersView(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['metaDataValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('metaDataValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto fittizio provvisto di tutti i campi data per soddisfare la vista */
        $mockRow = 
            (object)[
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'resetted_at'   => '2026-09-25 15:00:00',
                'suspended_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null,
                'status'       => 1
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getMetaData');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetPermissionsFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissionsValidationRules'])
                 ->getMock();

        $mockModel->method('getPermissionsValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getPermissions');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json,
            'Il messaggio di errore del Toast è assente'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetPermissionsFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissionsValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('getPermissionsValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result'  => false,
                      'message' => 'Utente non trovato'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getPermissions');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertEquals(
            'Utente non trovato',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetPermissionsSucceedsWithContextShow(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissionsValidationRules', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('getPermissionsValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto completo necessario per permissionsPartial */
        $mockRow = 
            (object)[
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'    => 'Mario',
                'lastname'     => 'Rossi',
                'email'        => 'mario@example.com',
                'group_id'     => 1,
                'phone'     => '123456',
                'status'       => 1,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'suspended_at'   => '2026-09-20 10:00:00',
                'resetted_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST CON CONTEXT SHOW */
        $_POST = [
            'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
            'context' => 'show'
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getPermissions');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        $this->assertArrayNotHasKey(
            'group_id', 
            $json,
            'La chiave group_id non doveva essere iniettata nel contesto show'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testGetPermissionsSucceedsWithContextEdit(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK CONFIGURAZIONE PERMESSI */
        $mockConfig = 
            $this->getMockBuilder(\Config\Backend\Permissions::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissions'])
                 ->getMock();

        $mockConfig->method('getPermissions')
                   ->willReturn([
                       'Admins' => [
                           'title'      => 'Gestione',
                           'icon'       => 'fas fa-cog',
                           'controller' => 'admins',
                           'perms'      => ['CAN_EDIT' => 'Modifica']
                       ]
                   ]);

        \CodeIgniter\Config\Factories::injectMock('config', \Config\Backend\Permissions::class, $mockConfig);

        /* 3. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getPermissionsValidationRules', 'getByUUID', 'getGroupPermissions', 'getAdminExceptions'])
                 ->getMock();

        $mockModel->method('getPermissionsValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto completo necessario per permissionsPartial */
        $mockRow = 
            (object)[
                'uuid'         => '123e4567-e89b-12d3-a456-426614174000',
                'firstname'    => 'Luigi',
                'lastname'     => 'Verdi',
                'email'        => 'luigi@example.com',
                'group_id'     => 1,
                'phone'     => '123456',
                'status'       => 1,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'suspended_at'   => '2026-09-20 10:00:00',
                'resetted_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 4. DATI POST CON CONTEXT EDIT */
        $_POST = [
            'uuid'    => '123e4567-e89b-12d3-a456-426614174000',
            'context' => 'edit'
        ];
        
        $_FILES = [];

        /* 5. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 6. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getPermissions');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 7. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        $this->assertEquals(
            1,
            $json['group_id'],
            'La chiave group_id non è stata iniettata o è errata nel JSON'
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testGetTokensFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getTokensValidationRules'])
                 ->getMock();

        $mockModel->method('getTokensValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getTokens');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetTokensFailsWhenAdminNotFound(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getTokensValidationRules', 'getByUUID'])
                 ->getMock();

        $mockModel->method('getTokensValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result'  => false,
                      'message' => 'Admin non trovato'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getTokens');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertEquals(
            'Admin non trovato',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testGetTokensSucceedsAndRendersView(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getTokensValidationRules', 'getByUUID', 'getTokens'])
                 ->getMock();

        $mockModel->method('getTokensValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto fittizio base per soddisfare l'eventuale output della vista */
        $mockRow = 
            (object)[
                'uuid'      => '123e4567-e89b-12d3-a456-426614174000',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi',
                'phone'     => '123456',
                'status'       => 1,
                'created_at'   => '2026-09-20 10:00:00',
                'updated_at'   => '2026-09-25 15:00:00',
                'suspended_at'   => '2026-09-20 10:00:00',
                'resetted_at'   => '2026-09-25 15:00:00',
                'deleted_at'   => null
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        /* Restituiamo un array vuoto per i token in modo da non far iterare o crashare il foreach della vista */
        $mockModel->method('getTokens')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000'
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('getTokens');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'output', 
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testDeleteTokenFailsValidationOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['deleteTokenValidationRules'])
                 ->getMock();

        $mockModel->method('deleteTokenValidationRules')
                  ->willReturn([
                      'uuid' => 'required',
                      'id'   => 'required'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST SCORRETTI */
        $_POST = [];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('deleteToken');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result'],
            'Il Controller avrebbe dovuto fallire la validazione restituendo false'
        );
        
        $this->assertArrayHasKey(
            'message',
            $json
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testDeleteTokenFailsFromModelOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['deleteTokenValidationRules', 'deleteToken'])
                 ->getMock();

        $mockModel->method('deleteTokenValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        $mockModel->method('deleteToken')
                  ->willReturn([
                      'result'  => false,
                      'message' => 'Token inesistente o record protetto'
                  ]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST VALIDI */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'id'   => 10
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('deleteToken');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertIsArray(
            $json
        );

        $this->assertFalse(
            $json['result']
        );
        
        $this->assertEquals(
            'Token inesistente o record protetto',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testDeleteTokenSucceedsAndRendersViewOnAjaxPost(): void
    {
        /* 1. MOCK AUTENTICAZIONE */
        $mockAuth = 
            $this->getMockBuilder(\stdClass::class)
                 ->addMethods(['currentAdmin'])
                 ->getMock();
                 
        $mockAuth->method('currentAdmin')
                 ->willReturn((object)[
                     'id' => 1, 'firstname' => 'Sys', 'lastname' => 'Test', 'superadmin' => 1
                 ]);
                 
        \Config\Services::injectMock('authorization', $mockAuth);

        /* 2. MOCK ADMINS MODEL */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['deleteTokenValidationRules', 'deleteToken', 'getTokens'])
                 ->getMock();

        $mockModel->method('deleteTokenValidationRules')
                  ->willReturn([
                      'uuid' => 'required'
                  ]);

        /* Oggetto fittizio base per soddisfare l'eventuale output della vista */
        $mockRow = 
            (object)[
                'uuid'      => '123e4567-e89b-12d3-a456-426614174000',
                'firstname' => 'Mario',
                'lastname'  => 'Rossi'
            ];

        $mockModel->method('deleteToken')
                  ->willReturn([
                      'result'  => true,
                      'message' => 'Token eliminato con successo',
                      'admin'   => $mockRow
                  ]);

        /* Restituiamo un array vuoto per i token rimanenti, così non iteriamo sulla vista */
        $mockModel->method('getTokens')
                  ->willReturn([]);

        \CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\AdminsModel::class, $mockModel);

        /* 3. DATI POST */
        $_POST = [
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'id'   => 10
        ];
        
        $_FILES = [];

        /* 4. REQUEST REALE AJAX */
        $config = 
            config('App');
            
        $uri = 
            new \CodeIgniter\HTTP\SiteURI(
                $config, 
                'http://essential.test'
            );
            
        $request = 
            new \CodeIgniter\HTTP\IncomingRequest(
                $config, 
                $uri, 
                null, 
                new \CodeIgniter\HTTP\UserAgent()
            );
            
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        
        $request->setGlobal(
            'post', 
            $_POST
        );

        /* 5. ESECUZIONE */
        $result = 
            $this->withRequest(
                $request
            )
            ->controller(\App\Controllers\Backend\AdminsController::class)
            ->execute('deleteToken');

        $body = 
            strip_tags(
                $result->getBody()
            );
            
        $json = 
            json_decode(
                $body, 
                true
            );

        /* 6. ASSERZIONI */
        $this->assertTrue(
            $result->isOK()
        );

        $this->assertTrue(
            $json['result']
        );
        
        $this->assertArrayHasKey(
            'tokensView', 
            $json
        );

        $this->assertEquals(
            'Token eliminato con successo',
            $json['message']
        );

        /* Pulizia */
        $_POST = [];
        \Config\Services::reset(true);
        \CodeIgniter\Config\Factories::reset('models');
    }

    public function testIndexRendersView(): void
    {
        /* 1. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AdminsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_admins_index');

        /* 2. INIEZIONE */
        $injector = 
            function() {
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AdminsController::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $expected = 
            'mock_view_admins_index';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    /* 1. SCENARIO: Ritorna null se la richiesta non è AJAX */
    public function testRestoreDeleteReturnsNullIfNotAjaxOrPost(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(false);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $controller->initController(
            $request,
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $result = 
            $controller->restoreDelete();

        $this->assertNull(
            $result
        );
    }

    /* 2. SCENARIO: Richiesta corretta ma validazione fallita */
    public function testRestoreDeleteFailsValidation(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['uuid' => '123']);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('delValidationRules')
                  ->willReturn(['uuid' => 'required']);

        $controller = 
            $this->getMockBuilder(\App\Controllers\Backend\AdminsController::class)
                 ->onlyMethods([
                     'validateData', 
                     'jsonResponse'
                 ])
                 ->disableOriginalConstructor()
                 ->getMock();

        $controller->initController(
            $request,
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $controller->method('validateData')
                   ->willReturn(false);

        $mockResponse = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $callback = 
            function (
                $data
            ) use (
                $mockResponse
            ) {
                $this->assertFalse(
                    $data['result']
                );

                return $mockResponse;
            };

        $controller->method('jsonResponse')
                   ->willReturnCallback(
                       $callback
                   );

        $mockValidator = 
            $this->createMock(\CodeIgniter\Validation\ValidationInterface::class);

        $mockValidator->method('getErrors')
                      ->willReturn(['uuid' => 'Errore Validazione']);

        $injector = 
            function () use (
                $mockModel, 
                $mockValidator
            ) {
                $this->adminsModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->restoreDelete();

        $this->assertSame(
            $mockResponse,
            $result
        );
    }

    /* 3. SCENARIO: Validazione superata e ripristino avvenuto con successo */
    public function testRestoreDeleteSucceeds(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['uuid' => '123']);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('delValidationRules')
                  ->willReturn(['uuid' => 'required']);

        $mockModel->method('restoreDelete')
                  ->willReturn([
                      'result' => true, 
                      'message' => 'Ok'
                  ]);

        $controller = 
            $this->getMockBuilder(\App\Controllers\Backend\AdminsController::class)
                 ->onlyMethods([
                     'validateData', 
                     'jsonResponse'
                 ])
                 ->disableOriginalConstructor()
                 ->getMock();

        $controller->initController(
            $request,
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $controller->method('validateData')
                   ->willReturn(true);

        $mockResponse = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $callback = 
            function (
                $data
            ) use (
                $mockResponse
            ) {
                $this->assertTrue(
                    $data['result']
                );

                return $mockResponse;
            };

        $controller->method('jsonResponse')
                   ->willReturnCallback(
                       $callback
                   );

        $injector = 
            function () use (
                $mockModel
            ) {
                $this->adminsModel = 
                    $mockModel;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->restoreDelete();

        $this->assertSame(
            $mockResponse,
            $result
        );
    }

    /* 6. AJAX: Fallimento se admin non trovato */
    public function testEditAjaxFailsIfAdminNotFound(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['uuid' => '123']);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => false, 
                      'message' => 'Err'
                  ]);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $regexp,
                $mockModel
            ) {
                $this->regexp = 
                    $regexp;
                    
                $this->adminsModel = 
                    $mockModel;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit();

        $responseBody = 
            $result->getBody();

        $bodyArray = 
            json_decode(
                $responseBody, 
                true
            );

        $isResultFalse = 
            $bodyArray['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    /* 7. AJAX: Scudo di sicurezza Superadmin */
    public function testEditAjaxFailsIfAdminIsSuperadmin(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['uuid' => '123']);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRow = 
            (object)[
                'superadmin' => 1
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true, 
                      'row'    => $mockRow
                  ]);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $regexp,
                $mockModel
            ) {
                $this->regexp = 
                    $regexp;
                    
                $this->adminsModel = 
                    $mockModel;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit();

        $responseBody = 
            $result->getBody();

        $bodyArray = 
            json_decode(
                $responseBody, 
                true
            );

        $isResultFalse = 
            $bodyArray['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    /* 3. GET: Redirect se l'amministratore non viene trovato */
    public function testEditRedirectsIfAdminNotFound(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(false);

        /* Aggiunto mock Regexp per superare il blocco iniziale dell'UUID */
        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => false,
                      'message' => 'Err'
                  ]);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();
            
        \Config\Services::session();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $mockModel,
                $regexp
            ) {
                $this->adminsModel = 
                    $mockModel;
                    
                $this->regexp = 
                    $regexp;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit('123');

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $result
        );
    }

    /* 4. GET: Redirect se l'amministratore è nel cestino (Scudo Enterprise) */
    public function testEditRedirectsIfAdminDeleted(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(false);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRow = 
            (object)[
                'deleted_at' => '2023-01-01',
                'superadmin' => 0
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();
            
        \Config\Services::session();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $mockModel,
                $regexp
            ) {
                $this->adminsModel = 
                    $mockModel;
                    
                $this->regexp = 
                    $regexp;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit('123');

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $result
        );
    }

    /* 5. GET: Redirect se si tenta di modificare il Superadmin */
    public function testEditRedirectsIfAdminIsSuperadmin(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(false);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRow = 
            (object)[
                'deleted_at' => null,
                'superadmin' => 1
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();
            
        \Config\Services::session();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $mockModel,
                $regexp
            ) {
                $this->adminsModel = 
                    $mockModel;
                    
                $this->regexp = 
                    $regexp;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit('123');

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\RedirectResponse::class,
            $result
        );
    }

    /* 1. AJAX: Fallimento validazione UUID */
    public function testEditAjaxFailsInvalidUuid(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['action' => 'fail']);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(false);

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $regexp
            ) {
                $this->regexp = 
                    $regexp;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit();

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $result
        );

        $responseBody = 
            $result->getBody();

        $bodyArray = 
            json_decode(
                $responseBody, 
                true
            );

        $isResultFalse = 
            $bodyArray['result'];

        $this->assertFalse(
            $isResultFalse
        );
    }

    /* 2. AJAX: Azione Refresh con successo */
    public function testEditAjaxRefreshSucceeds(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn([
                    'uuid' => '123', 
                    'action' => 'refresh'
                ]);

        $regexp = 
            $this->createMock(\App\Libraries\RegExp::class);

        $regexp->method('validateUUID')
               ->willReturn(true);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockRow = 
            (object)[
                'superadmin' => 0,
                'group_id' => 1
            ];

        $mockModel->method('getByUUID')
                  ->willReturn([
                      'result' => true,
                      'row'    => $mockRow
                  ]);

        $mockModel->method('getGroupPermissions')
                  ->willReturn([]);

        $mockModel->method('getAdminExceptions')
                  ->willReturn([]);

        $mockGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockGallery->method('getImages')
                    ->willReturn([]);

        $mockView = 
            $this->createMock(\CodeIgniter\View\View::class);

        $mockView->method('render')
                 ->willReturn('html');

        \CodeIgniter\Config\Services::injectMock(
            'renderer',
            $mockView
        );

        $controller = 
            new \App\Controllers\Backend\AdminsController();

        $response = 
            \Config\Services::response();

        $logger = 
            \Config\Services::logger();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $injector = 
            function () use (
                $regexp,
                $mockModel,
                $mockGallery
            ) {
                $this->regexp = 
                    $regexp;
                    
                $this->adminsModel = 
                    $mockModel;
                    
                $this->galleryOneModel = 
                    $mockGallery;
                    
                $this->data = 
                    [];
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->edit();

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $result
        );

        $responseBody = 
            $result->getBody();

        $bodyArray = 
            json_decode(
                $responseBody, 
                true
            );

        $isResultTrue = 
            $bodyArray['result'];

        $this->assertTrue(
            $isResultTrue
        );
    }

    /* 1. AJAX showAll: Fallimento validazione ricerca */
    public function testShowAllAjaxFailsValidation(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['dummy' => 'data']);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('showAllSearchValidationRules')
                  ->willReturn(['dummy' => 'required']);

        /* Creiamo un Mock Parziale del Controller per controllare validateData e jsonResponse */
        $controller = 
            $this->getMockBuilder(\App\Controllers\Backend\AdminsController::class)
                 ->onlyMethods([
                     'validateData', 
                     'jsonResponse'
                 ])
                 ->disableOriginalConstructor()
                 ->getMock();

        //* Superiamo la prima validazione (true) e facciamo fallire la seconda (false) */
        $controller->method('validateData')
                   ->willReturnOnConsecutiveCalls(
                       true,
                       false
                   );

        $mockResponse = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $callback = 
            function (
                $data
            ) use (
                $mockResponse
            ) {
                /* Asserzione chirurgica: verifichiamo che venga generata la chiave errors */
                $this->assertArrayHasKey(
                    'errors',
                    $data
                );

                return $mockResponse;
            };

        $controller->method('jsonResponse')
                   ->willReturnCallback(
                       $callback
                   );

        /* Mockiamo il validatore e glielo iniettiamo a forza tramite Closure */
        $mockValidator = 
            $this->createMock(\CodeIgniter\Validation\ValidationInterface::class);

        $mockValidator->method('getErrors')
                      ->willReturn(['searchFields.dummy' => 'Error']);

        $controller->initController(
            $request,
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $injector = 
            function () use (
                $mockModel,
                $mockValidator
            ) {
                $this->adminsModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->showAll();

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $result
        );
    }

    /* 2. AJAX showAll: Validazione OK ma Database restituisce false */
    public function testShowAllAjaxReturnsFalseIfDataFails(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $request->method('isAJAX')
                ->willReturn(true);

        $request->method('is')
                ->with('post')
                ->willReturn(true);

        $request->method('getPost')
                ->willReturn(['dummy' => 'data']);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AdminsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        $mockModel->method('showAllSearchValidationRules')
                  ->willReturn(['dummy' => 'required']);

        /* INSERISCI QUI IL NOME ESATTO DEL METODO CHE IL CONTROLLER USA PER IL DATABASE */
        $mockModel->method('getData')
                  ->willReturn([
                      'result' => false,
                      'message' => 'DB Error'
                  ]);

        $controller = 
            $this->getMockBuilder(\App\Controllers\Backend\AdminsController::class)
                 ->onlyMethods([
                     'validateData', 
                     'jsonResponse'
                 ])
                 ->disableOriginalConstructor()
                 ->getMock();

        /* Forziamo il successo della validazione per far scattare l'ELSEIF */
        $controller->method('validateData')
                   ->willReturn(true);

        $mockResponse = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $callback = 
            function (
                $data
            ) use (
                $mockResponse
            ) {
                $this->assertFalse(
                    $data['result']
                );

                return $mockResponse;
            };

        $controller->method('jsonResponse')
                   ->willReturnCallback(
                       $callback
                   );

        $controller->initController(
            $request,
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $injector = 
            function () use (
                $mockModel
            ) {
                $this->adminsModel = 
                    $mockModel;
            };

        $bind = 
            \Closure::bind(
                $injector,
                $controller,
                \App\Controllers\Backend\AdminsController::class
            );

        $bind();

        $result = 
            $controller->showAll();

        $this->assertInstanceOf(
            \CodeIgniter\HTTP\ResponseInterface::class,
            $result
        );
    }
}