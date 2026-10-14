<?php declare(strict_types = 1);

namespace Config;

use App\Libraries\Backend\AuthorizationClass;
use App\Libraries\CryptoService;
use App\Libraries\RegExp;
use CodeIgniter\Email\Email as EmailClient;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\MocksSettings;

class ServicesTest extends CIUnitTestCase
{
    use MocksSettings;

    protected function tearDown(): void
    {
        Services::reset();
        $this->resetSettingsMocks();

        parent::tearDown();
    }

    public function testRegexpServiceHonorsSharedAndFreshLifecycle(): void
    {
        $shared = Services::regexp();

        $this->assertInstanceOf(RegExp::class, $shared);
        $this->assertSame($shared, Services::regexp());
        $this->assertNotSame($shared, Services::regexp(false));
    }

    public function testEmailServiceUsesDatabaseSettingsAndLifecycle(): void
    {
        $this->mockSettings([
            'Backend\\Email' => [
                'protocol' => 'smtp',
                'SMTPHost' => 'smtp.database.test',
                'SMTPPort' => '465',
                'SMTPUser' => 'database-user',
                'SMTPPass' => 'database-password',
                'SMTPCrypto' => '',
            ],
        ]);
        Services::resetSingle('email');

        $this->assertSame('smtp.database.test', setting('Backend\\Email')->SMTPHost);

        $shared = Services::email();

        $this->assertInstanceOf(EmailClient::class, $shared);
        $this->assertSame('smtp.database.test', $shared->SMTPHost);
        $this->assertSame(465, $shared->SMTPPort);
        $this->assertSame('database-user', $shared->SMTPUser);
        $this->assertSame('database-password', $shared->SMTPPass);
        $this->assertSame($shared, Services::email());
        $this->assertNotSame($shared, Services::email(null, false));
    }

    public function testEmailServiceHonorsExplicitConfiguration(): void
    {
        $email = Services::email([
            'protocol' => 'mail',
            'fromEmail' => 'explicit@essential.test',
        ], false);

        $this->assertSame('mail', $email->protocol);
        $this->assertSame('explicit@essential.test', $email->fromEmail);
    }

    public function testAuthorizationServiceHonorsSharedAndFreshLifecycle(): void
    {
        $shared = Services::authorization();

        $this->assertInstanceOf(AuthorizationClass::class, $shared);
        $this->assertSame($shared, Services::authorization());
        $this->assertNotSame($shared, Services::authorization(false));
    }

    public function testCryptoServiceUsesConfiguredKeyAndLifecycle(): void
    {
        $key = '12345678901234567890123456789012';
        $this->mockSettings([
            'Backend\Auth' => [
                'sessionCryptoKey' => $key,
            ],
        ]);

        $shared = Services::crypto();
        $reference = new CryptoService($key);
        $encrypted = $shared->encrypt('configuration-service-test');

        $this->assertInstanceOf(CryptoService::class, $shared);
        $this->assertSame($shared, Services::crypto());
        $this->assertNotSame($shared, Services::crypto(false));
        $this->assertSame('configuration-service-test', $reference->decrypt($encrypted));
    }
}
