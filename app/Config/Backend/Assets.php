<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione centralizzata delle risorse statiche (CSS e JavaScript) per il backend.
 */
class Assets extends BaseConfig
{
    /**
     * Restituisce l'elenco dei fogli di stile CSS di base necessari al backend.
     *
     * @return array Array multidimensionale contenente gli identificativi e i percorsi dei file CSS
     */
    public static function getCoreCss(): array
    {
        return [
            ['id' => 'bootstrap-css','path' => 'assets/vendor/bootstrap/css/bootstrap.min.css'],
            ['id' => 'fontawesome','path' => 'assets/vendor/fontawesome/css/all.min.css'],
            ['id' => 'backend-css','path' => 'assets/css/backend/backend.css'],
        ];
    }

    /**
     * Restituisce l'elenco dei file JavaScript di base, includendo opzionalmente lo script specifico del controller.
     *
     * @param string|null $controller Nome del controller corrente per l'inclusione dello script dedicato, oppure null
     * @return array Array multidimensionale contenente gli attributi e i percorsi dei file JavaScript
     */
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