<?php declare(strict_types = 1);

namespace App\Models\Backend;

class TokensModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
	protected function setUp(): void
    {
        parent::setUp();

        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            new $modelClass();

        $this
            ->model
            =
            $model;
    }

    public function testShowAllValidationRulesReturnsArrayWithExpectedKeys()
    {
        $model
            =
            $this
            ->model;

        $result
            =
            $model
            ->showAllValidationRules();

        $this
            ->assertSame(
                [
                    'column' => ['rules' => ['required', 'alpha_dash']],
                    'order' => ['rules' => ['required', 'in_list[asc,desc]']],
                    'page' => ['rules' => ['required', 'is_natural_no_zero']],
                    'rows' => ['rules' => ['required', 'is_natural_no_zero']]
                ],
                $result
            );
    }

    public function testShowAllSearchValidationRulesReturnsArrayWithExpectedKeys()
    {
        $model
            =
            $this
            ->model;

        $result
            =
            $model
            ->showAllSearchValidationRules();

        $this
            ->assertSame(
                [
                    'searchFields.email' => [
                        'label' => lang('backend/tokens.labels.username'),
                        'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]']
                    ],
                    'searchFields.token_type' => [
                        'label' => lang('backend/tokens.labels.token_type'),
                        'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]']
                    ],
                    'searchDates.token_create-from' => [
                        'label' => lang('backend/tokens.labels.dateFrom'),
                        'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]']
                    ],
                    'searchDates.token_create-to' => [
                        'label' => lang('backend/tokens.labels.dateTo'),
                        'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]']
                    ]
                ],
                $result
            );
    }

    public function testDelValidationRulesReturnsArrayWithExpectedKeys()
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
            ->assertSame(
                [
                    'uuid' => [
                        'label' => lang('backend/tokens.labels.uuid'),
                        'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                        'errors' => [
                            'required' => lang('backend/tokens.errors.uuid'),
                            'regex_match' => lang('backend/tokens.errors.uuid')
                        ]
                    ],
                    'id' => [
                        'label' => lang('backend/tokens.labels.id'),
                        'rules' => ['required', 'is_natural_no_zero'],
                        'errors' => [
                            'required' => lang('backend/tokens.errors.id'),
                            'is_natural_no_zero' => lang('backend/tokens.errors.id')
                        ]
                    ]
                ],
                $result
            );
    }

    public function testHardDeleteUserInTrashReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                '2',
                'uuid'
                =>
                'uuid-123'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeRow
            =
            new \stdClass();

        $fakeRow
            ->deleted_at
            =
            '2026-01-01';

        $fakeData
            =
            [
                'result'
                =>
                true,
                'row'
                =>
                $fakeRow
            ];

        $model
            ->method('getByUUID')
            ->with('2')
            ->willReturn(
                $fakeData
            );

        $result
            =
            $model
            ->hardDelete(
                $posts
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/tokens.messages.cannotModifyDeleted');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testHardDeleteSuperadminReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                '3',
                'uuid'
                =>
                'uuid-123'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeRow
            =
            new \stdClass();

        $fakeRow
            ->deleted_at
            =
            null;

        $fakeRow
            ->superadmin
            =
            1;

        $fakeData
            =
            [
                'result'
                =>
                true,
                'row'
                =>
                $fakeRow
            ];

        $model
            ->method('getByUUID')
            ->with('3')
            ->willReturn(
                $fakeData
            );

        $result
            =
            $model
            ->hardDelete(
                $posts
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/tokens.messages.protectedAdmin');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testHardDeleteNoAffectedRowsReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                '4',
                'uuid'
                =>
                'uuid-123'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeRow
            =
            new \stdClass();

        $fakeRow
            ->deleted_at
            =
            null;

        $fakeRow
            ->superadmin
            =
            0;

        $fakeData
            =
            [
                'result'
                =>
                true,
                'row'
                =>
                $fakeRow
            ];

        $model
            ->method('getByUUID')
            ->with('4')
            ->willReturn(
                $fakeData
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
            ->method('getRow')
            ->willReturn(null);

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $dbMock
            ->method('affectedRows')
            ->willReturn(0);

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
            ->hardDelete(
                $posts
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/tokens.messages.deleteTokenError');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testHardDeleteThrowsExceptionReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                6,
                'uuid'
                =>
                'uuid-123'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeRow
            =
            new \stdClass();

        $fakeRow
            ->deleted_at
            =
            null;

        $fakeRow
            ->superadmin
            =
            0;

        $fakeData
            =
            [
                'result'
                =>
                true,
                'row'
                =>
                $fakeRow
            ];

        $model
            ->method('getByUUID')
            ->willReturn(
                $fakeData
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

        $exceptionClass
            =
            \Exception::class;

        $exceptionMsg
            =
            'DB Error';

        $exceptionObj
            =
            new $exceptionClass(
                $exceptionMsg
            );

        $dbMock
            ->method('query')
            ->willThrowException(
                $exceptionObj
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
            ->hardDelete(
                $posts
            );

        $this
            ->assertFalse(
                $result['result']
            );
    }

    public function testHardDeleteUserNotFoundReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                '1',
                'uuid'
                =>
                'some-uuid'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeData
            =
            [
                'result'
                =>
                false,
                'message'
                =>
                'User not found'
            ];

        $model
            ->method('getByUUID')
            ->with('1')
            ->willReturn(
                $fakeData
            );

        $result
            =
            $model
            ->hardDelete(
                $posts
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $this
            ->assertSame(
                'User not found',
                $result['message']
            );
    }

    public function testHardDeleteSuccessWithLogUpdate()
    {
        if (
            ! function_exists(
                __NAMESPACE__ . '\log_admin_activity'
            )
        ):
            function log_admin_activity()
            {
            }
        endif;

        $modelClass
            =
            \App\Models\Backend\TokensModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'getByUUID'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'id'
                =>
                '5',
                'uuid'
                =>
                'uuid-123'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $fakeRow
            =
            new \stdClass();

        $fakeRow
            ->deleted_at
            =
            null;

        $fakeRow
            ->superadmin
            =
            0;

        $fakeRow
            ->firstname
            =
            'Mario';

        $fakeRow
            ->lastname
            =
            'Rossi';

        $fakeData
            =
            [
                'result'
                =>
                true,
                'row'
                =>
                $fakeRow
            ];

        $model
            ->method('getByUUID')
            ->willReturn(
                $fakeData
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

        $tokenRow
            =
            new \stdClass();

        $tokenRow
            ->id
            =
            10;

        $tokenRow
            ->token_type
            =
            'cookie';

        $tokenRow
            ->last_activity
            =
            '2026-01-01 10:00:00';

        $resMock
            ->method('getRow')
            ->willReturn(
                $tokenRow
            );

        $dbMock
            ->method('query')
            ->willReturn(
                $resMock
            );

        $dbMock
            ->method('affectedRows')
            ->willReturn(1);

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
                ['currentAdmin']
            )
            ->getMock();

        $authMock
            ->method('currentAdmin')
            ->willReturn(1);

        \Config\Services::injectMock(
            'authorization',
            $authMock
        );

        $result
            =
            $model
            ->hardDelete(
                $posts
            );

        $this
            ->assertTrue(
                $result['result']
            );
    }
}
