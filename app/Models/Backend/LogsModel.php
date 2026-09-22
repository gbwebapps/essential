<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Descrizione classe
 */
class LogsModel extends BackendModel
{
    /**
     * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
     *
     * @var string|null
     */
    protected ?string $module = 'admins_logs';

    /**
     * Colonna di ordinamento predefinita utilizzata nelle query di estrazione se non specificata.
     *
     * @var string|null
     */
    protected ?string $defaultColumn = 'id';

    /**
     * Elenco dei parametri di input autorizzati per il filtraggio e l'impaginazione della vista tabellare globale.
     *
     * @var array
     */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    /**
     * Corrispondenza rigida tra gli indici dell'interfaccia utente e le colonne reali della tabella per l'ordinamento.
     *
     * @var array
     */
    protected array $allowedOrderColumns = ['username', 'login', 'logout', 'logout_reason']; 

    /**
     * Elenco dei campi su cui è consentita l'applicazione dei filtri di ricerca testuale nella vista globale.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['username', 'logout_reason']; 

    /**
     * [$showAllSearchAllowedDates description]
     * @var array
     */
    protected array $showAllSearchAllowedDates = ['login'];

    /**
     * [$getDataQuery description]
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
     * [$getNumRowsQuery description]
     * @var string
     */
    protected ?string $getNumRowsQuery = "select count(*) as count from admins_logs where 1 = 1";

    /**
     * [$getUUIDQuery description]
     * @var string
     */
    protected ?string $getUUIDQuery = "select * from admins_logs where id = ?";

    /**
     * Inizializza il modello eseguendo le configurazioni di base ereditate dalla classe madre.
     *
     * Sincronizza lo stato del modello impostando le dipendenze native e i driver di connessione
     * necessari al funzionamento del modulo amministratori.
     *
     * @return void
     */
    protected function initModel(): void 
    {
        parent::initModel();
    }

    /**
     * Definisce i vincoli di ordinamento e paginazione per la griglia tabellare.
     *
     * Restituisce le regole di validazione necessarie a blindare i parametri della richiesta DataTables,
     * verificando l'integrità della colonna bersaglio, il verso di ordinamento e la naturalità degli indici di pagina.
     *
     * @return array Mappa dei criteri di validazione per i flussi di paginazione.
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

    /**
     * @return [type]
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.username' => [
                'label' => lang('backend/logs.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
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

    /**
     * @param  int
     * @return [type]
     */
    public function deleteToken(int $tokenId): array
    {
        try {
            /* 1. Recupero il token tramite il suo ID */
            $tokenSql = "select at.id, at.admin_uuid, at.last_activity, at.token_type, a.firstname, a.lastname, a.superadmin, a.deleted_at 
                         from admins_tokens as at 
                         join admins as a 
                         on at.admin_uuid = a.uuid 
                         where at.id = ?";
            $tokenRow = $this->db->query($tokenSql, [$tokenId])->getRow();

            if ($tokenRow):

                /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
                if ($tokenRow->deleted_at !== null):
                    return ['result' => false, 'message' => lang('backend/logs.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
                endif;

                /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
                if ((int) $tokenRow->superadmin === 1):
                    return ['result'  => false, 'message' => lang('backend/logs.messages.protectedAdmin')];
                endif;
                
                /* 2. Aggiorno il log registrando la forzatura (banned) */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;

                /* 3. Elimino fisicamente il token */
                $sqlDelete = "delete from admins_tokens where id = ?";
                $this->db->query($sqlDelete, [$tokenRow->id]);

                /* 4. Log dell'azione per l'audit di sistema (opzionale ma consigliato) */
                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN', 'logs', lang('backend/logs.audits.deleteToken'), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/logs.messages.deleteTokenSuccess'), esc($tokenRow->firstname), esc($tokenRow->lastname))];
                
            endif;

            return ['result' => false, 'message' => lang('backend/logs.messages.deleteTokenError')];

        } catch (\Throwable $e) {
            
            /* Tracciamento dell'errore tecnico */
            log_message('error', 'Errore ban sessione - ' . $e);
            return ['result' => false, 'message' => lang('backend/logs.messages.deleteTokenError')];
            
        }
    }
}