<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\Test\CIUnitTestCase;

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

    public function testSafeTextRejectsEncodedHtmlCharacters(): void
    {
        $rules = new AdminsRules();

        $this->assertFalse($rules->safeText('Testo &lt;script&gt; pericoloso'));
        $this->assertFalse($rules->safeText('Testo &#60;script&#62; pericoloso'));
        $this->assertFalse($rules->safeText('Testo &#96; pericoloso'));
    }

    public function testSafeTextAllowsQuotesWhenMarkupCharactersAreAbsent(): void
    {
        $rules = new AdminsRules();

        $this->assertTrue($rules->safeText('Testo con "virgolette" e apostrofo'));
    }
}
