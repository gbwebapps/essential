<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Componente infrastrutturale per il controllo degli accessi e la persistenza dell'identità.
 *
 * Rappresenta il nucleo del sistema di sicurezza del backend. Questa classe implementa una strategia
 * di autenticazione multi-livello analizzando in sequenza la memoria locale (cache a livello di istanza),
 * lo stato della sessione attiva e i cookie di tracciamento persistenti. Gestisce inoltre la verifica
 * dell'integrità dei token crittografici e la mappatura dinamica dei permessi associati all'utente.
 */
class AuthorizationClass
{
    /**
     * Connessione condivisa al driver del database per le operazioni di lettura e aggiornamento.
     *
     * @var ConnectionInterface
     */
    private ConnectionInterface $db;

    /**
     * Registro di cache locale per la memorizzazione temporanea dell'utente autenticato.
     *
     * Evita ridondanze e query multiple verso il database durante il medesimo ciclo di esecuzione
     * della richiesta HTTP (pattern Singleton di istanza).
     *
     * @var object|null
     */
    private ?object $currentAdminCache;

    /**
     * Costruttore del componente con iniezione della connessione al database.
     *
     * Imposta lo stato iniziale dell'infrastruttura di sicurezza azzerando la cache locale.
     *
     * @param ConnectionInterface $db Istanza di connessione al database corrente.
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->currentAdminCache = null;
        helper('settings');
    }

    /**
     * Risolve l'identità dell'amministratore corrente attraverso la pipeline di controllo.
     *
     * Ispeziona progressivamente la cache interna, lo storage di sessione del server e i cookie
     * del client. Restituisce l'oggetto anagrafico completo comprensivo di permessi in caso
     * di riscontro positivo, oppure null qualora nessun canale risulti valido o attivo.
     *
     * @return object|null Dati dell'amministratore autenticato, o null se non autorizzato.
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
     * Verifica lo stato dell'autenticazione analizzando la sessione attiva.
     *
     * Estrae l'identificativo di sessione, ne calcola l'hash di sicurezza e interroga la tabella
     * dei token per verificarne la validità temporale. Se il token è valido, provvede a estendere
     * la scadenza sul database (meccanismo di sliding expiration) e a caricare il profilo.
     *
     * @return object|null Dati dell'amministratore se la sessione è valida, altrimenti null.
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

        /* Controlla la validità temporale confrontando le stringhe DATETIME */
        if (isset($query->token_hash) && $query->token_expire > date('Y-m-d H:i:s')):

            /* Aggiorna la scadenza per mantenere la sessione attiva */
            $newExpire = date('Y-m-d H:i:s', time() + (int) setting('Backend\Auth')->sessionTime);
            $sqlUpdate = "update admins_tokens set token_expire = ? where token_hash = ? and token_type = ?";
            $this->db->query($sqlUpdate, [$newExpire, $tokenHash, 'session']);

            $data = $this->getAdmin($query->admin_uuid);
            if ($data):
                return $data;
            endif;

        endif;

        return null;
    }

    /**
     * Verifica lo stato dell'autenticazione analizzando il cookie di persistenza (Remember Me).
     *
     * Recupera il cookie cifrato dal client, esegue la decifratura tramite la chiave simmetrica
     * configurata e computa l'hash del token estratto. Esegue il controllo di corrispondenza
     * e validità temporale sul database per rigenerare l'autenticazione.
     *
     * @return object|null Dati dell'amministratore se il cookie è integro e valido, altrimenti null.
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

        /* Anche qui il confronto avviene in formato DATETIME */
        if (isset($query->token_hash) && $query->token_expire > date('Y-m-d H:i:s')):
            $data = $this->getAdmin($query->admin_uuid);
            if ($data):
                return $data;
            endif;
        endif;

        return null;
    }

    /**
     * Estrae l'anagrafica dell'amministratore dal database e ne mappa i privilegi operativi.
     *
     * Seleziona i campi sensibili dell'utente verificando che lo stato sia attivo e non sospeso.
     * Successivamente, analizza il flag di superamministrazione (superadmin): se abilitato, concede
     * l'accesso universale, altrimenti popola dinamicamente l'oggetto delle autorizzazioni
     * mappando come proprietà i singoli record estratti dalla tabella dei permessi.
     *
     * @param string $uuid Identificativo univoco dell'amministratore da recuperare.
     * @return object|null Oggetto utente completo di permessi, o null se l'utente non è abilitato.
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
     * Svuota la cache interna dell'utente loggato per forzare il ricaricamento dei dati.
     *
     * @return self
     */
    public function refresh(): self
    {
        $this->currentAdminCache = null;

        return $this;
    }
}