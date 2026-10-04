<?php declare(strict_types = 1);

namespace App\Models\Backend;

class SettingsModelTest extends \CodeIgniter\Test\CIUnitTestCase
{
	protected function setUp(): void
    {
        parent::setUp();

        $modelClass = \App\Models\Backend\SettingsModel::class;

        $model = new $modelClass();

        $this->model = $model;
    }

	public function testAuthSettingsValidateRulesReturnsArrayWithExpectedKeys()
    {
        $model = $this->model;

        $result = $model->authSettingsValidateRules();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('attempts', $result);

        $this->assertArrayHasKey('sessionTime', $result);
    }

    public function testUploadSettingsValidateRulesReturnsArrayWithExpectedKeys()
    {
        $model = $this->model;

        $result = $model->uploadSettingsValidateRules();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('maxFileSize', $result);

        $this->assertArrayHasKey('allowedExtensions.*', $result);
    }

    public function testEmailSettingsValidateRulesWithoutSmtpSetsPermitEmpty()
    {
        $model = $this->model;

        $posts =['protocol' => 'mail'];

        $result = $model->emailSettingsValidateRules($posts);

        $this->assertIsArray($result);

        $rule = $result['SMTPHost']['rules'][0];

        $this->assertSame('permit_empty', $rule);
    }

    public function testEmailSettingsValidateRulesWithSmtpSetsRequired()
    {
        $model = $this->model;

        $posts = ['protocol' => 'smtp'];

        $result = $model->emailSettingsValidateRules($posts);

        $rule = $result['SMTPHost']['rules'][0];

        $this->assertSame('required', $rule);
    }

    public function testGeneralSettingsValidateRulesReturnsArrayWithExpectedKeys()
    {
        $model = $this->model;

        $result = $model->generalSettingsValidateRules();

        $this->assertIsArray($result );

        $this->assertArrayHasKey('timezone', $result );

        $this->assertArrayHasKey('dateFormat', $result );
    }

