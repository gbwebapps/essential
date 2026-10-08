<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

/* Aggiungere in cima al file o fuori dalla classe del test se non presente */
if (! function_exists('removeDotPermissions')):
    function removeDotPermissions(
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

class GroupsControllerTest extends CIUnitTestCase
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
        new \App\Controllers\Backend\GroupsController();

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
                'groupsModel' => 
                $this
                ->groupsModel,
                'groupsClass' => 
                $this
                ->groupsClass
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
            'groups', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertIsObject(
            $properties
            ['groupsModel']
        );

        $this
        ->assertIsObject(
            $properties
            ['groupsClass']
        );
    }

    public function testIndexReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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

    public function testOpenAddReturnsJsonResponseWithOutput(): void
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

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        ->openAdd();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetGroupsReturnsJsonResponseWithOutput(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroups'
        )
        ->willReturn(
            []
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getGroups();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetGroupReturnsValidationToastErrorsForInvalidPostData(): void
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
            ['id' => 'invalid']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupByIdValidationRules'
        )
        ->willReturn(
            ['id' => 'required']
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
            ['id' => 'error message']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getGroup();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetGroupReturnsFalseIfGroupNotFound(): void
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
            'id' => '999'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupByIdValidationRules'
        )
        ->willReturn(
            ['id' => 'required']
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->with(
            $postData
        )
        ->willReturn(
            null
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
                    'Gruppo non trovato.', 
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->getGroup();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetGroupReturnsJsonResponseWithOutputOnSuccess(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupByIdValidationRules'
        )
        ->willReturn(
            ['id' => 'required']
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->with(
            $postData
        )
        ->willReturn(
            (object) ['id' => '1', 'name' => 'Admin', 'description' => 'Administration group']
        );

        $groupsModel
        ->method(
            'getGroup'
        )
        ->with(
            1
        )
        ->willReturn(
            ['perm_1', 'perm_2']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getGroup();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testAddReturnsOutputOnResetAction(): void
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
            ['action' => 'reset']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        ->add();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testAddReturnsValidationErrorsWhenValidationFails(): void
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
            ['dummy' => 'data']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'addValidationRules'
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
            ['field' => 'error']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->add();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testAddReturnsModelResultOnSuccess(): void
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

        $postData = [
            'name' => 'Test'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'addValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $modelResult = [
            'result' => true, 
            'message' => 'success'
        ];

        $groupsModel
        ->method(
            'add'
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
            \App\Controllers\Backend\GroupsController::class
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
        ->with(
            $modelResult
        )
        ->willReturn(
            $expectedResponse
        );

        $closure = 
        function() use (
            $request,
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->add();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditRefreshReturnsFalseIfInvalidId(): void
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
            ['action' => 'refresh', 'id' => 'invalid']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditRefreshReturnsFalseIfGroupNotFound(): void
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

        $postData = [
            'action' => 'refresh', 
            'id' => '99'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->with(
            $postData
        )
        ->willReturn(
            null
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditReturnsValidationErrorsWhenValidationFails(): void
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

        $postData = [
            'id' => '1', 
            'name' => 'Test'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->willReturn(
            (object) ['id' => 1]
        );

        $groupsModel
        ->method(
            'editValidationRules'
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
            ['field' => 'error']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditReturnsModelResultOnSuccess(): void
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

        $postData = [
            'id' => '1', 
            'name' => 'Test'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->willReturn(
            (object) ['id' => 1]
        );

        $groupsModel
        ->method(
            'editValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $modelResult = [
            'result' => true
        ];

        /* id viene castato a int nel controller */
        $expectedData = 
        $postData;

        $expectedData
        ['id'] = 
        1;

        $groupsModel
        ->method(
            'edit'
        )
        ->with(
            $expectedData
        )
        ->willReturn(
            $modelResult
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        ->with(
            $modelResult
        )
        ->willReturn(
            $expectedResponse
        );

        $closure = 
        function() use (
            $request,
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDelReturnsFalseIfInvalidId(): void
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
            ['id' => 'invalid']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'delValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->del();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDelReturnsFalseIfAdminsAttached(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'delValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $groupsModel
        ->method(
            'hasAdminsAttached'
        )
        ->with(
            1
        )
        ->willReturn(
            true
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->del();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDelReturnsModelResultOnSuccess(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'delValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $groupsModel
        ->method(
            'hasAdminsAttached'
        )
        ->willReturn(
            false
        );

        $modelResult = [
            'result' => true
        ];

        $groupsModel
        ->method(
            'del'
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
            \App\Controllers\Backend\GroupsController::class
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
        ->with(
            $modelResult
        )
        ->willReturn(
            $expectedResponse
        );

        $closure = 
        function() use (
            $request,
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->del();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteConstraintsReturnsFalseIfInvalidId(): void
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
            ['id' => 'invalid']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteConstraints();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteConstraintsReturnsFalseIfAdminsAttached(): void
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
            ['id' => '1']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'hasAdminsAttached'
        )
        ->with(
            1
        )
        ->willReturn(
            true
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteConstraints();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testCheckDeleteConstraintsReturnsTrueIfNoConstraints(): void
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
            ['id' => '1']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'hasAdminsAttached'
        )
        ->with(
            1
        )
        ->willReturn(
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->checkDeleteConstraints();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testOpenExceptionsReturnsJsonResponseWithOutput(): void
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

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        ->openExceptions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetDropdownAdminsReturnsValidationToastErrorsForInvalidPostData(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'dropdownAdminsRules'
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
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getDropdownAdmins();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetDropdownAdminsReturnsJsonResponseWithOutputOnSuccess(): void
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
            'search' => 'admin'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'dropdownAdminsRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $groupsModel
        ->method(
            'getDropdownAdmins'
        )
        ->with(
            $postData
        )
        ->willReturn(
            [
                [
                    'uuid' => '11111111-1111-4111-8111-111111111111',
                    'identity' => 'Mario Rossi',
                ],
                [
                    'uuid' => '22222222-2222-4222-8222-222222222222',
                    'identity' => 'Luigi Bianchi',
                ],
            ]
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getDropdownAdmins();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveExceptionsReturnsFalseIfInvalidUuid(): void
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

        $regexpMock = 
        $this
        ->createMock(
            \App\Libraries\RegExp::class
        );

        $regexpMock
        ->method(
            'validateUUID'
        )
        ->willReturn(
            false
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $regexpMock
        ) {
            $this
            ->request = 
            $request;

            $this
            ->regexp = 
            $regexpMock;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveExceptions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveExceptionsReturnsValidationErrorsWhenValidationFails(): void
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
            'uuid' => 'valid-uuid'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $regexpMock = 
        $this
        ->createMock(
            \App\Libraries\RegExp::class
        );

        $regexpMock
        ->method(
            'validateUUID'
        )
        ->willReturn(
            true
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'saveExceptionsValidationRules'
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
            ['permissions.0' => 'error message']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $regexpMock,
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->regexp = 
            $regexpMock;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->saveExceptions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveExceptionsReturnsModelResultOnSuccess(): void
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
            'uuid' => 'valid-uuid'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $regexpMock = 
        $this
        ->createMock(
            \App\Libraries\RegExp::class
        );

        $regexpMock
        ->method(
            'validateUUID'
        )
        ->willReturn(
            true
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'saveExceptionsValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $modelResult = [
            'result' => true,
            'message' => 'success message'
        ];

        $groupsModel
        ->method(
            'saveExceptions'
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
            \App\Controllers\Backend\GroupsController::class
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
                    'success message', 
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
            $regexpMock,
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->regexp = 
            $regexpMock;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->saveExceptions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetAdminPermissionsReturnsValidationToastErrorsForInvalidPostData(): void
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

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'adminPermissionsValidationRules'
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
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getAdminPermissions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetAdminPermissionsReturnsFalseIfAdminNotFound(): void
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
            'uuid' => 'invalid-uuid'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'adminPermissionsValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $groupsModel
        ->method(
            'getAdminByUuid'
        )
        ->with(
            $postData
        )
        ->willReturn(
            null
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
                    'Amministratore non trovato.', 
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->getAdminPermissions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGetAdminPermissionsReturnsJsonResponseWithOutputOnSuccess(): void
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
            'uuid' => 'valid-uuid'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'adminPermissionsValidationRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $groupsModel
        ->method(
            'getAdminByUuid'
        )
        ->with(
            $postData
        )
        ->willReturn(
            ['name' => 'Admin Test', 'group_id' => '1']
        );

        $groupsModel
        ->method(
            'getGroupPermissionsArray'
        )
        ->with(
            1
        )
        ->willReturn(
            ['perm_1']
        );

        $groupsModel
        ->method(
            'getAdminExceptionsArray'
        )
        ->with(
            'valid-uuid'
        )
        ->willReturn(
            ['perm_2']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->getAdminPermissions();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditRefreshReturnsJsonResponseWithOutputOnSuccess(): void
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

        $postData = [
            'action' => 'refresh',
            'id' => '1'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->with(
            $postData
        )
        ->willReturn(
            (object) ['id' => 1, 'name' => 'Admin', 'description' => 'Administration group']
        );

        $groupsModel
        ->method(
            'getGroup'
        )
        ->with(
            1
        )
        ->willReturn(
            ['perm_1']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditReturnsFalseIfInvalidId(): void
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
            ['id' => 'invalid']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testEditReturnsFalseIfGroupNotFound(): void
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

        $postData = [
            'id' => '999'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $expectedData = 
        $postData;

        $expectedData
        ['id'] = 
        999;

        $groupsModel
        ->method(
            'getGroupById'
        )
        ->with(
            $expectedData
        )
        ->willReturn(
            null
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->edit();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDelReturnsValidationToastErrorsWhenValidationFails(): void
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

        /* Passiamo un ID valido per superare lo sbarramento iniziale alla riga 278 */
        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['id' => '1']
        );

        $groupsModel = 
        $this
        ->createMock(
            \App\Models\Backend\GroupsModel::class
        );

        $groupsModel
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
            ['field' => 'error message test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\GroupsController::class
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
            $groupsModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->groupsModel = 
            $groupsModel;

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
        ->del();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }
}