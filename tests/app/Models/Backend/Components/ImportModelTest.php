<?php declare(strict_types = 1);

namespace App\Models\Backend\Components;

use CodeIgniter\Test\CIUnitTestCase;

class ImportModelTest extends CIUnitTestCase
{
	public function testGetTargetModelInstanceReturnsInstanceWhenClassExists()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            new $modelClass();

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'getTargetModelInstance'
            );

        $reflection
            ->setAccessible(
                true
            );

        /* Utilizziamo 'groups' per generare App\Models\Backend\GroupsModel che sappiamo esistere nel percorso corretto */
        $entity
            =
            'groups';

        $result
            =
            $reflection
            ->invoke(
                $model,
                $entity
            );

        $this
            ->assertNotNull(
                $result
            );

        $expectedClass
            =
            \App\Models\Backend\GroupsModel::class;

        $this
            ->assertInstanceOf(
                $expectedClass,
                $result
            );
    }

    public function testGetTargetModelInstanceReturnsNullWhenClassDoesNotExist()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            new $modelClass();

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'getTargetModelInstance'
            );

        $reflection
            ->setAccessible(
                true
            );

        $entity
            =
            'ghost_entity_that_does_not_exist';

        $result
            =
            $reflection
            ->invoke(
                $model,
                $entity
            );

        $this
            ->assertNull(
                $result
            );
    }

    public function testGetTableStructureReturnsEmptyArrayWhenTableMissing()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

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

        $table
            =
            'missing_table';

        $result
            =
            $model
            ->getTableStructure(
                $table
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

    public function testGetTableStructureReturnsFormattedStructureWithIndexesAndPrimaryKeys()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

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

        /* Simuliamo 3 campi: chiave primaria, campo indicizzato e campo normale */
        $field1
            =
            new \stdClass();

        $field1
            ->name
            =
            'id';

        $field1
            ->type
            =
            'int';

        $field1
            ->max_length
            =
            11;

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
            'email';

        $field2
            ->type
            =
            'varchar';

        $field2
            ->max_length
            =
            255;

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
            'notes';

        $field3
            ->type
            =
            'text';

        $field3
            ->max_length
            =
            null;

        $field3
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
                    $field3
                ]
            );

        /* Simuliamo un indice applicato solo al campo 'email' */
        $index1
            =
            new \stdClass();

        $index1
            ->name
            =
            'email_idx';

        $index1
            ->fields
            =
            [
                'email'
            ];

        $dbMock
            ->method(
                'getIndexData'
            )
            ->willReturn(
                [
                    $index1
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

        $table
            =
            'real_table';

        $result
            =
            $model
            ->getTableStructure(
                $table
            );

        $expected
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'type'
                    =>
                    'int',
                    'max_length'
                    =>
                    11,
                    'primary_key'
                    =>
                    1,
                    'is_index'
                    =>
                    false
                ],
                [
                    'name'
                    =>
                    'email',
                    'type'
                    =>
                    'varchar',
                    'max_length'
                    =>
                    255,
                    'primary_key'
                    =>
                    0,
                    'is_index'
                    =>
                    true
                ],
                [
                    'name'
                    =>
                    'notes',
                    'type'
                    =>
                    'text',
                    'max_length'
                    =>
                    null,
                    'primary_key'
                    =>
                    0,
                    'is_index'
                    =>
                    false
                ]
            ];

        $this
            ->assertSame(
                $expected,
                $result
            );
    }

    public function testParseAndValidateCsvReturnsErrorOnUnreadableFile()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                '/percorso/inesistente/file_fantasma.csv'
            );

        /* 
         * Sospendiamo l'error handler di CI4 per impedire che il Warning 
         * di fopen() venga scalato a ErrorException, permettendo così 
         * al metodo di ricevere il "false" e testare il blocco condizionale.
         */
        set_error_handler(
            function () {
                return true;
            }
        );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'users'
            );

        restore_error_handler();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testParseAndValidateCsvReturnsErrorOnInsufficientColumns()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Creiamo un CSV con una sola colonna per forzare l'errore */
        file_put_contents(
            $tempFile,
            "id\n1"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
            );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'users'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testParseAndValidateCsvReturnsErrorOnMissingPrimaryKey()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Manca l'header 'id' (chiave primaria) */
        file_put_contents(
            $tempFile,
            "email,name\ntest@test.com,Mario"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
            );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'users'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testParseAndValidateCsvReturnsErrorOnInvalidColumns()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Inseriamo 'fake_column' che non è presente nello schema per innescare l'errore */
        file_put_contents(
            $tempFile,
            "id,email,fake_column\n1,test@test.com,hacker"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
            );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'users'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testParseAndValidateCsvReturnsErrorOnEmptyDataRows()
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
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'getTargetModelInstance',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Forziamo un CSV con la corretta struttura ma privo di righe dati */
        file_put_contents(
            $tempFile,
            "id,email\n"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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
            ->parseAndValidateCsv(
                $fileMock,
                'users'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testParseAndValidateCsvReturnsValidationErrors()
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
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $model
            ->method(
                'buildDynamicRules'
            )
            ->willReturn(
                [
                    'email'
                    =>
                    'required'
                ]
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Riga con email vuota per scatenare la validazione */
        file_put_contents(
            $tempFile,
            "id,email\n1,\n"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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

        /* Mock del servizio Validation */
        $valClass
            =
            \CodeIgniter\Validation\ValidationInterface::class;

        $valMock
            =
            $this
            ->createMock(
                $valClass
            );

        $valMock
            ->method(
                'run'
            )
            ->willReturn(
                false
            );

        $valMock
            ->method(
                'getErrors'
            )
            ->willReturn(
                [
                    'email'
                    =>
                    'Email non valida.'
                ]
            );

        \Config\Services::injectMock(
            'validation',
            $valMock
        );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );

        $this
            ->assertArrayHasKey(
                'validationErrors',
                $result
            );
    }

    public function testParseAndValidateCsvSuccessWithInsertUpdateSkipActions()
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
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $model
            ->method(
                'buildDynamicRules'
            )
            ->willReturn(
                []
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /*
         * Creiamo 3 righe per innescare i 3 path logici del piano:
         * 1: non esistente (Insert)
         * 2: esistente ma modificata (Update)
         * 3: esistente e identica (Skip)
         */
        $csvData
            =
            "id,email\n1,nuovo@test.com\n2,modificato@test.com\n3,identico@test.com\n";

        file_put_contents(
            $tempFile,
            $csvData
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
            );

        /* Mock DB Builder */
        $dbClass
            =
            \CodeIgniter\Database\BaseConnection::class;

        $dbMock
            =
            $this
            ->createMock(
                $dbClass
            );

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
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

        /* Array di simulazione dati esistenti sul database */
        $existingRecords
            =
            [
                [
                    'id'
                    =>
                    '2',
                    'email'
                    =>
                    'vecchio@test.com'
                ],
                [
                    'id'
                    =>
                    '3',
                    'email'
                    =>
                    'identico@test.com'
                ]
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                $existingRecords
            );

        $builderMock
            ->method(
                'whereIn'
            )
            ->willReturnSelf();

        $builderMock
            ->method(
                'get'
            )
            ->willReturn(
                $resMock
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
                'table'
            )
            ->willReturn(
                $builderMock
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

        /* Assicuriamo che la validation passi */
        $valClass
            =
            \CodeIgniter\Validation\ValidationInterface::class;

        $valMock
            =
            $this
            ->createMock(
                $valClass
            );

        $valMock
            ->method(
                'run'
            )
            ->willReturn(
                true
            );

        \Config\Services::injectMock(
            'validation',
            $valMock
        );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        /* Pulizia del file di staging residuo */
        if (
            isset(
                $result['tempFile']
            )
        ):
            $stagingFile
                =
                WRITEPATH
                .
                'uploads/staging/'
                .
                $result['tempFile'];

            if (
                file_exists(
                    $stagingFile
                )
            ):
                unlink(
                    $stagingFile
                );
            endif;
        endif;

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertTrue(
                $result['status']
            );

        /* Verifichiamo che il master plan abbia contato correttamente: 1 Insert, 1 Update, 1 Skip */
        $this
            ->assertSame(
                1,
                $result['plan']['insert']
            );

        $this
            ->assertSame(
                1,
                $result['plan']['update']
            );

        $this
            ->assertSame(
                1,
                $result['plan']['skip']
            );
    }

    public function testParseAndValidateCsvUnlinksStagingFileWhenNoInsertsOrUpdates()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Creiamo 1 riga identica al DB per generare solo 1 Skip (Insert=0, Update=0) */
        file_put_contents(
            $tempFile,
            "id,email\n3,identico@test.com"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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
                'tableExists'
            )
            ->willReturn(
                true
            );

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
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

        $existingRecords
            =
            [
                [
                    'id'
                    =>
                    '3',
                    'email'
                    =>
                    'identico@test.com'
                ]
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                $existingRecords
            );

        $builderMock
            ->method(
                'whereIn'
            )
            ->willReturnSelf();

        $builderMock
            ->method(
                'get'
            )
            ->willReturn(
                $resMock
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
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

        $valClass
            =
            \CodeIgniter\Validation\ValidationInterface::class;

        $valMock
            =
            $this
            ->createMock(
                $valClass
            );

        $valMock
            ->method(
                'run'
            )
            ->willReturn(
                true
            );

        \Config\Services::injectMock(
            'validation',
            $valMock
        );

        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        /* Siccome Insert=0 e Update=0, il file di staging DEVE essere stato rimosso */
        $stagingPath
            =
            WRITEPATH
            .
            'uploads/staging/'
            .
            $result['tempFile'];

        $this
            ->assertFalse(
                file_exists(
                    $stagingPath
                )
            );
    }

    public function testParseAndValidateCsvCreatesStagingDirectory()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        /* Distruggiamo la cartella di staging per forzare il mkdir() */
        if (
            is_dir(
                $stagingDir
            )
        ):
            $files
                =
                glob(
                    $stagingDir
                    .
                    '*'
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
                $stagingDir
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* CSV strutturalmente valido per superare i blocchi iniziali e arrivare al mkdir */
        file_put_contents(
            $tempFile,
            "id,email\n1,test@test.com"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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

        $model
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        /* Verifichiamo che la directory sia stata ricreata */
        $this
            ->assertTrue(
                is_dir(
                    $stagingDir
                )
            );
    }

    public function testParseAndValidateCsvSkipsEmptyRowsAndCatchesMismatchedColumns()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Riga 1: Valida per incrementare insert. Riga 2: Vuota. Riga 3: Colonne sballate */
        $csvData
            =
            "id,email\n1,valid@test.com\n,\n3,test@test.com,colonna_di_troppo";

        file_put_contents(
            $tempFile,
            $csvData
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertFalse(
                $result['status']
            );

        $this
            ->assertArrayHasKey(
                'validationErrors',
                $result
            );

        $errorList
            =
            $result['validationErrors'];

        /* Simuliamo il formato esatto che il Model produce: Riga 4, 3 trovate, 2 dichiarate */
        $expectedError
            =
            sprintf(
                lang(
                    'backend/components/import.messages.wrongColumnsNumber'
                ),
                4,
                3,
                2
            );

        $this
            ->assertSame(
                $expectedError,
                $errorList[0]
            );
    }

    public function testParseAndValidateCsvUsesFallbackRulesWhenTargetModelIsNull()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $fallbackRules
            =
            [
                'id'
                =>
                'required'
            ];

        $model
            ->method(
                'buildDynamicRules'
            )
            ->willReturn(
                $fallbackRules
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Inseriamo una riga valida per bypassare il controllo "file vuoto" */
        file_put_contents(
            $tempFile,
            "id,email\n1,test@test.com"
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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

        $valClass
            =
            \CodeIgniter\Validation\ValidationInterface::class;

        $valMock
            =
            $this
            ->createMock(
                $valClass
            );

        $valMock
            ->method(
                'run'
            )
            ->willReturn(
                true
            );

        \Config\Services::injectMock(
            'validation',
            $valMock
        );

        /* Passiamo un'entità per la quale non esiste il Model, attivando nativamente il fallback */
        $result
            =
            $model
            ->parseAndValidateCsv(
                $fileMock,
                'ghost_entity'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        /* Se restituisce true significa che il fallback è andato a buon fine e l'elaborazione è completata */
        $this
            ->assertTrue(
                $result['status']
            );
    }

    public function testParseAndValidateCsvTruncatesErrorsWhenExceedingMaxLimit()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'buildDynamicRules'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        $tempFile
            =
            tempnam(
                sys_get_temp_dir(),
                'csv_'
            );

        /* Riga 1: Valida. Righe successive: 505 errori di colonna per innescare l'array slice */
        $csvData
            =
            "id,email\n1,valid@test.com\n";
            
        $i
            =
            0;
            
        while (
            $i
            <
            505
        ):
            $csvData
                .=
                "2,test,extra_col\n";
                
            $i++;
        endwhile;

        file_put_contents(
            $tempFile,
            $csvData
        );

        $fileClass
            =
            \CodeIgniter\HTTP\Files\UploadedFile::class;

        $fileMock
            =
            $this
            ->getMockBuilder(
                $fileClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTempName'
                ]
            )
            ->getMock();

        $fileMock
            ->method(
                'getTempName'
            )
            ->willReturn(
                $tempFile
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
            ->parseAndValidateCsv(
                $fileMock,
                'admins'
            );

        if (
            file_exists(
                $tempFile
            )
        ):
            unlink(
                $tempFile
            );
        endif;

        $this
            ->assertFalse(
                $result['status']
            );

        $errorList
            =
            $result['validationErrors'];

        /* Controlliamo che l'array sia stato troncato a 500 messaggi + 1 messaggio di overflow (totale 501) */
        $this
            ->assertCount(
                501,
                $errorList
            );

        $lastMessage
            =
            end(
                $errorList
            );

        /* Verifichiamo che l'ultimo messaggio indichi l'avvenuto troncamento */
        $this
            ->assertStringContainsString(
                'errori non mostrati per limiti di memoria',
                $lastMessage
            );
    }

    public function testExecuteImportReturnsErrorWhenFileNotFound()
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
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $entity
            =
            'admins';

        $tempFile
            =
            'file_inesistente_123.csv';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );

        $this
            ->assertSame(
                lang(
                    'backend/components/import.messages.fileNotFoundError'
                ),
                $result['message']
            );
    }

    public function testExecuteImportReturnsErrorWhenPrimaryKeyNotDetermined()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_nopk_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        file_put_contents(
            $filePath,
            "id,email\n1,test@test.com"
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        /* Schema volutamente privo di chiave primaria per innescare il blocco */
        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    0
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
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

        /* Ci aspettiamo l'avvio e il rollback della transazione */
        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transStart'
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

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        if (
            file_exists(
                $filePath
            )
        ):
            unlink(
                $filePath
            );
        endif;

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testExecuteImportRollbacksOnMismatchedColumns()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_mismatch_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        /* Intestazione 2 colonne, riga 3 colonne per innescare l'errore di integrità */
        file_put_contents(
            $filePath,
            "id,email\n1,test@test.com,colonna_fantasma"
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
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
                'transStart'
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

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        if (
            file_exists(
                $filePath
            )
        ):
            unlink(
                $filePath
            );
        endif;

        $this
            ->assertFalse(
                $result['status']
            );

        $this
            ->assertSame(
                lang(
                    'backend/components/messages.importationUndone'
                ),
                $result['message']
            );
    }

    public function testExecuteImportReturnsErrorOnTransactionFailure()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_trans_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        file_put_contents(
            $filePath,
            "id,email\n1,test@test.com"
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transStart'
            );

        $dbMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'transComplete'
            );

        /* Simuliamo il fallimento della transazione (es. violazione chiave univoca) */
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

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        if (
            file_exists(
                $filePath
            )
        ):
            unlink(
                $filePath
            );
        endif;

        $this
            ->assertFalse(
                $result['status']
            );
    }

    public function testExecuteImportProcessesRecordsAndReturnsSuccess()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_master_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        /*
         * RIGA 1 (Insert): ID vuoto per generare UUID, usa created_at fittizio.
         * RIGA 2 (Update): ID fisso, usa la stringa 'null' per ripulire il campo email.
         */
        $csvData
            =
            "id,email,created_at,updated_at,__import_action\n"
            .
            ",test@test.com,,,insert\n"
            .
            "2,null,2023-01-01,,update\n";

        file_put_contents(
            $filePath,
            $csvData
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'generateUUID'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ],
                [
                    'name'
                    =>
                    'created_at',
                    'primary_key'
                    =>
                    0
                ],
                [
                    'name'
                    =>
                    'updated_at',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
            );

        /* Forziamo la generazione dell'UUID per la riga Insert senza ID */
        $model
            ->method(
                'generateUUID'
            )
            ->willReturn(
                'fake-uuid-1234'
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        /* Ci aspettiamo 1 chiamata a update e 1 chiamata a insert */
        $builderMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'update'
            );

        $builderMock
            ->expects(
                $this
                ->once()
            )
            ->method(
                'insert'
            );

        $builderMock
            ->method(
                'where'
            )
            ->willReturnSelf();

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
            );

        $dbMock
            ->method(
                'transStatus'
            )
            ->willReturn(
                true
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

        $adminObj
            =
            new \stdClass();

        /* Aggiungiamo l'UUID richiesto dall'helper */
        $adminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

        $adminObj
            ->email
            =
            'aaa@aaa.com';

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

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        /* Assicuriamoci che il file di staging sia stato rimosso dalla routine isFinished */
        $this
            ->assertFalse(
                file_exists(
                    $filePath
                )
            );

        $this
            ->assertTrue(
                $result['status']
            );

        /* Verifichiamo i contatori del master plan */
        $this
            ->assertSame(
                1,
                $result['inserted']
            );

        $this
            ->assertSame(
                1,
                $result['updated']
            );
            
        $this
            ->assertTrue(
                $result['isFinished']
            );
    }

    public function testExecuteImportReturnsErrorOnUnreadableFile()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'unreadable_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        /* TRUCCO ENTERPRISE: Creiamo una directory con il nome del file. 
           file_exists() passerà, ma fopen() fallirà restituendo false. */
        mkdir(
            $filePath,
            0755
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            new $modelClass();

        /* Silenziamo il warning di fopen per non far fallire PHPUnit */
        set_error_handler(
            function () {
                return true;
            }
        );

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        restore_error_handler();

        rmdir(
            $filePath
        );

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertFalse(
                $result['status']
            );

        $this
            ->assertSame(
                lang(
                    'backend/components/import.messages.fileReadError'
                ),
                $result['message']
            );
    }

    public function testExecuteImportHandlesPaginationAndEmptyRows()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_pagination_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        /* 
         * PROGETTAZIONE DEL FILE PER COPRIRE TUTTI I BLOCCHI ROSSI:
         * Riga CSV 1: Intestazione.
         * Riga CSV 2: Dati validi. Ma usando $offset = 1, verrà skippata (blocco rosso offset).
         * Riga CSV 3: Riga completamente vuota, formattata solo da virgole (blocco rosso array_filter).
         * Riga CSV 4 a 8: 5 record validi per raggiungere il $chunkSize (fissato a 5) e incrementare$processedInChunk.
         * Riga CSV 9: Dati validi. Innescherà l'interruzione anticipata "break" perché 5 sono già stati processati (blocco rosso break).
         */
        $csvData
            =
            "id,email,__import_action\n"
            .
            "1,skip@test.com,insert\n"
            .
            ",,\n"
            .
            "3,test3@test.com,insert\n"
            .
            "4,test4@test.com,insert\n"
            .
            "5,test5@test.com,insert\n"
            .
            "6,test6@test.com,insert\n"
            .
            "7,test7@test.com,insert\n"
            .
            "8,break@test.com,insert\n";

        file_put_contents(
            $filePath,
            $csvData
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure',
                    'generateUUID'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        /* Ci aspettiamo ESATTAMENTE 5 insert, né uno in più né uno in meno */
        $builderMock
            ->expects(
                $this
                ->exactly(
                    5
                )
            )
            ->method(
                'insert'
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
            );

        $dbMock
            ->method(
                'transStatus'
            )
            ->willReturn(
                true
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

        $entity
            =
            'admins';

        $offset
            =
            1;

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile,
                $offset
            );

        if (
            file_exists(
                $filePath
            )
        ):
            unlink(
                $filePath
            );
        endif;

        $this
            ->assertTrue(
                $result['status']
            );

        /* Essendo uscito per il break e non per fine file, isFinished deve essere false */
        $this
            ->assertFalse(
                $result['isFinished']
            );

        $this
            ->assertSame(
                5,
                $result['inserted']
            );
    }

    public function testExecuteImportReturnsNoRecordsModified()
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

        $stagingDir
            =
            WRITEPATH
            .
            'uploads/staging/';

        if (
            ! is_dir(
                $stagingDir
            )
        ):
            mkdir(
                $stagingDir,
                0755,
                true
            );
        endif;

        $tempFile
            =
            'test_norecords_'
            .
            time()
            .
            '.csv';

        $filePath
            =
            $stagingDir
            .
            $tempFile;

        /* Creiamo un file contenente esclusivamente l'intestazione, senza record */
        file_put_contents(
            $filePath,
            "id,email,__import_action\n"
        );

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->onlyMethods(
                [
                    'getTableStructure'
                ]
            )
            ->getMock();

        $structure
            =
            [
                [
                    'name'
                    =>
                    'id',
                    'primary_key'
                    =>
                    1
                ],
                [
                    'name'
                    =>
                    'email',
                    'primary_key'
                    =>
                    0
                ]
            ];

        $model
            ->method(
                'getTableStructure'
            )
            ->willReturn(
                $structure
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
                'transStatus'
            )
            ->willReturn(
                true
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

        $adminObj
            =
            new \stdClass();

        $adminObj
            ->uuid
            =
            '123e4567-e89b-12d3-a456-426614174000';

        $adminObj
            ->email
            =
            'aaa@aaa.com';

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

        $entity
            =
            'admins';

        $result
            =
            $model
            ->executeImport(
                $entity,
                $tempFile
            );

        /* Il file doveva essere rimosso automaticamente dal blocco isFinished */
        $this
            ->assertFalse(
                file_exists(
                    $filePath
                )
            );

        $this
            ->assertTrue(
                $result['status']
            );

        $this
            ->assertTrue(
                $result['isFinished']
            );

        $this
            ->assertSame(
                0,
                $result['inserted']
            );

        $this
            ->assertSame(
                0,
                $result['updated']
            );

        $this
            ->assertSame(
                lang(
                    'backend/components/import.messages.importationNoRecordsModified'
                ),
                $result['message']
            );
    }

    public function testBackupTableBeforeImportReturnsTrueWhenTableIsEmpty()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                []
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        $builderMock
            ->method(
                'countAllResults'
            )
            ->with(
                false
            )
            ->willReturn(
                0
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
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
                $model,$modelClass
            );

        $binder($dbMock
        );

        $result
            =
            $model
            ->backupTableBeforeImport(
                'admins'
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testBackupTableBeforeImportReturnsFalseOnFopenFailure()
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
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                []
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        $builderMock
            ->method(
                'countAllResults'
            )
            ->with(
                false
            )
            ->willReturn(
                1
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
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
                $model,$modelClass
            );

        $binder($dbMock
        );

        /* Trucco: inserendo una sottocartella inesistente nel nome entità, fopen() fallirà */
        $entity
            =
            'folder_inesistente/admins';

        set_error_handler(
            function () {
                return true;
            }
        );

        $result
            =
            $model
            ->backupTableBeforeImport(
                $entity
            );

        restore_error_handler();

        $this
            ->assertFalse(
                $result
            );
    }

    public function testBackupTableBeforeImportCreatesBackupFile()
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

        $backupDir
            =
            WRITEPATH
            .
            'backups/imports/';

        if (
            is_dir(
                $backupDir
            )
        ):
            $files
                =
                glob(
                    $backupDir
                    .
                    '*'
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
                $backupDir
            );
        endif;

        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            $this
            ->getMockBuilder(
                $modelClass
            )
            ->disableOriginalConstructor()
            ->onlyMethods(
                []
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

        $builderClass
            =
            \CodeIgniter\Database\BaseBuilder::class;

        $builderMock
            =
            $this
            ->createMock(
                $builderClass
            );

        $builderMock
            ->method(
                'countAllResults'
            )
            ->with(
                false
            )
            ->willReturn(
                2
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

        $records
            =
            [
                [
                    'id'
                    =>
                    1,
                    'name'
                    =>
                    'test1'
                ],
                [
                    'id'
                    =>
                    2,
                    'name'
                    =>
                    'test2'
                ]
            ];

        $resMock
            ->method(
                'getResultArray'
            )
            ->willReturn(
                $records
            );

        $builderMock
            ->method(
                'limit'
            )
            ->willReturnSelf();

        $builderMock
            ->method(
                'get'
            )
            ->willReturn(
                $resMock
            );

        $dbMock
            ->method(
                'table'
            )
            ->willReturn(
                $builderMock
            );

        $dbMock
            ->method(
                'escape'
            )
            ->willReturnCallback(
                function (
                    $val
                ) {
                    return "'"
                        .
                        addslashes(
                            (string) $val
                        )
                        .
                        "'";
                }
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
                $model,$modelClass
            );

        $binder($dbMock
        );

        $entity
            =
            'admins';

        $result
            =
            $model
            ->backupTableBeforeImport(
                $entity
            );

        $this
            ->assertTrue(
                $result
            );

        $this
            ->assertTrue(
                is_dir(
                    $backupDir
                )
            );

        $files
            =
            glob(
                $backupDir
                .
                $entity
                .
                '_backup_*.sql'
            );

        $this
            ->assertNotEmpty(
                $files
            );

        $backupFile
            =
            $files[0];

        $content
            =
            file_get_contents(
                $backupFile
            );

        $this
            ->assertStringContainsString(
                'TRUNCATE TABLE `admins`;',
                $content
            );

        $this
            ->assertStringContainsString(
                "INSERT INTO `admins` (`id`, `name`) VALUES ('1', 'test1');",
                $content
            );

        if (
            file_exists(
                $backupFile
            )
        ):
            unlink(
                $backupFile
            );
        endif;
    }

    public function testBuildDynamicRulesMapsSchemaToValidationRules()
    {
        $modelClass
            =
            \App\Models\Backend\Components\ImportModel::class;

        $model
            =
            new $modelClass();

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'buildDynamicRules'
            );

        $reflection
            ->setAccessible(
                true
            );

        /* Simuliamo un intero schema coprendo il 100% delle varianti del metodo */
        $structure
            =
            [
                [
                    'name'
                    =>
                    'field_int',
                    'type'
                    =>
                    'int',
                    'max_length'
                    =>
                    11
                ],
                [
                    'name'
                    =>
                    'field_decimal',
                    'type'
                    =>
                    'decimal',
                    'max_length'
                    =>
                    10
                ],
                [
                    'name'
                    =>
                    'field_varchar',
                    'type'
                    =>
                    'varchar',
                    'max_length'
                    =>
                    255
                ],
                [
                    'name'
                    =>
                    'field_text',
                    'type'
                    =>
                    'text',
                    'max_length'
                    =>
                    65535
                ],
                [
                    'name'
                    =>
                    'field_date',
                    'type'
                    =>
                    'date',
                    'max_length'
                    =>
                    10
                ],
                [
                    'name'
                    =>
                    'field_datetime',
                    'type'
                    =>
                    'datetime',
                    'max_length'
                    =>
                    19
                ],
                [
                    'name'
                    =>
                    'field_timestamp',
                    'type'
                    =>
                    'timestamp',
                    'max_length'
                    =>
                    19
                ],
                [
                    'name'
                    =>
                    'field_unknown',
                    'type'
                    =>
                    'enum',
                    'max_length'
                    =>
                    5
                ]
            ];

        /* Risultato atteso: mappatura precisa dei filtri CodeIgniter 4 */
        $expected
            =
            [
                'field_int'
                =>
                'permit_empty|integer|max_length[11]',
                'field_decimal'
                =>
                'permit_empty|numeric|max_length[10]',
                'field_varchar'
                =>
                'permit_empty|string|max_length[255]',
                'field_text'
                =>
                'permit_empty|string',
                'field_date'
                =>
                'permit_empty|valid_date[Y-m-d]',
                'field_datetime'
                =>
                'permit_empty|valid_date[Y-m-d H:i:s]',
                'field_timestamp'
                =>
                'permit_empty|valid_date[Y-m-d H:i:s]',
                'field_unknown'
                =>
                'permit_empty|max_length[5]'
            ];

        $result
            =
            $reflection
            ->invoke(
                $model,
                $structure
            );

        $this
            ->assertSame(
                $expected,
                $result
            );
    }

}
