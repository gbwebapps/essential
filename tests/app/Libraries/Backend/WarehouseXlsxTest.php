<?php declare(strict_types = 1);

namespace Tests\App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Warehouse\Libraries\WarehouseXlsx;
use Modules\Backend\Warehouse\Models\WarehouseModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WarehouseXlsxTest extends CIUnitTestCase
{
    private const SESSION_TOKEN = 'phpunit-warehouse-session';

    private ?string $exportId = null;

    protected function setUp(): void
    {
        parent::setUp();

        session()->set('backendSession', self::SESSION_TOKEN);
    }

    protected function tearDown(): void
    {
        if ($this->exportId !== null):
            $this->removeExportDirectory($this->exportId);
        endif;

        session()->remove('backendSession');

        parent::tearDown();
    }

    public function testExportCreatesProtectedFiveSheetPackage(): void
    {
        $xlsx = new WarehouseXlsx($this->createWarehouseModelFixture());
        $export = $xlsx->export();
        $this->exportId = $export['exportId'];

        $this->assertFileExists($export['path']);
        $this->assertSame(5, $export['recordCount']);

        $spreadsheet = IOFactory::load($export['path']);

        try
        {
            $this->assertSame([
                'brands',
                'categories',
                'products',
                'brands_to_products',
                'categories_to_products',
            ], $spreadsheet->getSheetNames());

            $this->assertSame('brand', $spreadsheet->getSheetByName('brands')->getCell('C1')->getValue());
            $this->assertSame('Brand fixture', $spreadsheet->getSheetByName('brands')->getCell('C2')->getValue());
            $this->assertSame('0001', $spreadsheet->getSheetByName('products')->getCell('C2')->getValue());
            $this->assertSame('s', $spreadsheet->getSheetByName('products')->getCell('C2')->getDataType());

            foreach ($spreadsheet->getAllSheets() as $sheet):
                $headers = $sheet->rangeToArray('A1:' . $sheet->getHighestDataColumn() . '1')[0];
                $this->assertSame([], array_values(array_intersect(['created_at', 'updated_at', 'deleted_at', 'restored_at'], $headers)));
            endforeach;
        }
        finally
        {
            $spreadsheet->disconnectWorksheets();
        }

        $this->assertNotNull($xlsx->getDownloadFile($export['exportId']));

        session()->set('backendSession', 'different-session');
        $this->assertNull($xlsx->getDownloadFile($export['exportId']));
    }

    private function createWarehouseModelFixture(): WarehouseModel
    {
        return new class extends WarehouseModel {
            protected function initModel(): void
            {
            }

            public function getPackageData(): array
            {
                return [
                    'brands' => [
                        'headers' => ['id', 'uuid', 'brand', 'note', 'status'],
                        'rows' => [[
                            'id' => 1,
                            'uuid' => '00000000-0000-4000-8000-000000000001',
                            'brand' => 'Brand fixture',
                            'note' => null,
                            'status' => 1,
                        ]],
                    ],
                    'categories' => [
                        'headers' => ['id', 'uuid', 'parent_id', 'category', 'note', 'status'],
                        'rows' => [[
                            'id' => 1,
                            'uuid' => '00000000-0000-4000-8000-000000000002',
                            'parent_id' => null,
                            'category' => 'Category fixture',
                            'note' => null,
                            'status' => 1,
                        ]],
                    ],
                    'products' => [
                        'headers' => ['id', 'uuid', 'sku', 'product', 'note', 'quantity', 'price', 'status'],
                        'rows' => [[
                            'id' => 1,
                            'uuid' => '00000000-0000-4000-8000-000000000003',
                            'sku' => '0001',
                            'product' => 'Product fixture',
                            'note' => null,
                            'quantity' => 2,
                            'price' => '9.90',
                            'status' => 1,
                        ]],
                    ],
                    'brands_to_products' => [
                        'headers' => ['brand_id', 'product_id', 'status'],
                        'rows' => [['brand_id' => 1, 'product_id' => 1, 'status' => 1]],
                    ],
                    'categories_to_products' => [
                        'headers' => ['category_id', 'product_id', 'status'],
                        'rows' => [['category_id' => 1, 'product_id' => 1, 'status' => 1]],
                    ],
                ];
            }
        };
    }

    private function removeExportDirectory(string $exportId): void
    {
        $directory = WRITEPATH . 'exports/warehouse/' . $exportId . DIRECTORY_SEPARATOR;

        if ( ! is_dir($directory)):
            return;
        endif;

        $items = scandir($directory);

        if ($items !== false):
            foreach ($items as $item):
                if ($item === '.' || $item === '..'):
                    continue;
                endif;

                $path = $directory . $item;

                if (is_file($path)):
                    unlink($path);
                endif;
            endforeach;
        endif;

        rmdir($directory);
    }
}
