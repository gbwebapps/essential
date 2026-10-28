<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Models\Backend\AdminsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Verifica i conteggi su una tabella temporanea della sola connessione di test. */
class AdminsTrashCountsTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private AdminsModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = Database::connect('tests', false);
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->getDatabase());
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->query('select database() as db')->getRow()->db);
        $this->database->query('create temporary table admins (id int primary key, status tinyint not null, deleted_at datetime null)');
        $this->model = new AdminsModel();
        (new \ReflectionProperty($this->model, 'db'))->setValue($this->model, $this->database);
    }

    protected function tearDown(): void
    {
        if (isset($this->database)):
            $this->database->close();
        endif;

        parent::tearDown();
    }

    public function testEmptyTableReturnsIntegerZeroes(): void
    {
        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 0, 'all' => 0], $this->model->getTrashCounts());
    }

    public function testActiveAndSuspendedAreSeparate(): void
    {
        $this->database->query("insert into admins values (1, 1, null), (2, 0, null), (3, 0, '2026-10-23 10:00:00'), (4, 1, '2026-10-23 10:00:00')");

        $this->assertSame(['active' => 1, 'suspended' => 1, 'trashed' => 2, 'all' => 4], $this->model->getTrashCounts());
    }

    public function testCountsFollowSoftDeleteRestoreAndHardDelete(): void
    {
        $this->database->query('insert into admins values (1, 1, null)');
        $this->assertSame(['active' => 1, 'suspended' => 0, 'trashed' => 0, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('update admins set deleted_at = now() where id = 1');
        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 1, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('update admins set deleted_at = null where id = 1');
        $this->assertSame(['active' => 1, 'suspended' => 0, 'trashed' => 0, 'all' => 1], $this->model->getTrashCounts());

        $this->database->query('delete from admins where id = 1');
        $this->assertSame(['active' => 0, 'suspended' => 0, 'trashed' => 0, 'all' => 0], $this->model->getTrashCounts());
    }
}
