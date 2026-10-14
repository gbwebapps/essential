<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

class AppOtpServiceTest extends CIUnitTestCase
{
    use MocksSettings;
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettings([
            'Backend\Auth' => [
                'twoFactorDigits' => 6,
                'twoFactorIssuer' => 'Essential',
                'twoFactorWindow' => 1,
				'twoFactorOtpExpiry' => 30,
				'sessionCryptoKey' => str_repeat('k', 32)
            ]
        ]);
    }

    protected function tearDown(): void
    {
		Services::resetSingle('crypto');
        $this->resetSettingsMocks();

        parent::tearDown();
    }

    public function testGenerateSecretReturnsValidString()
    {
        $serviceClass
            =
            \App\Libraries\AppOtpService::class;

        $service
            =
            new $serviceClass();

        $secret
            =
            $service
            ->generateSecret();

        $this
            ->assertIsString(
                $secret
            );

        $this
            ->assertNotEmpty(
                $secret
            );
    }

    public function testGetProvisioningUriReturnsCorrectlyFormattedUri()
    {
        $authConfig
            =
            new \stdClass();

        $authConfig
            ->twoFactorDigits
            =
            6;

        $authConfig
            ->twoFactorIssuer
            =
            'Essential';

        $authConfig
            ->twoFactorWindow
            =
            1;

        $authConfig
            ->twoFactorOtpExpiry
            =
            30;

        if (
            class_exists(
                '\CodeIgniter\Config\Factories'
            )
        ):
            \CodeIgniter\Config\Factories::injectMock(
                'config',
                'Backend\Auth',
                $authConfig
            );
        endif;

        $serviceClass
            =
            \App\Libraries\AppOtpService::class;

        $service
            =
            new $serviceClass();

        $secret
            =
            'JBSWY3DPEHPK3PXP';

        $label
            =
            'superadmin@essential.it';

        $uri
            =
            $service
            ->getProvisioningUri(
                $secret,
                $label
            );

        $this
            ->assertStringStartsWith(
                'otpauth://totp/',
                $uri
            );

        $this
            ->assertStringContainsString(
                $secret,
                $uri
            );

        $this
            ->assertStringContainsString(
                'Essential',
                $uri
            );

        $this
            ->assertStringContainsString(
                $label,
                urldecode(
                    $uri
                )
            );
    }

    public function testVerifyReturnsTrueForValidCode()
    {
        $authConfig
            =
            new \stdClass();

        $authConfig
            ->twoFactorDigits
            =
            6;

        $authConfig
            ->twoFactorIssuer
            =
            'Essential';

        $authConfig
            ->twoFactorWindow
            =
            1;

        $authConfig
            ->twoFactorOtpExpiry
            =
            30;

        if (
            class_exists(
                '\CodeIgniter\Config\Factories'
            )
        ):
            \CodeIgniter\Config\Factories::injectMock(
                'config',
                'Backend\Auth',
                $authConfig
            );
        endif;

        $serviceClass
            =
            \App\Libraries\AppOtpService::class;

        $service
            =
            new $serviceClass();

        $secret
            =
            'JBSWY3DPEHPK3PXP';

        $totpClass
            =
            \OTPHP\TOTP::class;

        $totp
            =
            $totpClass::create(
                $secret,
                30,
                'sha1',
                6
            );

        $validCode
            =
            $totp
            ->now();

        $result
            =
            $service
            ->verify(
                $secret,
                $validCode
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testVerifyReturnsFalseForInvalidCode()
    {
        $authConfig
            =
            new \stdClass();

        $authConfig
            ->twoFactorDigits
            =
            6;

        $authConfig
            ->twoFactorIssuer
            =
            'Essential';

        $authConfig
            ->twoFactorWindow
            =
            1;

        $authConfig
            ->twoFactorOtpExpiry
            =
            30;

        if (
            class_exists(
                '\CodeIgniter\Config\Factories'
            )
        ):
            \CodeIgniter\Config\Factories::injectMock(
                'config',
                'Backend\Auth',
                $authConfig
            );
        endif;

        $serviceClass
            =
            \App\Libraries\AppOtpService::class;

        $service
            =
            new $serviceClass();

        $secret
            =
            'JBSWY3DPEHPK3PXP';

        $totpClass
            =
            \OTPHP\TOTP::class;

        $totp
            =
            $totpClass::create(
                $secret,
                30,
                'sha1',
                6
            );

        $currentCode
            =
            $totp
            ->now();

        $invalidCode
            =
            '000000';

        /* Sicurezza: evitiamo che, per pura casualità matematica, il codice corrente sia 000000 */
        if (
            $invalidCode
            ===
            $currentCode
        ):
            $invalidCode
                =
                '111111';
        endif;

        $result
            =
            $service
            ->verify(
                $secret,
                $invalidCode
            );

        $this
            ->assertFalse(
                $result
            );
    }

	public function testProtectAndRevealSecretRoundTrip(): void
	{
		$service = new \App\Libraries\AppOtpService();
		$secret = 'JBSWY3DPEHPK3PXP';
		$protected = $service->protectSecret($secret);

		$this->assertStringStartsWith('enc:v1:', $protected);
		$this->assertNotSame($secret, $protected);
		$this->assertSame($secret, $service->revealSecret($protected));
	}

	public function testRevealSecretSupportsLegacyPlaintext(): void
	{
		$service = new \App\Libraries\AppOtpService();

		$this->assertSame('LEGACYSECRET', $service->revealSecret('LEGACYSECRET'));
	}
}
