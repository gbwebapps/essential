<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\AdminsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class AdminsModelIntegrationTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private AdminsModel $model;
    private string $adminUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = db_connect();
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->getDatabase());

        $identity = $this->database
            ->query('select uuid, email from admins order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($identity);

        $authorization = $this->createMock(AuthorizationClass::class);
        $authorization->method('currentAdmin')->willReturn($identity);
        Services::injectMock('authorization', $authorization);

        $this->adminUuid = $this->generateUuid();
        $group = $this->database
            ->query('select id from admins_groups order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($group);

        $this->database->query(
            'insert into admins (uuid, firstname, lastname, email, phone, status, superadmin, group_id, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->adminUuid,
                'Integration',
                'Admin',
                $this->adminUuid . '@example.test',
                '+390000000001',
                1,
                0,
                (int) $group->id,
                'Integration test',
                date('Y-m-d H:i:s')
            ]
        );

        $this->model = new AdminsModel();
    }

    protected function tearDown(): void
    {
        if (isset($this->database, $this->adminUuid)):
            foreach (['admins_logs', 'admins_permissions', 'admins_tokens', 'admins_2fa_codes', 'admins_2fa_attempts', 'admins_2fa'] as $table):
                $this->database->query("delete from {$table} where admin_uuid = ?", [$this->adminUuid]);
            endforeach;

            $this->database->query('delete from images where entity = ? and entity_uuid = ?', ['admins', $this->adminUuid]);
            $this->database->query('delete from admins where uuid = ?', [$this->adminUuid]);
            \App\Libraries\ImageFileSystemService::removeAllImages('admins', $this->adminUuid);
        endif;

        Services::resetSingle('authorization');

        parent::tearDown();
    }

    public function testAdminLifecyclePersistsAcrossRealTransactions(): void
    {
        $admin = $this->database
            ->query('select group_id from admins where uuid = ?', [$this->adminUuid])
            ->getRow();

        $result = $this->model->edit([
            'uuid' => $this->adminUuid,
            'firstname' => 'Integration',
            'lastname' => 'Updated',
            'email' => $this->adminUuid . '@example.test',
            'phone' => '+390000000002',
            'status' => '1',
            'group_id' => (string) $admin->group_id,
            'note' => 'Updated integration test',
            'permissions' => [],
            'images' => []
        ]);

        $this->assertTrue($result['result']);
        $this->assertSame(
            'Updated',
            $this->database->query('select lastname from admins where uuid = ?', [$this->adminUuid])->getRow()->lastname
        );

        $result = $this->model->changeStatus(['uuid' => $this->adminUuid]);

        $this->assertTrue($result['result']);
        $this->assertSame(
            0,
            (int) $this->database->query('select status from admins where uuid = ?', [$this->adminUuid])->getRow()->status
        );

        $result = $this->model->changePermission([
            'uuid' => $this->adminUuid,
            'permission' => 'brands_delete'
        ]);

        $this->assertTrue($result['result']);
        $groupPermissions = $this->model->getGroupPermissions((int) $admin->group_id);
        $exceptions = $this->model->getAdminExceptions($this->adminUuid);
        $brandsDeleteEnabled = array_key_exists('brands_delete', $exceptions)
            ? $exceptions['brands_delete'] === 1
            : in_array('brands_delete', $groupPermissions, true);

        $this->assertTrue($brandsDeleteEnabled);

        $this->database->query(
            'insert into admins_tokens (admin_uuid, token_hash, token_create, last_activity, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->adminUuid,
                hash('sha256', $this->adminUuid),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s', time() + 3600),
                'session',
                'Integration Agent',
                '127.0.0.1',
                date('Y-m-d H:i:s')
            ]
        );

        $tokenId = $this->database->insertID();

        $this->database->query(
            'insert into admins_logs (token_id, admin_uuid, username, login, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tokenId, $this->adminUuid, 'integration@example.test', date('Y-m-d H:i:s'), 'session', 'Integration Agent', '127.0.0.1', date('Y-m-d H:i:s')]
        );

        $tokens = $this->model->getTokens($this->adminUuid);
        $this->assertCount(1, $tokens);
        $this->assertObjectNotHasProperty('token_hash', $tokens[0]);

        $result = $this->model->softDelete(['uuid' => $this->adminUuid]);

        $this->assertTrue($result['result']);
        $this->assertNull(
            $this->database->query('select id from admins_tokens where admin_uuid = ?', [$this->adminUuid])->getRow()
        );

        $log = $this->database
            ->query('select logout, logout_reason from admins_logs where admin_uuid = ?', [$this->adminUuid])
            ->getRow();

        $this->assertNotNull($log->logout);
        $this->assertSame('deleted', $log->logout_reason);

        $result = $this->model->restoreDelete(['uuid' => $this->adminUuid]);

        $this->assertTrue($result['result']);
        $restoredAdmin = $this->database->query('select deleted_at, restored_at from admins where uuid = ?', [$this->adminUuid])->getRow();
        $this->assertNull($restoredAdmin->deleted_at);
        $this->assertNotNull($restoredAdmin->restored_at);

        $this->assertTrue($this->model->softDelete(['uuid' => $this->adminUuid])['result']);
        $this->assertTrue($this->model->hardDelete(['uuid' => $this->adminUuid])['result']);
        $this->assertNull(
            $this->database->query('select uuid from admins where uuid = ?', [$this->adminUuid])->getRow()
        );
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);

        return vsprintf('%08s-%04s-%04s-%04s-%12s', sscanf($hex, '%8s%4s%4s%4s%12s'));
    }
}
