<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class BackendControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        \Config\Services::resetSingle('security');

        parent::tearDown();
    }

    public function testRenderWithoutHelper()
    {
        /* 1. Mock del Renderer per intercettare view(), setData() e render() */
        $rndCls = 
            \CodeIgniter\View\View::class;

        $rndBld =
            $this->getMockBuilder(
                $rndCls
            );

        $rndBld->disableOriginalConstructor();

        $mockRnd =
            $rndBld->getMock();

        $mockRnd->method(
            'render'
        )
        ->willReturn(
            'mocked_html_view'
        );

        $mockRnd->method(
            'setData'
        )
        ->willReturn(
            $mockRnd
        );

        $mockRnd->method(
            'setVar'
        )
        ->willReturn(
            $mockRnd
        );

        \Config\Services::injectMock(
            'renderer',
            $mockRnd
        );

        /* 2. Mock del BackendController per isolare getHelperClass */
        $ctrlCls = 
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['getHelperClass']
        );

        $controller =
            $builder->getMockForAbstractClass();

        $controller->expects(
            $this->once()
        )
        ->method(
            'getHelperClass'
        )
        ->willReturn(
            null
        );

        /* 3. Mock della libreria BackendClass per gli assets */
        $backendLibCls = 
            \App\Libraries\Backend\BackendClass::class;

        $backendBld =
            $this->getMockBuilder(
                $backendLibCls
            );

        $backendBld->disableOriginalConstructor();

        $backendCls =
            $backendBld->getMock();

        $backendCls->method(
            'getOrderedAssets'
        )
        ->willReturn(
            []
        );

        /* 4. Iniezione delle proprietà tramite Closure */
        $closure =
            function () use (
                $backendCls
            ) {
                $this->backendClass =
                    $backendCls;

                $this->data =
                    [
                        'action'     => 'index',
                        'controller' => 'TestController'
                    ];

                $this->customCss =
                    [];

                $this->customJs =
                    [];
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 5. Uso della Reflection per sbloccare il metodo protected render() */
        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'render'
            );

        $method->setAccessible(
            true
        );

        /* 6. Invocazione e Assert */
        $result =
            $method->invokeArgs(
                $controller,
                [
                    'my_test_view',
                    []
                ]
            );

        $this->assertEquals(
            'mocked_html_view',
            $result
        );
    }

    public function testRenderWithHelperAndAllMethods()
    {
        /* 1. Mock del Renderer per intercettare view(), setData() e render() */
        $rndCls = 
            \CodeIgniter\View\View::class;

        $rndBld =
            $this->getMockBuilder(
                $rndCls
            );

        $rndBld->disableOriginalConstructor();

        $mockRnd =
            $rndBld->getMock();

        $mockRnd->method(
            'render'
        )
        ->willReturn(
            'mocked_html_view'
        );

        $mockRnd->method(
            'setData'
        )
        ->willReturn(
            $mockRnd
        );

        $mockRnd->method(
            'setVar'
        )
        ->willReturn(
            $mockRnd
        );

        \Config\Services::injectMock(
            'renderer',
            $mockRnd
        );

        /* 2. Mock dell'Helper dummy con i metodi attesi */
        $hlpBld =
            $this->getMockBuilder(
                \stdClass::class
            );

        $hlpBld->addMethods(
            [
                'getJsEdit',
                'getCssEdit',
                'getLinksBarEdit',
                'getOptionsEdit'
            ]
        );

        $hlpMock =
            $hlpBld->getMock();

        /* Correggiamo i tipi di ritorno in array */
        $hlpMock->expects(
            $this->once()
        )
        ->method(
            'getJsEdit'
        )
        ->willReturn(
            ['script.js']
        );

        $hlpMock->expects(
            $this->once()
        )
        ->method(
            'getCssEdit'
        )
        ->willReturn(
            ['style.css']
        );

        $hlpMock->expects(
            $this->once()
        )
        ->method(
            'getLinksBarEdit'
        )
        ->with(
            '555-uuid'
        )
        ->willReturn(
            'html_links'
        );

        $hlpMock->expects(
            $this->once()
        )
        ->method(
            'getOptionsEdit'
        )
        ->with(
            '555-uuid'
        )
        ->willReturn(
            ['opt1' => 'val1']
        );

        /* 3. Mock del Controller */
        $ctrlCls =
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'getHelperClass',
                'addJs',
                'addCss'
            ]
        );

        $controller =
            $builder->getMockForAbstractClass();

        $controller->expects(
            $this->once()
        )
        ->method(
            'getHelperClass'
        )
        ->willReturn(
            $hlpMock
        );

        /* Aggiorniamo le aspettative per ricevere array */
        $controller->expects(
            $this->once()
        )
        ->method(
            'addJs'
        )
        ->with(
            ['script.js']
        );

        $controller->expects(
            $this->once()
        )
        ->method(
            'addCss'
        )
        ->with(
            ['style.css']
        );

        /* 4. Mock di BackendClass */
        $backendLibCls =
            \App\Libraries\Backend\BackendClass::class;

        $backendBld =
            $this->getMockBuilder(
                $backendLibCls
            );

        $backendBld->disableOriginalConstructor();

        $backendCls =
            $backendBld->getMock();

        $backendCls->method(
            'getOrderedAssets'
        )
        ->willReturn(
            ['mock_asset']
        );

        /* 5. Iniezione proprietà tramite Closure */
        $closure =
            function () use (
                $backendCls
            ) {
                $this->backendClass =
                    $backendCls;

                $this->data =
                    [
                        /* Azione "edit" per far chiamare getJsEdit ecc. */
                        'action'     => 'edit',
                        'controller' => 'TestController'
                    ];

                $this->customCss =
                    [];

                $this->customJs =
                    [];
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 6. Invocazione con Reflection */
        $inputData =
            [
                'uuid' => '555-uuid'
            ];

        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'render'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invokeArgs(
                $controller,
                [
                    'my_view',
                    $inputData
                ]
            );

        $this->assertEquals(
            'mocked_html_view',
            $result
        );

        /* 7. Verifica array interno (linksBar e options elaborati) */
        $dataClosure =
            function () {
                return $this->data;
            };

        $getData =
            \Closure::bind(
                $dataClosure,
                $controller,
                get_class(
                    $controller
                )
            );

        $finalData =
            $getData();

        $this->assertEquals(
            'html_links',
            $finalData['linksBar']
        );

        $this->assertEquals(
            'mocked_html_view',
            $finalData['options']
        );
    }

    public function testRenderWithHelperButMissingMethods()
    {
        /* 1. Mock del Renderer (identico per prevenire l'errore view mancante) */
        $rndCls = 
            \CodeIgniter\View\View::class;

        $rndBld =
            $this->getMockBuilder(
                $rndCls
            );

        $rndBld->disableOriginalConstructor();

        $mockRnd =
            $rndBld->getMock();

        $mockRnd->method(
            'render'
        )
        ->willReturn(
            'mocked_html_view'
        );

        $mockRnd->method(
            'setData'
        )
        ->willReturn(
            $mockRnd
        );

        $mockRnd->method(
            'setVar'
        )
        ->willReturn(
            $mockRnd
        );

        \Config\Services::injectMock(
            'renderer',
            $mockRnd
        );

        /* 2. Mock di un Helper VUOTO (nessun metodo aggiunto) */
        $hlpBld =
            $this->getMockBuilder(
                \stdClass::class
            );

        $emptyHlp =
            $hlpBld->getMock();

        /* 3. Mock del Controller */
        $ctrlCls =
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'getHelperClass',
                'addJs',
                'addCss'
            ]
        );

        $controller =
            $builder->getMockForAbstractClass();

        $controller->expects(
            $this->once()
        )
        ->method(
            'getHelperClass'
        )
        ->willReturn(
            $emptyHlp
        );

        /* Ci aspettiamo che addJs e addCss NON vengano MAI chiamati */
        $controller->expects(
            $this->never()
        )
        ->method(
            'addJs'
        );

        $controller->expects(
            $this->never()
        )
        ->method(
            'addCss'
        );

        /* 4. Mock di BackendClass */
        $backendLibCls =
            \App\Libraries\Backend\BackendClass::class;

        $backendBld =
            $this->getMockBuilder(
                $backendLibCls
            );

        $backendBld->disableOriginalConstructor();

        $backendCls =
            $backendBld->getMock();

        $backendCls->method(
            'getOrderedAssets'
        )
        ->willReturn(
            []
        );

        /* 5. Iniezione proprietà tramite Closure */
        $closure =
            function () use (
                $backendCls
            ) {
                $this->backendClass =
                    $backendCls;

                $this->data =
                    [
                        /* Azione generica */
                        'action' => 'list'
                    ];

                $this->customCss =
                    [];

                $this->customJs =
                    [];
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 6. Invocazione con Reflection */
        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'render'
            );

        $method->setAccessible(
            true
        );

        $result =
            $method->invokeArgs(
                $controller,
                [
                    'my_view',
                    []
                ]
            );

        $this->assertEquals(
            'mocked_html_view',
            $result
        );
    }

    public function testGetHelperClassReturnsNull()
    {
        /* 1. Mock del Controller base */
        $ctrlCls = 
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        /* 2. Mock e inizializzazione di backendClass per evitare il Fatal Error PHP */
        $backendLibCls =
            \App\Libraries\Backend\BackendClass::class;

        $backendBld =
            $this->getMockBuilder(
                $backendLibCls
            );

        $backendBld->disableOriginalConstructor();

        $backendCls =
            $backendBld->getMock();

        $closure =
            function () use (
                $backendCls
            ) {
                $this->backendClass =
                    $backendCls;
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 3. Sblocco tramite Reflection */
        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'getHelperClass'
            );

        $method->setAccessible(
            true
        );

        /* 4. Invocazione e Assert: Fallirà se il metodo reale non filtra backendClass */
        $result =
            $method->invoke(
                $controller
            );

        $this->assertNull(
            $result
        );
    }

    public function testGetHelperClassReturnsObject()
    {
        /* 1. Mock di backendClass da iniettare per evitare il Fatal Error */
        $backendLibCls =
            \App\Libraries\Backend\BackendClass::class;

        $backendBld =
            $this->getMockBuilder(
                $backendLibCls
            );

        $backendBld->disableOriginalConstructor();

        $backendCls =
            $backendBld->getMock();

        /* 2. Classe anonima con Helper specifico e proprietà tipizzate inizializzate */
        $dummyCls =
            new class (
                $backendCls
            ) extends \App\Controllers\Backend\BackendController {

                public $adminsClass;

                public function __construct(
                    $injectedBackend
                ) {
                    $this->backendClass =
                        $injectedBackend;

                    /* Inserisco il vero helper da trovare */
                    $this->adminsClass =
                        new \stdClass();
                }
            };

        /* 3. Sblocco del metodo protetto */
        $method =
            new \ReflectionMethod(
                get_class(
                    $dummyCls
                ),
                'getHelperClass'
            );

        $method->setAccessible(
            true
        );

        /* 4. Invocazione e Assert: Fallirà perché il metodo restituirà backendClass al posto di adminsClass */
        $result =
            $method->invoke(
                $dummyCls
            );

        $this->assertInstanceOf(
            \stdClass::class,
            $result
        );
    }

    public function testJsonResponseDefaultStatusCode()
    {
        /* 1. Mock del Servizio Security per congelare il CSRF */
        $secCls =
            \CodeIgniter\Security\Security::class;

        $secBld =
            $this->getMockBuilder(
                $secCls
            );

        $secBld->disableOriginalConstructor();

        $mockSec =
            $secBld->getMock();

        $mockSec->method(
            'getTokenName'
        )
        ->willReturn(
            'static_name'
        );

        $mockSec->method(
            'getHash'
        )
        ->willReturn(
            'static_hash'
        );

        \Config\Services::injectMock(
            'security',
            $mockSec
        );

        /* 2. Mock del Controller */
        $ctrlCls =
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        /* 3. Mock della Response */
        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $mockResp->expects(
            $this->once()
        )
        ->method(
            'setStatusCode'
        )
        ->with(
            200
        )
        ->willReturn(
            $mockResp
        );

        /* Array atteso con i valori CSRF congelati */
        $expectedPayload =
            [
                'status'   => 'ok',
                'csrfName' => 'static_name',
                'csrfHash' => 'static_hash'
            ];

        $mockResp->expects(
            $this->once()
        )
        ->method(
            'setJSON'
        )
        ->with(
            $expectedPayload
        )
        ->willReturn(
            $mockResp
        );

        /* 4. Iniezione della Response */
        $closure =
            function () use (
                $mockResp
            ) {
                $this->response =
                    $mockResp;
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 5. Sblocco e Invocazione */
        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'jsonResponse'
            );

        $method->setAccessible(
            true
        );

        $inputData =
            [
                'status' => 'ok'
            ];

        $result =
            $method->invokeArgs(
                $controller,
                [
                    $inputData
                ]
            );

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testJsonResponseCustomStatusCode()
    {
        /* 1. Mock del Servizio Security per congelare il CSRF */
        $secCls =
            \CodeIgniter\Security\Security::class;

        $secBld =
            $this->getMockBuilder(
                $secCls
            );

        $secBld->disableOriginalConstructor();

        $mockSec =
            $secBld->getMock();

        $mockSec->method(
            'getTokenName'
        )
        ->willReturn(
            'static_name'
        );

        $mockSec->method(
            'getHash'
        )
        ->willReturn(
            'static_hash'
        );

        \Config\Services::injectMock(
            'security',
            $mockSec
        );

        /* 2. Mock del Controller */
        $ctrlCls =
            \App\Controllers\Backend\BackendController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        /* 3. Mock della Response */
        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $mockResp->expects(
            $this->once()
        )
        ->method(
            'setStatusCode'
        )
        ->with(
            404
        )
        ->willReturn(
            $mockResp
        );

        $expectedPayload =
            [
                'error'    => 'Not Found',
                'csrfName' => 'static_name',
                'csrfHash' => 'static_hash'
            ];

        $mockResp->expects(
            $this->once()
        )
        ->method(
            'setJSON'
        )
        ->with(
            $expectedPayload
        )
        ->willReturn(
            $mockResp
        );

        /* 4. Iniezione della Response */
        $closure =
            function () use (
                $mockResp
            ) {
                $this->response =
                    $mockResp;
            };

        $bind =
            \Closure::bind(
                $closure,
                $controller,
                get_class(
                    $controller
                )
            );

        $bind();

        /* 5. Sblocco e Invocazione */
        $method =
            new \ReflectionMethod(
                get_class(
                    $controller
                ),
                'jsonResponse'
            );

        $method->setAccessible(
            true
        );

        $inputData =
            [
                'error' => 'Not Found'
            ];

        $statusCode =
            404;

        $result =
            $method->invokeArgs(
                $controller,
                [
                    $inputData,
                    $statusCode
                ]
            );

        $this->assertSame(
            $mockResp,
            $result
        );
    }
}
