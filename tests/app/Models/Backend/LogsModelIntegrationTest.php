<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\LogsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class LogsModelIntegrationTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private LogsModel $model;
    private string $adminUuid;
    private int $auditStartId;

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

        $this->auditStartId = (int) ($this->database
            ->query('select coalesce(max(id), 0) as id from admins_audits')
            ->getRow()->id ?? 0);

        $group = $this->database
            ->query('select id from admins_groups order by id asc limit 1')
            ->getRow();

        $this->assertNotNull($group);

        $this->adminUuid = $this->generateUuid();
        $this->database->query(
            'insert into admins (uuid, firstname, lastname, email, phone, status, superadmin, group_id, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->adminUuid,
                'Integration',
                'Logs',
                $this->adminUuid . '@example.test',
                '+390000000003',
                1,
                0,
                (int) $group->id,
                'Logs integration test',
                date('Y-m-d H:i:s')
            ]
        );

        $this->model = new LogsModel();
    }

    protected function tearDown(): void
    {
        if (isset($this->database, $this->adminUuid)):
            $this->database->query('delete from admins_logs where admin_uuid = ?', [$this->adminUuid]);
            $this->database->query('delete from admins_tokens where admin_uuid = ?', [$this->adminUuid]);
            $this->database->query('delete from admins where uuid = ?', [$this->adminUuid]);
            $this->database->query(
                'delete from admins_audits where id > ? and action = ? and section = ?',
                [$this->auditStartId, 'DISCONNECT_SESSION', 'logs']
            );
        endif;

        Services::resetSingle('authorization');

        parent::tearDown();
    }

    public function testDisconnectSessionCommitsLogClosureAndForeignKeyUpdate(): void
    {
        $lastActivity = '2026-10-13 10:11:12';

        $this->database->query(
            'insert into admins_tokens (admin_uuid, token_hash, token_create, last_activity, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->adminUuid,
                hash('sha256', $this->adminUuid),
                '2026-10-13 10:00:00',
                $lastActivity,
                '2026-10-13 11:00:00',
                'session',
                'Logs Integration Agent',
                '127.0.0.1',
                '2026-10-13 10:00:00'
            ]
        );

        $tokenId = (int) $this->database->insertID();

        $this->database->query(
            'insert into admins_logs (token_id, admin_uuid, username, login, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $tokenId,
                $this->adminUuid,
                $this->adminUuid . '@example.test',
                '2026-10-13 10:00:00',
                'session',
                'Logs Integration Agent',
                '127.0.0.1',
                '2026-10-13 10:00:00'
            ]
        );

        $logId = (int) $this->database->insertID();
        $result = $this->model->disconnectSession($tokenId);

        $this->assertTrue($result['result'], json_encode($result));
        $this->assertNull(
            $this->database->query('select id from admins_tokens where id = ?', [$tokenId])->getRow()
        );

        $log = $this->database
            ->query('select token_id, logout, logout_reason from admins_logs where id = ?', [$logId])
            ->getRow();

        $this->assertNotNull($log);
        $this->assertNull($log->token_id);
        $this->assertSame($lastActivity, $log->logout);
        $this->assertSame('banned', $log->logout_reason);
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
