<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\AppOtpService;
use App\Libraries\Backend\AuthorizationClass;
use App\Models\Backend\AccountModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class AccountModelIntegrationTest extends CIUnitTestCase
{
	private BaseConnection $database;
	private AccountModel $model;
	private string $adminUuid;
	private int $auditStartId;

	protected function setUp(): void
	{
		parent::setUp();

		/* AccountModel usa setting già durante la propria inizializzazione. */
		helper('settings');

		$this->database = db_connect();
		$this->assertSame('essential_test', $this->database->getDatabase());

		$group = $this->database->query('select id from admins_groups order by id asc limit 1')->getRow();
		$this->assertNotNull($group);

		$this->adminUuid = $this->generateUuid();
		$this->auditStartId = (int) ($this->database
			->query('select coalesce(max(id), 0) as id from admins_audits')
			->getRow()->id ?? 0);

		$this->database->query(
			'insert into admins (uuid, firstname, lastname, email, phone, password_hash, status, superadmin, group_id, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			[
				$this->adminUuid,
				'Integration',
				'Account',
				$this->adminUuid . '@example.test',
				'+390000000006',
				password_hash('ValidPassword1!', PASSWORD_DEFAULT),
				1,
				0,
				(int) $group->id,
				'Account integration test',
				date('Y-m-d H:i:s'),
			]
		);

		$identity = (object) [
			'uuid' => $this->adminUuid,
			'firstname' => 'Integration',
			'lastname' => 'Account',
			'email' => $this->adminUuid . '@example.test',
		];
		$authorization = $this->createMock(AuthorizationClass::class);
		$authorization->method('currentAdmin')->willReturn($identity);
		Services::injectMock('authorization', $authorization);

		$this->model = new AccountModel();
	}

	protected function tearDown(): void
	{
		if (isset($this->database, $this->adminUuid)):
			foreach (['admins_logs', 'admins_tokens', 'admins_2fa_codes', 'admins_2fa_attempts', 'admins_2fa'] as $table):
				$this->database->query("delete from {$table} where admin_uuid = ?", [$this->adminUuid]);
			endforeach;

			$this->database->query('delete from admins where uuid = ?', [$this->adminUuid]);
			$this->database->query(
				'delete from admins_audits where id > ? and section = ?',
				[$this->auditStartId, 'account']
			);
		endif;

		session()->remove('account_totp_setup');
		Services::resetSingle('authorization');
		Services::resetSingle('crypto');

		parent::tearDown();
	}

	public function testCurrentPasswordVerificationUsesStoredHash(): void
	{
		$this->assertTrue($this->model->verifyCurrentPassword($this->adminUuid, 'ValidPassword1!'));
		$this->assertFalse($this->model->verifyCurrentPassword($this->adminUuid, 'WrongPassword1!'));
	}

	public function testPendingTotpDoesNotAlterActiveDatabaseMethod(): void
	{
		$this->database->query(
			"insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'totp', ?, 1)",
			[$this->adminUuid, 'LEGACYACTIVESECRET']
		);

		$this->assertTrue($this->model->saveTemporarySecret($this->adminUuid, 'NEWPENDINGSECRET'));
		$this->assertSame('NEWPENDINGSECRET', $this->model->getTemporarySecret($this->adminUuid));

		$stored = $this->database
			->query("select secret, enabled from admins_2fa where admin_uuid = ? and method = 'totp'", [$this->adminUuid])
			->getRow();

		$this->assertSame('LEGACYACTIVESECRET', $stored->secret);
		$this->assertSame(1, (int) $stored->enabled);

		$this->model->clearTemporarySecret();
		$this->assertNull($this->model->getTemporarySecret($this->adminUuid));
	}

	public function testTotpActivationIsAtomicAndStoresEncryptedSecret(): void
	{
		$this->database->query(
			"insert into admins_2fa (admin_uuid, method, enabled) values (?, 'email', 1)",
			[$this->adminUuid]
		);

		$currentAdmin = (object) [
			'uuid' => $this->adminUuid,
			'firstname' => 'Integration',
			'lastname' => 'Account',
		];
		$secret = 'JBSWY3DPEHPK3PXP';

		$this->assertTrue($this->model->activateTotpMethod($this->adminUuid, $secret, $currentAdmin));

		$methods = $this->database
			->query('select method, secret, enabled from admins_2fa where admin_uuid = ? order by method', [$this->adminUuid])
			->getResult();

		$this->assertCount(2, $methods);
		$this->assertSame(0, (int) $methods[0]->enabled);
		$this->assertSame('totp', $methods[1]->method);
		$this->assertSame(1, (int) $methods[1]->enabled);
		$this->assertStringStartsWith('enc:v1:', $methods[1]->secret);
		$this->assertSame($secret, (new AppOtpService())->revealSecret($methods[1]->secret));
	}

	public function testDeleteTokenClosesLogAndDeletesTokenAtomically(): void
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
				'Account Integration Agent',
				'127.0.0.1',
				'2026-10-13 10:00:00',
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
				'Account Integration Agent',
				'127.0.0.1',
				'2026-10-13 10:00:00',
			]
		);

		$logId = (int) $this->database->insertID();
		$currentAdmin = (object) [
			'uuid' => $this->adminUuid,
			'firstname' => 'Integration',
			'lastname' => 'Account',
		];

		$result = $this->model->deleteToken(['id' => $tokenId], $currentAdmin, null);

		$this->assertTrue($result['result'], json_encode($result));
		$this->assertNull($this->database->query('select id from admins_tokens where id = ?', [$tokenId])->getRow());

		$log = $this->database
			->query('select token_id, logout, logout_reason from admins_logs where id = ?', [$logId])
			->getRow();

		$this->assertNotNull($log);
		$this->assertNull($log->token_id);
		$this->assertSame($lastActivity, $log->logout);
		$this->assertSame('deleted', $log->logout_reason);
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
