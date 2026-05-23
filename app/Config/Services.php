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
    /* Servizio per l'autorizzazione degli Admins (Singleton) */
    public static function authorization(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('authorization');
        endif;

        /* Inietta la connessione condivisa al database */
        $db = \Config\Database::connect();

        return new \App\Libraries\Backend\AuthorizationClass($db);
    }

    /* Servizio per la cifratura (Singleton) */
    public static function crypto(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('crypto');
        endif;

        /* Inietta la chiave di cifratura recuperata dalla configurazione */
        $key = config('BackendAuth')->sessionCryptoKey;

        return new \App\Libraries\CryptoService($key);
    }

    public static function regexp(bool $getShared = true)
    {
        if ($getShared):
            return static::getSharedInstance('regexp');
        endif;
     
        return new \App\Libraries\RegExp();
    }
}
