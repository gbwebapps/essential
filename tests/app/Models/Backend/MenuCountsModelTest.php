<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Models\Backend\MenuCountsModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Verifica semantica e autorizzazioni dei contatori senza modificare dati permanenti. */
class MenuCountsModelTest extends CIUnitTestCase
{
    private BaseConnection $database;
    private MenuCountsModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = Database::connect('tests', false);
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->getDatabase());
        $this->assertSame(config(\Config\Database::class)->tests['database'], $this->database->query('select database() as db')->getRow()->db);

        foreach (['admins', 'brands', 'categories', 'products'] as $table):
            $this->database->query('create temporary table ' . $table . ' (id int primary key, status tinyint not null, deleted_at datetime null)');
        endforeach;

        $this->model = new MenuCountsModel();
        (new \ReflectionProperty($this->model, 'db'))->setValue($this->model, $this->database);
    }

    protected function tearDown(): void
    {
        if (isset($this->database)):
            $this->database->close();
        endif;

        parent::tearDown();
    }

    private function superadmin(): object
    {
        return (object) ['superadmin' => 1, 'permissions' => (object) ['all' => true]];
    }

    public function testEmptyTablesReturnVisibleIntegerZeroes(): void
    {
        $counts = $this->model->getCounts($this->superadmin());

        $this->assertSame(['admins', 'brands', 'categories', 'products'], array_keys($counts));

        foreach ($counts as $entityCounts):
            $this->assertSame(['active' => 0, 'trashed' => 0, 'suspended' => 0], $entityCounts);
        endforeach;
    }

    public function testActiveSuspendedAndTrashedAreSeparate(): void
    {
        $this->database->query("insert into brands values (1, 1, null), (2, 0, null), (3, 0, '2026-10-23 10:00:00'), (4, 1, '2026-10-23 10:00:00')");

        $this->assertSame(['brands' => ['active' => 1, 'trashed' => 2, 'suspended' => 1]], $this->model->getCounts($this->superadmin(), 'brands'));
    }

    public function testPermissionsLimitWhichEntitiesAreCounted(): void
    {
        $admin = (object) ['superadmin' => 0, 'permissions' => (object) ['brands_show_all' => true]];

        $this->assertSame(['brands'], array_keys($this->model->getCounts($admin)));
        $this->assertSame([], $this->model->getCounts($admin, 'products'));
        $this->assertSame([], $this->model->getCounts($admin, 'admins'));
    }

    public function testAllPermissionsDoNotExposeSuperadminOnlyCounts(): void
    {
        $admin = (object) ['superadmin' => 0, 'permissions' => (object) ['all' => true]];

        $this->assertSame(['brands', 'categories', 'products'], array_keys($this->model->getCounts($admin)));
    }

    public function testGuestMissingPermissionsAndUnknownEntitiesReturnNoCounts(): void
    {
        $this->assertSame([], $this->model->getCounts(null));
        $this->assertSame([], $this->model->getCounts((object) []));
        $this->assertSame([], $this->model->getCounts($this->superadmin(), 'brands; drop table admins'));
    }

    public function testFreshCountsFollowChanges(): void
    {
        $this->database->query('insert into products values (1, 1, null)');
        $this->assertSame(['products' => ['active' => 1, 'trashed' => 0, 'suspended' => 0]], $this->model->getCounts($this->superadmin(), 'products'));

        $this->database->query('update products set status = 0 where id = 1');
        $this->assertSame(['products' => ['active' => 0, 'trashed' => 0, 'suspended' => 1]], $this->model->getCounts($this->superadmin(), 'products'));

        $this->database->query('update products set deleted_at = now() where id = 1');
        $this->assertSame(['products' => ['active' => 0, 'trashed' => 1, 'suspended' => 0]], $this->model->getCounts($this->superadmin(), 'products'));
    }

    public function testPartialRendersThreeAccessibleBadgesAndNothingForUnauthorizedEntities(): void
    {
        $html = view('backend/template/menuCountsPartial', [
            'menuCountEntity' => 'brands',
            'menuCounts' => ['brands' => ['active' => 4, 'trashed' => 2, 'suspended' => 1]],
        ]);

        $this->assertSame(3, substr_count($html, 'data-menu-count="'));
        $this->assertStringContainsString('app-bg-success', $html);
        $this->assertStringContainsString('app-bg-danger', $html);
        $this->assertStringContainsString('app-bg-warning', $html);
        $this->assertStringContainsString('aria-label=', $html);
        $this->assertStringNotContainsString('backend/global.menuCounts.', $html);
        $this->assertSame('', trim(strip_tags(view('backend/template/menuCountsPartial', ['menuCountEntity' => 'products', 'menuCounts' => []]))));
    }
}
