<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class CryptoServiceTest extends CIUnitTestCase
{
	public function testEncryptAndDecryptRoundTrip()
    {
        $plaintext
            =
            'Messaggio confidenziale';

        $encrypted
            =
            (
                new \App\Libraries\CryptoService(
                    '12345678901234567890123456789012'
                )
            )
            ->encrypt(
                $plaintext
            );

        $decrypted
            =
            (
                new \App\Libraries\CryptoService(
                    '12345678901234567890123456789012'
                )
            )
            ->decrypt(
                $encrypted
            );

        $this
            ->assertSame(
                $plaintext,
                $decrypted
            );
    }

    public function testDecryptReturnsNullOnInvalidOrTooShortBlob()
    {
        $invalidBlob
            =
            base64_encode(
                'troppo_corto'
            );

        $result
            =
            (
                new \App\Libraries\CryptoService(
                    '12345678901234567890123456789012'
                )
            )
            ->decrypt(
                $invalidBlob
            );

        $this
            ->assertNull(
                $result
            );
    }

    public function testDecryptReturnsNullOnTamperedBlob(): void
    {
        $crypto = new \App\Libraries\CryptoService(
            '12345678901234567890123456789012'
        );

        $encrypted = $crypto->encrypt(
            'Dato segreto'
        );

        $raw = base64_decode(
            $encrypted
        );

        $raw[0] = chr(
            ord($raw[0]) ^ 0x01
        );

        $tampered = base64_encode(
            $raw
        );

        $result = $crypto->decrypt(
            $tampered
        );

        $this->assertNull(
            $result
        );
    }
}