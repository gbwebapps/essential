<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class MessagesControllerTest extends CIUnitTestCase
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
        new \App\Controllers\Backend\MessagesController();

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
                'messagesModel' => 
                $this
                ->messagesModel,
                'messagesClass' => 
                $this
                ->messagesClass
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
            'messages', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertInstanceOf(
            \App\Models\Backend\MessagesModel::class,
            $properties
            ['messagesModel']
        );

        $this
        ->assertInstanceOf(
            \App\Libraries\Backend\MessagesClass::class,
            $properties
            ['messagesClass']
        );
    }

    public function testIndexReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\MessagesController::class
        )
        ->onlyMethods([
            'render'
        ])
        ->getMock();

        $controller
        ->expects(
            $this
            ->once()
        )
        ->method(
            'render'
        )
        ->with(
            'backend/messages/indexView',
            $this
            ->isType('array')
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

    public function testShowAllReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\MessagesController::class
        )
        ->onlyMethods([
            'render'
        ])
        ->getMock();

        $controller
        ->expects(
            $this
            ->once()
        )
        ->method(
            'render'
        )
        ->with(
            'backend/messages/indexView',
            $this
            ->isType('array')
        )
        ->willReturn(
            'rendered_showall_view'
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
        ->showAll();

        /* Asserzione risultato */
        $this
        ->assertEquals(
            'rendered_showall_view', 
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
            'showAll', 
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

    public function testShowReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\MessagesController::class
        )
        ->onlyMethods([
            'render'
        ])
        ->getMock();

        $controller
        ->expects(
            $this
            ->once()
        )
        ->method(
            'render'
        )
        ->with(
            'backend/messages/indexView',
            $this
            ->isType('array')
        )
        ->willReturn(
            'rendered_show_view'
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
        ->show();

        /* Asserzione risultato */
        $this
        ->assertEquals(
            'rendered_show_view', 
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
            'show', 
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
}
