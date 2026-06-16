<?php

use Doctum\Doctum;
use Symfony\Component\Finder\Finder;
use Doctum\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/app')
    ->exclude('ThirdParty')
    ->filter(function (\SplFileInfo $file) {
        /* Verifica se il file si trova dentro la cartella Config */
        if (strpos($file->getRelativePathname(), 'Config' . DIRECTORY_SEPARATOR) === 0) {
            
            /* LA TUA WHITELIST (Accetta sia file singoli che intere sottocartelle) */
            $whitelist = [
                'Backend' . DIRECTORY_SEPARATOR, /* Cartella intera */
                'Frontend' . DIRECTORY_SEPARATOR, /* Cartella intera */
                'Routes.php',
                'Services.php'
            ];
            
            /* Controlla se il file o la sua sottocartella corrispondono alla whitelist */
            foreach ($whitelist as $item) {
                if ($file->getFilename() === $item || strpos($file->getRelativePathname(), 'Config' . DIRECTORY_SEPARATOR . $item) === 0) {
                    return true; /* File approvato */
                }
            }
            
            return false; /* Esclude tutto il resto di Config */
        }
        
        return true; /* Mantieni il resto di app */
    });

return new Doctum($iterator, [
    'title'                => 'Essential API',
    'build_dir'            => __DIR__ . '/public/guide',
    'cache_dir'            => __DIR__ . '/.doctum_cache',
    'default_opened_level' => 1,
    'filter'               => new TrueFilter()
]);