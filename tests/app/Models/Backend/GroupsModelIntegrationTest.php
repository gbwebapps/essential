<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\GroupsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class GroupsModelIntegrationTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private GroupsModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = db_connect();
        $this->assertSame('essential_test', $this->database->getDatabase());

        $this->database->transBegin();

        $identity = $this->database
            ->query('select uuid, email from admins order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($identity);

        $authorization = $this->createMock(AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn($identity);
        Services::injectMock('authorization', $authorization);

        $this->model = new GroupsModel();
    }

    protected function tearDown(): void
    {
        $this->database->transRollback();
        Services::resetSingle('authorization');

        parent::tearDown();
    }

    public function testGroupCrudPersistsPermissionsInsideRealTransactions(): void
    {
        $name = 'Integration Group ' . bin2hex(random_bytes(4));

        $result = $this->model->add([
            'name' => $name,
            'description' => 'Integration group',
            'permissions' => ['brands_add', 'brands_show_all']
        ]);

        $this->assertTrue($result['result']);

        $group = $this->database
            ->query('select id, name, description from admins_groups where name = ?', [$name])
            ->getRow();

        $this->assertNotNull($group);
        $this->assertSame(['brands_add', 'brands_show_all'], $this->sortedPermissions((int) $group->id));

        $result = $this->model->edit([
            'id' => (int) $group->id,
            'name' => $name,
            'description' => 'Updated integration group',
            'permissions' => ['brands_show']
        ]);

        $this->assertTrue($result['result']);
        $this->assertSame(['brands_show'], $this->sortedPermissions((int) $group->id));

        $result = $this->model->del(['id' => (int) $group->id]);

        $this->assertTrue($result['result']);
        $this->assertNull(
            $this->database->query('select id from admins_groups where id = ?', [$group->id])->getRow()
        );
    }

    public function testSaveExceptionsPersistsTheEffectivePermissionDelta(): void
    {
        $admin = $this->database
            ->query('select uuid, group_id from admins where superadmin = 0 and deleted_at is null order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($admin);

        $activePermissions = $this->effectivePermissions($admin->uuid, (int) $admin->group_id);
        $permission = 'brands_delete';

        if (in_array($permission, $activePermissions, true)):
            $submittedPermissions = array_values(array_diff($activePermissions, [$permission]));
        else:
            $submittedPermissions = [...$activePermissions, $permission];
        endif;

        $result = $this->model->saveExceptions([
            'uuid' => $admin->uuid,
            'permissions' => $submittedPermissions
        ]);

        $this->assertTrue($result['result']);

        sort($submittedPermissions);

        $this->assertSame(
            $submittedPermissions,
            $this->effectivePermissions($admin->uuid, (int) $admin->group_id)
        );
    }

    private function sortedPermissions(int $groupId): array
    {
        $permissions = $this->model->getGroupPermissionsArray($groupId);
        sort($permissions);

        return $permissions;
    }

    private function effectivePermissions(string $adminUuid, int $groupId): array
    {
        $permissions = $this->model->getGroupPermissionsArray($groupId);

        foreach ($this->model->getAdminExceptionsArray($adminUuid) as $permission => $allow):
            if ($allow === 1 && ! in_array($permission, $permissions, true)):
                $permissions[] = $permission;
            elseif ($allow === 0):
                $permissions = array_diff($permissions, [$permission]);
            endif;
        endforeach;

        $permissions = array_values($permissions);
        sort($permissions);

        return $permissions;
    }
}
