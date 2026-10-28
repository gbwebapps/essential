<?php declare(strict_types = 1);

namespace Tests\Support\Database;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\Services;
use Modules\Backend\Warehouse\Config\Package;

/**
 * Isola le cinque tabelle con tabelle temporanee, senza modificare i dati permanenti.
 * La connessione condivisa permette alle regole is_unique di vedere lo stesso stato.
 */
trait WarehouseDatabaseFixture
{
    protected BaseConnection $warehouseDatabase;

    private array $warehouseTemporaryTables = [];

    /** Verifica il database di test prima di creare qualsiasi tabella. */
    protected function startWarehouseDatabase(): void
    {
        $this->warehouseDatabase = Database::connect('tests');
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->warehouseDatabase->getDatabase());
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->warehouseDatabase->query('select database() as db')->getRow()->db);
        $this->assertSame($this->warehouseDatabase, Database::connect());
        $this->assertSame(0, $this->warehouseDatabase->transDepth);
        $this->warehouseDatabase->resetDataCache();

        $common = 'id int unsigned not null auto_increment unique, uuid char(36) not null primary key, note text null,
                   status tinyint not null default 0, created_at datetime not null, updated_at datetime null,
                   deleted_at datetime null, restored_at datetime null';
        $definitions = [
            'brands' => $common . ', brand varchar(255) not null unique',
            'categories' => $common . ', parent_id int unsigned null, category varchar(255) not null',
            'products' => $common . ', sku varchar(100) not null unique, product varchar(255) not null unique,
                           quantity int unsigned not null default 0, price decimal(12,2) not null default 0.00',
            'brands_to_products' => 'brand_id int unsigned not null, product_id int unsigned not null,
                                     status tinyint not null default 1, primary key (brand_id, product_id)',
            'categories_to_products' => 'category_id int unsigned not null, product_id int unsigned not null,
                                         status tinyint not null default 1, primary key (category_id, product_id)',
        ];

        foreach (Package::TABLES as $table):
            $this->warehouseDatabase->query('create temporary table ' . $table . ' (' . $definitions[$table] . ') engine=innodb default charset=utf8mb4 collate=utf8mb4_general_ci');
            $this->warehouseTemporaryTables[] = $table;
        endforeach;
    }

    /** Rimuove solo le tabelle temporanee create dalla singola prova. */
    protected function stopWarehouseDatabase(): void
    {
        if ( ! isset($this->warehouseDatabase)):
            return;
        endif;

        while ($this->warehouseDatabase->transDepth > 0):
            $this->warehouseDatabase->transRollback();
        endwhile;

        foreach (array_reverse($this->warehouseTemporaryTables) as $table):
            $this->warehouseDatabase->query('drop temporary table if exists ' . $table);
        endforeach;

        $this->warehouseTemporaryTables = [];
        $this->warehouseDatabase->resetDataCache();
        Services::resetSingle('validation');
    }
}
