<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

/**
 * Gestisce la configurazione delle impostazioni generali, della localizzazione e dei formati di visualizzazione del backend.
 */
class General extends BaseConfig
{
    /**
     * Fuso orario predefinito del sistema
     * 
     * @var string 
     */
    public string $timezone = 'Europe/Rome';
    
    /**
     * Codice della lingua predefinita utilizzata nell'interfaccia
     * 
     * @var string 
     */
    public string $language = 'en';

    /**
     * Formato predefinito per la visualizzazione di date e ore
     * 
     * @var string 
     */
    public string $dateFormat = 'd MMMM yyyy HH:mm:ss';
}