<?php declare(strict_types = 1);

namespace App\Models\Backend;

class GroupsModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
	protected function setUp(): void
    {
        parent::setUp();

        $modelClass = \App\Models\Backend\GroupsModel::class;

        $model = new $modelClass();

        $this->model = $model;
    }

    public function testAddValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->addValidationRules();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'name',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'description',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'permissions.*',
	            $result
	        );
	}

    public function testGetGroupByIdValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->getGroupByIdValidationRules();

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

    public function testEditValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $posts
	        =
	        [
	            'id'
	            =>
	            1
	        ];

	    $result
	        =
	        $model
	        ->editValidationRules(
	            $posts
	        );

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'id',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'name',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'description',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'permissions.*',
	            $result
	        );
	}

    public function testDelValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->delValidationRules();

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

    public function testSaveExceptionsValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->saveExceptionsValidationRules();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'uuid',
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'permissions.*',
	            $result
	        );
	}

    public function testDropdownAdminsRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->dropdownAdminsRules();

	    $this
	        ->assertIsArray(
	            $result
	        );

	    $this
	        ->assertArrayHasKey(
	            'query',
	            $result
	        );
	}

    public function testAdminPermissionsValidationRulesReturnsExpectedArray()
    {
	    $model
	        =
	        $this
	        ->model;

	    $result
	        =
	        $model
	        ->adminPermissionsValidationRules();

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

	public function testGetGroupsReturnsArrayOfObjectsOnSuccess()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $fakeGroup
            =
            new \stdClass();

        $fakeGroup
            ->id
            =
            1;

        $fakeGroup
            ->name
            =
            'Administrators';

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $resMock
            ->method('getResult')
            ->willReturn(
                [
                    $fakeGroup
                ]
            );

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getGroups();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertNotEmpty(
                $result
            );
    }

    public function testGetGroupsReturnsEmptyArrayOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
            ->method('query')
            ->will(
                $this
                ->throwException(
                    new \Exception(
                        'Database error'
                    )
                )
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getGroups();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertEmpty(
                $result
            );
    }

    public function testGetGroupReturnsPermissionsColumnArray()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
            ->method('getResultArray')
            ->willReturn(
                [
                    ['permission' => 'manage_users'],
                    ['permission' => 'manage_settings']
                ]
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getGroup(
                1
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertSame(
                [
                    'manage_users',
                    'manage_settings'
                ],
                $result
            );
    }

    public function testGetGroupByIdReturnsGroupObject()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                1
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

        $fakeObj
            =
            new \stdClass();

        $fakeObj
            ->id
            =
            1;

        $fakeObj
            ->name
            =
            'Test Group';

        $resMock
            ->method('getRow')
            ->willReturn(
                $fakeObj
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getGroupById(
                $posts
            );

        $this
            ->assertIsObject(
                $result
            );

        $this
            ->assertSame(
                1,
                $result->id
            );
    }

    public function testGetAdminByUuidReturnsAdminRowArray()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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

        $resMock
            ->method('getRowArray')
            ->willReturn(
                [
                    'uuid'
                    =>
                    '123e4567-e89b-12d3-a456-426614174000',
                    'group_id'
                    =>
                    1,
                    'name'
                    =>
                    'Admins'
                ]
            );

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getAdminByUuid(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'group_id',
                $result
            );
    }

    public function testGetAdminExceptionsArrayReturnsIndexedArray()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
            ->method('getResultArray')
            ->willReturn(
                [
                    [
                        'permission'
                        =>
                        'edit_settings',
                        'allow'
                        =>
                        1
                    ]
                ]
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getAdminExceptionsArray(
                'some-uuid'
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'edit_settings',
                $result
            );

        $this
            ->assertSame(
                1,
                $result['edit_settings']
            );
    }

    public function testGetGroupPermissionsArrayReturnsColumnArray()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
            ->method('getResultArray')
            ->willReturn(
                [
                    [
                        'permission'
                        =>
                        'manage_users'
                    ]
                ]
            );

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->getGroupPermissionsArray(
                1
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertSame(
                [
                    'manage_users'
                ],
                $result
            );
    }

    public function testHasAdminsAttachedReturnsTrueWhenTotalGreaterThanZero()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $fakeTotal
            =
            new \stdClass();

        $fakeTotal
            ->total
            =
            3;

        $resMock
            ->method('getRow')
            ->willReturn(
                $fakeTotal
            );

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $injectDb
            =
            function (
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->hasAdminsAttached(
                1
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testAddSuccessWithPermissions()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'name'
                =>
                'Editors',
                'description'
                =>
                'Editor Group',
                'permissions'
                =>
                [
                    'edit_posts'
                ]
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
                ->any()
            )
            ->method(
                'query'
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'insertID'
            )
            ->willReturn(
                5
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
                $d
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $d;
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
                null
            );

        \Config\Services::injectMock(
            'authorization',
            $authMock
        );

        $result
            =
            $model
            ->add(
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
    }

    public function testAddRollbackWhenTransStatusIsFalse()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'name'
                =>
                'Editors',
                'description'
                =>
                'Editor Group'
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
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->add(
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

    public function testAddRollbackOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'name'
                =>
                'Editors'
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
            )
            ->will(
                $this
                ->throwException(
                    new \Exception(
                        'Database connection error'
                    )
                )
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
                $d
            ) {
                $this
                    ->db
                    =
                    $d;
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
            ->add(
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

    public function testSaveExceptionsAdminNotFoundReturnsError()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
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

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                null
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
                $d
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $d;
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
            ->saveExceptions(
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

    public function testSaveExceptionsAdminDeletedReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $adminObj
            ->superadmin
            =
            0;

        $adminObj
            ->deleted_at
            =
            '2026-01-01 00:00:00';

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
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
                $d
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $d;
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
            ->saveExceptions(
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

    public function testSaveExceptionsSuperadminReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Super';

        $adminObj
            ->lastname
            =
            'Admin';

        $adminObj
            ->superadmin
            =
            1;

        $adminObj
            ->deleted_at
            =
            null;

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
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
                $d
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $d;
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
            ->saveExceptions(
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

    public function testSaveExceptionsNoDataChangedReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupPermissionsArray',
                    'getAdminExceptionsArray'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
                =>
                [
                    'view_dashboard'
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
                'getGroupPermissionsArray'
            )
            ->willReturn(
                [
                    'view_dashboard'
                ]
            );

        $model
            ->method(
                'getAdminExceptionsArray'
            )
            ->willReturn(
                []
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $adminObj
            ->superadmin
            =
            0;

        $adminObj
            ->deleted_at
            =
            null;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
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
                $d
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $d;
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
            ->saveExceptions(
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

    public function testHasGroupChangedReturnsTrueWhenBaseDataChanged()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'hasDataChanged',
                    'getGroup'
                ]
            )
            ->getMock();

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'hasDataChanged'
            )
            ->willReturn(
                true
            );

        $posts
            =
            [
                'name'
                =>
                'New Name'
            ];

        $original
            =
            new \stdClass();

        $original
            ->id
            =
            1;

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'hasGroupChanged'
            );

        $reflection
            ->setAccessible(
                true
            );

        $result
            =
            $reflection
            ->invokeArgs(
                $model,
                [
                    $posts,
                    $original
                ]
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasGroupChangedReturnsTrueWhenPermissionsChanged()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'hasDataChanged',
                    'getGroup'
                ]
            )
            ->getMock();

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'hasDataChanged'
            )
            ->willReturn(
                false
            );

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'getGroup'
            )
            ->willReturn(
                [
                    'perm_one'
                ]
            );

        $posts
            =
            [
                'permissions'
                =>
                [
                    'perm_two'
                ]
            ];

        $original
            =
            new \stdClass();

        $original
            ->id
            =
            1;

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'hasGroupChanged'
            );

        $reflection
            ->setAccessible(
                true
            );

        $result
            =
            $reflection
            ->invokeArgs(
                $model,
                [
                    $posts,
                    $original
                ]
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasGroupChangedReturnsFalseWhenNothingChanged()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'hasDataChanged',
                    'getGroup'
                ]
            )
            ->getMock();

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'hasDataChanged'
            )
            ->willReturn(
                false
            );

        $model
            ->expects(
                $this
                ->once()
            )
            ->method(
                'getGroup'
            )
            ->willReturn(
                [
                    'perm_one'
                ]
            );

        $posts
            =
            [
                'permissions'
                =>
                [
                    'perm_one'
                ]
            ];

        $original
            =
            new \stdClass();

        $original
            ->id
            =
            1;

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'hasGroupChanged'
            );

        $reflection
            ->setAccessible(
                true
            );

        $result
            =
            $reflection
            ->invokeArgs(
                $model,
                [
                    $posts,
                    $original
                ]
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testEditGroupNotFoundReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupById'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                999,
                'name'
                =>
                'Updated Group',
                'description'
                =>
                'Desc',
                'permissions'
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

        $model
            ->method(
                'getGroupById'
            )
            ->willReturn(
                null
            );

        $result
            =
            $model
            ->edit(
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

    public function testEditNoDataChangedReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupById',
                    'hasGroupChanged'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                1,
                'name'
                =>
                'Editors',
                'description'
                =>
                'Editor Group',
                'permissions'
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

        $originalGroup
            =
            new \stdClass();

        $originalGroup
            ->id
            =
            1;

        $model
            ->method(
                'getGroupById'
            )
            ->willReturn(
                $originalGroup
            );

        $model
            ->method(
                'hasGroupChanged'
            )
            ->willReturn(
                false
            );

        $result
            =
            $model
            ->edit(
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

    public function testEditSuccessWithPermissions()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupById',
                    'hasGroupChanged'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                1,
                'name'
                =>
                'Editors Updated',
                'description'
                =>
                'Updated Description',
                'permissions'
                =>
                [
                    'edit_posts',
                    'delete_posts'
                ]
            ];

        $model
            ->method(
                'checkAllowedFields'
            )
            ->willReturn(
                $posts
            );

        $originalGroup
            =
            new \stdClass();

        $originalGroup
            ->id
            =
            1;

        $model
            ->method(
                'getGroupById'
            )
            ->willReturn(
                $originalGroup
            );

        $model
            ->method(
                'hasGroupChanged'
            )
            ->willReturn(
                true
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
                ->any()
            )
            ->method(
                'query'
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
                $this
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

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
                $adminObj
            );

        \Config\Services::injectMock(
            'authorization',
            $authMock
        );

        $result
            =
            $model
            ->edit(
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
    }

    public function testEditRollbackOnTransStatusFalse()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupById',
                    'hasGroupChanged'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                1,
                'name'
                =>
                'Editors Updated',
                'description'
                =>
                'Desc',
                'permissions'
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

        $originalGroup
            =
            new \stdClass();

        $originalGroup
            ->id
            =
            1;

        $model
            ->method(
                'getGroupById'
            )
            ->willReturn(
                $originalGroup
            );

        $model
            ->method(
                'hasGroupChanged'
            )
            ->willReturn(
                true
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
                ->any()
            )
            ->method(
                'query'
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
                $this
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
            ->edit(
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

    public function testEditCatchBlockReturnsErrorOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupById',
                    'hasGroupChanged'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                1,
                'name'
                =>
                'Editors Updated',
                'description'
                =>
                'Desc',
                'permissions'
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

        $originalGroup
            =
            new \stdClass();

        $originalGroup
            ->id
            =
            1;

        $model
            ->method(
                'getGroupById'
            )
            ->willReturn(
                $originalGroup
            );

        $model
            ->method(
                'hasGroupChanged'
            )
            ->willReturn(
                true
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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $this
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
            ->edit(
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

    // public function testDelSuccess()
    // {
    //     if (
    //         ! function_exists(
    //             __NAMESPACE__ . '\log_admin_activity'
    //         )
    //     ):
    //         function log_admin_activity(
    //             string $action,
    //             string $entity,
    //             string $message,
    //             ?object $admin
    //         ): void
    //         {
    //         }
    //     endif;

    //     $modelClass
    //         =
    //         \App\Models\Backend\GroupsModel::class;

    //     $model
    //         =
    //         $this
    //         ->getMockBuilder(
    //             $modelClass
    //         )
    //         ->onlyMethods(
    //             [
    //                 'checkAllowedFields'
    //             ]
    //         )
    //         ->getMock();

    //     $posts
    //         =
    //         [
    //             'id'
    //             =>
    //             1
    //         ];

    //     $model
    //         ->method(
    //             'checkAllowedFields'
    //         )
    //         ->willReturn(
    //             $posts
    //         );

    //     $dbClass
    //         =
    //         \CodeIgniter\Database\BaseConnection::class;

    //     $dbMock
    //         =
    //         $this
    //         ->createMock(
    //             $dbClass
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transBegin'
    //         );

    //     $resClass
    //         =
    //         \CodeIgniter\Database\BaseResult::class;

    //     $resMock
    //         =
    //         $this
    //         ->createMock(
    //             $resClass
    //         );

    //     $groupObj
    //         =
    //         new \stdClass();

    //     $groupObj
    //         ->name
    //         =
    //         'Editors';

    //     $resMock
    //         ->method(
    //             'getRow'
    //         )
    //         ->willReturn(
    //             $groupObj
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->any()
    //         )
    //         ->method(
    //             'query'
    //         )
    //         ->willReturn(
    //             $resMock
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transStatus'
    //         )
    //         ->willReturn(
    //             true
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transCommit'
    //         );

    //     $injectDb
    //         =
    //         function (
    //             $connection
    //         ) {
    //             $target
    //                 =
    //                 $this;

    //             $target
    //                 ->db
    //                 =
    //                 $connection;
    //         };

    //     $binder
    //         =
    //         \Closure::bind(
    //             $injectDb,
    //             $model,
    //             $modelClass
    //         );

    //     $binder(
    //         $dbMock
    //     );

    //     $adminObj
    //         =
    //         new \stdClass();

    //     $adminObj
    //         ->uuid
    //         =
    //         '123e4567-e89b-12d3-a456-426614174000';

    //     $stdClass
    //         =
    //         \stdClass::class;

    //     $authMock
    //         =
    //         $this
    //         ->getMockBuilder(
    //             $stdClass
    //         )
    //         ->addMethods(
    //             [
    //                 'currentAdmin'
    //             ]
    //         )
    //         ->getMock();

    //     $authMock
    //         ->method(
    //             'currentAdmin'
    //         )
    //         ->willReturn(
    //             $adminObj
    //         );

    //     \Config\Services::injectMock(
    //         'authorization',
    //         $authMock
    //     );

    //     $result
    //         =
    //         $model
    //         ->del(
    //             $posts
    //         );

    //     $this
    //         ->assertIsArray(
    //             $result
    //         );

    //     $this
    //         ->assertTrue(
    //             $result['result']
    //         );
    // }

    // public function testDelRollbackOnTransStatusFalse()
    // {
    //     $modelClass
    //         =
    //         \App\Models\Backend\GroupsModel::class;

    //     $model
    //         =
    //         $this
    //         ->getMockBuilder(
    //             $modelClass
    //         )
    //         ->onlyMethods(
    //             [
    //                 'checkAllowedFields'
    //             ]
    //         )
    //         ->getMock();

    //     $posts
    //         =
    //         [
    //             'id'
    //             =>
    //             1
    //         ];

    //     $model
    //         ->method(
    //             'checkAllowedFields'
    //         )
    //         ->willReturn(
    //             $posts
    //         );

    //     $dbClass
    //         =
    //         \CodeIgniter\Database\BaseConnection::class;

    //     $dbMock
    //         =
    //         $this
    //         ->createMock(
    //             $dbClass
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transBegin'
    //         );

    //     $resClass
    //         =
    //         \CodeIgniter\Database\BaseResult::class;

    //     $resMock
    //         =
    //         $this
    //         ->createMock(
    //             $resClass
    //         );

    //     $groupObj
    //         =
    //         new \stdClass();

    //     $groupObj
    //         ->name
    //         =
    //         'Editors';

    //     $resMock
    //         ->method(
    //             'getRow'
    //         )
    //         ->willReturn(
    //             $groupObj
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->any()
    //         )
    //         ->method(
    //             'query'
    //         )
    //         ->willReturn(
    //             $resMock
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transStatus'
    //         )
    //         ->willReturn(
    //             false
    //         );

    //     $dbMock
    //         ->expects(
    //             $this
    //             ->once()
    //         )
    //         ->method(
    //             'transRollback'
    //         );

    //     $injectDb
    //         =
    //         function (
    //             $connection
    //         ) {
    //             $target
    //                 =
    //                 $this;

    //             $target
    //                 ->db
    //                 =
    //                 $connection;
    //         };

    //     $binder
    //         =
    //         \Closure::bind(
    //             $injectDb,
    //             $model,
    //             $modelClass
    //         );

    //     $binder(
    //         $dbMock
    //     );

    //     $result
    //         =
    //         $model
    //         ->del(
    //             $posts
    //         );

    //     $this
    //         ->assertIsArray(
    //             $result
    //         );

    //     $this
    //         ->assertFalse(
    //             $result['result']
    //         );
    // }

    public function testDelCatchBlockReturnsErrorOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                1
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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
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
            ->del(
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

    public function testDelSuccess()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                1
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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $groupObj
            =
            new \stdClass();

        $groupObj
            ->name
            =
            'Editors';

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $groupObj
            );

        $dbMock
            ->expects(
                $this
                ->any()
            )
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

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
                $adminObj
            );

        \Config\Services::injectMock(
            'authorization',
            $authMock
        );

        $result
            =
            $model
            ->del(
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
    }

    public function testDelRollbackOnTransStatusFalse()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                1
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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $groupObj
            =
            new \stdClass();

        $groupObj
            ->name
            =
            'Editors';

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $groupObj
            );

        $dbMock
            ->expects(
                $this
                ->any()
            )
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
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
            ->del(
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

    // public function testDelCatchBlockReturnsErrorOnException()
    // {
    //     $modelClass
    //         =
    //         \App\Models\Backend\GroupsModel::class;

    //     $model
    //         =
    //         $this
    //         ->getMockBuilder(
    //             $modelClass
    //         )
    //         ->onlyMethods(
    //             [
    //                 'checkAllowedFields'
    //             ]
    //         )
    //         ->getMock();

    //     $posts
    //         =
    //         [
    //             'id'
    //             =>
    //             1
    //         ];

    //     $model
    //         ->method(
    //             'checkAllowedFields'
    //         )
    //         ->willReturn(
    //             $posts
    //         );

    //     $dbClass
    //         =
    //         \CodeIgniter\Database\BaseConnection::class;

    //     $dbMock
    //         =
    //         $this
    //         ->createMock(
    //             $dbClass
    //         );

    //     $dbMock
    //         ->method(
    //             'query'
    //         )
    //         ->will(
    //             $this
    //             ->throwException(
    //                 new \RuntimeException(
    //                     'Database error'
    //                 )
    //             )
    //         );

    //     $injectDb
    //         =
    //         function (
    //             $connection
    //         ) {
    //             $target
    //                 =
    //                 $this;

    //             $target
    //                 ->db
    //                 =
    //                 $connection;
    //         };

    //     $binder
    //         =
    //         \Closure::bind(
    //             $injectDb,
    //             $model,
    //             $modelClass
    //         );

    //     $binder(
    //         $dbMock
    //     );

    //     $result
    //         =
    //         $model
    //         ->del(
    //             $posts
    //         );

    //     $this
    //         ->assertIsArray(
    //             $result
    //         );

    //     $this
    //         ->assertFalse(
    //             $result['result']
    //         );
    // }

    public function testGetDropdownAdminsSuccess()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'query'
                =>
                'Mario'
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

        $expectedResult
            =
            [
                [
                    'uuid'
                    =>
                    '123e4567-e89b-12d3-a456-426614174000',
                    'identity'
                    =>
                    'Mario Rossi'
                ]
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                $expectedResult
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
            ->getDropdownAdmins(
                $posts
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertSame(
                $expectedResult,
                $result
            );
    }

    public function testGetDropdownAdminsReturnsEmptyArrayWhenNoMatch()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'query'
                =>
                'NonExistent'
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
            ->getDropdownAdmins(
                $posts
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

    public function testGetDropdownAdminsCatchBlockReturnsEmptyArrayOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'query'
                =>
                'Mario'
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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
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
            ->getDropdownAdmins(
                $posts
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

    public function testGetGroupCatchBlockReturnsEmptyArrayOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'query'
            )
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $this
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
            ->getGroup(
                1
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

    public function testGetGroupByIdCatchBlockReturnsNullOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                1
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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
            );

        $injectDb
            =
            function (
                $connection
            ) {
                $this
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
            ->getGroupById(
                $posts
            );

        $this
            ->assertNull(
                $result
            );
    }

    public function testGetAdminByUuidCatchBlockReturnsEmptyArrayOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
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
            ->getAdminByUuid(
                $posts
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

    public function testSaveExceptionsSuccessWithExtraAndRevokedPermissions()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupPermissionsArray',
                    'getAdminExceptionsArray'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
                =>
                [
                    'edit_posts',
                    'extra_permission'
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
                'getGroupPermissionsArray'
            )
            ->willReturn(
                [
                    'edit_posts',
                    'delete_posts'
                ]
            );

        $model
            ->method(
                'getAdminExceptionsArray'
            )
            ->willReturn(
                [
                    'delete_posts'
                    =>
                    0
                ]
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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $adminObj
            ->superadmin
            =
            0;

        $adminObj
            ->deleted_at
            =
            null;

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
            );

        $dbMock
            ->expects(
                $this
                ->any()
            )
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
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

        $currentAdminObj
            =
            new \stdClass();

        $currentAdminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

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
            ->saveExceptions(
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
    }

    public function testSaveExceptionsRollbackOnTransStatusFalse()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupPermissionsArray',
                    'getAdminExceptionsArray'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
                =>
                [
                    'extra_permission'
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
                'getGroupPermissionsArray'
            )
            ->willReturn(
                [
                    'edit_posts'
                ]
            );

        $model
            ->method(
                'getAdminExceptionsArray'
            )
            ->willReturn(
                []
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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $adminObj
            ->superadmin
            =
            0;

        $adminObj
            ->deleted_at
            =
            null;

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
            );

        $dbMock
            ->expects(
                $this
                ->any()
            )
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
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
            ->saveExceptions(
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

    public function testSaveExceptionsCatchBlockReturnsErrorOnException()
    {
        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

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
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
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
            ->will(
                $this
                ->throwException(
                    new \RuntimeException(
                        'Database error'
                    )
                )
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
            ->saveExceptions(
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

    public function testSaveExceptionsCoversAllowOneAndAllowZeroBranches()
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
                ?object $admin
            ): void
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\GroupsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getGroupPermissionsArray',
                    'getAdminExceptionsArray'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'uuid'
                =>
                '123e4567-e89b-12d3-a456-426614174000',
                'permissions'
                =>
                [
                    'extra_perm',
                    'new_feature'
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
                'getGroupPermissionsArray'
            )
            ->willReturn(
                [
                    'edit_posts'
                ]
            );

        $model
            ->method(
                'getAdminExceptionsArray'
            )
            ->willReturn(
                [
                    'extra_perm'
                    =>
                    1,
                    'edit_posts'
                    =>
                    0
                ]
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

        $resClass
            =
            \CodeIgniter\Database\BaseResult::class;

        $resMock
            =
            $this
            ->createMock(
                $resClass
            );

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->group_id
            =
            1;

        $adminObj
            ->firstname
            =
            'Mario';

        $adminObj
            ->lastname
            =
            'Rossi';

        $adminObj
            ->superadmin
            =
            0;

        $adminObj
            ->deleted_at
            =
            null;

        $resMock
            ->method(
                'getRow'
            )
            ->willReturn(
                $adminObj
            );

        $dbMock
            ->expects(
                $this
                ->any()
            )
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
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

        $currentAdminObj
            =
            new \stdClass();

        $currentAdminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

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
            ->saveExceptions(
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
    }
}
