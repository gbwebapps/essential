<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

/* Aggiungere in cima al file o fuori dalla classe del test se non presente */
if (! function_exists('removeDot')):
    function removeDot(
        $prefix,
        $array
    ) {
        return 
        $array;
    }
endif;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class LogsControllerTest extends CIUnitTestCase
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
        new \App\Controllers\Backend\LogsController();

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
                'logsModel' => 
                $this
                ->logsModel,
                'logsClass' => 
                $this
                ->logsClass
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
            'logs', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertEquals(
            'logs', 
            $properties
            ['data']['entity']
        );

        $this
        ->assertIsObject(
            $properties
            ['logsModel']
        );

        $this
        ->assertIsObject(
            $properties
            ['logsClass']
        );
    }

    public function testIndexReturnsRenderedViewForNonAjaxRequest(): void
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
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
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
            'rendered_view'
        );

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;

            $this
            ->data = 
            [];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->index();

        $this
        ->assertEquals(
            'rendered_view', 
            $result
        );

        /* Verifica popolamento data per GET request */
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

    public function testIndexReturnsValidationToastErrorsForInvalidPostData(): void
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
            ['dummy' => 'data']
        );

        $logsModel = 
        $this
        ->createMock(
            \App\Models\Backend\LogsModel::class
        );

        $logsModel
        ->method(
            'showAllValidationRules'
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
            \App\Controllers\Backend\LogsController::class
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
                ->assertFalse(
                    $data
                    ['result']
                );

                $this
                ->assertStringContainsString(
                    'error message', 
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
            $logsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->logsModel = 
            $logsModel;

            $this
            ->validator = 
            $validator;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->index();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testIndexReturnsValidationErrorsForInvalidSearchData(): void
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
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $logsModel = 
        $this
        ->createMock(
            \App\Models\Backend\LogsModel::class
        );

        $logsModel
        ->method(
            'showAllValidationRules'
        )
        ->willReturn(
            ['rule1' => 'required']
        );

        $logsModel
        ->method(
            'showAllSearchValidationRules'
        )
        ->willReturn(
            ['rule2' => 'required']
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
            ['searchDates.start' => 'error']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
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
        ->willReturnOnConsecutiveCalls(
            true,
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
            $logsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->logsModel = 
            $logsModel;

            $this
            ->validator = 
            $validator;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->index();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testIndexReturnsFalseJsonWhenGetDataFails(): void
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
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $logsModel = 
        $this
        ->createMock(
            \App\Models\Backend\LogsModel::class
        );

        $logsModel
        ->method(
            'getData'
        )
        ->willReturn([
            'result' => false,
            'message' => 'error message test'
        ]);

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
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
                    'error message test', 
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
            $logsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->logsModel = 
            $logsModel;

            $this
            ->data = 
            [];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->index();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testIndexReturnsTrueJsonWithOutputWhenGetDataSucceeds(): void
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
        ->willReturn(
            true
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['dummy' => 'post']
        );

        $logsModel = 
        $this
        ->createMock(
            \App\Models\Backend\LogsModel::class
        );

        $logsModel
        ->method(
            'getData'
        )
        ->willReturn([
            'result' => true
        ]);

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
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
            $logsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->logsModel = 
            $logsModel;

            $this
            ->data = 
            [];
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->index();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testHardDeleteReturnsNullWhenNotAjaxOrPost(): void
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
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
        )
        ->disableOriginalConstructor()
        ->onlyMethods([
            'jsonResponse'
        ])
        ->getMock();

        $closure = 
        function() use (
            $request
        ) {
            $this
            ->request = 
            $request;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result =
        $controller
        ->hardDelete();

        $this
        ->assertNull(
            $result
        );
    }

    public function testHardDeleteReturnsValidationToastErrorsForInvalidPostData(): void
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
            ['tokenId' => 'invalid']
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
            ['tokenId' => 'error message test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
        )
        ->disableOriginalConstructor()
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
                ->assertFalse(
                    $data
                    ['result']
                );

                $this
                ->assertStringContainsString(
                    'error message test', 
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
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->validator = 
            $validator;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->hardDelete();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testHardDeleteReturnsModelResultOnSuccess(): void
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
            'tokenId' => '1'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $logsModel = 
        $this
        ->createMock(
            \App\Models\Backend\LogsModel::class
        );

        $modelResult = [
            'result' => true,
            'message' => 'Eliminato con successo'
        ];

        $logsModel
        ->method(
            'deleteToken'
        )
        ->with(
            1
        )
        ->willReturn(
            $modelResult
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\LogsController::class
        )
        ->disableOriginalConstructor()
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
        ->with(
            $modelResult
        )
        ->willReturn(
            $expectedResponse
        );

        $closure = 
        function() use (
            $request,
            $logsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->logsModel = 
            $logsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->hardDelete();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }
}
