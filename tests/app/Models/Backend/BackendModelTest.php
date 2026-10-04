<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Test\CIUnitTestCase;

class BackendModelTest extends CIUnitTestCase
{
	public function testGetDataReturnsResultsOnSuccess(): void
    {
        /* 1. MOCK DB CON ESITO POSITIVO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRecord = 
            (object) ['id' => 1];
            
        $fakeResult = 
            [
                $fakeRecord
            ];
            
        $mockQuery->method('getResult')->willReturn(
            $fakeResult
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $methodsToMock = 
            [
                'checkAllowedFields',
                'buildTrashFilter',
                'buildFilters',
                'buildDateFilters',
                'getNumRows'
            ];
            
        $builderModel->onlyMethods(
            $methodsToMock
        );
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $filteredPosts = 
            [
                'order' => 'asc',
                'column' => 'id',
                'searchFields' => [],
                'searchDates' => [],
                'page' => 1,
                'rows' => 10
            ];
            
        $model->method('checkAllowedFields')->willReturn(
            $filteredPosts
        );

        $trashSql = 
            ' AND deleted_at IS NULL';
            
        $model->method('buildTrashFilter')->willReturn(
            $trashSql
        );
        
        $totalRowsVal = 
            15;
            
        $model->method('getNumRows')->willReturn(
            $totalRowsVal
        );

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->showAllAllowedFields = 
                    [];
                    
                $this->allowedOrderColumns = 
                    ['id'];
                    
                $this->defaultColumn = 
                    'id';
                    
                $this->getDataQuery = 
                    'SELECT * FROM test_table';
                    
                $this->module = 
                    'test_module';
                    
                $this->hasSoftDelete = 
                    true;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $inputPosts = 
            [];
            
        $result = 
            $model->getData(
                $inputPosts
            );

        /* 4. ASSERZIONE */
        $this->assertTrue(
            $result['result']
        );
        
        $this->assertSame(
            $fakeResult, 
            $result['records']
        );
        
        $expectedPage = 
            1;
            
        $this->assertEquals(
            $expectedPage, 
            $result['pagination']['page']
        );
        
        $expectedLimit = 
            10;
            
        $this->assertEquals(
            $expectedLimit, 
            $result['pagination']['limit']
        );
        
        $expectedTotal = 
            15;
            
        $this->assertEquals(
            $expectedTotal, 
            $result['pagination']['totalRows']
        );
        
        $expectedLast = 
            15;
            
        $this->assertEquals(
            $expectedLast, 
            $result['lastItemPage']
        );
    }

    public function testGetDataHandlesAllFiltersAndCustomPagination(): void
    {
        /* 1. MOCK DB CON ESITO POSITIVO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeResult = 
            [];
            
        $mockQuery->method('getResult')->willReturn(
            $fakeResult
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $methodsToMock = 
            [
                'checkAllowedFields',
                'buildTrashFilter',
                'buildFilters',
                'buildDateFilters',
                'getNumRows'
            ];
            
        $builderModel->onlyMethods(
            $methodsToMock
        );
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $filteredPosts = 
            [
                'order' => 'asc',
                'column' => 'id',
                'trash_filter' => 'trashed',
                'searchFields' => ['name' => 'test'],
                'searchDates' => ['created' => '2026'],
                'page' => 2,
                'rows' => 5
            ];
            
        $model->method('checkAllowedFields')->willReturn(
            $filteredPosts
        );

        $trashSql = 
            ' AND deleted_at IS NOT NULL';
            
        $model->method('buildTrashFilter')->willReturn(
            $trashSql
        );
        
        $fieldsSql = 
            ' AND name = "test"';
            
        $model->method('buildFilters')->willReturn(
            $fieldsSql
        );
        
        $datesSql = 
            ' AND created = "2026"';
            
        $model->method('buildDateFilters')->willReturn(
            $datesSql
        );
        
        $totalRowsVal = 
            20;
            
        $model->method('getNumRows')->willReturn(
            $totalRowsVal
        );

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->showAllAllowedFields = 
                    [];
                    
                $this->allowedOrderColumns = 
                    ['id'];
                    
                $this->defaultColumn = 
                    'id';
                    
                $this->getDataQuery = 
                    'SELECT * FROM test_table';
                    
                $this->module = 
                    'test_module';
                    
                $this->hasSoftDelete = 
                    true;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $inputPosts = 
            [];
            
        $result = 
            $model->getData(
                $inputPosts
            );

        /* 4. ASSERZIONE */
        $this->assertTrue(
            $result['result']
        );
    }

