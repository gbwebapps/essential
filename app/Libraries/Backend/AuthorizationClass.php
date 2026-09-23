<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Database\ConnectionInterface;

class AuthorizationClass
{
    private ConnectionInterface $db;

    private ?object $currentAdminCache;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->currentAdminCache = null;
        helper('settings');
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
        $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

        $sql = "select * from admins_tokens where token_hash = ? and token_type = ? limit 1";
        $query = $this->db->query($sql, [$tokenHash, 'session'])->getRow();

        /*
         * 1. Calcolo del limite di inattività basato su last_activity.
         * Il token è valido se (last_activity + sessionTime) è nel futuro.
         */
        $now = \CodeIgniter\I18n\Time::now();
        $sessionTimeLimit = (int) setting('Backend\Auth')->sessionTime;
        $maxActivityTime = isset($query->last_activity) ? \CodeIgniter\I18n\Time::parse($query->last_activity)->addSeconds($sessionTimeLimit) : null;

        if (isset($query->token_hash) && $maxActivityTime && $now->isBefore($maxActivityTime)):

            /* 
             * 2. Sliding Expiration & Audit Trail.
             * Registriamo l'ora esatta di questa interazione (last_activity) e posticipiamo
             * la scadenza assoluta del token.
             */
            $currentTimeStr = $now->format('Y-m-d H:i:s');
            $newExpireStr   = $now->addSeconds($sessionTimeLimit)->format('Y-m-d H:i:s');
            
            $sqlUpdate = "update admins_tokens set last_activity = ?, token_expire = ? where token_hash = ? and token_type = ?";
            $this->db->query($sqlUpdate, [$currentTimeStr, $newExpireStr, $tokenHash, 'session']);

            /* 3. Risoluzione dell'identità */
            $data = $this->getAdmin($query->admin_uuid);
            if ($data):
                return $data;
            endif;

        endif;

        return null;
    }

    private function getAdminFromCookie(): ?object
    {
        $cookie = service('request')->getCookie('backendRememberMe');

        if ($cookie === null):
            return null;
        endif;

        /* Decifra il valore del cookie prima di passarlo alla classe Token */
        $crypto = new \App\Libraries\CryptoService(setting('Backend\Auth')->sessionCryptoKey);
        $decryptedValue = $crypto->decrypt($cookie);

        if ( ! $decryptedValue):
            return null;
        endif;

        $token = new \App\Libraries\Token($decryptedValue);
        $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

        $sql = "select * from admins_tokens where token_hash = ? and token_type = ? limit 1";
        $query = $this->db->query($sql, [$tokenHash, 'cookie'])->getRow();

        /* 1. Controllo di validità assoluta (il RememberMe ha una data di "morte" rigida) */
        $now = \CodeIgniter\I18n\Time::now();
        $expireTime = isset($query->token_expire) ? \CodeIgniter\I18n\Time::parse($query->token_expire) : null;

        if (isset($query->token_hash) && $expireTime && $now->isBefore($expireTime)):
            
            /* 
             * 2. Audit Trail (Tracking).
             * Aggiorniamo in tempo reale solo l'ultima attività nota dell'amministratore,
             * senza alterare la scadenza assoluta del token persistente.
             */
            $currentTimeStr = $now->format('Y-m-d H:i:s');
            $sqlUpdate = "update admins_tokens set last_activity = ? where token_hash = ? and token_type = ?";
            $this->db->query($sqlUpdate, [$currentTimeStr, $tokenHash, 'cookie']);

            /* 3. Risoluzione dell'identità */
            $data = $this->getAdmin($query->admin_uuid);
            if ($data):
                return $data;
            endif;
            
        endif;

        return null;
    }

    private function getAdmin(string $uuid): ?object
    {
        /* 1. Recupero dei dati base dell'utente includendo group_id con blocco rigoroso del cestino */
        $sql = "select 
                    uuid, 
                    firstname, 
                    lastname, 
                    email, 
                    phone, 
                    status,
                    note,  
                    superadmin, 
                    group_id, 
                    created_at, 
                    updated_at, 
                    suspended_at, 
                    resetted_at, 
                    deleted_at 
                from admins 
                where uuid = ? 
                and status = 1 
                and suspended_at is null 
                and deleted_at is null 
                limit 1";

        $data = $this->db->query($sql, [$uuid])->getRow();

        /* Se l'utente non esiste o è disabilitato/sospeso, interrompiamo subito */
        if ( ! $data):
            return null;
        endif;

        /* 2. Inizializzazione della proprietà per i permessi come oggetto vuoto */
        $data->permissions = new \stdClass();

        /* 3. Logica di estrazione permessi o bypass per il superadmin */
        if ((int) $data->superadmin === 1):
            /* Bypass: il superadmin riceve una proprietà universale */
            $data->permissions->all = true;
        else:
            /* Array temporaneo per mappare i permessi finali */
            $finalPermissions = [];

            /* Estraggo i permessi base associati al gruppo dell'utente */
            $sqlGroupPerms = "select permission from admins_groups_permissions where group_id = ?";
            $groupPerms = $this->db->query($sqlGroupPerms, [$data->group_id])->getResultObject();

            if ($groupPerms):
                foreach ($groupPerms as $row):
                    $finalPermissions[$row->permission] = true;
                endforeach;
            endif;

            /* Estraggo le eccezioni specifiche dell'utente (colonna allow) */
            $sqlUserPerms = "select permission, allow from admins_permissions where admin_uuid = ?";
            $userPerms = $this->db->query($sqlUserPerms, [$uuid])->getResultObject();

            if ($userPerms):
                foreach ($userPerms as $row):
                    if ((int) $row->allow === 1):
                        /* Eccezione positiva: aggiungo o confermo il permesso */
                        $finalPermissions[$row->permission] = true;
                    else:
                        /* Eccezione negativa: revoco il permesso ereditato dal gruppo */
                        unset($finalPermissions[$row->permission]);
                    endif;
                endforeach;
            endif;

            /* Converto l'array finale in proprietà dinamiche dell'oggetto permissions */
            foreach ($finalPermissions as $permName => $value):
                $data->permissions->{$permName} = true;
            endforeach;
        endif;

        return $data;
    }

    public function refresh(): self
    {
        $this->currentAdminCache = null;

        return $this;
    }
}