<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

class General extends BaseConfig
{
    /* Imposta un valore di default valido per PHP */
    public string $timezone = 'Europe/Rome';
    
    /* Imposta il codice lingua di default */
    public string $language = 'it';

    /* Imposta il formato data/ora di default per la visualizzazione */
    public string $dateFormat = 'd MMMM yyyy HH:mm:ss';
}