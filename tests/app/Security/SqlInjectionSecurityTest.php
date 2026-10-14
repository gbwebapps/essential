<?php

namespace Tests\App\Security;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\Backend\AdminsModel;

class SqlInjectionSecurityTest extends CIUnitTestCase
{
    public function testSqlInjectionOnPermissions(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $payload = "' OR 1=1 -- ";

        $result = $model->getPermissions($payload);

        $this->assertSame([], $result);
    }

    public function testSqlInjectionOnTokens(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $payload = "' OR 1=1 -- ";

        $result = $model->getTokens($payload);

        $this->assertSame([], $result);
    }


    public function testSqlInjectionOnOrderBy(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $posts = [
            'column' => 'id desc; drop table admins; --',
            'order' => 'asc',
            'page' => 1,
            'rows' => 5,
            'searchFields' => [],
            'searchDates' => [],
            'trash_filter' => 'active',
        ];

        $result = $model->getData($posts);

        $this->assertTrue($result['result']);
        $this->assertIsArray($result['records']);
        $this->assertLessThanOrEqual(5, count($result['records']));

        $this->assertTrue(
            db_connect()->tableExists('admins')
        );
    }


    public function testSqlInjectionOnSearchFields(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $posts = [
            'column' => 'id',
            'order' => 'asc',
            'page' => 1,
            'rows' => 5,
            'searchFields' => [
                'email' => "' OR 1=1 -- ",
            ],
            'searchDates' => [],
            'trash_filter' => 'active',
        ];

        $result = $model->getData($posts);

        $this->assertTrue($result['result']);
        $this->assertSame([], $result['records']);
        $this->assertSame(0, $result['pagination']['totalRows']);
    }

    public function testUnauthorizedSearchColumnIsIgnored(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $posts = [
            'column' => 'id',
            'order' => 'asc',
            'page' => 1,
            'rows' => 5,
            'searchFields' => [
                'superadmin' => "' OR 1=1 -- ",
            ],
            'searchDates' => [],
            'trash_filter' => 'active',
        ];

        $result = $model->getData($posts);

        $this->assertTrue($result['result']);
        $this->assertIsArray($result['records']);
    }


    public function testSqlInjectionOnDateFilters(): void
    {
        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('essential_test', db_connect()->getDatabase());

        $model = new AdminsModel();

        $posts = [
            'column' => 'id',
            'order' => 'asc',
            'page' => 1,
            'rows' => 5,
            'searchFields' => [],
            'searchDates' => [
                'created_at-from' => "2020-01-01' OR 1=1 -- ",
            ],
            'trash_filter' => 'active',
        ];

        $result = $model->getData($posts);

        $this->assertTrue($result['result']);
        $this->assertIsArray($result['records']);
        $this->assertIsArray($result['pagination']);

        $this->assertTrue(db_connect()->tableExists('admins'));
    }

}
