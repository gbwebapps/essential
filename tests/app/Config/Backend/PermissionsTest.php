<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Permissions;

class PermissionsTest extends CIUnitTestCase
{
    public function testConfigurationCanBeInstantiated(): void
    {
        $config = new Permissions();

        $this->assertInstanceOf(Permissions::class, $config);
        $this->assertSame([], $config->permissions);
    }

    public function testGetPermissionsReturnsExpectedStructure(): void
    {
        $config = new Permissions();

        $result = $config->getPermissions();

        $this->assertCount(2, $result);

        $this->assertSame('users', $result[0]['controller']);
        $this->assertArrayHasKey('users_index', $result[0]['perms']);
        $this->assertArrayHasKey('users_showall', $result[0]['perms']);
        $this->assertArrayHasKey('users_show', $result[0]['perms']);
        $this->assertArrayHasKey('users_delete', $result[0]['perms']);

        $this->assertSame('messages', $result[1]['controller']);
        $this->assertArrayHasKey('messages_index', $result[1]['perms']);
        $this->assertArrayHasKey('messages_showall', $result[1]['perms']);
        $this->assertArrayHasKey('messages_show', $result[1]['perms']);
        $this->assertArrayHasKey('messages_delete', $result[1]['perms']);
    }
}