    public function testHasDatabaseSettingsReturnsTrueWhenRecordExists()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

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
            ->method('getRowArray')
            ->willReturn(
                [
                    'total'
                    =>
                    '1'
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
            ->hasDatabaseSettings(
                'App\\Config\\App'
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasDatabaseSettingsReturnsFalseWhenRecordDoesNotExist()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

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
            ->method('getRowArray')
            ->willReturn(
                [
                    'total'
                    =>
                    '0'
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
            ->hasDatabaseSettings(
                'App\\Config\\App'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testDeleteSettingsReturnsFalseWhenNoSettingsExist()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'hasDatabaseSettings'
                ]
            )
            ->getMock();

        $model
            ->method('hasDatabaseSettings')
            ->with('App\\Config\\App')
            ->willReturn(false);

        $result
            =
            $model
            ->deleteSettings(
                'App\\Config\\App'
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testDeleteSettingsDeletesCacheAndReturnsTrueWhenSettingsExist()
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
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'hasDatabaseSettings'
                ]
            )
            ->getMock();

        $model
            ->method('hasDatabaseSettings')
            ->with('App\\Config\\App')
            ->willReturn(true);

        $setCache
            =
            function (
                $ns
            ) {
                $this
                    ->settingsCache[$ns]
                    =
                    ['some' => 'value'];
            };

        $binder
            =
            \Closure::bind(
                $setCache,
                $model,
                $modelClass
            );

        $binder(
            'App\\Config\\App'
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
            ->method('query')
            ->with(
                $this
                ->stringContains(
                    'delete from'
                ),
                ['App\\Config\\App']
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

        $dbBinder
            =
            \Closure::bind(
                $injectDb,
                $model,
                $modelClass
            );

        $dbBinder(
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
            ->deleteSettings(
                'App\\Config\\App'
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasSettingsChangedReturnsFalseWhenValuesAreIdentical()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential',
                'extensions'
                =>
                'jpg|png'
            ];

        $model
            ->method('getSettings')
            ->with('App\\Config\\App')
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'site_name'
                =>
                'Essential',
                'extensions'
                =>
                ['png', 'jpg']
            ];

        $result
            =
            $model
            ->hasSettingsChanged(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testHasSettingsChangedReturnsTrueWhenScalarValueDiffers()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method('getSettings')
            ->with('App\\Config\\App')
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'site_name'
                =>
                'Essential Pro'
            ];

        $result
            =
            $model
            ->hasSettingsChanged(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasSettingsChangedReturnsTrueWhenArrayValueDiffers()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'extensions'
                =>
                'jpg|png'
            ];

        $model
            ->method('getSettings')
            ->with('App\\Config\\App')
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'extensions'
                =>
                ['jpg', 'gif']
            ];

        $result
            =
            $model
            ->hasSettingsChanged(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testHasSettingsChangedIgnoresKeysNotPresentInCurrentSettings()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method('getSettings')
            ->with('App\\Config\\App')
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'site_name'
                =>
                'Essential',
                'non_existent_key'
                =>
                'some_value'
            ];

        $result
            =
            $model
            ->hasSettingsChanged(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testSaveSettingsReturnsErrorWhenNoDataChanged()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'hasDatabaseSettings',
                    'getChangedKeys'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $model
            ->method('hasDatabaseSettings')
            ->willReturn(true);

        $model
            ->method('getChangedKeys')
            ->willReturn([]);

        $result
            =
            $model
            ->saveSettings(
                'General',
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

        $expectedMsg
            =
            lang('backend/settings.messages.noDataChanged');

        $this
            ->assertSame(
                $expectedMsg, $result['message']
            );
    }

    public function testSaveSettingsSuccessWithArrayConversionAndDatabaseUpsert()
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
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'hasDatabaseSettings',
                    'getChangedKeys'
                ]
            )
            ->getMock();

        $posts
            =
            [
                'allowedExtensions'
                =>
                ['jpg', 'png', 'gif']
            ];

        $model
            ->method('checkAllowedFields')
            ->willReturn(
                $posts
            );

        $model
            ->method('hasDatabaseSettings')
            ->willReturn(false);

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
            ->method('query')
            ->with(
                $this
                ->stringContains(
                    'insert into `settings`'
                ),
                $this
                ->callback(
                    function (
                        $params
                    ) {
                        return
                            in_array(
                                'jpg|png|gif',
                                $params
                            );
                    }
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
                $model,$modelClass
            );

        $binder($dbMock
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
            ->saveSettings(
                'Upload',
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

        $expectedMsg
            =
            lang('backend/settings.messages.saveSuccess');

        $this
            ->assertSame(
                $expectedMsg,$result['message']
            );
    }

    public function testGetChangedKeysReturnsEmptyArrayWhenNoChanges()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method(
                'getSettings'
            )
            ->with(
                'App\\Config\\App'
            )
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $result
            =
            $model
            ->getChangedKeys(
                'App\\Config\\App',
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

    public function testGetChangedKeysReturnsKeyWhenScalarChanged()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method(
                'getSettings'
            )
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'site_name'
                =>
                'Essential Pro'
            ];

        $result
            =
            $model
            ->getChangedKeys(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertSame(
                ['site_name'],
                $result
            );
    }

    public function testGetChangedKeysNormalizesAndComparesArraysCorrectly()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'extensions'
                =>
                'jpg|png'
            ];

        $model
            ->method(
                'getSettings'
            )
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'extensions'
                =>
                ['png', 'jpg', '']
            ];

        $result
            =
            $model
            ->getChangedKeys(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertEmpty(
                $result
            );
    }

    public function testGetChangedKeysIgnoresKeysNotInCurrent()
    {
        $modelClass
            =
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getSettings'
                ]
            )
            ->getMock();

        $current
            =
            [
                'site_name'
                =>
                'Essential'
            ];

        $model
            ->method(
                'getSettings'
            )
            ->willReturn(
                $current
            );

        $posts
            =
            [
                'non_existent'
                =>
                'value'
            ];

        $result
            =
            $model
            ->getChangedKeys(
                'App\\Config\\App',
                $posts
            );

        $this
            ->assertEmpty(
                $result
            );
    }

    public function testSaveSettingsClearsCacheWhenPresent()
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
            \App\Models\Backend\SettingsModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'checkAllowedFields',
                    'hasDatabaseSettings'
                ]
            )
            ->getMock();

        $namespace
            =
            'Backend\General';

        $posts
            =
            [
                'site_name'
                =>
                'Essential'
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
                'hasDatabaseSettings'
            )
            ->willReturn(
                false
            );

        $injectCache
            =
            function ()
            {
                $target
                    =
                    $this;

                $target
                    ->settingsCache['Backend\General']
                    =
                    [
                        'old_key'
                        =>
                        'old_value'
                    ];
            };

        $binder
            =
            \Closure::bind(
                $injectCache,
                
                $model,
                
                $modelClass
            );

        $binder();

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
                'query'
            );

        $injectDb
            =
            function (
                $conn
            ) {
                $target
                    =
                    $this;

                $target
                    ->db
                    =
                    $conn;
            };

        $binderDb
            =
            \Closure::bind(
                $injectDb,
                
                $model,
                
                $modelClass
            );

        $binderDb(
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
            ->saveSettings(
                $namespace,
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

    public function testGetSettingsParsesRowsIntoDatabaseSettings()
    {
        $model
            =
            new \App\Models\Backend\SettingsModel();

        $resMock
            =
            $this
            ->createMock(
                \CodeIgniter\Database\BaseResult::class
            );

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                [
                    [
                        'key'
                        =>
                        'site_title',
                        'value'
                        =>
                        'EssentialApp'
                    ]
                ]
            );

        $dbMock
            =
            $this
            ->createMock(
                \CodeIgniter\Database\BaseConnection::class
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $closure
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
                $closure,
                $model,
                \App\Models\Backend\SettingsModel::class
            );

        $binder(
            $dbMock
        );

        $result
            =
            $model
            ->getSettings(
                'Backend\General'
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'site_title',
                $result
            );
    }

    public function testGetSettingsFiltersResultsWhenKeysAreProvided()
    {
        $model
            =
            new \App\Models\Backend\SettingsModel();

        $resMock
            =
            $this
            ->createMock(
                \CodeIgniter\Database\BaseResult::class
            );

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                [
                    [
                        'key'
                        =>
                        'alpha',
                        'value'
                        =>
                        'one'
                    ],
                    [
                        'key'
                        =>
                        'beta',
                        'value'
                        =>
                        'two'
                    ]
                ]
            );

        $dbMock
            =
            $this
            ->createMock(
                \CodeIgniter\Database\BaseConnection::class
            );

        $dbMock
            ->method(
                'query'
            )
            ->willReturn(
                $resMock
            );

        $closure
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
                $closure,
                $model,
                \App\Models\Backend\SettingsModel::class
            );

        $binder(
            $dbMock
        );

        $keys
            =
            [
                'alpha'
            ];

        $result
            =
            $model
            ->getSettings(
                'Backend\General',
                $keys
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'alpha',
                $result
            );

        $this
            ->assertArrayNotHasKey(
                'beta',
                $result
            );
    }
}
