<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class SettingsControllerTest extends CIUnitTestCase
{
	public function testInitControllerSetsCorrectProperties(): void
    {
        /* Mock delle dipendenze richieste dal metodo */
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\RequestInterface::class
        );

        $response = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $logger = 
        $this
        ->createMock(
            \Psr\Log\LoggerInterface::class
        );

        /* Inizializzazione controller */
        $controller = 
        new \App\Controllers\Backend\SettingsController();

        /* Esecuzione */
        $controller
        ->initController(
            $request, 
            $response, 
            $logger
        );

        /* Estrazione delle proprietà tramite Closure */
        $closure = 
        function() {
            return [
                'data' => 
                $this
                ->data,
                'settingsModel' => 
                $this
                ->settingsModel,
                'settingsClass' => 
                $this
                ->settingsClass
            ];
        };

        $properties = 
        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        /* Asserzioni */
        $this
        ->assertEquals(
            'settings', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertIsObject(
            $properties
            ['settingsModel']
        );

        $this
        ->assertIsObject(
            $properties
            ['settingsClass']
        );
    }

    public function testIndexReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'render'
        ])
        ->getMock();

        $controller
        ->method(
            'render'
        )
        ->willReturn(
            'rendered_index_view'
        );

        /* Inizializzazione proprietà base */
        $closure = 
        function() {
            $this
            ->data = 
            [];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        /* Esecuzione */
        $result = 
        $controller
        ->index();

        /* Asserzione risultato */
        $this
        ->assertEquals(
            'rendered_index_view', 
            $result
        );

        /* Verifica popolamento data */
        $closureAssert = 
        function() {
            return 
            $this
            ->data;
        };
        
        $data = 
        \Closure::bind(
            $closureAssert, 
            $controller, 
            $controller
        )();

        $this
        ->assertEquals(
            'index', 
            $data
            ['action']
        );

        $this
        ->assertArrayHasKey(
            'title', 
            $data
        );

        $this
        ->assertArrayHasKey(
            'icon', 
            $data
        );
    }

    public function testOpenSettingsReturnsValidationErrorsForInvalidEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            'invalid_env'
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->openSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testOpenSettingsReturnsJsonResponseWithOutputOnSuccess(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            'general'
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'hasDatabaseSettings'
        )
        ->willReturn(
            true
        );

        $settingsModel
        ->method(
            'getSettings'
        )
        ->willReturn(
            ['dummy' => 'setting']
        );

        $settingsClass = 
        $this
        ->createMock(
            \App\Libraries\Backend\SettingsClass::class
        );

        $settingsClass
        ->method(
            'getTimezones'
        )
        ->willReturn(
            []
        );

        $settingsClass
        ->method(
            'getLanguages'
        )
        ->willReturn(
            []
        );

        $settingsClass
        ->method(
            'getDateFormats'
        )
        ->willReturn(
            []
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                $this
                ->assertArrayHasKey(
                    'output', 
                    $data
                );

                $this
                ->assertIsString(
                    $data
                    ['output']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel,
            $settingsClass
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->settingsClass = 
            $settingsClass;

            $this
            ->data = 
            [];

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->openSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveSettingsReturnsValidationErrorsForInvalidEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['env' => 'invalid_env']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveSettingsReturnsValidationErrorsWhenValidationFails(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $postData = [
            'env' => 'general'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'generalSettingsValidateRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $validator = 
        $this
        ->createMock(
            \CodeIgniter\Validation\ValidationInterface::class
        );

        $validator
        ->method(
            'getErrors'
        )
        ->willReturn(
            ['field' => 'error message']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'validateData',
            'jsonResponse'
        ])
        ->getMock();

        $controller
        ->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertArrayHasKey(
                    'errors', 
                    $data
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->validator = 
            $validator;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveSettingsReturnsFalseWhenSaveResultIsFalse(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $postData = [
            'env' => 'general'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'generalSettingsValidateRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $settingsModel
        ->method(
            'saveSettings'
        )
        ->willReturn(
            ['result' => false, 'message' => 'save error test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'validateData',
            'jsonResponse'
        ])
        ->getMock();

        $controller
        ->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                $this
                ->assertEquals(
                    'save error test', 
                    $data
                    ['message']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveSettingsReturnsJsonResponseWithOutputOnSuccess(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $postData = [
            'env' => 'general'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'generalSettingsValidateRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $settingsModel
        ->method(
            'saveSettings'
        )
        ->willReturn(
            ['result' => true, 'message' => 'success message test']
        );

        $settingsModel
        ->method(
            'hasDatabaseSettings'
        )
        ->willReturn(
            true
        );

        $settingsModel
        ->method(
            'getSettings'
        )
        ->willReturn(
            ['dummy' => 'setting']
        );

        $settingsClass = 
        $this
        ->createMock(
            \App\Libraries\Backend\SettingsClass::class
        );

        $settingsClass
        ->method(
            'getTimezones'
        )
        ->willReturn(
            []
        );

        $settingsClass
        ->method(
            'getLanguages'
        )
        ->willReturn(
            []
        );

        $settingsClass
        ->method(
            'getDateFormats'
        )
        ->willReturn(
            []
        );

        /* Mock globale view renderer per intercettare la funzione view() */
        $mockView = 
        $this
        ->createMock(
            \CodeIgniter\View\View::class
        );

        $mockView
        ->method(
            'render'
        )
        ->willReturn(
            'html_output'
        );

        \Config\Services::injectMock(
            'renderer', 
            $mockView
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'validateData',
            'jsonResponse'
        ])
        ->getMock();

        $controller
        ->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                $this
                ->assertEquals(
                    'success message test', 
                    $data
                    ['message']
                );

                $this
                ->assertArrayHasKey(
                    'fragments', 
                    $data
                );

                $this
                ->assertArrayHasKey(
                    'output', 
                    $data
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel,
            $settingsClass
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->settingsClass = 
            $settingsClass;

            $this
            ->data = 
            [];

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDeleteSettingsReturnsValidationErrorsForInvalidEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['env' => 'invalid_env']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->deleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDeleteSettingsReturnsFalseIfAlreadyDefault(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['env' => 'general']
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'deleteSettings'
        )
        ->willReturn(
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->deleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDeleteSettingsReturnsTrueOnSuccess(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['env' => 'general']
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'deleteSettings'
        )
        ->willReturn(
            true
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->deleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteSettingsReturnsValidationErrorsForInvalidEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            'invalid_env'
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteSettingsReturnsFalseIfAlreadyDefault(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            'general'
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'hasDatabaseSettings'
        )
        ->willReturn(
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteSettingsReturnsTrueOnSuccess(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            'general'
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'hasDatabaseSettings'
        )
        ->willReturn(
            true
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetSettingsReturnsValidationErrorsForInvalidEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['env' => 'invalid_env']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertFalse(
                    $data
                    ['result']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->getSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetSettingsReturnsJsonResponseWithDataOnSuccessGeneralEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $postData = [
            'env' => 'general',
            'keys' => ['siteName']
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'getSettings'
        )
        ->with(
            'Backend\General',
            ['siteName']
        )
        ->willReturn(
            ['siteName' => 'My Site']
        );

        $settingsClass = 
        $this
        ->createMock(
            \App\Libraries\Backend\SettingsClass::class
        );

        $settingsClass
        ->method(
            'getTimezones'
        )
        ->willReturn(
            ['UTC']
        );

        $settingsClass
        ->method(
            'getLanguages'
        )
        ->willReturn(
            ['it']
        );

        $settingsClass
        ->method(
            'getDateFormats'
        )
        ->willReturn(
            ['Y-m-d']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                $this
                ->assertEquals(
                    ['siteName' => 'My Site'], 
                    $data
                    ['data']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel,
            $settingsClass
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->settingsClass = 
            $settingsClass;

            $this
            ->data = 
            [];

            $this
            ->allowedEnvs = [
                'general'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->getSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );

        /* Asserzioni extra sui dati impostati nel controller per 'general' */
        $closureAssert = 
        function() {
            return 
            $this
            ->data;
        };

        $controllerData = 
        \Closure::bind(
            $closureAssert, 
            $controller, 
            $controller
        )();

        $this
        ->assertArrayHasKey(
            'timezones', 
            $controllerData
        );

        $this
        ->assertArrayHasKey(
            'languages', 
            $controllerData
        );

        $this
        ->assertArrayHasKey(
            'dateFormats', 
            $controllerData
        );
    }

    public function testGetSettingsReturnsJsonResponseWithDataOnSuccessOtherEnv(): void
    {
        $request = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\IncomingRequest::class
        );

        $request
        ->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $request
        ->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $postData = [
            'env' => 'security'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $settingsModel = 
        $this
        ->createMock(
            \App\Models\Backend\SettingsModel::class
        );

        $settingsModel
        ->method(
            'getSettings'
        )
        ->with(
            'Backend\Security',
            null
        )
        ->willReturn(
            ['force_https' => true]
        );
        
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\SettingsController::class
        )
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $controller
        ->method(
            'jsonResponse'
        )
        ->willReturnCallback(
            function(
                $data
            ) use (
                $expectedResponse
            ) {
                $this
                ->assertTrue(
                    $data
                    ['result']
                );

                $this
                ->assertArrayHasKey(
                    'force_https', 
                    $data
                    ['data']
                );

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $settingsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->settingsModel = 
            $settingsModel;

            $this
            ->data = 
            [];

            $this
            ->allowedEnvs = [
                'security'
            ];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->getSettings();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }
}