<?php declare(strict_types = 1);

namespace Config\Backend;

use App\Models\Backend\Components\ImportModel;
use App\Models\Backend\Components\ExportModel;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Warehouse\Config\Package;

/** Verifica il collegamento dei motori CSV alle configurazioni e l'autonomia del pacchetto XLSX. */
class TransferChunksTest extends CIUnitTestCase
{
    public function testGlobalImportReadsItsChunkSizeFromConfiguration(): void
    {
        $this->assertGreaterThan(0, Import::CHUNK_SIZE);
        $this->assertSame(Import::CHUNK_SIZE, (new \ReflectionClass(ImportModel::class))->getConstant('IMPORT_CHUNK_SIZE'));
    }

    public function testGlobalExportReadsItsChunkSizeFromConfiguration(): void
    {
        $this->assertGreaterThan(0, Export::CHUNK_SIZE);
        $this->assertSame(Export::CHUNK_SIZE, (new \ReflectionClass(ExportModel::class))->getConstant('EXPORT_CHUNK_SIZE'));
    }

    public function testWarehouseKeepsItsOwnPositiveChunkSize(): void
    {
        $this->assertGreaterThan(0, Package::CHUNK_SIZE);
        $this->assertSame(['brands', 'categories', 'products', 'brands_to_products', 'categories_to_products'], Package::TABLES);
    }
}