    public function testGetDataReturnsFalseOnException(): void
    {
        /* 0. DEFINIZIONE HELPER GLOBALI (Per bypassare errori nel catch) */
        if ( ! function_exists(__NAMESPACE__ . '\log_message')):
            function log_message(
                $level, 
                $msg
            ) {
                return true;
            }
        endif;

        if ( ! function_exists(__NAMESPACE__ . '\lang')):
            function lang(
                $line
            ) {
                return 'lang_error';
            }
        endif;

        /* 1. MOCK DB CON ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );

        /* 2. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $methodsToMock = 
            [
                'checkAllowedFields',
                'buildTrashFilter',
                'buildFilters',
                'buildDateFilters',
                'getNumRows'
            ];
            
        $builderModel->onlyMethods(
            $methodsToMock
        );
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $filteredPosts = 
            [
                'order' => 'asc',
                'column' => 'id',
                'searchFields' => [],
                'searchDates' => []
            ];
            
        $model->method('checkAllowedFields')->willReturn(
            $filteredPosts
        );

        $trashSql = 
            '';
            
        $model->method('buildTrashFilter')->willReturn(
            $trashSql
        );

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->showAllAllowedFields = 
                    [];
                    
                $this->allowedOrderColumns = 
                    ['id'];
                    
                $this->defaultColumn = 
                    'id';
                    
                $this->getDataQuery = 
                    'SELECT * FROM test_table';
                    
                $this->module = 
                    'test_module';
                    
                $this->hasSoftDelete = 
                    true;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $inputPosts = 
            [];
            
        $result = 
            $model->getData(
                $inputPosts
            );

        /* 4. ASSERZIONE */
        $this->assertFalse(
            $result['result']
        );
    }

    public function testGetNumRowsReturnsCountWithoutFilters(): void
    {
        /* 1. MOCK DB CON RISULTATO CONTEGGIO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['count' => 42];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $methodsToMock = 
            [
                'buildTrashFilter'
            ];
            
        $builderModel->onlyMethods(
            $methodsToMock
        );
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $trashSql = 
            ' AND deleted_at IS NULL';
            
        $model->method('buildTrashFilter')->willReturn(
            $trashSql
        );

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->getNumRowsQuery = 
                    'SELECT COUNT(*) as count FROM test_table WHERE 1=1';
                    
                $this->module = 
                    'test_module';
                    
                $this->hasSoftDelete = 
                    true;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'getNumRows'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE E ASSERZIONE */
        $paramsFilter = 
            [];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                [
                    $paramsFilter
                ]
            );

