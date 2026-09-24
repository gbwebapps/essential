<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato alla gestione e al monitoraggio dei token di sicurezza.
 * 
 * Estende il BackendModel per sfruttare le funzioni di paginazione, ricerca e filtraggio. 
 * Si occupa di mostrare l'elenco dei token attivi o passati (sessioni di login, cookie "ricordami", 
 * token per il reset della password) e permette di revocarli forzatamente, disconnettendo gli utenti.
 */
class TokensModel extends BackendModel
{
    /**
     * @var string Nome della tabella principale di riferimento per questo modello.
     */
    protected ?string $module = 'admins_tokens';

    /**
     * @var string Colonna utilizzata per l'ordinamento di default quando la pagina viene caricata.
     */
    protected ?string $defaultColumn = 'id';

    /**
     * @var array Whitelist dei parametri di paginazione accettati dal server (es. numero pagina, righe per pagina).
     */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    /**
     * @var array Elenco delle colonne su cui l'utente è autorizzato a cliccare per ordinare la tabella.
     */
    protected array $allowedOrderColumns = ['email', 'token_create', 'token_expire', 'token_type']; 

    /**
     * @var array Whitelist dei campi HTTP POST consentiti per l'operazione di cancellazione del token.
     */
    protected array $delAllowedFields = ['id', 'uuid'];

    /**
     * @var array Whitelist delle colonne di database abilitate per la ricerca tramite testo (es. cerca per email).
     */
    protected array $showAllSearchAllowedFields = ['email', 'token_type']; 

    /**
     * @var array Whitelist delle colonne di database abilitate per la ricerca tramite intervallo di date (Da/A).
     */
    protected array $showAllSearchAllowedDates = ['token_create'];

    /**
     * @var string Query principale per caricare la tabella dei dati. 
     * Esegue una JOIN con la tabella `admins` per associare ogni token ai dati anagrafici del proprietario (nome, email, ecc.).
     */
    protected ?string $getDataQuery = "select admins_tokens.*, admins.uuid, admins.firstname, admins.lastname, admins.email, admins.superadmin 
                                        from admins_tokens 
                                        join admins 
                                        on admins.uuid = admins_tokens.admin_uuid 
                                        where 1 = 1";
    /**
     * @var string Query utilizzata dal motore di paginazione per contare il numero totale di record disponibili.
     */
    protected ?string $getNumRowsQuery = "select count(*) as count 
                                            from admins_tokens 
                                            join admins 
                                            on admins.uuid = admins_tokens.admin_uuid 
                                            where 1 = 1";

    /**
     * @var string Query per recuperare i dettagli di un singolo token (e del suo proprietario) tramite il suo ID.
     */
    protected ?string $getUUIDQuery = "select admins_tokens.id, admins.uuid, admins.firstname, admins.lastname, admins.deleted_at, admins.superadmin 
                                        from admins_tokens 
                                        join admins 
                                        on admins.uuid = admins_tokens.admin_uuid 
                                        where admins_tokens.id = ?";

    /**
     * Metodo di inizializzazione base di CodeIgniter. 
     * Assicura che le impostazioni del modello genitore vengano caricate correttamente.
     */
    protected function initModel(): void 
    {
        parent::initModel();
    }

    /**
     * Fornisce le regole di validazione per la paginazione e l'ordinamento della tabella.
     * 
     * Controlla che la richiesta indichi correttamente quale colonna ordinare, la direzione (asc/desc), 
     * e che i parametri di pagina e riga siano numeri validi maggiori di zero.
     *
     * @return array Regole di validazione per i parametri strutturali
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
     * Fornisce le regole di validazione per i filtri di ricerca.
     * 
     * Verifica che l'input testuale inserito dall'utente (es. email o tipo di token) sia pulito 
     * e che le date inserite per filtrare la creazione dei token rispettino il formato corretto (Y-m-d H:i:s).
     *
     * @return array Regole per i filtri di ricerca
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.email' => [
                'label' => lang('backend/tokens.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.token_type' => [
                'label' => lang('backend/tokens.labels.token_type'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchDates.token_create-from' => [
                'label' => lang('backend/tokens.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.token_create-to' => [
                'label' => lang('backend/tokens.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /**
     * Fornisce le regole di validazione per l'eliminazione di un token.
     * 
     * Assicura che la richiesta contenga un UUID nel formato corretto (stringa standard UUIDv4) 
     * e che l'ID del token sia un numero intero valido, prevenendo alterazioni malevole dei parametri.
     *
     * @return array Regole di validazione per i campi id e uuid
     */
    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/tokens.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('backend/tokens.errors.uuid'), 
                    'regex_match' => lang('backend/tokens.errors.uuid') 
                ]
            ],
            'id' => [
                'label' => lang('backend/tokens.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => lang('backend/tokens.errors.id'), 
                    'is_natural_no_zero' => lang('backend/tokens.errors.id') 
                ]
            ],
        ];
    }

    /**
     * Elimina definitivamente un token dal database (disconnettendo di fatto l'utente se è una sessione).
     * 
     * Il metodo applica controlli di sicurezza rigorosi:
     * 1. Usa la Whitelist per filtrare i dati in ingresso.
     * 2. Scudi di sicurezza: blocca immediatamente l'operazione se si sta cercando di eliminare la sessione 
     *    di un utente già cestinato o di un Superadmin (che è protetto di default).
     * 3. Se il token da eliminare è legato a un login ('session' o 'cookie'), aggiorna la tabella dei log 
     *    (`admins_logs`) registrando che l'utente è stato buttato fuori ('banned').
     * 4. Cancella fisicamente il record da `admins_tokens` e salva l'azione nell'audit log.
     *
     * @param array $posts I dati inviati dal form, contenenti 'id' del token e 'uuid' dell'amministratore
     * @return array Risposta strutturata con l'esito (result) e il messaggio di sistema
     */
    public function hardDelete(array $posts): array
    {
        /* Match dei posts con i campi consentiti */
        $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

        try 
        {
            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['id']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/tokens.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/tokens.messages.protectedAdmin')];
            endif;

            /* 1. Recupero il token per leggere last_activity */
            $tokenSql = "select id, last_activity, token_type from admins_tokens where admin_uuid = ? and id = ?";
            $tokenRow = $this->db->query($tokenSql, [$posts['uuid'], $posts['id']])->getRow();

            if ($tokenRow):
                /* 2. Aggiorno il log registrando la forzatura (banned) */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;
            endif;

            /* 3. Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$posts['uuid'], $posts['id']]);

            if($this->db->affectedRows() > 0):

                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN', 'tokens', sprintf(lang('backend/tokens.audits.deleteToken'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/tokens.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/tokens.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        }
    }
}