<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AccountControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        /* Rende autonomi anche i test che invocano direttamente i singoli endpoint. */
        helper('settings');
    }

    public function testInitControllerSetsCorrectDataAndProperties(): void
    {
        /* 1. MOCK DIPENDENZE CONTROLLER */
        $request = 
            $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);

        $response = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $logger = 
            $this->createMock(\Psr\Log\LoggerInterface::class);

        /* 2. MOCK MODELLI TRAMITE FACTORIES */
        $mockAccount = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\AccountModel::class,
            $mockAccount
        );

        $mockGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\Components\GalleryOneModel::class,
            $mockGallery
        );

        /* 3. ISTANZIAZIONE E INIZIALIZZAZIONE */
        $controller = 
            new \App\Controllers\Backend\AccountController();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        /* 4. ESTRAZIONE PROPRIETA' PROTETTE TRAMITE CLOSURE */
        $extractor = 
            function () {
                return [
                    'data' => 
                        $this->data,
                    'accountModel' => 
                        $this->accountModel,
                    'galleryModel' => 
                        $this->galleryOneModel
                ];
            };

        $bind = 
            \Closure::bind(
                $extractor,
                $controller,
                \App\Controllers\Backend\AccountController::class
            );

        $props = 
            $bind();

        /* 5. ESTRAZIONE SINGOLI VALORI PER ASSERZIONI */
        $data = 
            $props['data'];

        $accModel = 
            $props['accountModel'];

        $gallModel = 
            $props['galleryModel'];

        /* 6. ASSERZIONI LOGICHE E STRUTTURALI */
        $this->assertEquals(
            'account',
            $data['controller']
        );

        $this->assertEquals(
            'admins',
            $data['entity']
        );

        $this->assertArrayHasKey(
            'sections',
            $data
        );

        $this->assertArrayHasKey(
            'general',
            $data['sections']
        );

        $this->assertArrayHasKey(
            'security',
            $data['sections']
        );

        $this->assertInstanceOf(
            \App\Models\Backend\AccountModel::class,
            $accModel
        );

        $this->assertInstanceOf(
            \App\Models\Backend\Components\GalleryOneModel::class,
            $gallModel
        );

    }

    public function testEditRendersViewOnGetRequest(): void
    {
        /* 1. MOCK REQUEST (Simuliamo chiamata NON Ajax) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('view_html');

        /* 3. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->edit();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testEditReturnsRefreshPartialOnRefreshAction(): void
    {
        /* 1. MOCK RENDERER (Intercetta la funzione view() di CodeIgniter) */
        $builderView = 
            $this->getMockBuilder(\CodeIgniter\View\View::class);
            
        $builderView->disableOriginalConstructor();
        
        $mockView = 
            $builderView->getMock();
            
        $mockView->method('setData')->willReturnSelf();
        
        $mockView->method('render')->willReturn('mock_partial_html');
        
        \CodeIgniter\Config\Services::injectMock(
            'renderer', 
            $mockView
        );

        /* 2. MOCK REQUEST (Simuliamo AJAX con action = refresh) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['action' => 'refresh'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn('json_refresh');

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->edit();
            
        $expected = 
            'json_refresh';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        /* 6. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testEditReturnsValidationErrors(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['firstname' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL E ADMIN (Richiesto per validare l'UUID) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['firstname' => 'required'];
            
        $mockModel->method('editValidationRules')->willReturn(
            $fakeRules
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK VALIDATOR */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['firstname' => 'Error'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn('json_val_error');

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockValidator,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->edit();
            
        $expected = 
            'json_val_error';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testEditReturnsFalseOnModelFailure(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['firstname' => 'Test'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('editValidationRules')->willReturn(
            $fakeRules
        );
        
        $modelFail = 
            ['result' => false, 'message' => 'Fail'];
            
        $mockModel->method('edit')->willReturn(
            $modelFail
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn('json_model_fail');

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->edit();
            
        $expected = 
            'json_model_fail';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testEditReturnsSuccessWithViews(): void
    {
        /* 1. MOCK RENDERER (Intercetta la generazione di view HTML in background) */
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

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['firstname' => 'Test'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK MODEL E ADMIN (Simula successo e restituzione utente aggiornato) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('editValidationRules')->willReturn(
            $fakeRules
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];
            
        $modelSuccess = 
            ['result' => true, 'currentAdmin' => $adminObj];
            
        $mockModel->method('edit')->willReturn(
            $modelSuccess
        );

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn('json_success');

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->edit();
            
        $expected = 
            'json_success';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        /* 7. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testPermissionsRendersViewOnGetRequest(): void
    {
        /* 1. MOCK CONFIGURAZIONE PERMESSI */
        $builderConfig = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderConfig->addMethods(['getPermissions']);
        
        $mockConfig = 
            $builderConfig->getMock();
            
        $fakePerms = 
            ['test.perm' => 'Test'];
            
        $mockConfig->method('getPermissions')->willReturn(
            $fakePerms
        );
        
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Permissions::class, 
            $mockConfig
        );

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 3. MOCK MODEL E ADMIN CORRENTE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeGroupPerms = 
            ['brands_show'];
            
        $mockModel->method('getGroupPermissions')->willReturn(
            $fakeGroupPerms
        );
        
        $fakeExceptions = 
            ['brands_edit' => 1];
            
        $mockModel->method('getAdminExceptions')->willReturn(
            $fakeExceptions
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc', 'group_id' => 1];

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('view_html');

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->permissions();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        /* 7. PULIZIA */
        \CodeIgniter\Config\Factories::reset('config');
    }

    public function testPermissionsReturnsJsonResponseOnAjaxPost(): void
    {
        /* 1. MOCK CONFIGURAZIONE PERMESSI */
        $builderConfig = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderConfig->addMethods(['getPermissions']);
        
        $mockConfig = 
            $builderConfig->getMock();
            
        $fakePerms = 
            ['test.perm' => 'Test'];
            
        $mockConfig->method('getPermissions')->willReturn(
            $fakePerms
        );
        
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            \Config\Backend\Permissions::class, 
            $mockConfig
        );

        /* 2. MOCK RENDERER (Intercetta la funzione view() di CodeIgniter) */
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

        /* 3. MOCK DEL SERVIZIO DI AUTHORIZATION (Chain method) */
        $builderAuth = 
            $this->getMockBuilder(\stdClass::class);
            
        $builderAuth->addMethods(['refresh', 'currentAdmin']);
        
        $mockAuth = 
            $builderAuth->getMock();
            
        $mockAuth->method('refresh')->willReturnSelf();
        
        $adminObj = 
            (object) ['uuid' => '123-abc', 'group_id' => 1];
            
        $mockAuth->method('currentAdmin')->willReturn(
            $adminObj
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'authorization', 
            $mockAuth
        );

        /* 4. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

        /* 5. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeGroupPerms = 
            ['brands_show'];
            
        $mockModel->method('getGroupPermissions')->willReturn(
            $fakeGroupPerms
        );
        
        $fakeExceptions = 
            ['brands_edit' => 1];
            
        $mockModel->method('getAdminExceptions')->willReturn(
            $fakeExceptions
        );

        /* 6. MOCK CONTROLLER E INIEZIONE */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn('json_success');

        $injector = 
            function() use (
                $mockRequest,
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 7. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->permissions();
            
        $expected = 
            'json_success';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        /* 8. PULIZIA */
        \CodeIgniter\Config\Factories::reset('config');
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testImagesRendersView(): void
    {
        /* 1. MOCK MODEL GALLERY */
        $builderGallery = 
            $this->getMockBuilder(\App\Models\Backend\Components\GalleryOneModel::class);
            
        $builderGallery->disableOriginalConstructor();
        
        $mockGallery = 
            $builderGallery->getMock();
            
        $fakeImages = 
            [];
            
        $mockGallery->method('getImages')->willReturn(
            $fakeImages
        );

        /* 2. MOCK ADMIN CORRENTE */
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('view_html');

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockGallery,
                $adminObj
            ) {
                $this->galleryOneModel = 
                    $mockGallery;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->images();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testTokensRendersViewOnGetRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK MODEL E ADMIN CORRENTE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeTokens = 
            [];
            
        $mockModel->method('getTokens')->willReturn(
            $fakeTokens
        );
        
        $fakeTokenId = 
            1;
            
        $mockModel->method('getCurrentTokenId')->willReturn(
            $fakeTokenId
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('view_html');

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->tokens();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testTokensReturnsJsonResponseOnAjaxPost(): void
    {
        /* 1. MOCK RENDERER (Intercetta la funzione view() di CodeIgniter) */
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

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

        /* 3. MOCK MODEL E ADMIN CORRENTE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeTokens = 
            [];
            
        $mockModel->method('getTokens')->willReturn(
            $fakeTokens
        );
        
        $fakeTokenId = 
            1;
            
        $mockModel->method('getCurrentTokenId')->willReturn(
            $fakeTokenId
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK CONTROLLER E INIEZIONE */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn('json_success');

        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->tokens();
            
        $expected = 
            'json_success';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        /* 6. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testDeleteTokenReturnsValidationErrors(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['id' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['id' => 'required'];
            
        $mockModel->method('deleteTokenValidationRules')->willReturn(
            $fakeRules
        );

        /* 3. MOCK VALIDATOR */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['id' => 'Invalid ID'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE (Verifichiamo che restituisca l'oggetto mockato) */
        $result = 
            $controller->deleteToken();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testDeleteTokenReturnsFalseOnModelFailure(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['id' => '2'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('deleteTokenValidationRules')->willReturn(
            $fakeRules
        );
        
        $fakeTokenId = 
            1;
            
        $mockModel->method('getCurrentTokenId')->willReturn(
            $fakeTokenId
        );
        
        $modelFail = 
            ['result' => false, 'message' => 'Delete failed'];
            
        $mockModel->method('deleteToken')->willReturn(
            $modelFail
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->deleteToken();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testDeleteTokenReturnsSuccessWithView(): void
    {
        /* 1. MOCK RENDERER (Intercetta la generazione di view HTML in background) */
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

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['id' => '2'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('deleteTokenValidationRules')->willReturn(
            $fakeRules
        );
        
        $fakeTokenId = 
            1;
            
        $mockModel->method('getCurrentTokenId')->willReturn(
            $fakeTokenId
        );
        
        $modelSuccess = 
            ['result' => true, 'message' => 'Deleted'];
            
        $mockModel->method('deleteToken')->willReturn(
            $modelSuccess
        );
        
        $fakeTokensArray = 
            [];
            
        $mockModel->method('getTokens')->willReturn(
            $fakeTokensArray
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->deleteToken();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
        
        /* 7. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testResetPasswordRendersViewOnGetRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeDate = 
            '2026-12-31';
            
        $mockModel->method('getExpiringDate')->willReturn(
            $fakeDate
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('view_html');

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->resetPassword();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testResetPasswordReturnsJsonOnModelFailure(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

		$mockRequest->method('getPost')->willReturn(['currentPassword' => 'ValidPassword1!']);
		$mockRequest->method('getIPAddress')->willReturn('127.0.0.1');

        /* 2. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('currentPasswordValidationRules')->willReturn([
			'currentPassword' => ['rules' => ['required']],
		]);
		$mockModel->expects($this->once())->method('verifyCurrentPassword')->willReturn(true);
            
        $modelFail = 
            ['result' => false, 'message' => 'Reset failed'];
            
        $mockModel->expects($this->once())->method('resetPassword')->willReturn(
            $modelFail
        );
        
        $fakeDate = 
            '2026-12-31';
            
        $mockModel->method('getExpiringDate')->willReturn(
            $fakeDate
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->resetPassword();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testResetPasswordReturnsJsonWithViewOnModelSuccess(): void
    {
        /* 1. MOCK RENDERER */
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

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

		$mockRequest->method('getPost')->willReturn(['currentPassword' => 'ValidPassword1!']);
		$mockRequest->method('getIPAddress')->willReturn('127.0.0.2');

        /* 3. MOCK MODEL E ADMIN */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('currentPasswordValidationRules')->willReturn([
			'currentPassword' => ['rules' => ['required']],
		]);
		$mockModel->expects($this->once())->method('verifyCurrentPassword')->willReturn(true);
            
        $modelSuccess = 
            ['result' => true, 'message' => 'Reset success'];
            
        $mockModel->expects($this->once())->method('resetPassword')->willReturn(
            $modelSuccess
        );
        
        $fakeDate = 
            '2026-12-31';
            
        $mockModel->method('getExpiringDate')->willReturn(
            $fakeDate
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->resetPassword();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
        
        /* 7. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testSaveBasicMethodReturnsNullOnNonAjaxRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        /* 3. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE (Uscita implicita) */
        $result = 
            $controller->saveBasicMethod();
            
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(400, $result->getStatusCode());
    }

    public function testSaveBasicMethodReturnsFalseOnInvalidMethod(): void
    {
        helper('settings');

        \CodeIgniter\Config\Factories::injectMock(
            'config',
            'Backend\Auth',
            (object) ['twoFactorMethods' => ['email']]
        );

        /* 2. MOCK REQUEST (Passiamo 'totp' che è esplicitamente vietato dalla guardia) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $mockRequest->method('getPost')->willReturn('totp');

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with([
                       'result' => false,
                       'message' => lang('backend/account.messages.methodNotValid')
                   ])
                   ->willReturn($mockResponse);

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->saveBasicMethod();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testSaveBasicMethodReturnsFalseOnModelFailure(): void
    {
        helper('settings');

        \CodeIgniter\Config\Factories::injectMock(
            'config',
            'Backend\Auth',
            (object) ['twoFactorMethods' => ['email']]
        );

        /* 2. MOCK REQUEST (Passiamo 'email', che è valido) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
		$mockRequest->method('getPost')->willReturnCallback(
			static fn (?string $key = null): string => $key === 'currentPassword' ? 'ValidPassword1!' : 'email'
		);

        /* 3. MOCK MODEL (Simuliamo fallimento aggiornamento) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('verifyCurrentPassword')->willReturn(true);
            
        $mockModel->expects($this->once())
                  ->method('setBasicMethod')
                  ->with($this->isInstanceOf(\stdClass::class), 'email')
                  ->willReturn(false);
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with([
                       'result' => false,
                       'message' => lang('backend/account.messages.updateSecuritySettingsError')
                   ])
                   ->willReturn($mockResponse);

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->saveBasicMethod();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testSaveBasicMethodReturnsTrueOnSuccess(): void
    {
        helper('settings');

        \CodeIgniter\Config\Factories::injectMock(
            'config',
            'Backend\Auth',
            (object) ['twoFactorMethods' => ['email']]
        );

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
		$mockRequest->method('getPost')->willReturnCallback(
			static fn (?string $key = null): string => $key === 'currentPassword' ? 'ValidPassword1!' : 'email'
		);

        /* 3. MOCK MODEL (Simuliamo successo aggiornamento) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('verifyCurrentPassword')->willReturn(true);
            
        $mockModel->expects($this->once())
                  ->method('setBasicMethod')
                  ->with($this->isInstanceOf(\stdClass::class), 'email')
                  ->willReturn(true);
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with([
                       'result' => true,
                       'message' => lang('backend/account.messages.updateSecuritySettingsSuccess')
                   ])
                   ->willReturn($mockResponse);

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->saveBasicMethod();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testSetupTotpReturnsNullOnNonAjaxRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        /* 3. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE (Uscita implicita) */
        $result = 
            $controller->setupTotp();
            
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(400, $result->getStatusCode());
    }

    public function testSetupTotpReturnsFalseOnModelFailure(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

		$mockRequest->method('getPost')->willReturn('ValidPassword1!');

        /* 2. MOCK MODEL E ADMIN (Email necessaria per AppOtpService) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('currentPasswordValidationRules')->willReturn([
			'currentPassword' => ['rules' => ['required']],
		]);
		$mockModel->expects($this->once())->method('verifyCurrentPassword')->willReturn(true);
            
        $mockModel->expects($this->once())->method('saveTemporarySecret')->willReturn(false);
        
        $adminObj = 
            (object) ['uuid' => '123-abc', 'email' => 'admin@test.com'];

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->setupTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testSetupTotpReturnsTrueWithViewOnSuccess(): void
    {
        /* 1. MOCK RENDERER (Intercetta la funzione view()) */
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

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);

		$mockRequest->method('getPost')->willReturn('ValidPassword1!');

        /* 3. MOCK MODEL E ADMIN (L'email sarà iniettata nell'uri di Endroid) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();

		$mockModel->method('currentPasswordValidationRules')->willReturn([
			'currentPassword' => ['rules' => ['required']],
		]);
		$mockModel->expects($this->once())->method('verifyCurrentPassword')->willReturn(true);
            
        $mockModel->expects($this->once())->method('saveTemporarySecret')->willReturn(true);
        
        $adminObj = 
            (object) ['uuid' => '123-abc', 'email' => 'admin@test.com'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE (Eseguirà realmente AppOtpService e QrCode Builder) */
        $result = 
            $controller->setupTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
        
        /* 7. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testConfirmTotpReturnsNullOnNonAjaxRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        /* 3. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE (Uscita implicita) */
        $result = 
            $controller->confirmTotp();
            
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(400, $result->getStatusCode());
    }

    public function testConfirmTotpReturnsValidationErrors(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['otp' => '12'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 3. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest
            ) {
                $this->request = 
                    $mockRequest;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->confirmTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testConfirmTotpReturnsFalseWhenSecretNotFound(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['otp' => '123456'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL E ADMIN (Restituiamo null per rispettare il type hint ?string) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeSecret = 
            null;
            
        $mockModel->method('getTemporarySecret')->willReturn(
            $fakeSecret
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->confirmTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testConfirmTotpReturnsFalseOnWrongOtpCode(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['otp' => '000000'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL E ADMIN (Usiamo un secret Base32 valido per superare la decodifica iniziale) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeSecret = 
            'JBSWY3DPEHPK3PXP';
            
        $mockModel->method('getTemporarySecret')->willReturn(
            $fakeSecret
        );
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->confirmTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testConfirmTotpReturnsFalseOnModelActivationFailure(): void
    {
        /* 1. SETUP SECRET BASE32 E GENERAZIONE DINAMICA OTP VALIDO */
        $fakeSecret = 
            'JBSWY3DPEHPK3PXP';
            
        $totp = 
            \OTPHP\TOTP::create($fakeSecret);
            
        $validOtp = 
            $totp->now();

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['otp' => $validOtp];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK MODEL E ADMIN (Simula fallimento attivazione DB) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->method('getTemporarySecret')->willReturn(
            $fakeSecret
        );
        
        $mockModel->method('activateTotpMethod')->willReturn(false);
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->confirmTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testConfirmTotpReturnsTrueOnSuccess(): void
    {
        /* 1. SETUP SECRET BASE32 E GENERAZIONE DINAMICA OTP VALIDO */
        $fakeSecret = 
            'JBSWY3DPEHPK3PXP';
            
        $totp = 
            \OTPHP\TOTP::create($fakeSecret);
            
        $validOtp = 
            $totp->now();

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['otp' => $validOtp];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK MODEL E ADMIN (Simula successo attivazione DB) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->method('getTemporarySecret')->willReturn(
            $fakeSecret
        );
        
        $mockModel->method('activateTotpMethod')->willReturn(true);
        
        $adminObj = 
            (object) ['uuid' => '123-abc'];

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE DIPENDENZE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->confirmTotp();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testSecurityRendersView(): void
    {
        /* 1. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AccountModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->method('getActiveMethod')->willReturn('totp');

        /* 2. MOCK CONTROLLER (Intercettiamo solo il render) */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_security');

        /* 3. INIEZIONE */
        $injector = 
            function() use (
                $mockModel
            ) {
                $this->accountModel = 
                    $mockModel;
                    
                $this->currentAdmin = 
                    (object) ['uuid' => '123-abc'];
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->security();
            
        $expected = 
            'mock_view_security';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testVerifyCurrentPasswordAcceptsValidPassword(): void
    {
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->expects($this->once())
            ->method('check')
            ->with(
                $this->stringStartsWith('account-current-password-'),
                3,
                600,
                0
            )
            ->willReturn(true);

        $test = $this->createVerifyCurrentPasswordController(
            'PasswordCorretta!',
            true,
            true,
            1,
            $throttler
        );

        $result = $test['controller']->verifyCurrentPassword();

        $this->assertSame($test['response'], $result);
        $this->assertSame(['result' => true], $test['output']->data);
        $this->assertSame(200, $test['output']->statusCode);
    }

    public function testVerifyCurrentPasswordRejectsWrongPasswordAndConsumesAttempt(): void
    {
        $costs = [];
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->expects($this->exactly(2))
            ->method('check')
            ->willReturnCallback(static function (string $key, int $capacity, int $seconds, int $cost) use (&$costs): bool {
                $costs[] = $cost;
                return true;
            });

        $test = $this->createVerifyCurrentPasswordController(
            'PasswordErrata!',
            true,
            false,
            1,
            $throttler
        );

        $result = $test['controller']->verifyCurrentPassword();

        $this->assertSame($test['response'], $result);
        $this->assertSame([0, 1], $costs);
        $this->assertSame(false, $test['output']->data['result']);
        $this->assertSame(
            lang('backend/account.errors.currentPasswordInvalid'),
            $test['output']->data['errors']['currentPassword']
        );
        $this->assertSame(200, $test['output']->statusCode);
    }

    public function testVerifyCurrentPasswordReturns429WhenRateLimitIsReached(): void
    {
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->expects($this->once())
            ->method('check')
            ->with(
                $this->stringStartsWith('account-current-password-'),
                3,
                600,
                0
            )
            ->willReturn(false);

        $test = $this->createVerifyCurrentPasswordController(
            'PasswordCorretta!',
            true,
            true,
            0,
            $throttler
        );

        $result = $test['controller']->verifyCurrentPassword();

        $this->assertSame($test['response'], $result);
        $this->assertSame(false, $test['output']->data['result']);
        $this->assertSame(lang('backend/account.messages.tooManyAttempts'), $test['output']->data['message']);
        $this->assertSame(429, $test['output']->statusCode);
    }

    public function testVerifyCurrentPasswordRejectsEmptyPasswordBeforeRateLimit(): void
    {
        $throttler = $this->createMock(\CodeIgniter\Throttle\ThrottlerInterface::class);
        $throttler->expects($this->never())->method('check');

        $test = $this->createVerifyCurrentPasswordController(
            '',
            false,
            false,
            0,
            $throttler,
            ['currentPassword' => 'Password obbligatoria']
        );

        $result = $test['controller']->verifyCurrentPassword();

        $this->assertSame($test['response'], $result);
        $this->assertSame(false, $test['output']->data['result']);
        $this->assertSame('Password obbligatoria', $test['output']->data['message']);
        $this->assertSame(200, $test['output']->statusCode);
    }

    public function testGeneralRendersView(): void
    {
        /* 1. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_general');

        $accountModel = $this->createMock(\App\Models\Backend\AccountModel::class);
        $accountModel->expects($this->once())->method('getGroupName')->with(1)->willReturn((object) ['name' => 'Administrators']);

        /* 2. INIEZIONE */
        $injector = 
            function() use ($accountModel) {
                $this->data = 
                    [];
                $this->accountModel = $accountModel;
                $this->currentAdmin = (object) ['group_id' => 1];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->general();
            
        $expected = 
            'mock_view_general';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testIndexRendersView(): void
    {
        /* 1. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AccountController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_index');

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
                \App\Controllers\Backend\AccountController::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $expected = 
            'mock_view_index';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    /**
     * Prepara il controller isolato per verificare il precontrollo della password corrente.
     *
     * @param string $password Password inviata dalla richiesta
     * @param bool $validationResult Esito della validazione formale
     * @param bool $passwordResult Esito della verifica della password nel modello
     * @param int $expectedVerifyCalls Numero atteso di verifiche sul modello
     * @param \CodeIgniter\Throttle\ThrottlerInterface $throttler Throttler simulato
     * @param array $validationErrors Errori di validazione simulati
     * @return array Controller e dipendenze osservabili del test
     */
    private function createVerifyCurrentPasswordController(
        string $password,
        bool $validationResult,
        bool $passwordResult,
        int $expectedVerifyCalls,
        \CodeIgniter\Throttle\ThrottlerInterface $throttler,
        array $validationErrors = []
    ): array {
        $request = $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('isAJAX')->willReturn(true);
        $request->method('is')->with('post')->willReturn(true);
        $request->method('getPost')->with('currentPassword')->willReturn($password);
        $request->method('getIPAddress')->willReturn('127.0.0.1');

        $accountModel = $this->getMockBuilder(\App\Models\Backend\AccountModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $accountModel->method('currentPasswordValidationRules')->willReturn([
            'currentPassword' => ['rules' => ['required']],
        ]);
        $accountModel->expects($this->exactly($expectedVerifyCalls))
            ->method('verifyCurrentPassword')
            ->with('admin-test', $password)
            ->willReturn($passwordResult);

        $validator = $this->getMockBuilder(\CodeIgniter\Validation\Validation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validator->method('getErrors')->willReturn($validationErrors);

        $settingsModel = $this->getMockBuilder(\App\Models\Backend\SettingsModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $settingsModel->method('getSettings')->with('Backend\\Auth')->willReturn([
            'attemptsLimit' => 3,
            'attemptsInterval' => 600,
        ]);
        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\SettingsModel::class,
            $settingsModel
        );
        Services::injectMock('throttler', $throttler);

        $response = $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);
        $output = (object) [
            'data' => null,
            'statusCode' => null,
        ];

        $controller = $this->getMockBuilder(\App\Controllers\Backend\AccountController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateData', 'jsonResponse'])
            ->getMock();
        $controller->method('validateData')->willReturn($validationResult);
        $controller->method('jsonResponse')
            ->willReturnCallback(static function (array $data, int $statusCode = 200) use ($output, $response) {
                $output->data = $data;
                $output->statusCode = $statusCode;
                return $response;
            });

        $injector = function () use ($request, $accountModel, $validator) {
            $this->request = $request;
            $this->accountModel = $accountModel;
            $this->validator = $validator;
            $this->currentAdmin = (object) ['uuid' => 'admin-test'];
        };

        $boundInjector = \Closure::bind(
            $injector,
            $controller,
            \App\Controllers\Backend\AccountController::class
        );
        $boundInjector();

        return [
            'controller' => $controller,
            'response' => $response,
            'output' => $output,
        ];
    }
}
