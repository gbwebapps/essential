<?php

use Doctum\Doctum;
use Symfony\Component\Finder\Finder;
use Doctum\Parser\Filter\DefaultFilter; /* Importa il filtro base */

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('ThirdParty')
    ->notPath('Config/Boot')
    ->notPath('Database')
    ->in(__DIR__ . '/app');

return new Doctum($iterator, [
    'title'                => 'Essential API',
    'build_dir'            => __DIR__ . '/guide',
    'cache_dir'            => __DIR__ . '/.doctum_cache',
    'default_opened_level' => 2,
    /* Configura il filtro per includere Public, Protected e Private */
    'filter'               => new DefaultFilter(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE),
]);