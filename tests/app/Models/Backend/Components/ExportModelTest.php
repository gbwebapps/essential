<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;

class ExportModelTest extends CIUnitTestCase
{
	public function testGenerateValidationRulesReturnsArray()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

        $result
            =
            $model
            ->generateValidationRules();

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

    public function testGetExportColumnsReturnsEmptyArrayIfTableDoesNotExist()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

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
                'tableExists'
            )
            ->willReturn(
                false
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
            ->getExportColumns(
                'fake_table'
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertEmpty(
                $result
            );
    }

    public function testGetExportColumnsFiltersPrimaryKeyAndIdAndReturnsColumns()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $field1
            =
            new \stdClass();

        $field1
            ->name
            =
            'id';

        $field1
            ->primary_key
            =
            1;

        $field2
            =
            new \stdClass();

        $field2
            ->name
            =
            'uuid';

        $field2
            ->primary_key
            =
            0;

        $field3
            =
            new \stdClass();

        $field3
            ->name
            =
            'pk_field';

        $field3
            ->primary_key
            =
            1;

        $field4
            =
            new \stdClass();

        $field4
            ->name
            =
            'title';

        $field4
            ->primary_key
            =
            0;

        $dbMock
            ->method(
                'getFieldData'
            )
            ->willReturn(
                [
                    $field1,
                    $field2,
                    $field3,
                    $field4
                ]
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
            ->getExportColumns(
                'real_table'
            );

        $this
            ->assertIsArray(
                $result
            );

        $expected
            =
            [
                'uuid',
                'title'
            ];

        $this
            ->assertSame(
                $expected,
                $result
            );
    }

    public function testGetPrimaryKeyReturnsNullIfTableDoesNotExist()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

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
                'tableExists'
            )
            ->willReturn(
                false
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
            ->getPrimaryKey(
                'fake_table'
            );

        $this
            ->assertNull(
                $result
            );
    }

    public function testGetPrimaryKeyReturnsFieldNameWhenFound()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $field1
            =
            new \stdClass();

        $field1
            ->name
            =
            'normal_field';

        $field1
            ->primary_key
            =
            0;

        $field2
            =
            new \stdClass();

        $field2
            ->name
            =
            'the_pk';

        $field2
            ->primary_key
            =
            1;

        $dbMock
            ->method(
                'getFieldData'
            )
            ->willReturn(
                [
                    $field1,
                    $field2
                ]
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
            ->getPrimaryKey(
                'real_table'
            );

        $this
            ->assertSame(
                'the_pk',
                $result
            );
    }

    public function testGetPrimaryKeyReturnsNullWhenNotFound()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            new $modelClass();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $field1
            =
            new \stdClass();

        $field1
            ->name
            =
            'normal_field';

        $field1
            ->primary_key
            =
            0;

        $dbMock
            ->method(
                'getFieldData'
            )
            ->willReturn(
                [
                    $field1
                ]
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
            ->getPrimaryKey(
                'real_table'
            );

        $this
            ->assertNull(
                $result
            );
    }

    public function testGenerateReturnsErrorWhenEntityIsInvalid()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

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
                'tableExists'
            )
            ->willReturn(
                false
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

        $posts
            =
            [
                'entity'
                =>
                'invalid_table'
            ];

        $result
            =
            $model
            ->generate(
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

    public function testGenerateReturnsErrorWhenIdColumnIsMissing()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'uuid',
                    'title'
                ]
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

        $posts
            =
            [
                'entity'
                =>
                'real_table'
            ];

        $result
            =
            $model
            ->generate(
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

    public function testGenerateReturnsErrorWhenNoColumnsSelected()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title'
                ]
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

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                []
            ];

        $result
            =
            $model
            ->generate(
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

    public function testGenerateReturnsErrorWhenSelectedColumnsAreInvalid()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title'
                ]
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

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'fake_column'
                ]
            ];

        $result
            =
            $model
            ->generate(
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

    public function testGenerateReturnsErrorWhenNoDataFoundForNewExport()
    {
        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir() . '/'
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title'
                ]
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

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                []
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                null
            );

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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
            ->generate(
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

    public function testGenerateFirstChunkReturnsNotFinished()
    {
        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir() . '/'
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title'
                ]
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

        $record1
            =
            [
                'id'
                =>
                1,
                'title'
                =>
                'Row 1'
            ];
            
        $record2
            =
            [
                'id'
                =>
                2,
                'title'
                =>
                'Row 2'
            ];
            
        $record3
            =
            [
                'id'
                =>
                3,
                'title'
                =>
                'Row 3'
            ];
            
        $record4
            =
            [
                'id'
                =>
                4,
                'title'
                =>
                'Row 4'
            ];
            
        $record5
            =
            [
                'id'
                =>
                5,
                'title'
                =>
                'Row 5'
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                [
                    $record1,
                    $record2,
                    $record3,
                    $record4,
                    $record5
                ]
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                null
            );

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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
            ->generate(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertTrue(
                $result['result']
            );

        $this
            ->assertFalse(
                $result['isFinished']
            );
            
        $this
            ->assertSame(
                5,
                $result['lastId']
            );
    }

    public function testGenerateFinalChunkAppliesFiltersAndReturnsFinished()
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

        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir() . '/'
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title',
                    'created_at',
                    'deleted_at'
                ]
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

        $record1
            =
            [
                'id'
                =>
                11,
                'title'
                =>
                'Row 11'
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                [
                    $record1
                ]
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                null
            );

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ],
                'title'
                =>
                'search_text',
                'created_at-from'
                =>
                '2024-01-01',
                'created_at-to'
                =>
                '2024-12-31',
                'trash_filter'
                =>
                'active'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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

        $currentAdminObj
            =
            new \stdClass();

        $currentAdminObj
            ->uuid
            =
            '123';

		$currentAdminObj
            ->email
            =
            'aaa@aaa.com';

        $stdClass
            =
            \stdClass::class;

        $authMock
            =
            $this
            ->getMockBuilder(
                $stdClass
            )
            ->addMethods(
                [
                    'currentAdmin'
                ]
            )
            ->getMock();

        $authMock
            ->method(
                'currentAdmin'
            )
            ->willReturn(
                $currentAdminObj
            );

        \Config\Services::injectMock(
            'authorization',
            $authMock
        );

        $result
            =
            $model
            ->generate(
                $posts,
                10,
                'existing_export.csv'
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertTrue(
                $result['result']
            );

        $this
            ->assertTrue(
                $result['isFinished']
            );
    }

    public function testGenerateIncludesPrimaryKeyWhenNotNull()
    {
        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir()
                .
                '/'
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'uuid',
                    'title'
                ]
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

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                []
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        /* Forza l'ingresso nel blocco if ($primaryKey !== null) */
        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                'uuid'
            );

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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
            ->generate(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );
    }

    public function testGenerateAppliesTrashedFilter()
    {
        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir()
                .
                '/'
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title',
                    'deleted_at'
                ]
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

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                []
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                null
            );

        /* Forza l'ingresso nel blocco elseif ($posts['trash_filter'] === 'trashed') */
        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ],
                'trash_filter'
                =>
                'trashed'
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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
            ->generate(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );
    }

    public function testGenerateCreatesExportDirectoryWhenMissing()
    {
        if (
            ! defined(
                'WRITEPATH'
            )
        ):
            define(
                'WRITEPATH',
                sys_get_temp_dir()
                .
                '/'
            );
        endif;

        $exportDir
            =
            WRITEPATH
            .
            'exports';

        /* Distruggiamo la cartella per forzare il mkdir() */
        if (
            is_dir(
                $exportDir
            )
        ):
            $files
                =
                glob(
                    $exportDir
                    .
                    '/*'
                );

            if (
                $files
                !==
                false
            ):
                foreach (
                    $files
                    as
                    $file
                ):
                    if (
                        is_file(
                            $file
                        )
                    ):
                        unlink(
                            $file
                        );
                    endif;
                endforeach;
            endif;

            rmdir(
                $exportDir
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ExportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getPrimaryKey'
                ]
            )
            ->getMock();

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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $dbMock
            ->method(
                'getFieldNames'
            )
            ->willReturn(
                [
                    'id',
                    'title'
                ]
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

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                []
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $model
            ->method(
                'getPrimaryKey'
            )
            ->willReturn(
                null
            );

        $posts
            =
            [
                'entity'
                =>
                'real_table',
                'selected_columns'
                =>
                [
                    'title'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
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

        $model
            ->generate(
                $posts
            );

        /* Verifichiamo che il metodo abbia effettivamente ricreato la directory */
        $this
            ->assertTrue(
                is_dir(
                    $exportDir
                )
            );
    }
}