        $expected = 
            42;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetNumRowsReturnsCountWithAllFilters(): void
    {
        /* 1. MOCK DB CON RISULTATO CONTEGGIO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['count' => 15];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $methodsToMock = 
            [
                'buildTrashFilter',
                'buildFilters',
                'buildDateFilters'
            ];
            
        $builderModel->onlyMethods(
            $methodsToMock
        );
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $trashSql = 
            ' AND deleted_at IS NOT NULL';
            
        $model->method('buildTrashFilter')->willReturn(
            $trashSql
        );
        
        $fieldsSql = 
            ' AND name = "test"';
            
        $model->method('buildFilters')->willReturn(
            $fieldsSql
        );
        
        $datesSql = 
            ' AND created = "2026"';
            
        $model->method('buildDateFilters')->willReturn(
            $datesSql
        );

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->getNumRowsQuery = 
                    'SELECT COUNT(*) as count FROM test_table WHERE 1=1';
                    
                $this->module = 
                    'test_module';
                    
                $this->hasSoftDelete = 
                    true;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'getNumRows'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE E ASSERZIONE */
        $paramsFilter = 
            [
                'trash_filter' => 'trashed',
                'searchFields' => ['name' => 'test'],
                'searchDates' => ['created' => '2026']
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                [
                    $paramsFilter
                ]
            );

        $expected = 
            15;
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testBuildFiltersReturnsEmptyWhenNoAllowedFields(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->showAllSearchAllowedFields = 
                    ['allowed_field'];
                    
                $this->module = 
                    'test_module';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildFilters'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Gestione del passaggio per riferimento) */
        $searchFields = 
            ['ignored_field' => 'value'];
            
        $params = 
            [];
            
        $args = 
            [
                $searchFields,
                &$params
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE STRINGA E ARRAY BY REF */
        $expected = 
            '';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        $expectedParams = 
            [];
            
        $this->assertEquals(
            $expectedParams, 
            $params
        );
    }

    public function testBuildFiltersReturnsClauseAndModifiesParams(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->showAllSearchAllowedFields = 
                    ['valid_field'];
                    
                $this->module = 
                    'test_module';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildFilters'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Mischiamo campo valido e non valido) */
        $searchFields = 
            [
                'valid_field' => 'testval', 
                'invalid_field' => 'skip'
            ];
            
        $params = 
            ['existing_param'];
            
        $args = 
            [
                $searchFields,
                &$params
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE STRINGA E ARRAY BY REF */
        $expected = 
            ' and test_module.valid_field like ?';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        $expectedParams = 
            [
                'existing_param',
                '%testval%'
            ];
            
        $this->assertEquals(
            $expectedParams, 
            $params
        );
    }

    public function testBuildDateFiltersReturnsEmptyWhenNoValidDates(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->showAllSearchAllowedDates = 
                    ['created_at'];
                    
                $this->module = 
                    'test_module';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildDateFilters'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE CON DATE VUOTE O NON AUTORIZZATE */
        $searchDates = 
            [
                'created_at-from' => '   ', 
                'invalid_date-to' => '2026-01-01'
            ];
            
        $params = 
            [];
            
        $args = 
            [
                $searchDates,
                &$params
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE STRINGA E ARRAY BY REF */
        $expected = 
            '';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        $expectedParams = 
            [];
            
        $this->assertEquals(
            $expectedParams, 
            $params
        );
    }

    public function testBuildDateFiltersReturnsClauseAndModifiesParams(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->showAllSearchAllowedDates = 
                    ['created_at'];
                    
                $this->module = 
                    'test_module';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildDateFilters'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE CON ENTRAMBI I LIMITI DATE VALIDI */
        $searchDates = 
            [
                'created_at-from' => '2026-01-01',
                'created_at-to' => '2026-12-31'
            ];
            
        $params = 
            ['existing_param'];
            
        $args = 
            [
                $searchDates,
                &$params
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE STRINGA E ARRAY BY REF */
        $expected = 
            ' and test_module.created_at >= ? and test_module.created_at <= ?';
            
        $this->assertEquals(
            $expected, 
            $result
        );
        
        $expectedParams = 
            [
                'existing_param',
                '2026-01-01',
                '2026-12-31'
            ];
            
        $this->assertEquals(
            $expectedParams, 
            $params
        );
    }

    public function testBuildTrashFilterReturnsEmptyWhenNoSoftDelete(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildTrashFilter'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (hasSoftDelete = false) */
        $filter = 
            'active';
            
        $table = 
            'test_table';
            
        $hasSoftDelete = 
            false;
            
        $args = 
            [
                $filter,
                $table,
                $hasSoftDelete
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $expected = 
            '';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testBuildTrashFilterReturnsTrashedCondition(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildTrashFilter'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Filtro trashed) */
        $filter = 
            'trashed';
            
        $table = 
            'test_table';
            
        $hasSoftDelete = 
            true;
            
        $args = 
            [
                $filter,
                $table,
                $hasSoftDelete
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $expected = 
            ' and test_table.deleted_at IS NOT NULL';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testBuildTrashFilterReturnsEmptyForAll(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildTrashFilter'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Filtro all) */
        $filter = 
            'all';
            
        $table = 
            'test_table';
            
        $hasSoftDelete = 
            true;
            
        $args = 
            [
                $filter,
                $table,
                $hasSoftDelete
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $expected = 
            '';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testBuildTrashFilterReturnsActiveConditionAndSanitizesTable(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'buildTrashFilter'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Filtro default e tabella da sanitizzare) */
        $filter = 
            'active';
            
        $table = 
            'in;val!id_t@ble1';
            
        $hasSoftDelete = 
            true;
            
        $args = 
            [
                $filter,
                $table,
                $hasSoftDelete
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE (La tabella in;val!id_t@ble1 deve diventare invalid_tble1) */
        $expected = 
            ' and invalid_tble1.deleted_at IS NULL';
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetByUUIDReturnsRowOnSuccess(): void
    {
        /* 1. MOCK DB CON RISULTATO POSITIVO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['uuid' => '123-abc'];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->getUUIDQuery = 
                    'SELECT * FROM test WHERE uuid = ?';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getByUUID(
                $uuid
            );

        /* 4. ASSERZIONE */
        $this->assertTrue(
            $result['result']
        );
        
        $this->assertSame(
            $fakeRow, 
            $result['row']
        );
    }

    public function testGetByUUIDReturnsFalseWhenNotFound(): void
    {
        /* 1. MOCK DB CON RISULTATO VUOTO */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(
            $mockQuery
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->getUUIDQuery = 
                    'SELECT * FROM test WHERE uuid = ?';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getByUUID(
                $uuid
            );

        /* 4. ASSERZIONE (Utilizziamo il lang() nativo per l'attesa) */
        $this->assertFalse(
            $result['result']
        );
        
        $expectedMsg = 
            lang('backend/global.messages.UUIDNotFound');
            
        $this->assertEquals(
            $expectedMsg, 
            $result['message']
        );
    }

    public function testGetByUUIDReturnsFalseOnException(): void
    {
        /* 0. DEFINIZIONE HELPER GLOBALI */
        if ( ! function_exists(__NAMESPACE__ . '\log_message')):
            function log_message(
                $level, 
                $msg
            ) {
                return true;
            }
        endif;

        /* 1. MOCK DB CON ECCEZIONE FORZATA */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $exception = 
            new \Exception('Simulated DB Crash');
            
        $mockDb->method('query')->willThrowException(
            $exception
        );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
                    
                $this->getUUIDQuery = 
                    'SELECT * FROM test WHERE uuid = ?';
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. ESECUZIONE */
        $uuid = 
            '123-abc';
            
        $result = 
            $model->getByUUID(
                $uuid
            );

        /* 4. ASSERZIONE (Utilizziamo il lang() nativo per l'attesa) */
        $this->assertFalse(
            $result['result']
        );
        
        $expectedMsg = 
            lang('backend/global.messages.getUUIDError');
            
        $this->assertEquals(
            $expectedMsg, 
            $result['message']
        );
    }

    public function testHasDataChangedReturnsFalseWhenNoChanges(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->toCompare = 
                    ['title', 'status'];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'hasDataChanged'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Dati identici, il cast a stringa azzera la differenza di tipo 1 vs '1') */
        $posts = 
            ['title' => 'Test', 'status' => '1'];
            
        $original = 
            (object) ['title' => 'Test', 'status' => 1];
            
        $args = 
            [
                $posts, 
                $original
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $this->assertFalse(
            $result
        );
    }

    public function testHasDataChangedReturnsTrueOnTextFieldChange(): void
    {
        /* 1. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->toCompare = 
                    ['title', 'status'];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'hasDataChanged'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Dato testuale modificato) */
        $posts = 
            ['title' => 'Test Modificato', 'status' => '1'];
            
        $original = 
            (object) ['title' => 'Test', 'status' => '1'];
            
        $args = 
            [
                $posts, 
                $original
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $this->assertTrue(
            $result
        );
    }

    public function testHasDataChangedReturnsTrueOnFileUpload(): void
    {
        /* 1. MOCK FILE UPLOAD */
        $builderFile = 
            $this->getMockBuilder(\CodeIgniter\HTTP\Files\UploadedFile::class);
            
        $builderFile->disableOriginalConstructor();
        
        $mockFile = 
            $builderFile->getMock();
            
        $mockFile->method('isValid')->willReturn(true);
        
        $mockFile->method('hasMoved')->willReturn(false);

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() {
                $this->toCompare = 
                    ['title'];
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'hasDataChanged'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE (Dato testuale uguale, ma file presente in array) */
        $posts = 
            ['title' => 'Test', 'images' => [$mockFile]];
            
        $original = 
            (object) ['title' => 'Test'];
            
        $args = 
            [
                $posts, 
                $original
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 5. ASSERZIONE */
        $this->assertTrue(
            $result
        );
    }

    public function testInsertImagesInAddModeSetsFirstAsCover(): void
    {
        /* 1. MOCK DB CON SUCCESSO (Solo query di insert) */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $insertSql = null;
        $insertBind = null;

        $mockDb
            ->expects(
                $this->once()
            )
            ->method('query')
            ->willReturnCallback(
                static function(string $sql, array $bind) use (&$insertSql, &$insertBind): bool {
                    $insertSql = $sql;
                    $insertBind = $bind;
                    return true;
                }
            );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'insertImages'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE */
        $filenames = 
            ['img1.jpg', 'img2.jpg'];
            
        $uuid = 
            '123-abc';
            
        $entity = 
            'test_entity';
            
        $action = 
            'add';
            
        $args = 
            [
                $filenames, 
                $uuid, 
                $entity, 
                $action
            ];
            
        $reflection->invokeArgs(
            $model, 
            $args
        );

        /* 5. ASSERZIONI */
        $this->assertSame(
            'insert into images (entity, entity_uuid, filename, is_cover, created_at) values (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)',
            $insertSql
        );
        $this->assertSame(
            ['test_entity', '123-abc', 'img1.jpg', '1'],
            array_slice($insertBind, 0, 4)
        );
        $this->assertSame(
            ['test_entity', '123-abc', 'img2.jpg', '0'],
            array_slice($insertBind, 5, 4)
        );
    }

    public function testInsertImagesInEditModeWithExistingCoverSetsAllToZero(): void
    {
        /* 1. MOCK QUERY E DB (Select che trova la riga + Insert successiva) */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $fakeRow = 
            (object) ['1' => 1];
            
        $mockQuery->method('getRow')->willReturn(
            $fakeRow
        );

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $queries = [];

        $mockDb
            ->expects(
                $this->exactly(2)
            )
            ->method('query')
            ->willReturnCallback(
                static function(string $sql, array $bind) use (&$queries, $mockQuery) {
                    $queries[] = [$sql, $bind];
                    return count($queries) === 1 ? $mockQuery : true;
                }
            );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'insertImages'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE */
        $filenames = 
            ['img1.jpg'];
            
        $uuid = 
            '123-abc';
            
        $entity = 
            'test_entity';
            
        $action = 
            'edit';
            
        $args = 
            [
                $filenames, 
                $uuid, 
                $entity, 
                $action
            ];
            
        $reflection->invokeArgs(
            $model, 
            $args
        );

        /* 5. ASSERZIONI */
        $this->assertSame(
            'select 1 from images where entity_uuid = ? and is_cover = ? and entity = ? limit 1',
            $queries[0][0]
        );
        $this->assertSame(['123-abc', '1', 'test_entity'], $queries[0][1]);
        $this->assertSame(
            ['test_entity', '123-abc', 'img1.jpg', '0'],
            array_slice($queries[1][1], 0, 4)
        );
    }

    public function testInsertImagesInEditModeWithoutExistingCoverSetsFirstAsCover(): void
    {
        /* 1. MOCK QUERY E DB (Select che NON trova la riga + Insert successiva) */
        $builderQuery = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseResult::class);
            
        $builderQuery->disableOriginalConstructor();
        
        $mockQuery = 
            $builderQuery->getMock();
            
        $mockQuery->method('getRow')->willReturn(null);

        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $queries = [];

        $mockDb
            ->expects(
                $this->exactly(2)
            )
            ->method('query')
            ->willReturnCallback(
                static function(string $sql, array $bind) use (&$queries, $mockQuery) {
                    $queries[] = [$sql, $bind];
                    return count($queries) === 1 ? $mockQuery : true;
                }
            );

        /* 2. MOCK MODEL ASTRATTO E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        $injector = 
            function() use (
                $mockDb
            ) {
                $this->db = 
                    $mockDb;
            };
            
        $boundInjector = 
            \Closure::bind(
                $injector, 
                $model, 
                \App\Models\Backend\BackendModel::class
            );
            
        $boundInjector();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'insertImages'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE */
        $filenames = 
            ['img1.jpg'];
            
        $uuid = 
            '123-abc';
            
        $entity = 
            'test_entity';
            
        $action = 
            'edit';
            
        $args = 
            [
                $filenames, 
                $uuid, 
                $entity, 
                $action
            ];
            
        $reflection->invokeArgs(
            $model, 
            $args
        );

        /* 5. ASSERZIONI */
        $this->assertSame(
            'select 1 from images where entity_uuid = ? and is_cover = ? and entity = ? limit 1',
            $queries[0][0]
        );
        $this->assertSame(['123-abc', '1', 'test_entity'], $queries[0][1]);
        $this->assertSame(
            ['test_entity', '123-abc', 'img1.jpg', '1'],
            array_slice($queries[1][1], 0, 4)
        );
    }

    public function testRrmdirReturnsSilentlyIfDirectoryDoesNotExist(): void
    {
        /* 1. DEFINIZIONE PATH INESISTENTE */
        $fakeDir = 
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fake_dir_' . uniqid();

        /* 2. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'rrmdir'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE */
        $args = 
            [
                $fakeDir
            ];
            
        $reflection->invokeArgs(
            $model, 
            $args
        );

        /* 5. ASSERZIONE */
        $this->assertDirectoryDoesNotExist($fakeDir);
    }

    public function testRrmdirDeletesDirectoryAndContentsRecursively(): void
    {
        /* 1. CREAZIONE STRUTTURA DI TEST TEMPORANEA REALE */
        $tempDir = 
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rrmdir_' . uniqid();
            
        mkdir(
            $tempDir
        );
        
        $subDir = 
            $tempDir . DIRECTORY_SEPARATOR . 'subdir';
            
        mkdir(
            $subDir
        );
        
        $fileOne = 
            $tempDir . DIRECTORY_SEPARATOR . 'file1.txt';
            
        file_put_contents(
            $fileOne, 
            'dummy_content'
        );
        
        $fileTwo = 
            $subDir . DIRECTORY_SEPARATOR . 'file2.txt';
            
        file_put_contents(
            $fileTwo, 
            'dummy_content'
        );

        /* 2. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 3. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'rrmdir'
            );
            
        $reflection->setAccessible(true);

        /* 4. ESECUZIONE (Cancellazione ricorsiva dell'intero albero) */
        $args = 
            [
                $tempDir
            ];
            
        $reflection->invokeArgs(
            $model, 
            $args
        );

        /* 5. ASSERZIONE (La cartella radice non deve più esistere sul disco) */
        $isDeleted = 
            ! is_dir(
                $tempDir
            );
            
        $this->assertTrue(
            $isDeleted
        );
    }

    public function testGenerateUUIDReturnsValidV4Format(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'generateUUID'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE */
        $args = 
            [];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE (RegExp che forza la presenza del '4' e di '8,9,a,b' nelle posizioni corrette) */
        $pattern = 
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            
        $isMatch = 
            preg_match(
                $pattern, 
                $result
            );
            
        $expected = 
            1;
            
        $this->assertEquals(
            $expected, 
            $isMatch
        );
    }

    public function testGenerateUUIDReturnsUniqueValues(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'generateUUID'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE (Generazione multipla) */
        $args = 
            [];
            
        $uuidOne = 
            $reflection->invokeArgs(
                $model, 
                $args
            );
            
        $uuidTwo = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE (Garantisce che non vi siano collisioni) */
        $this->assertNotEquals(
            $uuidOne, 
            $uuidTwo
        );
    }

    public function testCheckAllowedFieldsKeepsAllValidKeys(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION PER ACCEDERE AL METODO PROTECTED */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'checkAllowedFields'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE CON CAMPI TUTTI VALIDI */
        $posts = 
            [
                'title' => 'My Title',
                'status' => 'active'
            ];
            
        $allowedFields = 
            [
                'title',
                'status'
            ];
            
        $args = 
            [
                $posts,
                $allowedFields
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE */
        $expected = 
            [
                'title' => 'My Title',
                'status' => 'active'
            ];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testCheckAllowedFieldsRemovesInvalidKeys(): void
    {
        /* 1. MOCK MODEL ASTRATTO */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\BackendModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMockForAbstractClass();

        /* 2. REFLECTION */
        $modelClass = 
            get_class(
                $model
            );
            
        $reflection = 
            new \ReflectionMethod(
                $modelClass, 
                'checkAllowedFields'
            );
            
        $reflection->setAccessible(true);

        /* 3. ESECUZIONE CON CAMPI INTRUSI */
        $posts = 
            [
                'title' => 'My Title',
                'malicious_field' => 'Drop Table',
                'status' => 'active',
                'unwanted_key' => 123
            ];
            
        $allowedFields = 
            [
                'title',
                'status'
            ];
            
        $args = 
            [
                $posts,
                $allowedFields
            ];
            
        $result = 
            $reflection->invokeArgs(
                $model, 
                $args
            );

        /* 4. ASSERZIONE (Le chiavi malicious_field e unwanted_key devono essere assenti) */
        $expected = 
            [
                'title' => 'My Title',
                'status' => 'active'
            ];
            
        $this->assertEquals(
            $expected, 
            $result
        );
    }

    public function testGetUploadServiceReturnsInstance()
    {
        helper('settings');

        /* Instanzia il BackendModel (essendo astratto, usiamo getMockForAbstractClass) */
        $model 
            = 
            $this
            ->getMockForAbstractClass(\App\Models\Backend\BackendModel::class);
            
        /* Reflection per sbloccare il metodo protetto */
        $reflection 
            = new \ReflectionMethod(
                get_class(
                    $model
                )
                , 
                'getUploadService'
            );
            
        $reflection
            ->setAccessible(true);
            
        /* Invocazione del metodo */
        $result 
            = 
            $reflection
            ->invoke(
                $model
            );
            
        /* Verifica che restituisca l'oggetto corretto */
        $this
            ->assertInstanceOf(
                \App\Libraries\Backend\UploadClass::class
                , 
                $result
            );
    }
}
