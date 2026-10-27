<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Config;

use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Categories\Models\CategoriesModel;
use Modules\Backend\Products\Models\ProductsModel;

/**
 * Definisce esclusivamente il perimetro applicativo del pacchetto Warehouse.
 */
class Package
{
    /** Record per richiesta: condiviso da verifica import ed esportazione del pacchetto XLSX. */
    public const CHUNK_SIZE = 5;

    public const TABLES = ['brands', 'categories', 'products', 'brands_to_products', 'categories_to_products'];

    public const MODELS = [
        'brands' => BrandsModel::class,
        'categories' => CategoriesModel::class,
        'products' => ProductsModel::class,
    ];

    public const PIVOTS = [
        'brands_to_products' => ['brand_id' => 'brands', 'product_id' => 'products'],
        'categories_to_products' => ['category_id' => 'categories', 'product_id' => 'products'],
    ];

    /* Gli ID collegano i fogli; le date tecniche restano esclusivamente server-side. */
    public const METADATA = ['id'];
    public const TIMESTAMPS = ['created_at', 'updated_at', 'deleted_at', 'restored_at'];
}
