<?php declare(strict_types = 1);

/** Renderizza i partial reali dei form, senza avviare l'applicazione o il database. */
$productsRoot = dirname(__DIR__, 3);
require_once $productsRoot . '/app/Helpers/interface_helper.php';
$productsLabels = require $productsRoot . '/modules/Backend/Products/Language/it/backend/products.php';

function lang(string $key): string
{
    global $productsLabels;
    $value = $productsLabels;

    foreach (explode('.', substr($key, strlen('backend/products.'))) as $part):
        $value = $value[$part] ?? $key;
    endforeach;

    return is_string($value) ? $value : $key;
}

function esc(mixed $value, string $context = 'html'): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$preview = new class {
    /** Gli upload vengono verificati separatamente, senza includere altri componenti. */
    public function include(string $partial): string
    {
        return '';
    }

    public function render(string $root, string $context): void
    {
        $brandOptions = [];
        $categoryOptions = [];

        if ($context !== 'empty'):
            $brandOptions = [
                (object) ['uuid' => '00000000-0000-4000-8000-000000000001', 'brand' => 'Active brand', 'status' => 1],
                (object) ['uuid' => '00000000-0000-4000-8000-000000000002', 'brand' => 'Suspended brand', 'status' => 0],
            ];
            $categoryOptions = [
                (object) ['uuid' => '00000000-0000-4000-8000-000000000003', 'path' => 'Root > Active category', 'status' => 1],
                (object) ['uuid' => '00000000-0000-4000-8000-000000000004', 'path' => 'Suspended category', 'status' => 0],
            ];
        endif;

        if ($context === 'edit'):
            $product = (object) ['uuid' => '00000000-0000-4000-8000-000000000005', 'sku' => 'TEST-SKU', 'product' => 'Test product', 'quantity' => 1, 'price' => '1.00', 'status' => 1, 'note' => '', 'brand_uuids' => [], 'category_uuids' => []];
            include $root . '/modules/Backend/Products/Views/partials/edit/generalDataPartial.php';
        else:
            include $root . '/modules/Backend/Products/Views/partials/add/addPartial.php';
        endif;
    }
};

$preview->render($productsRoot, $argv[1] ?? 'empty');
