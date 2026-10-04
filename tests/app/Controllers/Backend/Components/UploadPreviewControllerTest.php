<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class UploadPreviewControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testConstructSetsCorrectProperties(): void
    {
        $controller = 
        new \App\Controllers\Backend\Components\UploadPreviewController();

        $closure = 
        function() {
            return 
            $this
            ->uploadPreview;
        };

        $uploadPreview = 
        \Closure::bind(
            $closure, 
            $controller, 
            $controller
        )();

        $this
        ->assertIsObject(
            $uploadPreview
        );
    }

    public function testSaveImagesReturnsFalseIfImagesEmpty(): void
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
            'getFileMultiple'
        )
        ->with(
            'images'
        )
        ->willReturn(
            null
        );

        $uploadPreviewModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\UploadPreviewModel::class
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\UploadPreviewModel::class, 
            $uploadPreviewModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\UploadPreviewController::class
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
        ->saveImages();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveImagesReturnsValidationToastErrorsForInvalidHiddenFields(): void
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

        $fileMock = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\Files\UploadedFile::class
        );

        $request
        ->method(
            'getFileMultiple'
        )
        ->with(
            'images'
        )
        ->willReturn(
            [$fileMock]
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['uuid' => 'invalid', 'entity' => 'users', 'context' => 'profile']
        );

        $uploadPreviewModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\UploadPreviewModel::class
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewHiddenRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewImagesRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\UploadPreviewModel::class, 
            $uploadPreviewModel
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
            ['field' => 'error hidden test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\UploadPreviewController::class
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
                    'error hidden test', 
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
        ->saveImages();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveImagesReturnsValidationErrorsForInvalidImagesFields(): void
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

        $fileMock = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\Files\UploadedFile::class
        );

        $request
        ->method(
            'getFileMultiple'
        )
        ->with(
            'images'
        )
        ->willReturn(
            [$fileMock]
        );

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            ['uuid' => 'test-uuid', 'entity' => 'users', 'context' => 'profile']
        );

        $uploadPreviewModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\UploadPreviewModel::class
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewHiddenRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewImagesRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\UploadPreviewModel::class, 
            $uploadPreviewModel
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
            ['field' => 'error images test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\UploadPreviewController::class
        )
        ->onlyMethods([
            'validateData',
            'jsonResponse'
        ])
        ->getMock();

        /* Prima chiamata per hidden passa, la seconda per images fallisce */
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
                    'imagesErrors', 
                    $data
                );

                $this
                ->assertEquals(
                    ['field' => 'error images test'], 
                    $data
                    ['imagesErrors']
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
        ->saveImages();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSaveImagesReturnsModelResultOnSuccess(): void
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

        $fileMock = 
        $this
        ->createMock(
            \CodeIgniter\HTTP\Files\UploadedFile::class
        );

        $request
        ->method(
            'getFileMultiple'
        )
        ->with(
            'images'
        )
        ->willReturn(
            [$fileMock]
        );

        $postData = [
            'uuid' => 'test-uuid', 
            'entity' => 'users', 
            'context' => 'profile'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $uploadPreviewModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\UploadPreviewModel::class
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewHiddenRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $uploadPreviewModel
        ->method(
            'uploadPreviewImagesRules'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $modelResult = [
            'result' => true,
            'message' => 'success message'
        ];

        $mergedData = 
        array_merge(
            $postData, 
            ['images' => [$fileMock]]
        );

        $uploadPreviewModel
        ->method(
            'saveImages'
        )
        ->with(
            $mergedData
        )
        ->willReturn(
            $modelResult
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\UploadPreviewModel::class, 
            $uploadPreviewModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\UploadPreviewController::class
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
        ->saveImages();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }
}