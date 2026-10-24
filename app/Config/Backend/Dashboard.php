<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;
use Modules\Backend\Products\Models\ProductStatisticsModel;

/** Configura le sezioni della dashboard e i provider delle analisi di dominio. */
class Dashboard extends BaseConfig
{
    public const RANKING_LIMIT = 5;

    public const SECTIONS = [
        'admins' => ['label' => 'backend/global.menu.topRight.admins', 'icon' => 'fa-users', 'route' => 'backend/admins/showAll'],
        'brands' => ['label' => 'backend/global.menu.bottomLeft.brands', 'icon' => 'fa-tags', 'route' => 'backend/brands/showAll'],
        'categories' => ['label' => 'backend/global.menu.bottomLeft.categories', 'icon' => 'fa-folder-tree', 'route' => 'backend/categories/showAll'],
        'products' => ['label' => 'backend/global.menu.bottomLeft.products', 'icon' => 'fa-box', 'route' => 'backend/products/showAll'],
    ];

    public const RANKINGS = [
        'brands' => ['model' => ProductStatisticsModel::class, 'method' => 'getTopBrands', 'requires' => ['products', 'brands']],
        'categories' => ['model' => ProductStatisticsModel::class, 'method' => 'getTopCategories', 'requires' => ['products', 'categories']],
        'brandsValue' => ['model' => ProductStatisticsModel::class, 'method' => 'getTopBrandsByValue', 'requires' => ['products', 'brands'], 'entity' => 'brands', 'metric' => 'value'],
        'categoriesValue' => ['model' => ProductStatisticsModel::class, 'method' => 'getTopCategoriesByValue', 'requires' => ['products', 'categories'], 'entity' => 'categories', 'metric' => 'value'],
    ];

    public const TOTALS = [
        'inventoryValue' => ['model' => ProductStatisticsModel::class, 'method' => 'getInventoryValue', 'requires' => ['products']],
    ];
}
