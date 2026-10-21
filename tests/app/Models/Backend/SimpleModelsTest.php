<?php declare(strict_types = 1);

namespace App\Models\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SimpleModelsTest extends CIUnitTestCase
{
    public static function modelProvider(): array
    {
        return [
            'dashboard' => [\App\Models\Backend\DashboardModel::class],
        ];
    }

    #[DataProvider('modelProvider')]
	public function testModelInitializesDatabaseConnection(string $modelClass): void
    {
        $model
            =
            new $modelClass();

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
