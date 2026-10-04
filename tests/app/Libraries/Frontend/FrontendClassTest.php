<?php declare(strict_types = 1);

namespace App\Libraries\Frontend;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\Frontend\FrontendClass;

class FrontendClassTest extends CIUnitTestCase
{
    public function testClassCanBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(FrontendClass::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(FrontendClass::class, $instance);
    }
}