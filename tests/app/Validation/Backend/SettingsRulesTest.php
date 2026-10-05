<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

use App\Validation\Backend\SettingsRules;

class SettingsRulesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

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

    public function testRequiredIfFieldRejectsWhitespaceWhenConditionMatches(): void
    {
        $result = (new SettingsRules())->required_if_field(
            '   ',
            'protocol,smtp',
            ['protocol' => 'smtp']
        );

        $this->assertFalse($result);
    }

    public function testRequiredIfFieldTrimsRuleParameters(): void
    {
        $result = (new SettingsRules())->required_if_field(
            '',
            ' protocol , smtp ',
            ['protocol' => 'smtp']
        );

        $this->assertFalse($result);
    }

    public function testRequiredIfFieldRejectsMalformedParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new SettingsRules())->required_if_field('', 'protocol', []);
    }

    public function testRequiredIfFieldIsRegisteredInValidationService(): void
    {
        $validation = Services::validation();
        $validation->setRule(
            'host',
            'Host',
            'required_if_field[protocol,smtp]'
        );

        $this->assertFalse($validation->run([
            'host' => '',
            'protocol' => 'smtp',
        ]));
        $this->assertNotSame('', $validation->getError('host'));
    }
}
