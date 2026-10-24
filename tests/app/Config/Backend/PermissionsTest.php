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

        $this->assertCount(3, $result);

        $this->assertSame('brands', $result[0]['controller']);
        $this->assertArrayHasKey('brands_add', $result[0]['perms']);
        $this->assertArrayHasKey('brands_edit', $result[0]['perms']);
        $this->assertArrayHasKey('brands_show', $result[0]['perms']);
        $this->assertArrayHasKey('brands_show_all', $result[0]['perms']);
        $this->assertArrayHasKey('brands_delete', $result[0]['perms']);
        $this->assertArrayHasKey('brands_change_status', $result[0]['perms']);

        $this->assertSame('categories', $result[1]['controller']);
        $this->assertArrayHasKey('categories_add', $result[1]['perms']);
        $this->assertArrayHasKey('categories_edit', $result[1]['perms']);
        $this->assertArrayHasKey('categories_show', $result[1]['perms']);
        $this->assertArrayHasKey('categories_show_all', $result[1]['perms']);
        $this->assertArrayHasKey('categories_delete', $result[1]['perms']);
        $this->assertArrayHasKey('categories_change_status', $result[1]['perms']);

        $this->assertSame('products', $result[2]['controller']);
        $this->assertArrayHasKey('products_add', $result[2]['perms']);
        $this->assertArrayHasKey('products_edit', $result[2]['perms']);
        $this->assertArrayHasKey('products_show', $result[2]['perms']);
        $this->assertArrayHasKey('products_show_all', $result[2]['perms']);
        $this->assertArrayHasKey('products_delete', $result[2]['perms']);
        $this->assertArrayHasKey('products_change_status', $result[2]['perms']);
    }

    public function testPermissionCatalogHasCompleteAndUniqueEntries(): void
    {
        $groups = (new Permissions())->getPermissions();
        $permissionNames = [];

        foreach ($groups as $group):
            $this->assertSame(['title', 'controller', 'icon', 'perms'], array_keys($group));
            $this->assertNotSame('', $group['title']);
            $this->assertNotSame('', $group['controller']);
            $this->assertNotSame('', $group['icon']);
            $this->assertNotEmpty($group['perms']);

            foreach ($group['perms'] as $permission => $label):
                $this->assertStringStartsWith($group['controller'] . '_', $permission);
                $this->assertNotSame('', $label);
                $permissionNames[] = $permission;
            endforeach;
        endforeach;

        $this->assertSame($permissionNames, array_values(array_unique($permissionNames)));
    }
}
