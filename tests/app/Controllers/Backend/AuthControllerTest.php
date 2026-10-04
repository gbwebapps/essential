<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AuthControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testLoginRendersViewOnGetRequest(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $once = 
            $this->once();
            
        $expects = 
            $controller->expects(
                $once
            );
            
        $method = 
            $expects->method('render');
            
        $anything = 
            $this->anything();
            
        $with = 
            $method->with(
                'backend/auth/loginView', 
                $anything
            );
            
        $with->willReturn('view_html');

        /* 3. INIEZIONE DIPENDENZE VIA CLOSURE */
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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $result = 
            $controller->login();

        /* 5. ASSERZIONI */
        $expectedHtml = 
            'view_html';
            
        $this->assertEquals(
            $expectedHtml, 
            $result
        );
    }

    public function testLoginFailsOnValidationErrors(): void
    {
        /* 1. MOCK REQUEST (Simula chiamata POST AJAX) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['email' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK AUTHMODEL (Per le regole di validazione) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['email' => 'required'];
            
        $mockModel->method('validateLoginRules')->willReturn(
            $fakeRules
        );

        /* 3. MOCK VALIDATOR (Per gli errori di validazione) */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['email' => 'Invalid email'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 4. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn('json_error');

        /* 5. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel, 
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $result = 
            $controller->login();

        /* 7. ASSERZIONI */
        $expectedResult = 
            'json_error';
            
        $this->assertEquals(
            $expectedResult, 
            $result
        );
    }

    public function testLoginSuccessReturnsJsonWithRedirect(): void
    {
        /* 1. MOCK SESSION */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $intendedUrl = 
            'http://localhost/intended';
            
        $mockSession->expects($this->once())
                    ->method('get')
                    ->with('intended_url')
                    ->willReturn($intendedUrl);

        $mockSession->expects($this->once())
                    ->method('remove')
                    ->with('intended_url');
        
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
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            ['email' => 'admin@test.com'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('validateLoginRules')->willReturn(
            $fakeRules
        );
        
        $modelResult = 
            ['result' => true];
            
        $mockModel->expects($this->once())
                  ->method('login')
                  ->with($postData, $mockRequest)
                  ->willReturn($modelResult);

        /* 4. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $jsonReturn = 
            'json_success';
            
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with([
                       'result' => true,
                       'redirect' => $intendedUrl
                   ])
                   ->willReturn($jsonReturn);

        /* 5. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $result = 
            $controller->login();

        /* 7. ASSERZIONI E PULIZIA */
        $expectedResult = 
            'json_success';
            
        $this->assertEquals(
            $expectedResult, 
            $result
        );
        
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

        /* 2. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $once = 
            $this->once();
            
        $expects = 
            $controller->expects(
                $once
            );
            
        $method = 
            $expects->method('render');
            
        $anything = 
            $this->anything();
            
        $with = 
            $method->with(
                'backend/auth/resetPasswordView', 
                $anything
            );
            
        $with->willReturn('view_html');

        /* 3. INIEZIONE DIPENDENZE VIA CLOSURE */
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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE */
        $result = 
            $controller->resetPassword();

        /* 5. ASSERZIONI */
        $expectedHtml = 
            'view_html';
            
        $this->assertEquals(
            $expectedHtml, 
            $result
        );
    }

    public function testResetPasswordFailsOnValidationErrors(): void
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
            ['email' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['email' => 'required'];
            
        $mockModel->method('validateResetPasswordRules')->willReturn(
            $fakeRules
        );

        /* 3. MOCK VALIDATOR */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['email' => 'Invalid email'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 4. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn('json_error');

        /* 5. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel, 
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $result = 
            $controller->resetPassword();

        /* 7. ASSERZIONI */
        $expectedResult = 
            'json_error';
            
        $this->assertEquals(
            $expectedResult, 
            $result
        );
    }

    public function testResetPasswordSuccessReturnsJson(): void
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
            ['email' => 'admin@test.com'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('validateResetPasswordRules')->willReturn(
            $fakeRules
        );
        
        $modelResult = 
            ['result' => true];
            
        $mockModel->expects($this->once())
                  ->method('resetPassword')
                  ->with($postData, $mockRequest)
                  ->willReturn($modelResult);

        /* 3. MOCK PARZIALE DEL CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $jsonReturn = 
            'json_success';
            
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with($modelResult)
                   ->willReturn($jsonReturn);

        /* 4. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $controller->resetPassword();

        /* 6. ASSERZIONI */
        $expectedResult = 
            'json_success';
            
        $this->assertEquals(
            $expectedResult, 
            $result
        );
    }

    public function testSetPasswordFailsOnValidationErrors(): void
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
            ['password' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['password' => 'required'];
            
        $mockModel->method('validateSetPasswordRules')->willReturn(
            $fakeRules
        );

        /* 3. MOCK VALIDATOR */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['password' => 'Invalid password'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn('json_error');

        /* 5. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel, 
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE */
        $result = 
            $controller->setPassword();

        /* 7. ASSERZIONE */
        $expected = 
            'json_error';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetPasswordSuccessReturnsJson(): void
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
            ['password' => 'NewPass123!'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('validateSetPasswordRules')->willReturn(
            $fakeRules
        );
        
        $modelResult = 
            ['result' => true];
            
        $mockModel->expects($this->once())
                  ->method('setPassword')
                  ->with($postData)
                  ->willReturn($modelResult);

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with($modelResult)
                   ->willReturn('json_success');

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $controller->setPassword();

        /* 6. ASSERZIONE */
        $expected = 
            'json_success';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetPasswordRendersViewOnValidToken(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->method('checkAuthToken')->willReturn(true);

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $once = 
            $this->once();
            
        $expects = 
            $controller->expects(
                $once
            );
            
        $method = 
            $expects->method('render');
            
        $anything = 
            $this->anything();
            
        $with = 
            $method->with(
                'backend/auth/setPasswordView', 
                $anything
            );
            
        $with->willReturn('view_html');

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'valid_token';
            
        $result = 
            $controller->setPassword(
                $token
            );

        /* 6. ASSERZIONE */
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testSetPasswordRedirectsOnInvalidToken(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 2. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->method('checkAuthToken')->willReturn(false);

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $token = 
            'invalid_token';
            
        $result = 
            $controller->setPassword(
                $token
            );

        /* 6. ASSERZIONE SULLA CLASSE DI REDIRECT DI CODEIGNITER */
        $classRedirect = 
            \CodeIgniter\HTTP\RedirectResponse::class;
            
        $this->assertInstanceOf(
            $classRedirect, 
            $result
        );
    }

    public function testVerifyThrowsExceptionWhen2FAIsDisabled(): void
    {
        helper('settings');

        $settingsModel =
            $this->getMockBuilder(\App\Models\Backend\SettingsModel::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['getSettings'])
                 ->getMock();

        $settingsModel->expects($this->once())
                      ->method('getSettings')
                      ->with('Backend\Auth')
                      ->willReturn(['twoFactor' => false]);

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\SettingsModel::class,
            $settingsModel
        );

        /* 2. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();

        /* 3. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        $this->expectException(
            \CodeIgniter\Exceptions\PageNotFoundException::class
        );

        $controller->verify();
    }

    public function testVerifyFailsOnValidationErrors(): void
    {
        /* CARICAMENTO MANUALE HELPER */
        helper('settings');

        /* 1. MOCK CONFIGURAZIONE SETTING (2FA Accesa) */
        $configArr = 
            ['twoFactor' => true];
            
        $mockConfig = 
            (object) $configArr;
            
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend\Auth', 
            $mockConfig
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
            ['code' => ''];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['code' => 'required'];
            
        $mockModel->method('validateVerifyRules')->willReturn(
            $fakeRules
        );

        /* 4. MOCK VALIDATOR */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['code' => 'Invalid'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        /* 5. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn('json_error');

        /* 6. INIEZIONE DIPENDENZE VIA CLOSURE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel, 
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 7. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->verify();
            
        $expected = 
            'json_error';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testVerifySuccessReturnsJson(): void
    {
        /* CARICAMENTO MANUALE HELPER */
        helper('settings');

        /* 1. MOCK CONFIGURAZIONE SETTING (2FA Accesa) */
        $configArr = 
            ['twoFactor' => true];
            
        $mockConfig = 
            (object) $configArr;
            
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend\Auth', 
            $mockConfig
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
            ['code' => '123456'];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK AUTHMODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('validateVerifyRules')->willReturn(
            $fakeRules
        );
        
        $modelResult = 
            ['result' => true];
            
        $mockModel->expects($this->once())
                  ->method('verify')
                  ->with($postData, $mockRequest)
                  ->willReturn($modelResult);

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->expects($this->once())
                   ->method('jsonResponse')
                   ->with($modelResult)
                   ->willReturn('json_success');

        /* 5. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest, 
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->verify();
            
        $expected = 
            'json_success';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testVerifyRedirectsOnEmptySessionGetRequest(): void
    {
        /* CARICAMENTO MANUALE HELPER */
        helper('settings');

        /* 1. MOCK CONFIGURAZIONE SETTING (2FA Accesa) */
        $configArr = 
            ['twoFactor' => true];
            
        $mockConfig = 
            (object) $configArr;
            
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend\Auth', 
            $mockConfig
        );

        /* 2. MOCK SESSION */
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

        /* 3. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        /* 5. INIEZIONE */
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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->verify();
            
        $classRedirect = 
            \CodeIgniter\HTTP\RedirectResponse::class;
            
        $this->assertInstanceOf(
            $classRedirect, 
            $result
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testVerifyRendersViewOnValidSessionGetRequest(): void
    {
        /* CARICAMENTO MANUALE HELPER */
        helper('settings');

        /* 1. MOCK CONFIGURAZIONE SETTING (2FA Accesa) */
        $configArr = 
            ['twoFactor' => true];
            
        $mockConfig = 
            (object) $configArr;
            
        \CodeIgniter\Config\Factories::injectMock(
            'config', 
            'Backend\Auth', 
            $mockConfig
        );

        /* 2. MOCK SESSION */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $sessData = 
            ['admin_uuid' => '123-abc'];
            
        $mockSession->method('get')->willReturn(
            $sessData
        );
        
        \CodeIgniter\Config\Services::injectMock(
            'session', 
            $mockSession
        );

        /* 3. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(false);

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $once = 
            $this->once();
            
        $expects = 
            $controller->expects(
                $once
            );
            
        $method = 
            $expects->method('render');
            
        $anything = 
            $this->anything();
            
        $with = 
            $method->with(
                'backend/auth/verifyView', 
                $anything
            );
            
        $with->willReturn('view_html');

        /* 5. INIEZIONE */
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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->verify();
            
        $expected = 
            'view_html';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        \CodeIgniter\Config\Services::reset();
    }

    public function testIndexRendersView(): void
    {
        /* 1. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_auth_index');

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
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $expected = 
            'mock_view_auth_index';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testLogoutExecutesCookieLogoutWhenCookieExists(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $fakeCookie = 
            'my_fake_cookie_value';
            
        $mockRequest->method('getCookie')->willReturn(
            $fakeCookie
        );

        /* 2. MOCK MODEL (Ci aspettiamo l'invocazione di logoutByCookie) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['logoutByCookie']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->expects(
            $this->once()
        )->method('logoutByCookie');

        /* 3. MOCK SESSION (Ci aspettiamo 3 chiamate a setFlashdata) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->expects(
            $this->exactly(3)
        )->method('setFlashdata');

        /* 4. MOCK CONTROLLER E INIEZIONE */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        $adminObj = 
            (object) ['firstname' => 'Mario', 'lastname' => 'Rossi'];

        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockSession,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->session = 
                    $mockSession;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $controller->logout();

        /* 6. ASSERZIONE (Verifichiamo il redirect) */
        $isRedirect = 
            $result instanceof \CodeIgniter\HTTP\RedirectResponse;
            
        $this->assertTrue(
            $isRedirect
        );
    }

    public function testLogoutExecutesSessionLogoutWhenCookieIsMissing(): void
    {
        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getCookie')->willReturn(null);

        /* 2. MOCK MODEL (Ci aspettiamo l'invocazione di logoutBySession) */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['logoutBySession']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $mockModel->expects(
            $this->once()
        )->method('logoutBySession');

        /* 3. MOCK SESSION (Ci aspettiamo 3 chiamate a setFlashdata) */
        $builderSession = 
            $this->getMockBuilder(\CodeIgniter\Session\Session::class);
            
        $builderSession->disableOriginalConstructor();
        
        $mockSession = 
            $builderSession->getMock();
            
        $mockSession->expects(
            $this->exactly(3)
        )->method('setFlashdata');

        /* 4. MOCK CONTROLLER E INIEZIONE */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuthController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods([]);
        
        $controller = 
            $builderCtrl->getMock();

        $adminObj = 
            (object) ['firstname' => 'Luigi', 'lastname' => 'Verdi'];

        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockSession,
                $adminObj
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->authModel = 
                    $mockModel;
                    
                $this->session = 
                    $mockSession;
                    
                $this->currentAdmin = 
                    $adminObj;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuthController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE */
        $result = 
            $controller->logout();

        /* 6. ASSERZIONE (Verifichiamo il redirect) */
        $isRedirect = 
            $result instanceof \CodeIgniter\HTTP\RedirectResponse;
            
        $this->assertTrue(
            $isRedirect
        );
    }

    public function testInitControllerSetsCorrectDataAndProperties(): void
    {
        /* Mock delle dipendenze native di CodeIgniter */
        $request = 
            $this->createMock(\CodeIgniter\HTTP\RequestInterface::class);

        $response = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $logger = 
            $this->createMock(\Psr\Log\LoggerInterface::class);

        /* Creazione e iniezione del Mock per AuthModel */
        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AuthModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\AuthModel::class,
            $mockModel
        );

        /* Inizializzazione del Controller */
        $controller = 
            new \App\Controllers\Backend\AuthController();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        /* Estrazione delle proprietà protette */
        $extractor = 
            function () {
                return [
                    'data' => 
                        $this->data,
                    'authModel' => 
                        $this->authModel,
                    'authClass' => 
                        $this->authClass
                ];
            };

        $bind = 
            \Closure::bind(
                $extractor,
                $controller,
                \App\Controllers\Backend\AuthController::class
            );

        $props = 
            $bind();

        $data = 
            $props['data'];

        $authModel = 
            $props['authModel'];

        $authClass = 
            $props['authClass'];

        /* Asserzioni strutturali e logiche */
        $this->assertEquals(
            'auth',
            $data['controller']
        );

        $this->assertTrue(
            $data['centerContent']
        );

        $this->assertInstanceOf(
            \App\Models\Backend\AuthModel::class,
            $authModel
        );

        $this->assertInstanceOf(
            \App\Libraries\Backend\AuthClass::class,
            $authClass
        );
    }
}
