<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class ToolsControllerTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');

        parent::tearDown();
    }

    public function testOpenToolsInvalidEnv()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['jsonResponse']
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method('isAJAX')->willReturn(true);

        $mockReq->method('is')->with('post')->willReturn(true);

        $mockReq->method('getPost')->with('env')->willReturn('hacker_env');

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $allowed =
            [
                'system',
                'backups'
            ];

        $closure =
            function () use (
                $mockReq,
                $allowed
            ) {
                $this->request =
                    $mockReq;

                $this->allowedEnvs =
                    $allowed;
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

        $result =
            $controller->openTools();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testOpenToolsDbMaintenance()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['jsonResponse']
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->with(
            'env'
        )
        ->willReturn(
            'dbMaintenance'
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'getDatabase',
                'getTablesStatus'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'getDatabase'
        )
        ->willReturn(
            ['db_data']
        );

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'getTablesStatus'
        )
        ->willReturn(
            ['tables_data']
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedJson =
            [
                'result' => true,
                'output' => 'mocked_html_view'
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $allowed =
            [
                'dbMaintenance'
            ];

        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $allowed
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->allowedEnvs =
                    $allowed;

                $this->data =
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

        $result =
            $controller->openTools();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public static function provideValidEnvs(): array
    {
        return [
            [
                'manageAudits',
                'getAuditsStats'
            ],
            [
                'manageLogs',
                'getLogsStats'
            ],
            [
                'backups',
                'getBackups'
            ],
            [
                'cleanSpace',
                'getWritableFoldersStatus'
            ],
            [
                'system',
                'getSystemInfo'
            ]
        ];
    }

    public static function validEnvsProvider(): array
    {
        return [
            ['manageAudits', 'getAuditsStats'],
            ['manageLogs', 'getLogsStats'],
            ['backups', 'getBackups'],
            ['cleanSpace', 'getWritableFoldersStatus'],
            ['system', 'getSystemInfo']
        ];
    }

    /**
     * @dataProvider validEnvsProvider
     */
    public function testOpenToolsValidEnvs(
        string $testEnv,
        string $testMethod
    ) {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['jsonResponse']
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->with(
            'post'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->with(
            'env'
        )
        ->willReturn(
            $testEnv
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                $testMethod
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->expects(
            $this->once()
        )
        ->method(
            $testMethod
        )
        ->willReturn(
            ['dummy_data']
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedJson =
            [
                'result' => true,
                'output' => 'mocked_html_view'
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $allowed =
            [
                'manageAudits',
                'manageLogs',
                'backups',
                'cleanSpace',
                'system'
            ];

        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $allowed
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->allowedEnvs =
                    $allowed;

                $this->data =
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

        $result =
            $controller->openTools();

        $this->assertSame(
            $mockResp,
            $result
        );

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

        $values =
            array_values(
                $finalData
            );

        $this->assertEquals(
            ['dummy_data'],
            $values[0]
        );
    }

    public function testOpenToolsNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        /* Simuliamo una richiesta NON Ajax */
        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        /* Invocazione */
        $result =
            $controller->openTools();

        /* Ora ci aspettiamo tranquillamente un null */
        $this->assertNull(
            $result
        );
    }

    public function testValidateAuditsDateRequestNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->validateAuditsDateRequest();

        $this->assertNull(
            $result
        );
    }

    public function testValidateAuditsDateRequestValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $postsData =
            ['start' => 'invalid'];

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            $postsData
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['validateManageAuditsRules']
        );

        $mockModel =
            $modelBld->getMock();

        $rulesData =
            ['start' => 'required'];

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            $rulesData
        );

        /* Simuliamo il fallimento della validazione dei dati */
        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        /* Mock del Validator per getErrors() */
        $valCls =
            \CodeIgniter\Validation\Validation::class;

        $valBld =
            $this->getMockBuilder(
                $valCls
            );

        $valBld->disableOriginalConstructor();

        $mockVal =
            $valBld->getMock();

        $validationErrors =
            ['start' => 'Error message'];

        $mockVal->method(
            'getErrors'
        )
        ->willReturn(
            $validationErrors
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'errors'  => $validationErrors,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        /* Iniettiamo Model, Request e Validator nel controller */
        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $mockVal
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->validator =
                    $mockVal;
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

        $result =
            $controller->validateAuditsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateAuditsDateRequestCountReturnsFalse()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageAuditsRules',
                'countAuditsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            []
        );

        /* Validazione passa */
        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        /* La query preventiva ritorna false */
        $mockModel->expects(
            $this->once()
        )
        ->method(
            'countAuditsToDelete'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.startDateAfterEndDate');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateAuditsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateAuditsDateRequestSuccessWithCount()
    {
        /* Carichiamo il vero helper di CodeIgniter che contiene convertDate */
        helper('date');

        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageAuditsRules',
                'countAuditsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $auditPayload =
            [
                'from'  => '2026-01-01',
                'to'    => '2026-01-31',
                'count' => 5
            ];

        $mockModel->method(
            'countAuditsToDelete'
        )
        ->willReturn(
            $auditPayload
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        /* Ora userà la tua VERA funzione convertDate */
        $fromStr =
            convertDate('2026-01-01', 'conversational');

        $toStr =
            convertDate('2026-01-31', 'conversational');

        $countVal =
            5;

        $langStr =
            lang('backend/tools.messages.areYouSureToDeleteAudits');

        $confirmStr =
            sprintf(
                $langStr,
                $fromStr,
                $toStr,
                $countVal
            );

        $expectedJson =
            [
                'result'         => true,
                'count'          => $countVal,
                'confirmMessage' => $confirmStr
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateAuditsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateAuditsDateRequestSuccessZeroCount()
    {
        /* Carichiamo il vero helper di CodeIgniter che contiene convertDate */
        helper('date');

        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageAuditsRules',
                'countAuditsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $auditPayload =
            [
                'from'  => '2026-02-01',
                'to'    => '2026-02-28',
                'count' => 0
            ];

        $mockModel->method(
            'countAuditsToDelete'
        )
        ->willReturn(
            $auditPayload
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        /* Ora userà la tua VERA funzione convertDate */
        $fromStr =
            convertDate('2026-02-01', 'conversational');

        $toStr =
            convertDate('2026-02-28', 'conversational');

        $countVal =
            0;

        $langStr =
            lang('backend/tools.messages.areYouSureToDeleteAudits');

        $confirmStr =
            sprintf(
                $langStr,
                $fromStr,
                $toStr,
                $countVal
            );

        $noDataMsg =
            lang('backend/tools.messages.noAuditsFound');

        $expectedJson =
            [
                'result'         => true,
                'count'          => $countVal,
                'confirmMessage' => $confirmStr,
                'noDataMessage'  => $noDataMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateAuditsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testDeleteAuditsNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->deleteAudits();

        $this->assertNull(
            $result
        );
    }

    public function testDeleteAuditsValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['validateManageAuditsRules']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            []
        );

        /* Simuliamo il fallimento della validazione */
        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $valCls =
            \CodeIgniter\Validation\Validation::class;

        $valBld =
            $this->getMockBuilder(
                $valCls
            );

        $valBld->disableOriginalConstructor();

        $mockVal =
            $valBld->getMock();

        $validationErrors =
            ['field' => 'error_message'];

        $mockVal->method(
            'getErrors'
        )
        ->willReturn(
            $validationErrors
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'errors'  => $validationErrors,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $mockVal
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->validator =
                    $mockVal;
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

        $result =
            $controller->deleteAudits();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testDeleteAuditsSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $postsData =
            ['dummy_post' => 'data'];

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            $postsData
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageAuditsRules',
                'deleteAudits'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageAuditsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $expectedJsonData =
            [
                'result'  => true,
                'message' => 'Success'
            ];

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'deleteAudits'
        )
        ->with(
            $postsData
        )
        ->willReturn(
            $expectedJsonData
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJsonData
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->deleteAudits();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateLogsDateRequestNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->validateLogsDateRequest();

        $this->assertNull(
            $result
        );
    }

    public function testValidateLogsDateRequestValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $postsData =
            ['start' => 'invalid'];

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            $postsData
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['validateManageLogsRules']
        );

        $mockModel =
            $modelBld->getMock();

        $rulesData =
            ['start' => 'required'];

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            $rulesData
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $valCls =
            \CodeIgniter\Validation\Validation::class;

        $valBld =
            $this->getMockBuilder(
                $valCls
            );

        $valBld->disableOriginalConstructor();

        $mockVal =
            $valBld->getMock();

        $validationErrors =
            ['start' => 'Error message'];

        $mockVal->method(
            'getErrors'
        )
        ->willReturn(
            $validationErrors
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'errors'  => $validationErrors,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $mockVal
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->validator =
                    $mockVal;
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

        $result =
            $controller->validateLogsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateLogsDateRequestCountReturnsFalse()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageLogsRules',
                'countLogsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'countLogsToDelete'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.startDateAfterEndDate');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateLogsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateLogsDateRequestSuccessWithCount()
    {
        /* Inserisci il nome reale del tuo helper */
        helper('date');

        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageLogsRules',
                'countLogsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $logPayload =
            [
                'from'  => '2026-01-01',
                'to'    => '2026-01-31',
                'count' => 5
            ];

        $mockModel->method(
            'countLogsToDelete'
        )
        ->willReturn(
            $logPayload
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $fromStr =
            convertDate('2026-01-01', 'conversational');

        $toStr =
            convertDate('2026-01-31', 'conversational');

        $countVal =
            5;

        $langStr =
            lang('backend/tools.messages.areYouSureToDeleteLogs');

        $confirmStr =
            sprintf(
                $langStr,
                $fromStr,
                $toStr,
                $countVal
            );

        $expectedJson =
            [
                'result'         => true,
                'count'          => $countVal,
                'confirmMessage' => $confirmStr
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateLogsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testValidateLogsDateRequestSuccessZeroCount()
    {
        /* Inserisci il nome reale del tuo helper */
        helper('date');

        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageLogsRules',
                'countLogsToDelete'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $logPayload =
            [
                'from'  => '2026-02-01',
                'to'    => '2026-02-28',
                'count' => 0
            ];

        $mockModel->method(
            'countLogsToDelete'
        )
        ->willReturn(
            $logPayload
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $fromStr =
            convertDate('2026-02-01', 'conversational');

        $toStr =
            convertDate('2026-02-28', 'conversational');

        $countVal =
            0;

        $langStr =
            lang('backend/tools.messages.areYouSureToDeleteLogs');

        $confirmStr =
            sprintf(
                $langStr,
                $fromStr,
                $toStr,
                $countVal
            );

        $noDataMsg =
            lang('backend/tools.messages.noLogsFound');

        $expectedJson =
            [
                'result'         => true,
                'count'          => $countVal,
                'confirmMessage' => $confirmStr,
                'noDataMessage'  => $noDataMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->validateLogsDateRequest();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testDeleteLogsNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->deleteLogs();

        $this->assertNull(
            $result
        );
    }

    public function testDeleteLogsValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            []
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['validateManageLogsRules']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $valCls =
            \CodeIgniter\Validation\Validation::class;

        $valBld =
            $this->getMockBuilder(
                $valCls
            );

        $valBld->disableOriginalConstructor();

        $mockVal =
            $valBld->getMock();

        $validationErrors =
            ['field' => 'error_message'];

        $mockVal->method(
            'getErrors'
        )
        ->willReturn(
            $validationErrors
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'errors'  => $validationErrors,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel,
                $mockVal
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;

                $this->validator =
                    $mockVal;
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

        $result =
            $controller->deleteLogs();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testDeleteLogsSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $postsData =
            ['dummy_log_post' => 'data'];

        $mockReq->method(
            'getPost'
        )
        ->willReturn(
            $postsData
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            [
                'validateManageLogsRules',
                'deleteLogs'
            ]
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'validateManageLogsRules'
        )
        ->willReturn(
            []
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $expectedJsonData =
            [
                'result'  => true,
                'message' => 'Logs deleted successfully'
            ];

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'deleteLogs'
        )
        ->with(
            $postsData
        )
        ->willReturn(
            $expectedJsonData
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJsonData
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->deleteLogs();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testOptimizeTableNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->optimizeTable();

        $this->assertNull(
            $result
        );
    }

    public function testOptimizeTableValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                $badName =
                    'invalid table!';

                if (
                    $key === 'table'
                ) {
                    return $badName;
                }

                $postData =
                    ['table' => 'invalid table!'];

                return $postData;
            };

	        $mockReq->method(
	            'getPost'
	        )
	        ->willReturnCallback(
	            $callback
	        );

	        $controller->method(
	            'validateData'
	        )
	        ->willReturn(
	            false
	        );

	        $valCls =
	            \CodeIgniter\Validation\Validation::class;

	        $valBld =
	            $this->getMockBuilder(
	                $valCls
	            );

	        $valBld->disableOriginalConstructor();

	        $mockVal =
	            $valBld->getMock();

	        $validationErrors =
	            ['table' => 'Invalid regex'];

	        $mockVal->method(
	            'getErrors'
	        )
	        ->willReturn(
	            $validationErrors
	        );

	        $respCls =
	            \CodeIgniter\HTTP\Response::class;

	        $respBld =
	            $this->getMockBuilder(
	                $respCls
	            );

	        $respBld->disableOriginalConstructor();

	        $mockResp =
	            $respBld->getMock();

	        $errorHtml =
	            implode(
	                '',
				$validationErrors
			);

	        $langStr =
	        lang('backend/tools.messages.validateToastErrors');

	    $expectedMsg =
	        sprintf(
	            $langStr,
	            $errorHtml
	        );

	    $expectedJson =
	        [
	            'result'  => false,
	            'message' => $expectedMsg
	        ];

	    $controller->expects(
	        $this->once()
	    )
	    ->method(
	        'jsonResponse'
	    )
	    ->with(
	        $expectedJson
	    )
	    ->willReturn(
	        $mockResp
	    );

	    $closure =
	        function () use (
	            $mockReq,
	            $mockVal
	        ) {
	            $this->request =
	                $mockReq;

	            $this->validator =
	                $mockVal;
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

	    $result =
	        $controller->optimizeTable();

	    $this->assertSame(
	        $mockResp,
	        $result
	    );
	}

	public function testOptimizeTableRunOptimizationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                $tableName =
                    'admins';

                if (
                    $key === 'table'
                ) {
                    return $tableName;
                }

                $postData =
                    ['table' => 'admins'];

                return $postData;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['runOptimization']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'runOptimization'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.optimizeError');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->optimizeTable();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testOptimizeTableSuccessSingleTable()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                $tableName =
                    'admins';

                if (
                    $key === 'table'
                ) {
                    return $tableName;
                }

                $postData =
                    ['table' => 'admins'];

                return $postData;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['runOptimization']
        );

        $mockModel =
            $modelBld->getMock();

        $optData =
            ['status' => 'OK'];

        $mockModel->method(
            'runOptimization'
        )
        ->with(
            'admins'
        )
        ->willReturn(
            $optData
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $langStr =
            lang('backend/tools.messages.optimizeSuccess');

        $expectedMsg =
            sprintf(
                $langStr,
                'admins'
            );

        $expectedJson =
            [
                'result'    => true,
                'message'   => $expectedMsg,
                'tableData' => $optData
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->optimizeTable();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testOptimizeTableSuccessMultipleTables()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'table'
                ) {
                    return ['admins', 'logs'];
                }

                $postData =
                    ['table' => ['admins', 'logs']];

                return $postData;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['runOptimization']
        );

        $mockModel =
            $modelBld->getMock();

        $optData =
            [
                ['status' => 'OK'],
                ['status' => 'OK']
            ];

        $mockModel->method(
            'runOptimization'
        )
        ->with(
            ['admins', 'logs']
        )
        ->willReturn(
            $optData
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.optimizeAllSuccess');

        $expectedJson =
            [
                'result'    => true,
                'message'   => $expectedMsg,
                'tableData' => $optData
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->optimizeTable();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->backups();

        $this->assertNull(
            $result
        );
    }

    public function testBackupsValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'invalid_action';
                }

                $postArr =
                    ['action' => 'invalid_action'];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsGenerateSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'generateBackups';
                }

                $postArr =
                    ['action' => 'generateBackups'];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['generateDatabaseBackups']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'generateDatabaseBackups'
        )
        ->willReturn(
            true
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.generateBackupsSuccess');

        $expectedJson =
            [
                'result'  => true,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsGenerateError()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'generateBackups';
                }

                $postArr =
                    ['action' => 'generateBackups'];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['generateDatabaseBackups']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->method(
            'generateDatabaseBackups'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.generateBackupsError');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsDeleteSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'deleteBackups';
                }

                if (
                    $key === 'filename'
                ) {
                    return 'backup_file.zip';
                }

                $postArr =
                    [
                        'action'   => 'deleteBackups',
                        'filename' => 'backup_file.zip'
                    ];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['deleteBackups']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'deleteBackups'
        )
        ->with(
            'backup_file.zip'
        )
        ->willReturn(
            true
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.deleteBackupsSuccess');

        $expectedJson =
            [
                'result'  => true,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsDeleteError()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'deleteBackups';
                }

                if (
                    $key === 'filename'
                ) {
                    return 'backup_file.zip';
                }

                $postArr =
                    [
                        'action'   => 'deleteBackups',
                        'filename' => 'backup_file.zip'
                    ];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['deleteBackups']
        );

        $mockModel =
            $modelBld->getMock();

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'deleteBackups'
        )
        ->with(
            'backup_file.zip'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.deleteBackupsError');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testBackupsDownloadSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $fileName =
            'test_down.zip';

        $callback =
            function (
                $key = null
            ) use (
                $fileName
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'downloadBackups';
                }

                if (
                    $key === 'filename'
                ) {
                    return $fileName;
                }

                $postArr =
                    [
                        'action'   => 'downloadBackups',
                        'filename' => $fileName
                    ];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $baseDir =
            WRITEPATH;

        $bkpDir =
            $baseDir . 'backups/database/';

        if ( ! is_dir($bkpDir)):
            mkdir(
                $bkpDir,
                0777,
                true
            );
        endif;

        $filePath =
            $bkpDir.$fileName;

        file_put_contents(
            $filePath,
            'dummy'
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $urlPath =
            'backend/tools/downloadBackups/' . $fileName;

        $expectedUrl =
            base_url(
                $urlPath
            );

        $expectedJson =
            [
                'result'      => true,
                'downloadUrl' => $expectedUrl
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );

        @unlink(
            $filePath
        );
    }

    public function testBackupsDownloadError()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $fileName =
            'not_exist.zip';

        $callback =
            function (
                $key = null
            ) use (
                $fileName
            ) {
                if (
                    $key === 'action'
                ) {
                    return 'downloadBackups';
                }

                if (
                    $key === 'filename'
                ) {
                    return $fileName;
                }

                $postArr =
                    [
                        'action'   => 'downloadBackups',
                        'filename' => $fileName
                    ];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $baseDir =
            WRITEPATH;

        $bkpDir =
            $baseDir . 'backups/database/';

        $filePath =
            $bkpDir.$fileName;

        @unlink(
            $filePath
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.backupNotFoundError');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->backups();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testDownloadBackupsFileDoesNotExist()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $fileNameStr =
            'not_exist_dl.zip';

        $baseDir =
            WRITEPATH;

        $bkpDir =
            $baseDir . 'backups/database/';

        $filePath =
            $bkpDir.$fileNameStr;

        @unlink(
            $filePath
        );

        /* Assicuriamoci che gli helper nativi per il redirect siano attivi */
        helper(
            'url'
        );

        $result =
            $controller->downloadBackups(
                $fileNameStr
            );

        $redCls =
            \CodeIgniter\HTTP\RedirectResponse::class;

        $this->assertInstanceOf(
            $redCls,
            $result
        );
    }

    public function testDownloadBackupsSuccess()
    {
        if ( ! function_exists('log_admin_activity')):
            function log_admin_activity(
                $a,
                $b,
                $c,
                $d
            ) {
            }
        endif;

        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        /* Mock del servizio Authorization */
        $authCls =
            \stdClass::class;

        $authBld =
            $this->getMockBuilder(
                $authCls
            );

        $authBld->addMethods(
            ['currentAdmin']
        );

        $mockAuth =
            $authBld->getMock();

        $mockAuth->method(
            'currentAdmin'
        )
        ->willReturn(
            (object) ['uuid' => 1, 'email' => 'gbwebapps@gmail.com', 'phone' => '123456']
        );

        \Config\Services::injectMock(
            'authorization',
            $mockAuth
        );

        /* Mock della Response nativa */
        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $fileNameStr =
            'test_dl.zip';

        $baseDir =
            WRITEPATH;

        $bkpDir =
            $baseDir . 'backups/database/';

        if ( ! is_dir($bkpDir)):
            mkdir(
                $bkpDir,
                0777,
                true
            );
        endif;

        $filePath =
            $bkpDir.$fileNameStr;

        file_put_contents(
            $filePath,
            'dummy'
        );

        $mockResp->expects(
            $this->once()
        )
        ->method(
            'download'
        )
        ->with(
            $filePath,
            null
        )
        ->willReturn(
            $mockResp
        );

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

        $result =
            $controller->downloadBackups(
                $fileNameStr
            );

        $this->assertSame(
            $mockResp,
            $result
        );

        @unlink(
            $filePath
        );
    }

    public function testCleanFolderNotAjaxReturnsNull()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $controller =
            $builder->getMockForAbstractClass();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->expects(
            $this->once()
        )
        ->method(
            'isAJAX'
        )
        ->willReturn(
            false
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->cleanFolder();

        $this->assertNull(
            $result
        );
    }

    public function testCleanFolderValidationFails()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'folder'
                ) {
                    return 'invalid folder!';
                }

                $postArr =
                    ['folder' => 'invalid folder!'];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            false
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $expectedMsg =
            lang('backend/tools.messages.validationErrors');

        $expectedJson =
            [
                'result'  => false,
                'message' => $expectedMsg
            ];

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $expectedJson
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq
            ) {
                $this->request =
                    $mockReq;
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

        $result =
            $controller->cleanFolder();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testCleanFolderSuccess()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            [
                'validateData',
                'jsonResponse'
            ]
        );

        $controller =
            $builder->getMock();

        $reqCls =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $reqBld =
            $this->getMockBuilder(
                $reqCls
            );

        $reqBld->disableOriginalConstructor();

        $mockReq =
            $reqBld->getMock();

        $mockReq->method(
            'isAJAX'
        )
        ->willReturn(
            true
        );

        $mockReq->method(
            'is'
        )
        ->willReturn(
            true
        );

        $callback =
            function (
                $key = null
            ) {
                if (
                    $key === 'folder'
                ) {
                    return 'cache';
                }

                $postArr =
                    ['folder' => 'cache'];

                return $postArr;
            };

        $mockReq->method(
            'getPost'
        )
        ->willReturnCallback(
            $callback
        );

        $controller->method(
            'validateData'
        )
        ->willReturn(
            true
        );

        $modelCls =
            \App\Models\Backend\ToolsModel::class;

        $modelBld =
            $this->getMockBuilder(
                $modelCls
            );

        $modelBld->disableOriginalConstructor();

        $modelBld->onlyMethods(
            ['cleanWritableFolder']
        );

        $mockModel =
            $modelBld->getMock();

        $modelResponse =
            [
                'result'  => true,
                'message' => 'Cleaned successfully'
            ];

        $mockModel->expects(
            $this->once()
        )
        ->method(
            'cleanWritableFolder'
        )
        ->with(
            'cache'
        )
        ->willReturn(
            $modelResponse
        );

        $respCls =
            \CodeIgniter\HTTP\Response::class;

        $respBld =
            $this->getMockBuilder(
                $respCls
            );

        $respBld->disableOriginalConstructor();

        $mockResp =
            $respBld->getMock();

        $controller->expects(
            $this->once()
        )
        ->method(
            'jsonResponse'
        )
        ->with(
            $modelResponse
        )
        ->willReturn(
            $mockResp
        );

        $closure =
            function () use (
                $mockReq,
                $mockModel
            ) {
                $this->request =
                    $mockReq;

                $this->toolsModel =
                    $mockModel;
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

        $result =
            $controller->cleanFolder();

        $this->assertSame(
            $mockResp,
            $result
        );
    }

    public function testIndex()
    {
        $ctrlCls =
            \App\Controllers\Backend\ToolsController::class;

        $builder =
            $this->getMockBuilder(
                $ctrlCls
            );

        $builder->disableOriginalConstructor();

        $builder->onlyMethods(
            ['render']
        );

        $controller =
            $builder->getMock();

        $closure =
            function () {
                $this->data =
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

        $isArr =
            $this->isType(
                'array'
            );

        $expectedHtml =
            'mocked_index_view';

        $controller->expects(
            $this->once()
        )
        ->method(
            'render'
        )
        ->with(
            'backend/tools/indexView',
            $isArr
        )
        ->willReturn(
            $expectedHtml
        );

        $result =
            $controller->index();

        $this->assertSame(
            $expectedHtml,
            $result
        );
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
            $this->getMockBuilder(\App\Models\Backend\ToolsModel::class)
                 ->disableOriginalConstructor()
                 ->getMock();

        \CodeIgniter\Config\Factories::injectMock(
            'models',
            \App\Models\Backend\ToolsModel::class,
            $mockModel
        );

        $controller = 
            new \App\Controllers\Backend\ToolsController();

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
                    'toolsModel' => 
                        $this->toolsModel,
                    'toolsClass' => 
                        $this->toolsClass
                ];
            };

        $bind = 
            \Closure::bind(
                $extractor,
                $controller,
                \App\Controllers\Backend\ToolsController::class
            );

        $props = 
            $bind();

        $data = 
            $props['data'];

        $toolsModel = 
            $props['toolsModel'];

        $toolsClass = 
            $props['toolsClass'];

        $this->assertEquals(
            'tools',
            $data['controller']
        );

        $this->assertInstanceOf(
            \App\Models\Backend\ToolsModel::class,
            $toolsModel
        );

        $this->assertInstanceOf(
            \App\Libraries\Backend\ToolsClass::class,
            $toolsClass
        );
    }
}
