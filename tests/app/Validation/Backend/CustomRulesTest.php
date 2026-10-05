<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use App\Validation\Backend\CustomRules;

class CustomRulesTest extends CIUnitTestCase
{
    public function testValidEmailReturnsTrueForValidEmail(): void
    {
        $rules = new CustomRules();

        $this->assertTrue($rules->valid_email('test@example.com'));
    }

    public function testValidEmailReturnsFalseForInvalidEmail(): void
    {
        $rules = new CustomRules();

        $this->assertFalse($rules->valid_email('invalid-email'));
    }

    public function testValidEmailReturnsTrueForValidSoftDeletedEmail(): void
    {
        $rules = new CustomRules();

        $this->assertTrue($rules->valid_email('test@example.com.deleted.1691234567'));
    }

    public function testValidEmailReturnsFalseForInvalidSoftDeletedEmail(): void
    {
        $rules = new CustomRules();

        $this->assertFalse($rules->valid_email('invalid-email.deleted.1691234567'));
    }

    public function testValidEmailDoesNotTreatInvalidTimestampAsSoftDelete(): void
    {
        $rules = new CustomRules();

        $this->assertFalse($rules->valid_email('test@example.com.deleted.123'));
    }

    public function testValidEmailRejectsSoftDeleteSuffixWithExtraContent(): void
    {
        $rules = new CustomRules();

        $this->assertFalse($rules->valid_email('test@example.com.deleted.1691234567.extra'));
    }

    public function testValidEmailRejectsSoftDeleteSuffixWithElevenDigitTimestamp(): void
    {
        $rules = new CustomRules();

        $this->assertFalse($rules->valid_email('test@example.com.deleted.16912345670'));
    }

    public function testValidEmailAcceptsDeletedWordInsideNormalLocalPart(): void
    {
        $rules = new CustomRules();

        $this->assertTrue($rules->valid_email('test.deleted@example.com'));
    }
}
