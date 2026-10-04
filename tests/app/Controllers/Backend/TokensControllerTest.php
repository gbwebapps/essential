<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

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

class TokensControllerTest extends CIUnitTestCase
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

        /* Inizializzazione controller (aggiustare il namespace se necessario) */
        $controller = 
        new \App\Controllers\Backend\TokensController();

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
                'tokensModel' => 
                $this
                ->tokensModel,
                'tokensClass' => 
                $this
                ->tokensClass
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
            'tokens', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertEquals(
            'tokens', 
            $properties
            ['data']['entity']
        );

        $this
        ->assertIsObject(
            $properties
            ['tokensModel']
        );

        $this
        ->assertIsObject(
            $properties
            ['tokensClass']
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
            \App\Controllers\Backend\TokensController::class
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

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
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
            \App\Controllers\Backend\TokensController::class
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

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $tokensModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;

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

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
        ->method(
            'showAllValidationRules'
        )
        ->willReturn(
            ['rule1' => 'required']
        );

        $tokensModel
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
            \App\Controllers\Backend\TokensController::class
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
            $tokensModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;

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

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
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
            \App\Controllers\Backend\TokensController::class
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
            $tokensModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;

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

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
        ->method(
            'getData'
        )
        ->willReturn([
            'result' => true
        ]);

        /* Rimosso il mock del renderer che veniva ignorato dalla funzione view() nativa */
        
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\TokensController::class
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

                /* Verifica semplicemente la presenza e il tipo invece del contenuto esatto */
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
            $tokensModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;

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
            \App\Controllers\Backend\TokensController::class
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
            ['dummy' => 'data']
        );

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
        ->method(
            'delValidationRules'
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
            \App\Controllers\Backend\TokensController::class
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
            $tokensModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;

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
            'id' => '1'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $tokensModel = 
        $this
        ->createMock(
            \App\Models\Backend\TokensModel::class
        );

        $tokensModel
        ->method(
            'delValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $modelResult = [
            'result' => true,
            'message' => 'Eliminato con successo'
        ];

        $tokensModel
        ->method(
            'hardDelete'
        )
        ->with(
            $postData
        )
        ->willReturn(
            $modelResult
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\TokensController::class
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
            $tokensModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->tokensModel = 
            $tokensModel;
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
