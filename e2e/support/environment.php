<?php declare(strict_types = 1);

if (PHP_SAPI !== 'cli'):
    exit(1);
endif;

chdir(dirname(__DIR__, 2));
require 'vendor/codeigniter4/framework/system/Config/DotEnv.php';
(new \CodeIgniter\Config\DotEnv(getcwd()))->load();

$environment = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production';

if ($environment !== 'e2e'):
    fwrite(STDERR, "La suite Playwright richiede CI_ENVIRONMENT=e2e.\n");
    exit(1);
endif;

$_SERVER['CI_ENVIRONMENT'] = $environment;
require 'vendor/codeigniter4/framework/system/util_bootstrap.php';

$config = config('Database');
$expectedDatabase = (string) $config->tests['database'];
$db = \Config\Database::connect();

if (ENVIRONMENT !== 'e2e' || $config->defaultGroup !== 'tests'
    || $expectedDatabase === ''
    || $db->getDatabase() !== $expectedDatabase
    || $db->query('select database() as db')->getRow()->db !== $expectedDatabase):
    fwrite(STDERR, "La suite Playwright richiede il database locale configurato nel gruppo tests.\n");
    exit(1);
endif;

echo json_encode(['environment' => ENVIRONMENT, 'group' => $config->defaultGroup, 'database' => $db->getDatabase()]);
