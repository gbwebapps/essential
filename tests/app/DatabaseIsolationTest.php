<?php

namespace Tests\App;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class DatabaseIsolationTest extends CIUnitTestCase
{
    public function testDatabaseIsolation(): void
    {
        $config = config('Database');

        $this->assertSame('testing', ENVIRONMENT);
        $this->assertSame('tests', $config->defaultGroup);

        $db = Database::connect();

        $this->assertSame('essential_test', $db->getDatabase());

        $result = $db->query('select database() as db_name')->getRow();

        $this->assertSame('essential_test', $result->db_name);
    }
}
