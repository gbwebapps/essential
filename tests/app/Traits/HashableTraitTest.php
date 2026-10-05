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

    public function testGenerateHashSupportsSingleByte(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $this->assertMatchesRegularExpression('/^[a-f0-9]{2}$/', $object->generateHash(1));
    }

    public function testGenerateHashRejectsZeroLength(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $this->expectException(\ValueError::class);

        $object->generateHash(0);
    }

    public function testGenerateHashRejectsNegativeLength(): void
    {
        $object = new class
        {
            use HashableTrait;
        };

        $this->expectException(\ValueError::class);

        $object->generateHash(-1);
    }
}
