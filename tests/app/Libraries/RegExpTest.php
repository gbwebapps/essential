<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class RegExpTest extends CIUnitTestCase
{
	public function testValidateUuidAcceptsCanonicalVersionFourUuid()
    {
        $this->assertTrue((new \App\Libraries\RegExp())->validateUUID('550e8400-e29b-41d4-a716-446655440000'));
    }

    public function testValidateUuidAcceptsUppercaseHexadecimalCharacters()
    {
        $this->assertTrue((new \App\Libraries\RegExp())->validateUUID('550E8400-E29B-41D4-A716-446655440000'));
    }

    public function testValidateUuidRejectsUnsupportedVersion()
    {
        $this->assertFalse((new \App\Libraries\RegExp())->validateUUID('550e8400-e29b-61d4-a716-446655440000'));
    }

    public function testValidateUuidRejectsInvalidVariant()
    {
        $this->assertFalse((new \App\Libraries\RegExp())->validateUUID('550e8400-e29b-41d4-7716-446655440000'));
    }

    public function testValidateUuidRejectsMalformedValue()
    {
        $this->assertFalse((new \App\Libraries\RegExp())->validateUUID('not-a-uuid'));
    }

	public function testValidatePasswordReturnsTrueForStrongPassword()
    {
        $regexClass
            =
            \App\Libraries\RegExp::class;

        $regexInstance
            =
            new $regexClass();

        $password
            =
            'Password1_';

        $result
            =
            $regexInstance
            ->validatePassword(
                $password
            );

        $this
            ->assertTrue(
                $result
            );
    }

    public function testValidatePasswordReturnsFalseForShortPassword()
    {
        $regexClass
            =
            \App\Libraries\RegExp::class;

        $regexInstance
            =
            new $regexClass();

        $password
            =
            'Ab1!';

        $result
            =
            $regexInstance
            ->validatePassword(
                $password
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testValidatePasswordReturnsFalseForMissingUppercase()
    {
        $regexClass
            =
            \App\Libraries\RegExp::class;

        $regexInstance
            =
            new $regexClass();

        $password
            =
            'abcdef1!';

        $result
            =
            $regexInstance
            ->validatePassword(
                $password
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testValidatePasswordReturnsFalseForMissingDigit()
    {
        $regexClass
            =
            \App\Libraries\RegExp::class;

        $regexInstance
            =
            new $regexClass();

        $password
            =
            'Abcdefgh!';

        $result
            =
            $regexInstance
            ->validatePassword(
                $password
            );

        $this
            ->assertFalse(
                $result
            );
    }

    public function testValidatePasswordReturnsFalseForMissingSpecialCharacter()
    {
        $regexClass
            =
            \App\Libraries\RegExp::class;

        $regexInstance
            =
            new $regexClass();

        $password
            =
            'Abcdefg1';

        $result
            =
            $regexInstance
            ->validatePassword(
                $password
            );

        $this
            ->assertFalse(
                $result
            );
    }
}
