<?php declare(strict_types = 1);

namespace App\Models\Backend;

class LogsModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
	protected function setUp(): void
    {
        parent::setUp();

        $modelClass = \App\Models\Backend\LogsModel::class;

        $model = new $modelClass();

        $this->model = $model;
    }

    public function testShowAllValidationRulesReturnsExpectedArray()
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

    public function testShowAllSearchValidationRulesReturnsExpectedArray()
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
                    'searchFields.username' => [
                        'label' => lang('backend/logs.labels.username'),
                        'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]']
                    ],
                    'searchFields.logout_reason' => [
                        'label' => lang('backend/logs.labels.logoutReason'),
                        'rules' => ['permit_empty', 'in_list[manual,timeout,deleted,banned]']
                    ],
                    'searchDates.login-from' => [
                        'label' => lang('backend/logs.labels.dateFrom'),
                        'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]']
                    ],
                    'searchDates.login-to' => [
                        'label' => lang('backend/logs.labels.dateTo'),
                        'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]']
                    ]
                ],
                $result
            );
    }

    public function testDeleteTokenNotFoundReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\LogsModel::class;

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
            ->method('getRow')
            ->willReturn(null);

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
            ->deleteToken(
                1
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/logs.messages.deleteTokenError');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testDeleteTokenInTrashReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\LogsModel::class;

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

        $tokenRow
            =
            new \stdClass();

        $tokenRow
            ->deleted_at
            =
            '2026-01-01 00:00:00';

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
            ->deleteToken(
                2
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/logs.messages.cannotModifyDeleted');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testDeleteTokenSuperadminReturnsError()
    {
        $modelClass
            =
            \App\Models\Backend\LogsModel::class;

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

        $tokenRow
            =
            new \stdClass();

        $tokenRow
            ->deleted_at
            =
            null;

        $tokenRow
            ->superadmin
            =
            1;

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
            ->deleteToken(
                3
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMsg
            =
            lang('backend/logs.messages.protectedAdmin');

        $this
            ->assertSame(
                $expectedMsg,
                $result['message']
            );
    }

    public function testDeleteTokenSuccessUpdatesLogAndDeletesToken()
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
            \App\Models\Backend\LogsModel::class;

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

        $tokenRow
            =
            new \stdClass();

        $tokenRow
            ->id
            =
            10;

        $tokenRow
            ->admin_uuid
            =
            'uuid-abc';

        $tokenRow
            ->last_activity
            =
            '2026-01-01 12:00:00';

        $tokenRow
            ->token_type
            =
            'cookie';

        $tokenRow
            ->firstname
            =
            'John';

        $tokenRow
            ->lastname
            =
            'Doe';

        $tokenRow
            ->superadmin
            =
            0;

        $tokenRow
            ->deleted_at
            =
            null;

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
            ->deleteToken(
                10
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

    public function testDeleteTokenCatchBlockReturnsErrorOnException()
    {
        $modelClass
            =
            \App\Models\Backend\LogsModel::class;

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
            ->deleteToken(
                1
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['result']
            );

        $expectedMessage
            =
            lang(
                'backend/logs.messages.deleteTokenError'
            );

        $this
            ->assertSame(
                $expectedMessage,
                $result['message']
            );
    }
}
