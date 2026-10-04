<?php declare(strict_types = 1);

namespace App\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

class DateHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('date');
        helper('settings');
    }

    public function testConvertDateReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', convertDate(null));
    }

    public function testConvertDateReturnsEmptyStringForEmptyString(): void
    {
        $this->assertSame('', convertDate(''));
    }

    public function testConvertDateReturnsEmptyStringForWhitespace(): void
    {
        $this->assertSame('', convertDate('   '));
    }

    public function testConvertDateUsesExplicitFormat(): void
    {
        $result = convertDate(
            '2026-10-03 12:00:00',
            'yyyy-MM-dd'
        );

        $this->assertSame('2026-10-03', $result);
    }

    public function testConvertDateReturnsOriginalValueWhenParsingFails(): void
    {
        $result = convertDate(
            'invalid-date-value',
            'yyyy-MM-dd'
        );

        $this->assertSame('invalid-date-value', $result);
    }

    public function testConvertDateSupportsConversationalFormat(): void
    {
        $result = convertDate(
            '2026-10-03 12:00:00',
            'conversational'
        );

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }
}