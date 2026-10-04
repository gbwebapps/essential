<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AppOtpServiceTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testGenerateSecretReturnsValidString()
    {
        if (
            ! function_exists(
                'helper'
            )
        ):
            function helper(
                $name
            ) {
            }
        endif;

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
        if (
            ! function_exists(
                'helper'
            )
        ):
            function helper(
                $name
            ) {
            }
        endif;

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

        if (
            ! function_exists(
                'setting'
            )
        ):
            function setting(
                $key
            ) {
                $fallbackConf
                    =
                    new \stdClass();

                $fallbackConf
                    ->twoFactorDigits
                    =
                    6;

                $fallbackConf
                    ->twoFactorIssuer
                    =
                    'Essential';

                $fallbackConf
                    ->twoFactorWindow
                    =
                    1;

                return $fallbackConf;
            }
        endif;

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
        if (
            ! function_exists(
                'helper'
            )
        ):
            function helper(
                $name
            ) {
            }
        endif;

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

        if (
            ! function_exists(
                'setting'
            )
        ):
            function setting(
                $key
            ) {
                $fallbackConf
                    =
                    new \stdClass();

                $fallbackConf
                    ->twoFactorDigits
                    =
                    6;

                $fallbackConf
                    ->twoFactorIssuer
                    =
                    'Essential';

                $fallbackConf
                    ->twoFactorWindow
                    =
                    1;

                return $fallbackConf;
            }
        endif;

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
        if (
            ! function_exists(
                'helper'
            )
        ):
            function helper(
                $name
            ) {
            }
        endif;

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

        if (
            ! function_exists(
                'setting'
            )
        ):
            function setting(
                $key
            ) {
                $fallbackConf
                    =
                    new \stdClass();

                $fallbackConf
                    ->twoFactorDigits
                    =
                    6;

                $fallbackConf
                    ->twoFactorIssuer
                    =
                    'Essential';

                $fallbackConf
                    ->twoFactorWindow
                    =
                    1;

                return $fallbackConf;
            }
        endif;

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
}