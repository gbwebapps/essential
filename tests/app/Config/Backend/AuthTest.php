<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Auth;

class AuthTest extends CIUnitTestCase
{
    private bool $originalEnvExists;
    private mixed $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvExists = array_key_exists('encryption.key', $_ENV);
        $this->originalEnv = $_ENV['encryption.key'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEnvExists):
            $_ENV['encryption.key'] = $this->originalEnv;
        else:
            unset($_ENV['encryption.key']);
        endif;

        parent::tearDown();
    }

    public function testConstructorUsesRawEncryptionKey(): void
    {
        $_ENV['encryption.key'] = 'test-encryption-key';

        $config = new Auth();

        $this->assertSame('test-encryption-key', $config->hashKey);
        $this->assertSame('test-encryption-key', $config->sessionCryptoKey);
    }

    public function testConstructorConvertsHexadecimalEncryptionKey(): void
    {
        $binaryKey = '12345678901234567890123456789012';

        $_ENV['encryption.key'] = 'hex2bin:' . bin2hex($binaryKey);

        $config = new Auth();

        $this->assertSame($binaryKey, $config->hashKey);
        $this->assertSame($binaryKey, $config->sessionCryptoKey);
    }

    public function testConfigurationDefaults(): void
    {
        $config = new Auth();

        $this->assertTrue($config->attempts);
        $this->assertSame(600, $config->attemptsInterval);
        $this->assertSame(3, $config->attemptsLimit);
        $this->assertTrue($config->twoFactor);
        $this->assertSame(3, $config->twoFactorLimit);
        $this->assertSame(600, $config->twoFactorTime);
        $this->assertSame('Essential', $config->twoFactorIssuer);
        $this->assertSame(6, $config->twoFactorDigits);
        $this->assertSame(1, $config->twoFactorWindow);
        $this->assertSame(60, $config->twoFactorEmailExpiry);
        $this->assertSame('superadmin@essential.it', $config->twoFactorEmailFrom);
        $this->assertSame(['none', 'email', 'totp'], $config->twoFactorMethods);
        $this->assertSame(864000, $config->rememberMeTime);
        $this->assertSame(1200, $config->sessionTime);
        $this->assertSame(43200, $config->activationTime);
        $this->assertSame('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $config->passwordRegex);
    }

    public function testPasswordRegexEnforcesConfiguredComplexity(): void
    {
        $regex = (new Auth())->passwordRegex;

        $this->assertSame(1, preg_match($regex, 'Secure1!'));
        $this->assertSame(0, preg_match($regex, 'secure1!'));
        $this->assertSame(0, preg_match($regex, 'Secure!!'));
        $this->assertSame(0, preg_match($regex, 'Secure12'));
        $this->assertSame(0, preg_match($regex, 'Sh0rt!'));
    }
}
