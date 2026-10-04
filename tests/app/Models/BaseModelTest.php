<?php declare(strict_types = 1);

namespace App\Models;

use CodeIgniter\Test\CIUnitTestCase;

class BaseModelTest extends CIUnitTestCase
{
	public function testConstructorInitializesDatabaseConnection(): void
    {
        $model
            =
            new class extends \App\Models\BaseModel
            {
            };

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
