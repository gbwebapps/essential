<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Test\CIUnitTestCase;

class AuditsModelTest extends CIUnitTestCase
{
	public function testLogActivityReturnsTrueWithIdentity(): void
    {
        /* 1. MOCK REQUEST (Recuperata tramite Services) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('192.168.1.1');
        
        $mockRequest->method('getUserAgent')->willReturn('TestAgent');
        
        \CodeIgniter\Config\Services::injectMock(
            'request', 
            $mockRequest
        );

        /* 2. MOCK DB CON SUCCESSO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AuditsModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE (Con Identity) */
        $action = 
            'CREATE';
            
        $section = 
            'users';
            
        $details = 
            'Created a new user';
            
        $identity = 
            (object) ['uuid' => '123-abc', 'email' => 'admin@test.com'];

        $result = 
            $model->logActivity(
                $action, 
                $section, 
                $details, 
                $identity
            );

        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );

        /* 5. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testLogActivityReturnsTrueWithoutIdentity(): void
    {
        /* 1. MOCK REQUEST (Recuperata tramite Services) */
        $builderReq = 
            $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class);
            
        $builderReq->disableOriginalConstructor();
        
        $mockRequest = 
            $builderReq->getMock();
            
        $mockRequest->method('getIPAddress')->willReturn('192.168.1.1');
        
        $mockRequest->method('getUserAgent')->willReturn('TestAgent');
        
        \CodeIgniter\Config\Services::injectMock(
            'request', 
            $mockRequest
        );

        /* 2. MOCK DB CON SUCCESSO */
        $builderDb = 
            $this->getMockBuilder(\CodeIgniter\Database\BaseConnection::class);
            
        $builderDb->disableOriginalConstructor();
        
        $mockDb = 
            $builderDb->getMock();
            
        $mockDb->method('query')->willReturn(true);

        /* 3. MOCK MODEL E INIEZIONE */
        $builderModel = 
            $this->getMockBuilder(\App\Models\Backend\AuditsModel::class);
            
        $builderModel->disableOriginalConstructor();
        
        $builderModel->onlyMethods([]);
        
        $model = 
            $builderModel->getMock();

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
                \App\Models\Backend\AuditsModel::class
            );
            
        $boundInjector();

        /* 4. ESECUZIONE E ASSERZIONE (Senza Identity) */
        $action = 
            'LOGIN';
            
        $section = 
            'auth';
            
        $details = 
            'Failed login attempt';

        $result = 
            $model->logActivity(
                $action, 
                $section, 
                $details, 
                null
            );

        $expected = 
            true;
            
        $this->assertEquals(
            $expected, 
            $result
        );

        /* 5. PULIZIA SERVIZI */
        \CodeIgniter\Config\Services::reset();
    }

    public function testShowAllValidationRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuditsModel();

        $rules = 
            $model->showAllValidationRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'column',
            $rules
        );

        $this->assertArrayHasKey(
            'order',
            $rules
        );

        $this->assertArrayHasKey(
            'page',
            $rules
        );

        $this->assertArrayHasKey(
            'rows',
            $rules
        );

        $columnConfig = 
            $rules['column'];

        $columnRulesList = 
            $columnConfig['rules'];

        $this->assertContains(
            'required',
            $columnRulesList
        );
    }

    public function testShowAllSearchValidationRulesReturnsCorrectArray(): void
    {
        $model = 
            new \App\Models\Backend\AuditsModel();

        $rules = 
            $model->showAllSearchValidationRules();

        $this->assertIsArray(
            $rules
        );

        $this->assertArrayHasKey(
            'searchFields.username',
            $rules
        );

        $this->assertArrayHasKey(
            'searchFields.action',
            $rules
        );

        $this->assertArrayHasKey(
            'searchFields.section',
            $rules
        );

        $this->assertArrayHasKey(
            'searchFields.details',
            $rules
        );

        $this->assertArrayHasKey(
            'searchDates.created_at-from',
            $rules
        );

        $this->assertArrayHasKey(
            'searchDates.created_at-to',
            $rules
        );

        $usernameConfig = 
            $rules['searchFields.username'];

        $usernameRulesList = 
            $usernameConfig['rules'];

        $this->assertContains(
            'permit_empty',
            $usernameRulesList
        );

        $dateConfig = 
            $rules['searchDates.created_at-from'];

        $dateRulesList = 
            $dateConfig['rules'];

        $this->assertContains(
            'valid_date[Y-m-d H:i:s]',
            $dateRulesList
        );
    }
}