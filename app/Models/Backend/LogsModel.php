<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/*
 * Modello dedicato alla consultazione dei log di accesso e alla gestione delle sessioni (Logs).
 * 
 * Estende il BackendModel per sfruttarne il motore di paginazione e filtraggio. 
 * Configura in modo mirato le query per estrarre lo storico degli accessi (unendo i dati 
 * dell'utente e della sessione) e fornisce la logica operativa per disconnettere 
 * forzatamente (ban/kick) una sessione specifica in caso di necessità.
 */
class LogsModel extends BackendModel  
{
    /**
     * Nome della tabella principale usata dal motore del BackendModel per costruire dinamicamente query e filtri.
     * 
     * @var string 
     */
    protected ?string $module = 'admins_logs';

    /**
     * Whitelist dei parametri di paginazione accettati dal server (es. numero pagina, righe per pagina).
     * 
     * @var array 
     */
    protected array $showAllAllowedFields = ['id', 'column', 'order', 'page', 'rows', 'searchFields', 'searchDates'];

    /**
     * Elenco esclusivo delle colonne su cui l'operatore è autorizzato a ordinare la griglia dati (ordinamento sicuro).
     * 
     * @var array 
     */
    protected array $allowedOrderColumns = ['id', 'username', 'login', 'logout', 'logout_reason'];

    /**
     * Whitelist dei campi di database su cui è consentito applicare la ricerca testuale libera (filtri LIKE).
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedFields = ['username', 'logout_reason']; 

    /**
     * Whitelist delle colonne di tipo data filtrabili tramite un intervallo temporale (range Da/A).
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedDates = ['login'];

    /**
     * Query SQL per il conteggio dei log filtrati.
     * Utilizza le JOIN per arricchire la riga di log con i dettagli della sessione (token) e l'anagrafica dell'amministratore.
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select admins_logs.*, admins_tokens.token_expire, admins_tokens.last_activity, admins_tokens.id as token_id_val, admins.firstname, admins.lastname, admins.superadmin  
                                        from admins_logs 
                                        left join admins_tokens 
                                        on admins_tokens.id = admins_logs.token_id 
                                        left join admins 
                                        on admins.uuid = admins_logs.admin_uuid 
                                        where 1 = 1";

    /**
     * Query SQL principale per l'estrazione paginata dei log. 
     * 
     * @var string
     */
    protected ?string $getNumRowsQuery = "select count(*) as count from admins_logs where 1 = 1";

    /**
     * Query SQL per il recupero puntuale di un log.
     *
     * @var string
     */
    protected ?string $getUUIDQuery = "select * from admins_logs where id = ?";

    /*
     * Regole di validazione per il motore di paginazione e ordinamento.
     * 
     * Assicura che la richiesta inviata dal browser contenga valori validi per calcolare 
     * la pagina e l'offset: richiede numeri interi positivi per righe e pagine, 
     * e vincola l'ordine esclusivamente a 'asc' o 'desc'.
     *
     * @return array Regole native di CodeIgniter per i parametri strutturali
     */
    public function showAllValidationRules(): array
    {
        return [
            'column' => [
                'rules' => ['required', 'alpha_dash'] 
            ],
            'order' => [
                'rules' => ['required', 'in_list[asc,desc]'] 
            ],
            'page' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
            'rows' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
        ];
    }

    /*
     * Regole di validazione per i campi di ricerca testuale e temporale.
     * 
     * Controlla che il testo inserito dall'operatore sia sicuro, consentendo ricerche parziali sullo username,
     * e vincola il motivo del logout (logout_reason) a una lista chiusa di valori noti. 
     * Verifica inoltre che le date inserite per il filtro temporale rispettino il formato corretto.
     *
     * @return array Regole per i filtri di ricerca
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.username' => [
                'label' => lang('backend/logs.labels.username'), 
                'rules' => ['permit_empty', 'trim', 'max_length[100]', 'regex_match[/^[a-zA-Z0-9@._+\-]+$/]'],
            ],
            'searchFields.logout_reason' => [
                'label' => lang('backend/logs.labels.logoutReason'), 
                'rules' => ['permit_empty', 'in_list[manual,timeout,deleted,banned]'], 
            ],
            'searchDates.login-from' => [
                'label' => lang('backend/logs.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.login-to' => [
                'label' => lang('backend/logs.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /*
     * Interrompe forzatamente una singola sessione attiva (operazione di "Kick" o "Ban").
     * 
     * Il metodo recupera i dettagli del token tramite ID. Controlla preventivamente tramite gli 
     * "Scudi Enterprise" che l'utente bersaglio non sia protetto (es. Superadmin) o inesistente/cestinato. 
     * Se i controlli passano, aggiorna il log associato impostando il motivo di uscita su 'banned' 
     * ed elimina fisicamente il token dal database, invalidando all'istante la navigazione dell'utente colpito.
     *
     * @param int $tokenId L'ID numerico del token di sessione o cookie da distruggere
     * @return array Risposta strutturata con esito (result) e messaggio di feedback per l'operatore
     */
    public function disconnectSession(int $tokenId): array
    {
        $transactionStarted = false;

        try {
            if ( ! $this->db->transBegin()):
                throw new \RuntimeException('disconnectSession: impossibile avviare la transazione.');
            endif;

            $transactionStarted = true;

            /* 1. Recupero il token tramite il suo ID */
            $tokenSql = "select at.id, at.admin_uuid, at.last_activity, at.token_type, a.firstname, a.lastname, a.superadmin, a.deleted_at 
                         from admins_tokens as at 
                         join admins as a 
                         on at.admin_uuid = a.uuid 
                         where at.id = ?
                         for update";
            $tokenRow = $this->db->query($tokenSql, [$tokenId])->getRow();

            if ( ! $tokenRow):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/logs.messages.disconnectSessionError')];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($tokenRow->deleted_at !== null):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/logs.messages.cannotModifyDeleted')];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $tokenRow->superadmin === 1):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result'  => false, 'message' => lang('backend/logs.messages.protectedAdmin')];
            endif;

            /* 2. Aggiorno il log registrando la forzatura (banned) */
            if (in_array($tokenRow->token_type, ['cookie', 'session'], true)):
                $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                
                $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ? and logout is null";
                $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
            endif;

            /* 3. Elimino fisicamente il token */
            $sqlDelete = "delete from admins_tokens where id = ?";
            $this->db->query($sqlDelete, [$tokenRow->id]);

            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/logs.messages.disconnectSessionError')];
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('disconnectSession: commit della transazione non riuscito.');
            endif;

            $transactionStarted = false;

            /* 4. Log dell'azione per l'audit di sistema */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('DISCONNECT_SESSION', 'logs', lang('backend/logs.audits.disconnectSession'), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/logs.messages.disconnectSessionSuccess'), esc($tokenRow->firstname), esc($tokenRow->lastname))];

        } catch (\Throwable $e) {
            if ($transactionStarted):
                $this->db->transRollback();
            endif;
            
            /* Tracciamento dell'errore tecnico */
            log_message('error', 'Errore ban sessione - ' . $e);
            return ['result' => false, 'message' => lang('backend/logs.messages.disconnectSessionError')];
            
        }
    }
}
