<?php declare(strict_types = 1);

/** Renderizza esclusivamente le viste di dashboard: nessun bootstrap HTTP o accesso al database. */
$dashboardRoot = dirname(__DIR__, 3);
require_once $dashboardRoot . '/app/Helpers/interface_helper.php';
require $dashboardRoot . '/vendor/autoload.php';
require_once $dashboardRoot . '/app/Config/Backend/Dashboard.php';

$dashboardLocale = in_array($argv[1] ?? '', ['it', 'en', 'de', 'es', 'fr', 'zh'], true) ? $argv[1] : 'it';
$dashboardScenario = $argv[2] ?? 'normal';

function lang(string $key): string
{
    global $dashboardRoot, $dashboardLocale;
    static $translations = [];
    [$file, $nested] = explode('.', $key, 2);
    $translations[$file] ??= require $dashboardRoot . '/app/Language/' . $dashboardLocale . '/' . $file . '.php';
    $value = $translations[$file];

    foreach (explode('.', $nested) as $part):
        $value = $value[$part] ?? $key;
    endforeach;

    return is_string($value) ? $value : $key;
}

function esc(mixed $value, string $context = 'html'): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $route): string
{
    return 'https://dashboard-preview.test/' . $route;
}

function view(string $partial, array $data): string
{
    global $dashboardRoot;
    extract($data, EXTR_SKIP);
    ob_start();
    include $dashboardRoot . '/app/Views/' . $partial . '.php';
    return ob_get_clean();
}

$model = (new ReflectionClass(\App\Models\Backend\DashboardModel::class))->newInstanceWithoutConstructor();
$dashboardPresenter = new \App\Libraries\Backend\DashboardClass($model);
$admin = (object) ['superadmin' => $dashboardScenario === 'restricted' ? 0 : 1];
$counts = [
    'admins' => ['active' => 19, 'suspended' => 3, 'trashed' => 2],
    'brands' => ['active' => 31, 'suspended' => 4, 'trashed' => 3],
    'categories' => ['active' => 112, 'suspended' => 9, 'trashed' => 5],
    'products' => ['active' => 1096, 'suspended' => 112, 'trashed' => 40],
];
$rankings = [
    'brands' => [['id' => 1, 'label' => 'Lenovo', 'count' => 318], ['id' => 2, 'label' => 'Dell', 'count' => 274], ['id' => 3, 'label' => 'Fujitsu', 'count' => 208]],
    'categories' => [['id' => 1, 'label' => 'Informatica > Notebook', 'count' => 418], ['id' => 2, 'label' => 'Informatica > Desktop', 'count' => 312]],
];
$rankings['brandsValue'] = [['id' => 2, 'label' => 'Dell', 'value' => '987654.32'], ['id' => 1, 'label' => 'Lenovo', 'value' => '543210.01']];
$rankings['categoriesValue'] = [['id' => 1, 'label' => 'Informatica > Notebook', 'value' => '765432.10'], ['id' => 2, 'label' => 'Informatica > Desktop', 'value' => '123456.78']];
$dashboardTotals = ['inventoryValue' => '1234567.89'];

if ($dashboardScenario === 'empty'):
    $counts = array_fill_keys(array_keys($counts), ['active' => 0, 'suspended' => 0, 'trashed' => 0]);
    $rankings = array_fill_keys(array_keys($rankings), []);
    $dashboardTotals = ['inventoryValue' => '0.00'];
elseif ($dashboardScenario === 'restricted'):
    $counts = ['brands' => $counts['brands']];
    $rankings = [];
    $dashboardTotals = [];
elseif ($dashboardScenario === 'error'):
    $rankings = ['brands' => null, 'categories' => []];
    $dashboardTotals = ['inventoryValue' => null];
elseif ($dashboardScenario === 'unsafe'):
    $rankings['brands'][0]['label'] = '<img src=x onerror="window.fixtureXss=true">';
elseif ($dashboardScenario === 'large'):
    $dashboardTotals = ['inventoryValue' => '42949672949957050327.05'];
    $rankings['brandsValue'][0]['value'] = '42949672949957050327.05';
elseif ($dashboardScenario === 'zeroValues'):
    $dashboardTotals = ['inventoryValue' => '0.00'];
    $rankings['brandsValue'] = [['id' => 1, 'label' => 'Lenovo', 'value' => '0.00']];
elseif ($dashboardScenario === 'productsOnly'):
    $counts = ['products' => $counts['products']];
    $rankings = [];
endif;

$dashboardCards = $dashboardPresenter->getCards($counts, $admin);
$dashboardRankings = $dashboardPresenter->getRankings($rankings);
include $dashboardRoot . '/app/Views/backend/dashboard/partials/index/indexPartial.php';
