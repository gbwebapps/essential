<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use App\Validation\Backend\AdminRules;

class AdminsRulesTest extends CIUnitTestCase
{
    public function testSafeTextReturnsTrueForValidText(): void
    {
        $rules = new AdminsRules();

        $this->assertTrue($rules->safeText('Testo normale senza caratteri pericolosi'));
    }

    public function testSafeTextReturnsFalseForLessThanCharacter(): void
    {
        $rules = new AdminsRules();

        $this->assertFalse($rules->safeText('Testo < pericoloso'));
    }

    public function testSafeTextReturnsFalseForGreaterThanCharacter(): void
    {
        $rules = new AdminsRules();

        $this->assertFalse($rules->safeText('Testo > pericoloso'));
    }

    public function testSafeTextReturnsFalseForBacktickCharacter(): void
    {
        $rules = new AdminsRules();

        $this->assertFalse($rules->safeText('Testo ` pericoloso'));
    }

    public function testSafeTextReturnsTrueForEmptyString(): void
    {
        $rules = new AdminsRules();

        $this->assertTrue($rules->safeText(''));
    }
}