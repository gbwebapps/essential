<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class ExportControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testConstructSetsCorrectProperties(): void
    {
        $controller = 
        new \App\Controllers\Backend\Components\ExportController();

        $closure = 
        function() {
            return 
            $this
            ->exportModel;
        };

        $exportModel = 
        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $this
        ->assertIsObject(
            $exportModel
        );
    }

    public function testShowModalReturnsValidationErrorsForInvalidData(): void
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
            ['entity' => '']
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
            ['entity' => 'error message']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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
        ->showModal();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testShowModalReturnsJsonResponseWithOutputOnSuccess(): void
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
            ['entity' => 'users']
        );

        $exportModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\ExportModel::class
        );

        $exportModel
        ->method(
            'getExportColumns'
        )
        ->with(
            'users'
        )
        ->willReturn(
            ['id', 'name']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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

                return 
                $expectedResponse;
            }
        );

        $closure = 
        function() use (
            $request,
            $exportModel
        ) {
            $this
            ->request = 
            $request;

            $this
            ->exportModel = 
            $exportModel;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->showModal();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGenerateReturnsValidationErrorsWhenValidationFails(): void
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

        $exportModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\ExportModel::class
        );

        $exportModel
        ->method(
            'generateValidationRules'
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
            ['field' => 'error generate']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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
                    'error generate', 
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
            $exportModel,
            $validator
        ) {
            $this
            ->request = 
            $request;

            $this
            ->exportModel = 
            $exportModel;

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
        ->generate();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGenerateReturnsResultDirectlyWhenFinishedOrError(): void
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
            'lastId' => '',
            'fileName' => 'test.csv',
            'processedCount' => '0'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturnCallback(
            function(
                $key = null
            ) use (
                $postData
            ) {
                if (
                    $key === 
                    null
                ):
                    return 
                    $postData;
                endif;

                return 
                $postData
                [$key] ?? 
                null;
            }
        );

        $exportModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\ExportModel::class
        );

        $exportModel
        ->method(
            'generateValidationRules'
        )
        ->willReturn(
            []
        );

        $exportResult = [
            'result' => true,
            'isFinished' => true,
            'fileName' => 'test.csv'
        ];

        $exportModel
        ->method(
            'generate'
        )
        ->willReturn(
            $exportResult
        );

        /* Inietta il mock nei Factories di CodeIgniter PRIMA di creare il controller */
        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\ExportModel::class, 
            $exportModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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
                ->assertTrue(
                    $data
                    ['isFinished']
                );

                $this
                ->assertEquals(
                    'test.csv', 
                    $data
                    ['fileName']
                );

                return 
                $expectedResponse;
            }
        );

        /* Assegna solo la request tramite Closure, il model è già mockato dai Factories */
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
        ->generate();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testGenerateReturnsProgressDataWhenNotFinished(): void
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
            'lastId' => '100',
            'fileName' => 'test.csv',
            'processedCount' => '50'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturnCallback(
            function(
                $key = null
            ) use (
                $postData
            ) {
                if (
                    $key === 
                    null
                ):
                    return 
                    $postData;
                endif;

                return 
                $postData
                [$key] ?? 
                null;
            }
        );

        $exportModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\ExportModel::class
        );

        $exportModel
        ->method(
            'generateValidationRules'
        )
        ->willReturn(
            []
        );

        $exportResult = [
            'result' => true,
            'isFinished' => false,
            'lastId' => 200,
            'fileName' => 'test.csv',
            'chunkSize' => 50
        ];

        $exportModel
        ->method(
            'generate'
        )
        ->willReturn(
            $exportResult
        );

        /* Inietta il mock nei Factories di CodeIgniter PRIMA di creare il controller */
        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\ExportModel::class, 
            $exportModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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
                ->assertFalse(
                    $data
                    ['isFinished']
                );

                $this
                ->assertEquals(
                    200, 
                    $data
                    ['lastId']
                );

                $this
                ->assertEquals(
                    100, 
                    $data
                    ['processedCount']
                );

                return 
                $expectedResponse;
            }
        );

        /* Assegna solo la request tramite Closure */
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
        ->generate();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testRemoveDeletesFileAndReturnsTrue(): void
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

        $fileName = 
        'test_remove.csv';

        $request
        ->method(
            'getPost'
        )
        ->with(
            'fileName'
        )
        ->willReturn(
            $fileName
        );

        $path = 
        WRITEPATH . 
        'exports/';

        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;

        $fullPath = 
        $path . 
        $fileName;

        file_put_contents(
            $fullPath, 
            'dummy content'
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\ExportController::class
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
        ->remove();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );

        $this
        ->assertFileDoesNotExist(
            $fullPath
        );
    }

    public function testDownloadThrowsExceptionIfFileNameIsEmpty(): void
    {
        $controller = 
        new \App\Controllers\Backend\Components\ExportController();

        $this
        ->expectException(
            \CodeIgniter\Exceptions\PageNotFoundException::class
        );

        $controller
        ->download(
            ''
        );
    }

    public function testDownloadThrowsExceptionIfFileDoesNotExist(): void
    {
        $controller = 
        new \App\Controllers\Backend\Components\ExportController();

        $this
        ->expectException(
            \CodeIgniter\Exceptions\PageNotFoundException::class
        );

        $controller
        ->download(
            'non_existent_file.csv'
        );
    }

    public function testDownloadReturnsDownloadResponseOnSuccess(): void
    {
        $fileName = 
        'test_download.csv';
        
        $path = 
        WRITEPATH . 
        'exports/';

        if (! is_dir(
            $path
        )):
            mkdir(
                $path, 
                0775, 
                true
            );
        endif;

        $fullPath = 
        $path . 
        $fileName;

        file_put_contents(
            $fullPath, 
            'dummy download'
        );

        $controller = 
        new \App\Controllers\Backend\Components\ExportController();

        $expectedResponse = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $responseMock = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\ResponseInterface::class
        );

        $responseMock
        ->method(
            'download'
        )
        ->with(
            $fullPath, 
            null
        )
        ->willReturn(
            $expectedResponse
        );

        $closure = 
        function() use (
            $responseMock
        ) {
            $this
            ->response = 
            $responseMock;
        };

        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $result = 
        $controller
        ->download(
            $fileName
        );

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );

        /* Pulizia */
        unlink(
            $fullPath
        );
    }
}