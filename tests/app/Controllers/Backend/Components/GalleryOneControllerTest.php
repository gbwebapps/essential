<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class GalleryOneControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testDeleteImageReturnsFalseWhenDeleteFails(): void
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
            'entity' => 'users'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'deleteImageValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'deleteImage'
        )
        ->with(
            $postData
        )
        ->willReturn(
            false
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
        ->deleteImage();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDeleteImageReturnsJsonResponseWithOutputOnSuccess(): void
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
            'entity' => 'users',
            'uuid' => 'test-uuid',
            'context' => 'profile',
            'filename' => 'image.jpg'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'deleteImageValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'deleteImage'
        )
        ->with(
            $postData
        )
        ->willReturn(
            true
        );

        $galleryModel
        ->method(
            'getImages'
        )
        ->with(
            $postData
        )
        ->willReturn(
            ['image1.jpg']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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

        $mockView = 
        $this
        ->createMock(
            \CodeIgniter\View\View::class
        );

        $mockView
        ->method(
            'render'
        )
        ->willReturn(
            'html_gallery_output'
        );

        \Config\Services::injectMock(
            'renderer', 
            $mockView
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
        ->deleteImage();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSetCoverReturnsFalseWhenSetFails(): void
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
            'entity' => 'users'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'setCover'
        )
        ->with(
            $postData
        )
        ->willReturn(
            false
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
        ->setCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSetCoverReturnsJsonResponseWithOutputOnSuccess(): void
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
            'entity' => 'users',
            'uuid' => 'test-uuid',
            'context' => 'profile'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'setCover'
        )
        ->with(
            $postData
        )
        ->willReturn(
            true
        );

        $galleryModel
        ->method(
            'getImages'
        )
        ->with(
            $postData
        )
        ->willReturn(
            ['image1.jpg']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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

        $mockView = 
        $this
        ->createMock(
            \CodeIgniter\View\View::class
        );

        $mockView
        ->method(
            'render'
        )
        ->willReturn(
            'html_gallery_output'
        );

        \Config\Services::injectMock(
            'renderer', 
            $mockView
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
        ->setCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testRemoveCoverReturnsFalseWhenRemoveFails(): void
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
            'entity' => 'users'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'removeCover'
        )
        ->with(
            $postData
        )
        ->willReturn(
            false
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
        ->removeCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testRemoveCoverReturnsJsonResponseWithOutputOnSuccess(): void
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
            'entity' => 'users',
            'uuid' => 'test-uuid',
            'context' => 'profile'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'removeCover'
        )
        ->with(
            $postData
        )
        ->willReturn(
            true
        );

        $galleryModel
        ->method(
            'getImages'
        )
        ->with(
            $postData
        )
        ->willReturn(
            ['image1.jpg']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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

        $mockView = 
        $this
        ->createMock(
            \CodeIgniter\View\View::class
        );

        $mockView
        ->method(
            'render'
        )
        ->willReturn(
            'html_gallery_output'
        );

        \Config\Services::injectMock(
            'renderer', 
            $mockView
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
        ->removeCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testShowGalleryReturnsValidationErrorsWhenValidationFails(): void
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

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'getImagesValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
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
            ['field' => 'error generate test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
                    'error generate test', 
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
        ->showGallery();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testShowGalleryReturnsJsonResponseWithOutputOnSuccess(): void
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
            'entity' => 'users',
            'uuid' => 'test-uuid',
            'context' => 'profile'
        ];

        $request
        ->method(
            'getPost'
        )
        ->willReturn(
            $postData
        );

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'getImagesValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        $galleryModel
        ->method(
            'getImages'
        )
        ->with(
            $postData
        )
        ->willReturn(
            ['image1.jpg']
        );

        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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

        $mockView = 
        $this
        ->createMock(
            \CodeIgniter\View\View::class
        );

        $mockView
        ->method(
            'render'
        )
        ->willReturn(
            'html_gallery_output'
        );

        \Config\Services::injectMock(
            'renderer', 
            $mockView
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
        ->showGallery();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testDeleteImageReturnsValidationErrorsWhenValidationFails(): void
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

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'deleteImageValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        /* Iniettiamo il mock del model nei Factories per isolare il costruttore */
        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
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
            ['field' => 'error delete test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
                    'error delete test', 
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
        ->deleteImage();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testSetCoverReturnsValidationErrorsWhenValidationFails(): void
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

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        /* Iniettiamo il mock del model nei Factories per isolare il costruttore */
        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
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
            ['field' => 'error setcover test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
                    'error setcover test', 
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
        ->setCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
    }

    public function testRemoveCoverReturnsValidationErrorsWhenValidationFails(): void
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

        $galleryModel = 
        $this
        ->createMock(
            \App\Models\Backend\Components\GalleryOneModel::class
        );

        $galleryModel
        ->method(
            'coverValidateFields'
        )
        ->willReturn(
            ['rule' => 'required']
        );

        /* Iniettiamo il mock del model nei Factories per isolare il costruttore */
        \CodeIgniter\Config\Factories::injectMock(
            'models', 
            \App\Models\Backend\Components\GalleryOneModel::class, 
            $galleryModel
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
            ['field' => 'error removecover test']
        );

        $controller = 
        $this
        ->getMockBuilder(
            \App\Controllers\Backend\Components\GalleryOneController::class
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
                    'error removecover test', 
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
        ->removeCover();

        $this
        ->assertSame(
            $expectedResponse, 
            $result
        );
	}

	public function testDeleteImageRejectsDifferentAdminGallery(): void
	{
		$postData = [
			'entity' => 'admins',
			'uuid' => 'target-uuid',
			'context' => 'profile',
			'filename' => 'image.jpg'
		];

		$request = $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
		$request->method('isAJAX')->willReturn(true);
		$request->method('is')->with('post')->willReturn(true);
		$request->method('getPost')->willReturn($postData);

		$galleryModel = $this->createMock(\App\Models\Backend\Components\GalleryOneModel::class);
		$galleryModel->method('deleteImageValidateFields')->willReturn(['rule' => 'required']);
		$galleryModel->expects($this->never())->method('deleteImage');
		\CodeIgniter\Config\Factories::injectMock('models', \App\Models\Backend\Components\GalleryOneModel::class, $galleryModel);

		$expectedResponse = $this->createMock(\CodeIgniter\HTTP\ResponseInterface::class);
		$controller = $this->getMockBuilder(\App\Controllers\Backend\Components\GalleryOneController::class)
			->onlyMethods(['validateData', 'jsonResponse'])
			->getMock();
		$controller->method('validateData')->willReturn(true);
		$controller->expects($this->once())->method('jsonResponse')
			->with($this->callback(fn(array $data): bool => $data['result'] === false), 403)
			->willReturn($expectedResponse);

		$inject = function() use ($request) {
			$this->request = $request;
			$this->currentAdmin = (object) ['uuid' => 'current-uuid', 'superadmin' => 0];
		};
		\Closure::bind($inject, $controller, $controller)();

		$this->assertSame($expectedResponse, $controller->deleteImage());
	}
}
