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

        $this->assertSame(config(\Config\Database::class)->tests['database'], $db->getDatabase());

        $result = $db->query('select database() as db_name')->getRow();

        $this->assertSame(config(\Config\Database::class)->tests['database'], $result->db_name);
    }
}
