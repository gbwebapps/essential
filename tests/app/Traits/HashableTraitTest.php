<?php declare(strict_types = 1);

namespace App\Traits;

use CodeIgniter\Test\CIUnitTestCase;

class HashableTraitTest extends CIUnitTestCase
{
    public function testGenerateHashReturnsExpectedLength(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $result = $object->generateHash(16);

        $this->assertSame(32, strlen($result));
    }

    public function testGenerateHashReturnsHexadecimalString(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $result = $object->generateHash(16);

        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $result);
    }

    public function testGenerateHashReturnsDifferentValues(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $first = $object->generateHash(16);
        $second = $object->generateHash(16);

        $this->assertNotSame($first, $second);
    }
}