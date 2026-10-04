<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class RegExpTest extends CIUnitTestCase
{
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