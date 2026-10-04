<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class ImportControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->createMock($modelClass);

        $this
            ->mockImportModel
            =
            $model;

        $reqClass
            =
            \CodeIgniter\HTTP\IncomingRequest::class;

        $req
            =
            $this
            ->createMock($reqClass);

        $this
            ->mockRequest
            =
            $req;

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $res
            =
            $this
            ->createMock($resClass);

        $this
            ->mockResponse
            =
            $res;

        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $mockBuilder
            =
            $this
            ->getMockBuilder($ctrlClass);

        $mockBuilder
            ->onlyMethods([
                'validateData',
                'jsonResponse'
            ]);

        $mockBuilder
            ->disableOriginalConstructor();

        $ctrl
            =
            $mockBuilder
            ->getMock();

        $this
            ->controller
            =
            $ctrl;

        $inject
            =
            function (
                $m_model,
                $m_req,
                $m_res
            ) {
                $this
                    ->importModel
                    =
                    $m_model;

                $this
                    ->request
                    =
                    $m_req;

                $this
                    ->response
                    =
                    $m_res;
            };

        $binder
            =
            \Closure::bind(
                $inject,
                $this
                ->controller,
                $ctrlClass
            );

        $binder(
            $this
            ->mockImportModel,
            $this
            ->mockRequest,
            $this
            ->mockResponse
        );
    }

    public function testShowModalNotAjaxReturnsNull()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(false);

        $ctrl
            =
            $this
            ->controller;

        $result
            =
            $ctrl
            ->showModal();

        $this
            ->assertNull(
                $result
            );
    }

    public function testShowModalValidationFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $postData
            =
            [
                'entity'
                =>
                ''
            ];

        $req
            ->method('getPost')
            ->willReturn(
                $postData
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(false);

        $valClass
            =
            \CodeIgniter\Validation\Validation::class;

        $valMock
            =
            $this
            ->createMock($valClass);

        $errors
            =
            [
                'entity'
                =>
                'Required field'
            ];

        $valMock
            ->method('getErrors')
            ->willReturn(
                $errors
            );

        $injectVal
            =
            function (
                $v
            ) {
                $this
                    ->validator
                    =
                    $v;
            };

        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $binder
            =
            \Closure::bind(
                $injectVal,
                $ctrl,
                $ctrlClass
            );

        $binder(
            $valMock
        );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->showModal();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testShowModalSuccessReturnsJsonWithOutput()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $postData
            =
            [
                'entity'
                =>
                'users'
            ];

        $req
            ->method('getPost')
            ->willReturn(
                $postData
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $struct
            =
            [
                [
                    'name'
                    =>
                    'id'
                ]
            ];

        $model
            ->method('getTableStructure')
            ->with('users')
            ->willReturn(
                $struct
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->showModal();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testDownloadInvalidEntityRedirectsBack()
    {
        $ctrl
            =
            $this
            ->controller;

        $result
            =
            $ctrl
            ->download('invalid entity !');

        $redirectClass
            =
            \CodeIgniter\HTTP\RedirectResponse::class;

        $this
            ->assertInstanceOf(
                $redirectClass,
                $result
            );
    }

    public function testDownloadEmptyStructureRedirectsBack()
    {
        $ctrl
            =
            $this
            ->controller;

        $model
            =
            $this
            ->mockImportModel;

        $model
            ->method('getTableStructure')
            ->with('users')
            ->willReturn([]);

        $result
            =
            $ctrl
            ->download('users');

        $redirectClass
            =
            \CodeIgniter\HTTP\RedirectResponse::class;

        $this
            ->assertInstanceOf(
                $redirectClass,
                $result
            );
    }

    public function testDownloadSuccessReturnsCsvResponse()
    {
        $ctrl
            =
            $this
            ->controller;

        $model
            =
            $this
            ->mockImportModel;

        $struct
            =
            [
                [
                    'name'
                    =>
                    'id'
                ],
                [
                    'name'
                    =>
                    'email'
                ]
            ];

        $model
            ->method('getTableStructure')
            ->with('users')
            ->willReturn(
                $struct
            );

        $res
            =
            $this
            ->mockResponse;

        $res
            ->method('download')
            ->willReturnSelf();

        $res
            ->method('setContentType')
            ->willReturnSelf();

        $result
            =
            $ctrl
            ->download('users');

        $this
            ->assertSame(
                $res,
                $result
            );
    }

    public function testProcessCsvNotAjaxReturnsNull()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(false);

        $ctrl
            =
            $this
            ->controller;

        $result
            =
            $ctrl
            ->processCsv();

        $this
            ->assertNull(
                $result
            );
    }

    public function testProcessCsvValidationFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $postData
            =
            [
                'entity'
                =>
                ''
            ];

        $req
            ->method('getPost')
            ->willReturn(
                $postData
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(false);

        $valClass
            =
            \CodeIgniter\Validation\Validation::class;

        $valMock
            =
            $this
            ->createMock($valClass);

        $errors
            =
            [
                'csvFile'
                =>
                'Invalid file format'
            ];

        $valMock
            ->method('getErrors')
            ->willReturn(
                $errors
            );

        $injectVal
            =
            function (
                $v
            ) {
                $this
                    ->validator
                    =
                    $v;
            };

        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $binder
            =
            \Closure::bind(
                $injectVal,
                $ctrl,
                $ctrlClass
            );

        $binder(
            $valMock
        );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->processCsv();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testProcessCsvModelValidationFailsWithStructuralErrors()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                if (
                    $key
                    ===
                    'entity'
                ):
                    return 'users';
                endif;

                $data
                    =
                    [
                        'entity'
                        =>
                        'users'
                    ];

                return $data;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->createMock($fileClass);

        $req
            ->method('getFile')
            ->with('csvFile')
            ->willReturn(
                $fileMock
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $previewData
            =
            [
                'status'
                =>
                false,
                'validationErrors'
                =>
                [
                    'Riga 2'
                    =>
                    'Dato mancante'
                ]
            ];

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('parseAndValidateCsv')
            ->with(
                $fileMock,
                'users'
            )
            ->willReturn(
                $previewData
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with(
                $this
                ->callback(
                    function(array $data): bool {
                        $this
                            ->assertFalse(
                                $data['result']
                            );

                        $this
                            ->assertIsString(
                                $data['errorOutput']
                            );

                        return true;
                    }
                )
            )
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->processCsv();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testProcessCsvModelFailsWithGenericError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                if (
                    $key
                    ===
                    'entity'
                ):
                    return 'users';
                endif;

                $data
                    =
                    [
                        'entity'
                        =>
                        'users'
                    ];

                return $data;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->createMock($fileClass);

        $req
            ->method('getFile')
            ->with('csvFile')
            ->willReturn(
                $fileMock
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $previewData
            =
            [
                'status'
                =>
                false,
                'message'
                =>
                'Errore generico di parsing'
            ];

        $model
            ->method('parseAndValidateCsv')
            ->with(
                $fileMock,
                'users'
            )
            ->willReturn(
                $previewData
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->processCsv();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testProcessCsvSuccessReturnsPreviewData()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                if (
                    $key
                    ===
                    'entity'
                ):
                    return 'users';
                endif;

                $data
                    =
                    [
                        'entity'
                        =>
                        'users'
                    ];

                return $data;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->createMock($fileClass);

        $req
            ->method('getFile')
            ->with('csvFile')
            ->willReturn(
                $fileMock
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $planData
            =
            [
                'insert'
                =>
                5,
                'update'
                =>
                2,
                'skip'
                =>
                0
            ];

        $previewData
            =
            [
                'status'
                =>
                true,
                'headers'
                =>
                [],
                'rows'
                =>
                [],
                'tempFile'
                =>
                'tmp/file.csv',
                'plan'
                =>
                $planData
            ];

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('parseAndValidateCsv')
            ->with(
                $fileMock,
                'users'
            )
            ->willReturn(
                $previewData
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with(
                $this
                ->callback(
                    function(array $data): bool {
                        $this
                            ->assertTrue(
                                $data['result']
                            );

                        $this
                            ->assertIsString(
                                $data['output']
                            );

                        $this
                            ->assertTrue(
                                $data['hasProcessableData']
                            );

                        return true;
                    }
                )
            )
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->processCsv();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testExecuteImportNotAjaxReturnsNull()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(false);

        $ctrl
            =
            $this
            ->controller;

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertNull(
                $result
            );
    }

    public function testExecuteImportValidationFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $postData
            =
            [
                'entity'
                =>
                ''
            ];

        $req
            ->method('getPost')
            ->willReturn(
                $postData
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(false);

        $valClass
            =
            \CodeIgniter\Validation\Validation::class;

        $valMock
            =
            $this
            ->createMock($valClass);

        $errors
            =
            [
                'entity'
                =>
                'Required field'
            ];

        $valMock
            ->method('getErrors')
            ->willReturn(
                $errors
            );

        $injectVal
            =
            function (
                $v
            ) {
                $this
                    ->validator
                    =
                    $v;
            };

        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $binder
            =
            \Closure::bind(
                $injectVal,
                $ctrl,
                $ctrlClass
            );

        $binder(
            $valMock
        );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testExecuteImportBackupFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                $data
                    =
                    [
                        'entity'
                        =>
                        'users',
                        'tempFile'
                        =>
                        'file.csv',
                        'step'
                        =>
                        'confirm',
                        'offset'
                        =>
                        0
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('backupTableBeforeImport')
            ->with('users')
            ->willReturn(false);

        $model
            ->expects(
                $this
                ->never()
            )
            ->method('executeImport');

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with([
                'result' => false,
                'message' => lang('backend/components/import.messages.backupError')
            ])
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testExecuteImportModelExecutionFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                $data
                    =
                    [
                        'entity'
                        =>
                        'users',
                        'tempFile'
                        =>
                        'file.csv',
                        'step'
                        =>
                        'confirm',
                        'offset'
                        =>
                        0
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('backupTableBeforeImport')
            ->with('users')
            ->willReturn(true);

        $failResult
            =
            [
                'status'
                =>
                false,
                'message'
                =>
                'Errore di importazione'
            ];

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('executeImport')
            ->with(
                'users',
                'file.csv',
                0
            )
            ->willReturn(
                $failResult
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with([
                'result' => false,
                'message' => 'Errore di importazione'
            ])
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testExecuteImportFinishedWithRecordsReturnsSuccessMessage()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                $data
                    =
                    [
                        'entity'
                        =>
                        'users',
                        'tempFile'
                        =>
                        'file.csv',
                        'step'
                        =>
                        'confirm',
                        'offset'
                        =>
                        100,
                        'accumulatedInserted'
                        =>
                        10,
                        'accumulatedUpdated'
                        =>
                        5
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $model
            ->expects(
                $this
                ->never()
            )
            ->method('backupTableBeforeImport');

        $importReturn
            =
            [
                'status'
                =>
                true,
                'isFinished'
                =>
                true,
                'inserted'
                =>
                5,
                'updated'
                =>
                2,
                'nextOffset'
                =>
                112
            ];

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('executeImport')
            ->with(
                'users',
                'file.csv',
                100
            )
            ->willReturn(
                $importReturn
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with(
                $this
                ->callback(
                    function(array $data): bool {
                        $this
                            ->assertSame(
                                true,
                                $data['result']
                            );

                        $this
                            ->assertSame(
                                sprintf(lang('backend/components/import.messages.importSuccess'), 15, 7),
                                $data['message']
                            );

                        $this
                            ->assertSame(
                                112,
                                $data['nextOffset']
                            );

                        $this
                            ->assertTrue(
                                $data['isFinished']
                            );

                        $this
                            ->assertSame(
                                5,
                                $data['inserted']
                            );

                        $this
                            ->assertSame(
                                2,
                                $data['updated']
                            );

                        $this
                            ->assertIsString(
                                $data['progressOutput']
                            );

                        $this
                            ->assertSame(
                                sprintf(lang('backend/components/import.messages.processedRows'), 112),
                                $data['progressMessage']
                            );

                        return true;
                    }
                )
            )
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testConstructorInitializesModel()
    {
        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $ctrl
            =
            new $ctrlClass();

        $prop
            =
            new \ReflectionProperty(
                $ctrlClass,
                'importModel'
            );

        $prop
            ->setAccessible(true);

        $model
            =
            $prop
            ->getValue(
                $ctrl
            );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $this
            ->assertInstanceOf(
                $modelClass,
                $model
            );
    }

    public function testDeleteFileNotAjaxReturnsNull()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(false);

        $ctrl
            =
            $this
            ->controller;

        $result
            =
            $ctrl
            ->deleteFile();

        $this
            ->assertNull(
                $result
            );
    }

    public function testDeleteFileValidationFailsReturnsJsonError()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                $data
                    =
                    [
                        'file'
                        =>
                        'invalid file !'
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(false);

        $valClass
            =
            \CodeIgniter\Validation\Validation::class;

        $valMock
            =
            $this
            ->createMock($valClass);

        $errors
            =
            [
                'file'
                =>
                'Invalid format'
            ];

        $valMock
            ->method('getErrors')
            ->willReturn(
                $errors
            );

        $injectVal
            =
            function (
                $v
            ) {
                $this
                    ->validator
                    =
                    $v;
            };

        $ctrlClass
            =
            \App\Controllers\Backend\Components\ImportController::class;

        $binder
            =
            \Closure::bind(
                $injectVal,
                $ctrl,
                $ctrlClass
            );

        $binder(
            $valMock
        );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->deleteFile();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testDeleteFileSuccessDeletesFileAndReturnsJson()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $fileName
            =
            'test_file_to_delete.csv';

        $cb
            =
            function (
                $key
                =
                null
            ) use (
                $fileName
            ) {
                $data
                    =
                    [
                        'file'
                        =>
                        $fileName
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0777,
                true
            );
        endif;

        $filePath
            =
            $stagingDir
            .
            $fileName;

        file_put_contents(
            $filePath,
            'dummy content'
        );

        $this
            ->assertFileExists(
                $filePath
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->method('jsonResponse')
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->deleteFile();

        $this
            ->assertFileDoesNotExist(
                $filePath
            );

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }

    public function testExecuteImportFinishedWithNoRecordsModifiedReturnsSpecificMessage()
    {
        $req
            =
            $this
            ->mockRequest;

        $req
            ->method('isAJAX')
            ->willReturn(true);

        $req
            ->method('is')
            ->with('post')
            ->willReturn(true);

        $cb
            =
            function (
                $key
                =
                null
            ) {
                $data
                    =
                    [
                        'entity'
                        =>
                        'users',
                        'tempFile'
                        =>
                        'file.csv',
                        'step'
                        =>
                        'confirm',
                        'offset'
                        =>
                        100,
                        'accumulatedInserted'
                        =>
                        0,
                        'accumulatedUpdated'
                        =>
                        0
                    ];

                if (
                    $key
                    ===
                    null
                ):
                    return $data;
                endif;

                if (
                    isset(
                        $data[$key]
                    )
                ):
                    return $data[$key];
                endif;

                return null;
            };

        $req
            ->method('getPost')
            ->willReturnCallback(
                $cb
            );

        $ctrl
            =
            $this
            ->controller;

        $ctrl
            ->method('validateData')
            ->willReturn(true);

        $model
            =
            $this
            ->mockImportModel;

        $importReturn
            =
            [
                'status'
                =>
                true,
                'isFinished'
                =>
                true,
                'inserted'
                =>
                0,
                'updated'
                =>
                0,
                'nextOffset'
                =>
                100
            ];

        $model
            ->expects(
                $this
                ->once()
            )
            ->method('executeImport')
            ->with(
                'users',
                'file.csv',
                100
            )
            ->willReturn(
                $importReturn
            );

        $resClass
            =
            \CodeIgniter\HTTP\Response::class;

        $jsonReturn
            =
            $this
            ->createMock($resClass);

        $ctrl
            ->expects(
                $this
                ->once()
            )
            ->method('jsonResponse')
            ->with(
                $this
                ->callback(
                    function(array $data): bool {
                        $this
                            ->assertSame(
                                true,
                                $data['result']
                            );

                        $this
                            ->assertSame(
                                lang('backend/components/import.messages.importationNoRecordsModified'),
                                $data['message']
                            );

                        $this
                            ->assertSame(
                                100,
                                $data['nextOffset']
                            );

                        $this
                            ->assertTrue(
                                $data['isFinished']
                            );

                        $this
                            ->assertSame(
                                0,
                                $data['inserted']
                            );

                        $this
                            ->assertSame(
                                0,
                                $data['updated']
                            );

                        return true;
                    }
                )
            )
            ->willReturn(
                $jsonReturn
            );

        $result
            =
            $ctrl
            ->executeImport();

        $this
            ->assertSame(
                $jsonReturn,
                $result
            );
    }
}
