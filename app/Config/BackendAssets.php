<?php declare(strict_types = 1); 

namespace Config;

use CodeIgniter\Config\BaseConfig;

class BackendAssets extends BaseConfig
{
    /* Definiamo i CSS fissi con i loro ID identificativi */
    public static function getCoreCss(): array
    {
        return [
            ['id' => 'bootstrap-css','path' => 'assets/vendor/bootstrap/css/bootstrap.min.css'],
            ['id' => 'fontawesome','path' => 'assets/vendor/fontawesome/css/all.min.css'],
            ['id' => 'backend-css','path' => 'assets/css/backend/backend.css'],
        ];
    }

    /* Definiamo i JS fissi con i loro ID identificativi */
    public static function getCoreJs(?string $controller = null): array
    {
        $js = [
            ['id' => 'bootstrap-js', 'path' => 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js', 'isModule' => false],
        ];

        /* Se il controller esiste, aggiungiamo il suo file JS specifico come modulo */
        if ($controller):
            $js[] = [
                'id' => $controller . '-js',
                'path' => 'assets/js/backend/' . $controller . '.js',
                'isModule' => true
            ];
        endif;

        return $js;
    }
}