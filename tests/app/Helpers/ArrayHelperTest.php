<?php declare(strict_types = 1);

namespace App\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

class ArrayHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('array');
    }

    public function testRemoveDotRemovesPrefixFromKeys(): void
    {
        $result = removeDot('searchFields.', [
            'searchFields.name' => 'Error name',
            'searchFields.email' => 'Error email'
        ]);

        $this->assertSame([
            'name' => 'Error name',
            'email' => 'Error email'
        ], $result);
    }

    public function testRemoveDotLeavesKeysWithoutPrefixUnchanged(): void
    {
        $result = removeDot('searchFields.', [
            'name' => 'Error name'
        ]);

        $this->assertSame([
            'name' => 'Error name'
        ], $result);
    }

    public function testRemoveDotReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], removeDot('test.', []));
    }

    public function testRemoveDotPermissionsGroupsMatchingKeys(): void
    {
        $result = removeDotPermissions('permissions', [
            'permissions.0' => 'First error',
            'permissions.1' => 'Second error',
            'name' => 'Name error'
        ]);

        $this->assertSame([
            'permissions' => 'Second error',
            'name' => 'Name error'
        ], $result);
    }

    public function testRemoveDotPermissionsLeavesUnrelatedKeysUnchanged(): void
    {
        $result = removeDotPermissions('permissions', [
            'name' => 'Name error'
        ]);

        $this->assertSame([
            'name' => 'Name error'
        ], $result);
    }
}