<?php declare(strict_types = 1);

namespace App\Models\Frontend;

use CodeIgniter\Test\CIUnitTestCase;

class FrontendModelTest extends CIUnitTestCase
{
	public function testFrontendModelInitModel()
    {
        $anonymous
            =
            new class extends \App\Models\Frontend\FrontendModel
            {
                public function callInitModel()
                {
                    $this
                        ->initModel();
                }
            };

        $anonymous
            ->callInitModel();

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
                        $anonymous
                    )
            );
    }
}
