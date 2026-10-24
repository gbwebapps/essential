<?php declare(strict_types = 1);

namespace Tests\App\Models\Backend;

use App\Libraries\Backend\DashboardClass;
use App\Models\Backend\DashboardModel;
use App\Models\Backend\MenuCountsModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Modules\Backend\Products\Models\ProductStatisticsModel;

class DashboardModelTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Factories::reset('models');
        parent::tearDown();
    }

    public function testUnauthorizedStatisticsAreNotRequested(): void
    {
        $counts = $this->createMock(MenuCountsModel::class);
        $counts->expects($this->once())->method('getCounts')->willReturn(['brands' => ['active' => 1, 'suspended' => 0, 'trashed' => 0]]);
        Factories::injectMock('models', MenuCountsModel::class, $counts);
        $statistics = $this->createMock(ProductStatisticsModel::class);
        $statistics->expects($this->never())->method('getTopBrands');
        $statistics->expects($this->never())->method('getTopCategories');
        $statistics->expects($this->never())->method('getTopBrandsByValue');
        $statistics->expects($this->never())->method('getTopCategoriesByValue');
        $statistics->expects($this->never())->method('getInventoryValue');
        Factories::injectMock('models', ProductStatisticsModel::class, $statistics);

        $overview = (new DashboardModel())->getOverview((object) ['superadmin' => 0]);

        $this->assertSame(['brands'], array_keys($overview['counts']));
        $this->assertSame([], $overview['rankings']);
        $this->assertSame([], $overview['totals']);
    }

    public function testAuthorizedStatisticsAndErrorsRemainSeparate(): void
    {
        $counts = $this->createMock(MenuCountsModel::class);
        $counts->method('getCounts')->willReturn(array_fill_keys(['brands', 'categories', 'products'], ['active' => 1, 'suspended' => 0, 'trashed' => 0]));
        Factories::injectMock('models', MenuCountsModel::class, $counts);
        $statistics = $this->createMock(ProductStatisticsModel::class);
        $statistics->expects($this->once())->method('getTopBrands')->with(5)->willReturn([['id' => 1, 'label' => 'Brand', 'count' => 1]]);
        $statistics->expects($this->once())->method('getTopCategories')->with(5)->willThrowException(new \RuntimeException('Fixture error'));
        $statistics->expects($this->once())->method('getTopBrandsByValue')->with(5)->willReturn([['id' => 1, 'label' => 'Brand', 'value' => '12.34']]);
        $statistics->expects($this->once())->method('getTopCategoriesByValue')->with(5)->willThrowException(new \RuntimeException('Fixture error'));
        $statistics->expects($this->once())->method('getInventoryValue')->willReturn('12.34');
        Factories::injectMock('models', ProductStatisticsModel::class, $statistics);

        $overview = (new DashboardModel())->getOverview((object) ['superadmin' => 0]);

        $this->assertSame(1, $overview['rankings']['brands'][0]['count']);
        $this->assertNull($overview['rankings']['categories']);
        $this->assertSame('12.34', $overview['rankings']['brandsValue'][0]['value']);
        $this->assertNull($overview['rankings']['categoriesValue']);
        $this->assertSame('12.34', $overview['totals']['inventoryValue']);
        $this->assertArrayNotHasKey('admins', $overview['counts']);
    }

    public function testPresenterProtectsAdminsAndHandlesEmptyDonuts(): void
    {
        $presenter = new DashboardClass($this->createMock(DashboardModel::class));
        $counts = array_fill_keys(['admins', 'brands', 'categories', 'products'], ['active' => 0, 'suspended' => 0, 'trashed' => 0]);

        $cards = $presenter->getCards($counts, (object) ['superadmin' => 0]);
        $this->assertCount(3, $cards);
        $this->assertArrayNotHasKey('admins', $cards);
        $this->assertSame(0, $cards['brands']['total']);
        $this->assertSame('0.0000', $cards['brands']['activeEnd']);
        $this->assertCount(4, $presenter->getCards($counts, (object) ['superadmin' => 1]));
    }

    public function testPresenterDoesNotConfuseMissingDataWithZero(): void
    {
        $presenter = new DashboardClass($this->createMock(DashboardModel::class));
        $charts = $presenter->getRankings([
            'brands' => [['id' => 1, 'label' => 'A', 'count' => 4], ['id' => 2, 'label' => 'B', 'count' => 2]],
            'categories' => null,
        ]);

        $this->assertSame('100.0000', $charts['brands']['items'][0]['share']);
        $this->assertSame('50.0000', $charts['brands']['items'][1]['share']);
        $this->assertFalse($charts['brands']['unavailable']);
        $this->assertTrue($charts['categories']['unavailable']);
        $this->assertSame([], $charts['categories']['items']);
        $this->assertSame([], $presenter->getRankings(['brands' => []])['brands']['items']);
    }

    public function testValuePresenterFormatsExactDecimalsAndHandlesZeroBars(): void
    {
        $presenter = new DashboardClass($this->createMock(DashboardModel::class));
        $charts = $presenter->getRankings([
            'brandsValue' => [['id' => 1, 'label' => 'A', 'value' => '20.10'], ['id' => 2, 'label' => 'B', 'value' => '10.05']],
            'categoriesValue' => [['id' => 1, 'label' => 'C', 'value' => '0.00']],
        ]);

        $this->assertSame('100.0000', $charts['brandsValue']['items'][0]['share']);
        $this->assertSame('50.0000', $charts['brandsValue']['items'][1]['share']);
        $this->assertSame('brands', $charts['brandsValue']['entity']);
        $this->assertSame('value', $charts['brandsValue']['metric']);
        $this->assertSame('0.0000', $charts['categoriesValue']['items'][0]['share']);
        $formatted = $presenter->formatValue('42949672949957050327.05');
        $this->assertSame('42949672949957050327.05', str_replace([
            lang('backend/dashboard.formats.thousandsSeparator'),
            lang('backend/dashboard.formats.decimalSeparator'),
        ], ['', '.'], $formatted));
    }

    public function testProductsOnlyPermissionAllowsTotalButNoBrandOrCategoryRankings(): void
    {
        $counts = $this->createMock(MenuCountsModel::class);
        $counts->method('getCounts')->willReturn(['products' => ['active' => 1, 'suspended' => 0, 'trashed' => 0]]);
        Factories::injectMock('models', MenuCountsModel::class, $counts);
        $statistics = $this->createMock(ProductStatisticsModel::class);
        $statistics->expects($this->never())->method('getTopBrandsByValue');
        $statistics->expects($this->never())->method('getTopCategoriesByValue');
        $statistics->expects($this->once())->method('getInventoryValue')->willThrowException(new \RuntimeException('Fixture error'));
        Factories::injectMock('models', ProductStatisticsModel::class, $statistics);

        $overview = (new DashboardModel())->getOverview((object) ['superadmin' => 0]);
        $this->assertSame([], $overview['rankings']);
        $this->assertNull($overview['totals']['inventoryValue']);
    }
}
