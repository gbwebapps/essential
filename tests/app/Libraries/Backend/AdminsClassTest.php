<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AdminsModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\MocksSettings;

class AdminsClassTest extends CIUnitTestCase
{
    use MocksSettings;

    private AdminsClass $admins;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettings(['Backend\General' => ['language' => 'it']]);
        $this->admins = new AdminsClass($this->createMock(AdminsModel::class));
    }

    protected function tearDown(): void
    {
        $this->resetSettingsMocks();

        parent::tearDown();
    }

    public function testGetLinksBarShowAllReturnsAddLink(): void
    {
        $this->assertSame('backend/admins/add', $this->admins->getLinksBarShowAll()[0]['route']);
    }

    public function testGetLinksBarAddReturnsShowAllLink(): void
    {
        $this->assertSame('backend/admins/showAll', $this->admins->getLinksBarAdd()[0]['route']);
    }

    public function testGetLinksBarEditReturnsEmptyArrayWithoutUuid(): void
    {
        $this->assertSame([], $this->admins->getLinksBarEdit());
        $this->assertSame([], $this->admins->getLinksBarEdit(''));
    }

    public function testGetLinksBarEditIncludesUuidInShowRoute(): void
    {
        $result = $this->admins->getLinksBarEdit('admin-uuid');

        $this->assertSame('backend/admins/show/admin-uuid', $result[2]['route']);
    }

    public function testGetLinksBarShowReturnsEmptyArrayWithoutUuid(): void
    {
        $this->assertSame([], $this->admins->getLinksBarShow());
        $this->assertSame([], $this->admins->getLinksBarShow(''));
    }

    public function testGetLinksBarShowIncludesUuidInEditRoute(): void
    {
        $result = $this->admins->getLinksBarShow('admin-uuid');

        $this->assertSame('backend/admins/edit/admin-uuid', $result[2]['route']);
    }

    public function testGetJsShowAllUsesConfiguredLocale(): void
    {
        $result = $this->admins->getJsShowAll();

        $this->assertSame('it-js', $result[1]['id']);
        $this->assertSame('assets/vendor/flatpickr/js/it.js', $result[1]['path']);
    }

    public function testGetJsShowReturnsExpectedAsset(): void
    {
        $this->assertSame([
            [
                'id' => 'html2pdf-js',
                'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js',
                'position' => 'before',
                'target' => 'admins-js'
            ]
        ], $this->admins->getJsShow());
    }

    public function testGetCssShowAllReturnsExpectedAsset(): void
    {
        $this->assertSame([
            [
                'id' => 'flatpickr-css',
                'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css',
                'position' => 'before',
                'target' => 'backend-css'
            ]
        ], $this->admins->getCssShowAll());
    }
}
