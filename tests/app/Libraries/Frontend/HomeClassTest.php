<?php declare(strict_types = 1);

namespace App\Libraries\Frontend;

use CodeIgniter\Test\CIUnitTestCase;

use App\Libraries\Frontend\HomeClass;

class HomeClassTest extends CIUnitTestCase
{
    public function testClassCanBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(HomeClass::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $this->assertInstanceOf(HomeClass::class, $instance);
    }
}