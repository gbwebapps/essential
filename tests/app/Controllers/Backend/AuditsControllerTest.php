<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AuditsControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testIndexRendersViewOnNonAjaxRequest(): void
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
            $this->getMockBuilder(\App\Controllers\Backend\AuditsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['render']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('render')->willReturn('mock_view_audits_index');

        /* 3. INIEZIONE */
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
                \App\Controllers\Backend\AuditsController::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $expected = 
            'mock_view_audits_index';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testIndexReturnsFalseOnGenericValidationFail(): void
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
            [];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['showAllValidationRules']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            ['rule' => 'required'];
            
        $mockModel->method('showAllValidationRules')->willReturn(
            $fakeRules
        );

        /* 3. MOCK VALIDATOR E RESPONSE */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['error_key' => 'Error Message'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        /* 4. MOCK CONTROLLER */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuditsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(false);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->auditsModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuditsController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testIndexReturnsErrorsOnSearchValidationFail(): void
    {
        /* 0. DEFINIZIONE HELPER */
        if ( ! function_exists('removeDot')):
            function removeDot(
                $prefix, 
                $errors
            ) {
                return $errors;
            }
        endif;

        /* 1. MOCK REQUEST */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('isAJAX')->willReturn(true);
        
        $mockRequest->method('is')->willReturn(true);
        
        $postData = 
            [];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['showAllValidationRules', 'showAllSearchValidationRules']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('showAllValidationRules')->willReturn(
            $fakeRules
        );
        
        $fakeSearchRules = 
            ['search' => 'required'];
            
        $mockModel->method('showAllSearchValidationRules')->willReturn(
            $fakeSearchRules
        );

        /* 3. MOCK VALIDATOR E RESPONSE */
        $builderVal = 
            $this->getMockBuilder(\CodeIgniter\Validation\Validation::class);
            
        $builderVal->disableOriginalConstructor();
        
        $mockValidator = 
            $builderVal->getMock();
            
        $fakeErrors = 
            ['searchFields.error' => 'Search Error'];
            
        $mockValidator->method('getErrors')->willReturn(
            $fakeErrors
        );

        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        /* 4. MOCK CONTROLLER (Il validateData ritorna true al primo giro, false al secondo) */
        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuditsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturnOnConsecutiveCalls(
            true, 
            false
        );
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel,
                $mockValidator
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->auditsModel = 
                    $mockModel;
                    
                $this->validator = 
                    $mockValidator;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuditsController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testIndexReturnsFalseWhenModelDataFails(): void
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
            [];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 2. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['showAllValidationRules', 'showAllSearchValidationRules', 'getData']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('showAllValidationRules')->willReturn(
            $fakeRules
        );
        
        $mockModel->method('showAllSearchValidationRules')->willReturn(
            $fakeRules
        );
        
        $fakeData = 
            ['result' => false, 'message' => 'No logs found'];
            
        $mockModel->method('getData')->willReturn(
            $fakeData
        );

        /* 3. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuditsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 4. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->auditsModel = 
                    $mockModel;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuditsController::class
            );
            
        $boundInjector();

        /* 5. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );
    }

    public function testIndexReturnsTrueWithOutputOnSuccess(): void
    {
        /* 1. MOCK RENDERER (Intercetta la chiamata al partial view()) */
        $builderView = 
            $this->getMockBuilder(\CodeIgniter\View\View::class);
            
        $builderView->disableOriginalConstructor();
        
        $mockView = 
            $builderView->getMock();
            
        $mockView->method('setData')->willReturnSelf();
        
        $mockView->method('render')->willReturn('mock_html_partial');
        
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
            [];
            
        $mockRequest->method('getPost')->willReturn(
            $postData
        );

        /* 3. MOCK MODEL */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods(['showAllValidationRules', 'showAllSearchValidationRules', 'getData']);
        
        $mockModel = 
            $builderModel->getMock();
            
        $fakeRules = 
            [];
            
        $mockModel->method('showAllValidationRules')->willReturn(
            $fakeRules
        );
        
        $mockModel->method('showAllSearchValidationRules')->willReturn(
            $fakeRules
        );
        
        $fakeData = 
            ['result' => true];
            
        $mockModel->method('getData')->willReturn(
            $fakeData
        );

        /* 4. MOCK RESPONSE E CONTROLLER */
        $builderResp = 
            $this->getMockBuilder(\CodeIgniter\HTTP\ResponseInterface::class);
            
        $mockResponse = 
            $builderResp->getMock();

        $builderCtrl = 
            $this->getMockBuilder(\App\Controllers\Backend\AuditsController::class);
            
        $builderCtrl->disableOriginalConstructor();
        
        $builderCtrl->onlyMethods(['validateData', 'jsonResponse']);
        
        $controller = 
            $builderCtrl->getMock();
            
        $controller->method('validateData')->willReturn(true);
        
        $controller->method('jsonResponse')->willReturn(
            $mockResponse
        );

        /* 5. INIEZIONE */
        $injector = 
            function() use (
                $mockRequest,
                $mockModel
            ) {
                $this->request = 
                    $mockRequest;
                    
                $this->auditsModel = 
                    $mockModel;
                    
                $this->data = 
                    [];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $controller, 
                \App\Controllers\Backend\AuditsController::class
            );
            
        $boundInjector();

        /* 6. ESECUZIONE E ASSERZIONE */
        $result = 
            $controller->index();
            
        $this->assertSame(
            $mockResponse, 
            $result
        );

        /* 7. PULIZIA */
        \CodeIgniter\Config\Services::reset();
    }

    public function testInitControllerSetsCorrectDataAndProperties(): void
    {
        $request = 
            $this->createMock(\CodeIgniter\HTTP\RequestInterface::class);

        $response = 
            $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);

        $logger = 
            $this->createMock(\Psr\Log\LoggerInterface::class);

        $mockModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\AuditsModel::class,
            $mockModel
        );

        $controller = 
            new \App\Controllers\Backend\AuditsController();

        $controller->initController(
            $request,
            $response,
            $logger
        );

        $extractor = 
            function () {
                return [
                    'data' => 
                        $this->data,
                    'auditsModel' => 
                        $this->auditsModel,
                    'auditsClass' => 
                        $this->auditsClass
                ];
            };

        $bind = 
            \Closure::bind(
                $extractor,
                $controller,
                \App\Controllers\Backend\AuditsController::class
            );

        $props = 
            $bind();

        $data = 
            $props['data'];

        $audModel = 
            $props['auditsModel'];

        $audClass = 
            $props['auditsClass'];

        $this->assertEquals(
            'audits',
            $data['controller']
        );

        $this->assertEquals(
            'audits',
            $data['entity']
        );

        $this->assertInstanceOf(
            \App\Models\Backend\AuditsModel::class,
            $audModel
        );

        $this->assertInstanceOf(
            \App\Libraries\Backend\AuditsClass::class,
            $audClass
        );
    }
} 