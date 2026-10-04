<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Auth;

class AuthTest extends CIUnitTestCase
{
    private mixed $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnv = $_ENV['encryption.key'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv === null):
            unset($_ENV['encryption.key']);
        else:
            $_ENV['encryption.key'] = $this->originalEnv;
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
        $this->assertSame(['none', 'email', 'totp'], $config->twoFactorMethods);
        $this->assertSame(864000, $config->rememberMeTime);
        $this->assertSame(1200, $config->sessionTime);
        $this->assertSame(43200, $config->activationTime);
    }
}