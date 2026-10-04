<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;

class GalleryOneModelTest extends CIUnitTestCase
{
	public function testGetImagesValidateFieldsReturnsArray()
	{
	    $modelClass
	        =
	        \App\Models\Backend\Components\GalleryOneModel::class;

	    $model
	        =
	        new $modelClass();

	    $result
	        =
	        $model
	        ->getImagesValidateFields();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'entity',
	            $result
	        );
	}

	public function testDeleteImageValidateFieldsReturnsArray()
	{
	    $modelClass
	        =
	        \App\Models\Backend\Components\GalleryOneModel::class;

	    $model
	        =
	        new $modelClass();

	    $result
	        =
	        $model
	        ->deleteImageValidateFields();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'id',
	            $result
	        );
	}

	public function testCoverValidateFieldsReturnsArray()
	{
	    $modelClass
	        =
	        \App\Models\Backend\Components\GalleryOneModel::class;

	    $model
	        =
	        new $modelClass();

	    $result
	        =
	        $model
	        ->coverValidateFields();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'uuid',
	            $result
	        );
	}

	public function testGetImagesReturnsDataArray()
	{
	    $modelClass
	        =
	        \App\Models\Backend\Components\GalleryOneModel::class;

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
	            'entity'
	            =>
	            'posts',
	            'uuid'
	            =>
	            '123e4567-e89b-12d3-a456-426614174000'
	        ];

	    $model
	        ->method(
	            'checkAllowedFields'
	        )
	        ->willReturn(
	            $posts
	        );

	    $dbClass
	        =
	        \CodeIgniter\Database\BaseConnection::class;

	    $dbMock
	        =
	        $this
	        ->createMock(
	            $dbClass
	        );

	    $resClass
	        =
	        \CodeIgniter\Database\BaseResult::class;

	    $resMock
	        =
	        $this
	        ->createMock(
	            $resClass
	        );

	    $expectedData
	        =
	        [
	            [
	                'id'
	                =>
	                1,
	                'filename'
	                =>
	                'test.jpg',
	                'is_cover'
	                =>
	                1
	            ]
	        ];

	    $resMock
	        ->method(
	            'getResultArray'
	        )
	        ->willReturn(
	            $expectedData
	        );

	    $dbMock
	        ->method(
	            'query'
	        )
	        ->willReturn(
	            $resMock
	        );

	    $injectDb
	        =
	        function (
	            $connection
	        ) {
	            $target
	                =
	                $this;

	            $target
	                ->db
	                =
	                $connection;
	        };

	    $binder
	        =
	        \Closure::bind(
	            $injectDb,
	            $model,
	            $modelClass
	        );

	    $binder(
	        $dbMock
	    );

	    $result
	        =
	        $model
	        ->getImages(
	            $posts
	        );

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertSame(
	            $expectedData,
	            $result
	        );
	}

	public function testDeleteImageSuccessReturnsTrue()
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
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                10,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'filename'
                =>
                'image.jpg'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'affectedRows'
            )
            ->willReturn(
                1
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->deleteImage(
                $posts
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testDeleteImageFailsWhenAffectedRowsIsZero()
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
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                99,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'filename'
                =>
                'missing.jpg'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'affectedRows'
            )
            ->willReturn(
                0
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->deleteImage(
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSetCoverSuccessReturnsTrue()
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
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                10,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transBegin'
            );

        $dbMock
            ->expects(
                $this
                ->exactly(
                    2
                )
            )
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transStatus'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transCommit'
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->setCover(
                $posts
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testSetCoverRollbackOnTransStatusFalseReturnsFalse()
    {
        $modelClass
            =
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                10,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transBegin'
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transStatus'
            )
            ->willReturn(
                false
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transRollback'
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->setCover(
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSetCoverCatchBlockReturnsFalseOnException()
    {
        $modelClass
            =
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                10,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000'
            ];

        /* Lanciamo un'eccezione intenzionale nel blocco try */
        $model
            ->method(
                'checkAllowedFields'
            )
            ->will(
                $this
                ->throwException(
                    new \Exception(
                        'Simulated Exception'
                    )
                )
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        /* Il blocco catch deve chiamare il rollback */
        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transRollback'
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->setCover(
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testRemoveCoverSuccessReturnsTrue()
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
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                10,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'affectedRows'
            )
            ->willReturn(
                1
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->removeCover(
                $posts
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testRemoveCoverFailsWhenAffectedRowsIsZero()
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
            \App\Models\Backend\Components\GalleryOneModel::class;

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
                'id'
                =>
                99,
                'entity'
                =>
                'posts',
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'affectedRows'
            )
            ->willReturn(
                0
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $connection;
            };

        $binder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->removeCover(
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }
}
