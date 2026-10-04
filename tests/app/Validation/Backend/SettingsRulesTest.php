<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use App\Validation\Backend\SettingsRules;

class SettingsRulesTest extends CIUnitTestCase
{
    public function testRequiredIfFieldReturnsFalseWhenConditionMatchesAndValueIsEmpty(): void
    {
        $rules = new SettingsRules();

        $result = $rules->required_if_field(
            '',
            'protocol,smtp',
            [
                'protocol' => 'smtp'
            ]
        );

        $this->assertFalse($result);
    }

    public function testRequiredIfFieldReturnsTrueWhenConditionMatchesAndValueIsNotEmpty(): void
    {
        $rules = new SettingsRules();

        $result = $rules->required_if_field(
            'smtp.example.com',
            'protocol,smtp',
            [
                'protocol' => 'smtp'
            ]
        );

        $this->assertTrue($result);
    }

    public function testRequiredIfFieldReturnsTrueWhenConditionDoesNotMatch(): void
    {
        $rules = new SettingsRules();

        $result = $rules->required_if_field(
            '',
            'protocol,smtp',
            [
                'protocol' => 'mail'
            ]
        );

        $this->assertTrue($result);
    }

    public function testRequiredIfFieldReturnsTrueWhenControlFieldDoesNotExist(): void
    {
        $rules = new SettingsRules();

        $result = $rules->required_if_field(
            '',
            'protocol,smtp',
            []
        );

        $this->assertTrue($result);
    }
}