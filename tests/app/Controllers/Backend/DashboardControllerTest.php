<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class DashboardControllerTest extends CIUnitTestCase
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
        new \App\Controllers\Backend\DashboardController();

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
                'dashboardModel' => 
                $this
                ->dashboardModel,
                'dashboardClass' => 
                $this
                ->dashboardClass
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
            'dashboard', 
            $properties
            ['data']['controller']
        );

        $this
        ->assertInstanceOf(
            \App\Models\Backend\DashboardModel::class,
            $properties
            ['dashboardModel']
        );

        $this
        ->assertInstanceOf(
            \App\Libraries\Backend\DashboardClass::class,
            $properties
            ['dashboardClass']
        );
    }

    public function testIndexReturnsRenderedView(): void
    {
        /* Mock parziale del controller per intercettare render */
        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\DashboardController::class
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
            'backend/dashboard/indexView',
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
}
