<?php declare(strict_types = 1);

namespace Config;

use App\Validation\Backend\AdminsRules;
use App\Validation\Backend\AuthRules;
use App\Validation\Backend\CustomRules;
use App\Validation\Backend\ImagesRules;
use App\Validation\Backend\SettingsRules;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class ValidationTest extends CIUnitTestCase
{
    public function testExpectedStrictAndApplicationRuleSetsAreRegistered(): void
    {
        $config = new Validation();
        $expected = [
            CustomRules::class,
            Rules::class,
            FormatRules::class,
            FileRules::class,
            CreditCardRules::class,
            AuthRules::class,
            AdminsRules::class,
            ImagesRules::class,
            SettingsRules::class,
        ];

        $this->assertSame($expected, $config->ruleSets);
        $this->assertSame($config->ruleSets, array_values(array_unique($config->ruleSets)));

        foreach ($config->ruleSets as $ruleSet):
            $this->assertTrue(class_exists($ruleSet), $ruleSet);
        endforeach;
    }

    public function testValidationErrorTemplatesAreConfigured(): void
    {
        $config = new Validation();

        $this->assertSame([
            'list' => 'CodeIgniter\Validation\Views\list',
            'single' => 'CodeIgniter\Validation\Views\single',
        ], $config->templates);
    }
}
