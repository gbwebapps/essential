<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Libreria core per la risoluzione dell'identità e la gestione dei privilegi di accesso (ACL).
 * 
 * Centralizza l'intero motore di autorizzazione del pannello di controllo: gestisce la validazione crittografica 
 * dei token (Sessione e Remember Me), implementa meccanismi di "Sliding Expiration" per il timeout di inattività 
 * e compila dinamicamente la matrice dei permessi dell'utente fondendo le policy di gruppo con le eccezioni individuali.
 */
class AuthorizationClass
{
    /**
     * @var ConnectionInterface Istanza della connessione al database per l'interrogazione diretta dei token e degli utenti.
     */
    private ConnectionInterface $db;

    /**
     * @var object|null Cache in-memory (singleton-like) per conservare i dati dell'amministratore autenticato, prevenendo query ridondanti nel medesimo ciclo HTTP.
     */
    private ?object $currentAdminCache;

    /**
     * Inizializza il servizio di autorizzazione.
     * 
     * Inietta la dipendenza per la connessione al database, predispone la cache interna e 
     * carica l'helper globale necessario al recupero delle policy di sicurezza (impostazioni di timeout e chiavi crittografiche).
     *
     * @param ConnectionInterface $db Connessione attiva al database
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->currentAdminCache = null;
        helper('settings');
    }

    /**
     * Recupera e restituisce il profilo dell'amministratore attualmente autenticato.
     * 
     * Segue un pattern a cascata (waterfall) per ottimizzare le risorse: verifica innanzitutto l'esistenza 
     * dell'identità nella cache in memoria; in caso di "miss", tenta la risoluzione tramite il token di sessione 
     * e, come ultima ratio, decifra e valida l'eventuale cookie di persistenza (Remember Me).
     *
     * @return object|null Oggetto contenente i dati e i permessi dell'utente, oppure null se non autorizzato
     */
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

    /**
     * Restituisce i dati dell'amministratore corrente con un accesso basato su sessioni. 
     * Valida l'autenticità del token di sessione e aggiorna il ciclo di vita dell'accesso.
     * 
     * Il metodo estrae il token dalla sessione, ne calcola l'hash HMAC-SHA256 e lo confronta con il record a database.
     * Implementa una logica di "Sliding Expiration": verifica che il tempo di inattività non abbia superato 
     * il limite configurato e, in caso di esito positivo, aggiorna il timestamp di ultima operazione (audit trail) 
     * posticipando simultaneamente la scadenza assoluta del token.
     *
     * @return object|null Il profilo dell'amministratore se la sessione è valida e attiva, null in caso contrario
     */ 
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

    /**
     * Restituisce i dati dell'amministratore corrente con un accesso basato su cookies. 
     * Valida il token di accesso persistente (Remember Me) tramite decifrazione del cookie.
     * 
     * Recupera il cookie client-side, ne decifra il payload tramite algoritmo AES-256-GCM e ne verifica l'hash sul database.
     * A differenza della sessione, il Remember Me ha una scadenza assoluta ("hard limit") che non viene prolungata: 
     * il metodo si limita pertanto a registrare l'ultima attività nota ai fini di audit, procedendo infine all'estrazione dell'identità.
     *
     * @return object|null Il profilo dell'amministratore se il cookie è valido e non scaduto, null in caso contrario
     */
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

    /**
     * Estrae e compila il profilo completo dell'amministratore, inclusa la matrice dinamica dei permessi.
     * 
     * Effettua un controllo rigoroso di integrità: blocca l'accesso a utenti sospesi, cestinati (soft-delete) o disattivati.
     * Se l'utente è un Superadmin, inietta automaticamente un permesso globale ('all'). In caso contrario, 
     * costruisce la lista dei privilegi ereditandoli dal gruppo di appartenenza e applicando successivamente 
     * le eccezioni specifiche (override) definite a livello di singolo utente, siano esse concessive (allow) o restrittive (deny).
     *
     * @param string $uuid L'identificatore univoco dell'amministratore da estrarre
     * @return object|null L'oggetto utente arricchito con la proprietà 'permissions', o null se l'account non è idoneo
     */
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

    /**
     * Invalida e purga la cache in memoria dell'identità corrente.
     * 
     * Metodo di utilità fondamentale quando, durante la medesima richiesta HTTP, i permessi o lo stato 
     * dell'amministratore vengono alterati (es. aggiornamento del profilo) e si rende necessario forzare 
     * una rilettura a database al successivo richiamo di currentAdmin().
     *
     * @return self Restituisce l'istanza corrente per consentire il method chaining
     */
    public function refresh(): self
    {
        $this->currentAdminCache = null;

        return $this;
    }
}