<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Database\ConnectionInterface;

class AuthorizationClass
{
    private ConnectionInterface $db;

    private ?object $currentAdminCache;

    /* Inietta esclusivamente la connessione al database */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->currentAdminCache = null;
    }

    public function currentAdmin(): ?object
    {
        /* 1. Restituisce la cache se presente */
        if ($this->currentAdminCache !== null):
            return $this->currentAdminCache;
        endif;

        /* 2. Verifica tramite sessione */
        $data = $this->getAdminFromSession();
        if ($data !== null):
            $this->currentAdminCache = $data;
            return $data;
        endif;

        /* 3. Verifica tramite cookie */
        $data = $this->getAdminFromCookie();
        if ($data !== null):
            $this->currentAdminCache = $data;
            return $data;
        endif;

        return null;
    }

    private function getAdminFromSession(): ?object
    {
        if ( ! session()->has('backendSession') || session()->get('backendSession') === null):
            return null;
        endif;

        /* Istanzia il token passando il valore salvato in sessione */
        $token = new \App\Libraries\Token(session()->get('backendSession'));
        $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

        $sql = "select * from admins_tokens where token_hash = ? and token_type = ? limit 1";
        $query = $this->db->query($sql, [$tokenHash, 'session'])->getRow();

        /* Controlla la validità temporale confrontando le stringhe DATETIME */
        if (isset($query->token_hash) && $query->token_expire > date('Y-m-d H:i:s')):

            /* Aggiorna la scadenza per mantenere la sessione attiva */
            $newExpire = date('Y-m-d H:i:s', time() + (int) config('BackendAuth')->sessionTime);
            $sqlUpdate = "update admins_tokens set token_expire = ? where token_hash = ? and token_type = ?";
            $this->db->query($sqlUpdate, [$newExpire, $tokenHash, 'session']);

            $data = $this->getAdmin($query->user_uuid);
            if ($data):
                return $data;
            endif;

        endif;

        return null;
    }

    private function getAdminFromCookie(): ?object
    {
        helper('cookie');
        $cookieValue = get_cookie('backendRememberMe');

        if ($cookieValue === null):
            return null;
        endif;

        /* Decifra il valore del cookie prima di passarlo alla classe Token */
        $crypto = new \App\Libraries\CryptoService(config('BackendAuth')->sessionCryptoKey);
        $decryptedValue = $crypto->decrypt($cookieValue);

        if ( ! $decryptedValue):
            return null;
        endif;

        $token = new \App\Libraries\Token($decryptedValue);
        $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

        $sql = "select * from admins_tokens where token_hash = ? and token_type = ? limit 1";
        $query = $this->db->query($sql, [$tokenHash, 'cookie'])->getRow();

        /* Anche qui il confronto avviene in formato DATETIME */
        if (isset($query->token_hash) && $query->token_expire > date('Y-m-d H:i:s')):
            $data = $this->getAdmin($query->user_uuid);
            if ($data):
                return $data;
            endif;
        endif;

        return null;
    }

    private function getAdmin(string $uuid): ?object
    {
        /* 1. Recupero dei dati base dell'utente */
        $sql = "select 
                    uuid, 
                    firstname, 
                    lastname, 
                    email, 
                    phone, 
                    status, 
                    master, 
                    created_at, 
                    updated_at, 
                    suspended_at, 
                    resetted_at 
                from admins 
                where uuid = ? 
                and status = 1 
                and suspended_at is null 
                limit 1";

        $data = $this->db->query($sql, [$uuid])->getRow();

        /* Se l'utente non esiste o è disabilitato/sospeso, interrompiamo subito */
        if ( ! $data):
            return null;
        endif;

        /* 2. Inizializzazione della proprietà per i permessi come oggetto vuoto */
        $data->permissions = new \stdClass();

        /* 3. Logica di estrazione permessi o bypass per il master */
        if ((int) $data->master === 1):
            /* Bypass: il master riceve una proprietà universale */
            $data->permissions->all = true;
        else:
            /* Interroga il database restituendo un array di oggetti */
            $sqlPerms = "select permission from admins_permissions where user_uuid = ?";
            $permsResult = $this->db->query($sqlPerms, [$uuid])->getResultObject();
            
            if ($permsResult):
                foreach ($permsResult as $row):
                    /* Crea dinamicamente la proprietà (es. $data->permissions->users_index) */
                    $permName = $row->permission;
                    $data->permissions->{$permName} = true;
                endforeach;
            endif;
        endif;

        return $data;
    }
}