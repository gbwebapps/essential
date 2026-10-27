<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function language(?string $locale = null, bool $getShared = true): \CodeIgniter\Language\Language
    {
        if ($getShared):
            return static::getSharedInstance('language', $locale);
        endif;

        return new \App\Libraries\Backend\InterfaceLanguage($locale ?? config('App')->defaultLocale);
    }

    /**
     * Inizializza e restituisce il servizio Email utilizzando le impostazioni unificate (database + configurazione).
     *
     * @param array|\Config\Email|null $config Configurazione esplicita opzionale.
     * @param bool $getShared Determina se restituire l'istanza condivisa (Singleton) o una nuova istanza.
     * @return \CodeIgniter\Email\Email
     */
    public static function email($config = null, bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('email', $config);
        endif;

        if ($config === null):
            helper('settings');
            $config = (array) setting('Backend\\Email');
            $config['SMTPPort'] = (int) $config['SMTPPort'];
        endif;

        return new \CodeIgniter\Email\Email($config);
    }

    /**
     * Inizializza e restituisce il servizio di gestione dell'autorizzazione e dei permessi per gli amministratori.
     *
     * @param bool $getShared Determina se restituire l'istanza condivisa (Singleton) o una nuova istanza.
     * @return \App\Libraries\Backend\AuthorizationClass
     */
    public static function authorization(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('authorization');
        endif;

        /* Inietta la connessione condivisa al database */
        $db = \Config\Database::connect();

        return new \App\Libraries\Backend\AuthorizationClass($db);
    }

    /**
     * Inizializza e restituisce il servizio centralizzato dedicato alla cifratura e decifratura dei dati.
     *
     * @param bool $getShared Determina se restituire l'istanza condivisa (Singleton) o una nuova istanza.
     * @return \App\Libraries\CryptoService
     */
    public static function crypto(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('crypto');
        endif;

        /* Inietta la chiave di cifratura recuperata dalla configurazione */
        $key = setting('Backend\Auth')->sessionCryptoKey;

        return new \App\Libraries\CryptoService($key);
    }

    /**
     * Inizializza e restituisce la libreria di utilità per la validazione di stringhe tramite espressioni regolari.
     *
     * @param bool $getShared Determina se restituire l'istanza condivisa (Singleton) o una nuova istanza.
     * @return \App\Libraries\RegExp
     */
    public static function regexp(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('regexp');
        endif;
     
        return new \App\Libraries\RegExp();
    }
}
