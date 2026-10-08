<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Models\Backend\Components\ExportModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Validation\ValidationInterface;
use Config\Services;
use CodeIgniter\HTTP\DownloadResponse;

class ExportControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    private const EXPORT_ID = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();
        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');
    }

    protected function tearDown(): void
    {
        Factories::reset();
        Services::resetSingle('renderer');
        Services::resetSingle('validation');
        Services::resetSingle('language');
        parent::tearDown();
    }

    public function testConstructSetsExportModel(): void
    {
        $exportModel = $this->createMock(ExportModel::class);
        Factories::injectMock('models', ExportModel::class, $exportModel);

        $controller = new ExportController();
        $property = new \ReflectionProperty(ExportController::class, 'exportModel');
        $property->setAccessible(true);

        $this->assertSame($exportModel, $property->getValue($controller));
    }

    public function testShowModalRejectsNonAjaxRequest(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $controller = $this->createController($request, $this->createMock(ExportModel::class), ['validateData', 'jsonResponse']);

        $this->assertSame(400, $controller->showModal()->getStatusCode());
    }

    public function testShowModalReturnsValidationErrorsForInvalidData(): void
    {
        $request = $this->ajaxPostRequest(['entity' => '']);
        $validator = $this->createMock(ValidationInterface::class);
        $validator->method('getErrors')->willReturn(['entity' => 'error message']);

        $controller = $this->createController($request, $this->createMock(ExportModel::class), ['validateData', 'jsonResponse'], $validator);
        $controller->method('validateData')->willReturn(false);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertStringContainsString('error message', $data['message']);

            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->showModal());
    }

    public function testShowModalReturnsJsonResponseWithOutputOnSuccess(): void
    {
        $request = $this->ajaxPostRequest(['entity' => 'users']);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('getExportColumns')->with('users', ExportModel::CONTEXT_CRUD)->willReturn(['uuid', 'name']);
        $exportModel->expects($this->once())->method('getRequiredExportColumns')->with('users', ExportModel::CONTEXT_CRUD)->willReturn(['uuid']);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertTrue($data['result']);
            $this->assertArrayHasKey('output', $data);
            $this->assertIsString($data['output']);

            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->showModal());
    }

    public function testShowModalFailsClosedWhenNoColumnsAreExportable(): void
    {
        $request = $this->ajaxPostRequest(['entity' => 'users']);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('getExportColumns')->with('users', ExportModel::CONTEXT_CRUD)->willReturn([]);
        $exportModel->expects($this->never())->method('getRequiredExportColumns');

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => lang('backend/components/export.messages.invalidEntity'),
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->showModal());
    }

    public function testShowDatabaseModalUsesDatabaseContext(): void
    {
        $request = $this->ajaxPostRequest(['entity' => 'users']);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('getExportColumns')->with('users', ExportModel::CONTEXT_DATABASE)->willReturn(['id', 'uuid', 'name']);
        $exportModel->expects($this->once())->method('getRequiredExportColumns')->with('users', ExportModel::CONTEXT_DATABASE)->willReturn(['uuid']);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $controller->method('jsonResponse')->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->showDatabaseModal());
    }

    public function testGenerateReturnsValidationErrorsWhenInitialRequestValidationFails(): void
    {
        $request = $this->ajaxPostRequest(['entity' => '']);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->method('generateValidationRules')->willReturn(['entity' => ['rules' => ['required']]]);

        $validator = $this->createMock(ValidationInterface::class);
        $validator->method('getErrors')->willReturn(['entity' => 'error generate']);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse'], $validator);
        $controller->method('validateData')->willReturn(false);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertStringContainsString('error generate', $data['message']);

            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generate());
    }

    public function testGenerateInitialRequestUsesCrudContext(): void
    {
        $postData = [
            'entity' => 'users',
            'selected_columns' => ['uuid', 'name'],
        ];

        $request = $this->ajaxPostRequest($postData);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->method('generateValidationRules')->willReturn([]);
        $exportModel->expects($this->once())->method('generate')->with($postData, null, ExportModel::CONTEXT_CRUD)->willReturn([
            'result' => true,
            'isFinished' => false,
            'exportId' => self::EXPORT_ID,
            'processedCount' => 5,
        ]);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => true,
            'isFinished' => false,
            'exportId' => self::EXPORT_ID,
            'processedCount' => 5,
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generate());
    }

    public function testGenerateContinuationUsesOnlyExportId(): void
    {
        $postData = [
            'exportId' => self::EXPORT_ID,
            'entity' => 'forged',
            'processedCount' => '999',
        ];

        $request = $this->ajaxPostRequest($postData);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->never())->method('generateValidationRules');
        $exportModel->expects($this->once())->method('generate')->with([], self::EXPORT_ID, ExportModel::CONTEXT_CRUD)->willReturn([
            'result' => true,
            'isFinished' => true,
            'exportId' => self::EXPORT_ID,
            'processedCount' => 10,
        ]);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => true,
            'isFinished' => true,
            'exportId' => self::EXPORT_ID,
            'processedCount' => 10,
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generate());
    }

    public function testGenerateRejectsInvalidExportIdBeforeModelExecution(): void
    {
        $request = $this->ajaxPostRequest(['exportId' => 'invalid']);

        $validator = $this->createMock(ValidationInterface::class);
        $validator->method('getErrors')->willReturn(['exportId' => 'invalid export id']);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->never())->method('generate');

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse'], $validator);
        $controller->method('validateData')->willReturn(false);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertStringContainsString('invalid export id', $data['message']);

            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generate());
    }

    public function testGenerateDatabaseInitialRequestUsesDatabaseContext(): void
    {
        $postData = [
            'entity' => 'users',
            'selected_columns' => ['id', 'uuid'],
        ];

        $request = $this->ajaxPostRequest($postData);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->method('generateValidationRules')->willReturn([]);
        $exportModel->expects($this->once())->method('generate')->with($postData, null, ExportModel::CONTEXT_DATABASE)->willReturn([
            'result' => false,
            'message' => 'database-error',
        ]);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => false,
            'message' => 'database-error',
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generateDatabase());
    }

    public function testGenerateDatabaseContinuationUsesDatabaseContext(): void
    {
        $request = $this->ajaxPostRequest([
            'exportId' => self::EXPORT_ID,
        ]);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('generate')->with([], self::EXPORT_ID, ExportModel::CONTEXT_DATABASE)->willReturn([
            'result' => true,
            'isFinished' => true,
            'exportId' => self::EXPORT_ID,
        ]);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => true,
            'isFinished' => true,
            'exportId' => self::EXPORT_ID,
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->generateDatabase());
    }

    public function testRemoveRejectsNonAjaxRequest(): void
    {
        $request = $this->createMock(IncomingRequest::class);
        $request->method('isAJAX')->willReturn(false);

        $controller = $this->createController($request, $this->createMock(ExportModel::class), ['jsonResponse']);

        $this->assertSame(400, $controller->remove()->getStatusCode());
    }

    public function testRemoveRejectsMissingExportId(): void
    {
        $request = $this->ajaxPostRequest([]);

        $validator = $this->createMock(ValidationInterface::class);
        $validator->method('getErrors')->willReturn([
            'exportId' => 'The exportId field is required.',
        ]);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->never())->method('deleteExport');

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse'], $validator);
        $controller->method('validateData')->willReturn(false);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with($this->callback(function (array $data): bool {
            $this->assertFalse($data['result']);
            $this->assertStringContainsString('exportId', $data['message']);

            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->remove());
    }

    public function testRemoveDelegatesDeletionByExportId(): void
    {
        $request = $this->ajaxPostRequest([
            'exportId' => self::EXPORT_ID,
        ]);

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('deleteExport')->with(self::EXPORT_ID)->willReturn(true);

        $controller = $this->createController($request, $exportModel, ['validateData', 'jsonResponse']);
        $controller->method('validateData')->willReturn(true);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $controller->expects($this->once())->method('jsonResponse')->with([
            'result' => true,
        ])->willReturn($expectedResponse);

        $this->assertSame($expectedResponse, $controller->remove());
    }

    public function testDownloadThrowsExceptionIfExportIdIsInvalid(): void
    {
        $controller = $this->createController(
            $this->createMock(IncomingRequest::class),
            $this->createMock(ExportModel::class),
            []
        );

        $this->expectException(PageNotFoundException::class);

        $controller->download('invalid');
    }

    public function testDownloadThrowsExceptionIfExportCannotBeResolved(): void
    {
        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('getDownloadFile')->with(self::EXPORT_ID)->willReturn(null);

        $controller = $this->createController(
            $this->createMock(IncomingRequest::class),
            $exportModel,
            []
        );

        $this->expectException(PageNotFoundException::class);

        $controller->download(self::EXPORT_ID);
    }

    public function testDownloadReturnsDownloadResponseWithManifestFileName(): void
    {
        $filePath = WRITEPATH . 'exports/staging/' . self::EXPORT_ID . '/data.csv';
        $downloadName = 'export_users_08_10_2026_16_00_00.csv';

        $exportModel = $this->createMock(ExportModel::class);
        $exportModel->expects($this->once())->method('getDownloadFile')->with(self::EXPORT_ID)->willReturn([
            'path' => $filePath,
            'fileName' => $downloadName,
        ]);

        $controller = $this->createController($this->createMock(IncomingRequest::class), $exportModel, []);

        $expectedResponse = $this->createMock(ResponseInterface::class);

        $downloadResponse = $this->getMockBuilder(DownloadResponse::class)->disableOriginalConstructor()->onlyMethods(['setFileName'])->getMock();
        $downloadResponse->expects($this->once())->method('setFileName')->with($downloadName)->willReturn($expectedResponse);

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->onlyMethods(['download'])->getMock();
        $response->expects($this->once())->method('download')->with($filePath, null)->willReturn($downloadResponse);

        $this->injectResponse($controller, $response);

        $this->assertSame($expectedResponse, $controller->download(self::EXPORT_ID));
    }

    private function ajaxPostRequest(array $data): IncomingRequest
    {
        $request = $this->createMock(IncomingRequest::class);

        $request->method('isAJAX')->willReturn(true);
        $request->method('is')->with('post')->willReturn(true);
        $request->method('getPost')->willReturnCallback(static fn (?string $key = null) => $key === null ? $data : ($data[$key] ?? null));

        return $request;
    }

    private function createController(
        IncomingRequest $request,
        ExportModel $exportModel,
        array $methods,
        ?ValidationInterface $validator = null
    ): ExportController {
        $builder = $this->getMockBuilder(ExportController::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods);

        $controller = $builder->getMock();
        $response = $this->createMock(Response::class);

        $inject = function (
            ExportModel $model,
            IncomingRequest $request,
            Response $response,
            ?ValidationInterface $validator
        ): void {
            $this->exportModel = $model;
            $this->request = $request;
            $this->response = $response;

            if ($validator !== null):
                $this->validator = $validator;
            endif;
        };

        $binder = \Closure::bind($inject, $controller, ExportController::class);
        $binder($exportModel, $request, $response, $validator);

        return $controller;
    }

    private function injectResponse(ExportController $controller, Response $response): void
    {
        $inject = function (Response $response): void {
            $this->response = $response;
        };

        $binder = \Closure::bind($inject, $controller, ExportController::class);
        $binder($response);
    }
}