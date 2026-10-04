<?php declare(strict_types = 1);

namespace App\Models\Frontend;

use CodeIgniter\Test\CIUnitTestCase;

class HomeModelTest extends CIUnitTestCase
{
	public function testHomeModelInitModel()
    {
        $modelClass
            =
            \App\Models\Frontend\HomeModel::class;

        $model
            =
            new $modelClass();

        $reflection
            =
            new \ReflectionMethod(
                $modelClass,
                'initModel'
            );

        $reflection
            ->setAccessible(
                true
            );

        $reflection
            ->invoke(
                $model
            );

        $dbProperty
            =
            new \ReflectionProperty(
                \App\Models\BaseModel::class,
                'db'
            );

        $this
            ->assertInstanceOf(
                \CodeIgniter\Database\BaseConnection::class,
                $dbProperty
                    ->getValue(
                        $model
                    )
            );
    }
}
