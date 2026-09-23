<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione delle impostazioni generali, della localizzazione e dei formati di visualizzazione del backend.
 */
class General extends BaseConfig
{
    /**
     * @var string Fuso orario predefinito del sistema
     */
    public string $timezone = 'Europe/Rome';
    
    /**
     * @var string Codice della lingua predefinita utilizzata nell'interfaccia
     */
    public string $language = 'en';

    /**
     * @var string Formato predefinito per la visualizzazione di date e ore
     */
    public string $dateFormat = 'd MMMM yyyy HH:mm:ss';
}