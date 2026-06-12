<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Class Assets
 *
 * Configurazione centrale per la gestione degli asset nativi (CSS e JavaScript)
 * richiesti dal nucleo (Core) dell'ambiente di Backend.
 */
class Assets extends BaseConfig
{
    /**
     * Restituisce l'elenco dei file CSS fondamentali e obbligatori per il layout del backend.
     *
     * @return array Insieme degli asset CSS core con relativi identificativi e percorsi.
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
     * Genera l'elenco dei file JavaScript fondamentali, integrando dinamicamente
     * l'eventuale script specifico associato al controller in esecuzione.
     *
     * @param string|null $controller Nome del controller corrente per l'inclusione del file dedicato.
     * @return array Insieme degli asset JavaScript core e condizionali.
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