<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\TokensModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class TokensModelIntegrationTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private TokensModel $model;
    private string $adminUuid;
    private int $auditStartId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = db_connect();
        $this->assertSame('essential_test', $this->database->getDatabase());

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
                'Tokens',
                $this->adminUuid . '@example.test',
                '+390000000004',
                1,
                0,
                (int) $group->id,
                'Tokens integration test',
                date('Y-m-d H:i:s')
            ]
        );

        $this->model = new TokensModel();
    }

    protected function tearDown(): void
    {
        if (isset($this->database, $this->adminUuid)):
            $this->database->query('delete from admins_logs where admin_uuid = ?', [$this->adminUuid]);
            $this->database->query('delete from admins_tokens where admin_uuid = ?', [$this->adminUuid]);
            $this->database->query('delete from admins where uuid = ?', [$this->adminUuid]);
            $this->database->query(
                'delete from admins_audits where id > ? and action = ? and section = ?',
                [$this->auditStartId, 'DELETE_TOKEN', 'tokens']
            );
        endif;

        Services::resetSingle('authorization');
        parent::tearDown();
    }

    public function testHardDeleteCommitsLogClosureAndForeignKeyUpdate(): void
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
                'Tokens Integration Agent',
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
                'Tokens Integration Agent',
                '127.0.0.1',
                '2026-10-13 10:00:00'
            ]
        );

        $logId = (int) $this->database->insertID();
        $result = $this->model->hardDelete(['id' => $tokenId, 'uuid' => $this->adminUuid]);

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

    /**
     * Genera un UUID versione 4 valido per il record di integrazione.
     *
     * @return string UUID versione 4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);

        return vsprintf('%08s-%04s-%04s-%04s-%12s', sscanf($hex, '%8s%4s%4s%4s%12s'));
    }
}
