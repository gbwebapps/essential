<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;

class UploadPreviewModelTest extends CIUnitTestCase
{
	public function testUploadPreviewHiddenRulesReturnsArray()
    {
        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            new $modelClass();

        $result
            =
            $model
            ->uploadPreviewHiddenRules();

        $this
            ->assertSame(
                [
                    'entity' => [
                        'label' => lang('backend/components/uploadPreviewImg.labels.entity'),
                        'rules' => ['required', 'alpha']
                    ],
                    'uuid' => [
                        'label' => lang('backend/components/uploadPreviewImg.labels.uuid'),
                        'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']
                    ],
                    'context' => [
                        'label' => lang('backend/components/uploadPreviewImg.labels.context'),
                        'rules' => ['required', 'in_list[edit,show]']
                    ]
                ],
                $result
            );
    }

    public function testUploadPreviewImagesRulesReturnsArray()
    {
        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            new $modelClass();

        $result
            =
            $model
            ->uploadPreviewImagesRules();

        $this
            ->assertSame(
                [
                    'images' => [
                        'rules' => ['checkImages[size:2048,ext:png|jpg|jpeg|webp]']
                    ]
                ],
                $result
            );
    }

	public function testSaveImagesEmptyImagesReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'images'
                =>
                []
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $result
            =
            $model
            ->saveImages(
                $posts
            );

        $this
            ->assertSame(
                [
                    'result' => false,
                    'message' => lang('backend/components/uploadPreviewImg.messages.saveImagesError')
                ],
                $result
            );
    }

    public function testSaveImagesCatchBlockReturnsErrorOnException()
    {
        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getUploadService'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'entity'
                =>
                'test',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'context'
                =>
                'edit',
                'images'
                =>
                [
                    'fake_payload'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $uploadClass
            =
            \App\Libraries\Backend\UploadClass::class;

        $uploadMock
            =
            $this
            ->createMock(
                $uploadClass
            );

        $uploadMock
            ->method(
                'doUpload'
            )
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Upload failed'
                    )
                )
            );

        $model
            ->method(
                'getUploadService'
            )
            ->willReturn(
                $uploadMock
            );

        $result
            =
            $model
            ->saveImages(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['result']
            );
    }

    public function testSaveImagesExecutesUploadFlowAndReturnsArray()
    {
        if (
            ! function_exists(
                __NAMESPACE__ . '\log_admin_activity'
            )
        ):
            function log_admin_activity(
                string $action,
                string $entity,
                string $message,
                ?object $admin = null
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getUploadService',
                    'insertImages'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'entity'
                =>
                'test',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'context'
                =>
                'edit',
                'images'
                =>
                [
                    'fake_image_payload'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $model
            ->method(
                'getUploadService'
            )
            ->willReturn(
                $uploadMock = $this
                    ->createMock(
                        \App\Libraries\Backend\UploadClass::class
                    )
            );

        $uploadMock
            ->expects(
                $this
                    ->once()
            )
            ->method(
                'doUpload'
            )
            ->with(
                $posts['images'],
                $posts['entity'],
                $posts['uuid']
            )
            ->willReturn(
                [
                    'preview.jpg'
                ]
            );

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'insertImages'
            )
            ->with(
                ['preview.jpg'],
                $posts['uuid'],
                $posts['entity'],
                $posts['context']
            )
            ->willReturn(
                true
            );

        $result
            =
            $model
            ->saveImages(
                $posts
            );

        $this
            ->assertSame(
                [
                    'result' => true,
                    'message' => lang('backend/components/uploadPreviewImg.messages.saveImagesSuccess')
                ],
                $result
            );
    }

    public function testSaveImagesReturnsErrorWhenInsertImagesFails()
    {
        $modelClass
            =
            \App\Models\Backend\Components\UploadPreviewModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getUploadService',
                    'insertImages'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'entity'
                =>
                'test_entity',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'context'
                =>
                'edit',
                'images'
                =>
                [
                    'fake_image_data'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $uploadClass
            =
            \App\Libraries\Backend\UploadClass::class;

        $uploadMock
            =
            $this
            ->createMock(
                $uploadClass
            );

        /* Facciamo in modo che l'upload abbia successo per entrare nell'if */
        $uploadMock
            ->method(
                'doUpload'
            )
            ->willReturn(
                [
                    'file1.jpg',
                    'file2.png'
                ]
            );

        $model
            ->method(
                'getUploadService'
            )
            ->willReturn(
                $uploadMock
            );

        /* Facciamo fallire l'inserimento nel database per innescare il blocco rosso */
        $model
            ->method(
                'insertImages'
            )
            ->willReturn(
                false
            );

        $result
            =
            $model
            ->saveImages(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['result']
            );
    }
}
