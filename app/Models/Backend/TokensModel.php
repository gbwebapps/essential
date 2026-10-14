<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato alla gestione e al monitoraggio dei token di sicurezza.
 * 
 * Estende il BackendModel per sfruttare le funzioni di paginazione, ricerca e filtraggio. 
 * Si occupa di mostrare l'elenco dei token attivi o passati (sessioni di login, cookie "ricordami", 
 * token di attivazione) e permette di revocarli forzatamente, disconnettendo gli utenti.
 */
class TokensModel extends BackendModel 
{
    /**
     * Nome della tabella principale di riferimento per questo modello.
     * 
     * @var string 
     */
    protected ?string $module = 'admins_tokens';

    /**
     * Whitelist dei parametri di paginazione accettati dal server (es. numero pagina, righe per pagina).
     * 
     * @var array 
     */
    protected array $showAllAllowedFields = ['id', 'column', 'order', 'page', 'rows', 'searchFields', 'searchDates'];

    /**
     * Elenco delle colonne su cui l'utente è autorizzato a cliccare per ordinare la tabella.
     * 
     * @var array 
     */
    protected array $allowedOrderColumns = ['id', 'email', 'token_create', 'token_expire', 'token_type', 'created_at'];

    /**
     * Whitelist dei campi HTTP POST consentiti per l'operazione di cancellazione del token.
     * 
     * @var array 
     */
    protected array $delAllowedFields = ['id', 'uuid'];

    /**
     * Whitelist delle colonne di database abilitate per la ricerca tramite testo (es. cerca per email).
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedFields = ['email', 'token_type']; 

    /**
     * Whitelist delle colonne di database abilitate per la ricerca tramite intervallo di date (Da/A).
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedDates = ['token_create'];

    /**
     * Query principale per caricare la tabella dei dati. 
     * Esegue una JOIN con la tabella `admins` per associare ogni token ai dati anagrafici del proprietario (nome, email, ecc.).
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select admins_tokens.*, admins.uuid, admins.firstname, admins.lastname, admins.email, admins.superadmin 
                                        from admins_tokens 
                                        join admins 
                                        on admins.uuid = admins_tokens.admin_uuid 
                                        where 1 = 1";
    /**
     * Query utilizzata dal motore di paginazione per contare il numero totale di record disponibili.
     * 
     * @var string 
     */
    protected ?string $getNumRowsQuery = "select count(*) as count 
                                            from admins_tokens 
                                            join admins 
                                            on admins.uuid = admins_tokens.admin_uuid 
                                            where 1 = 1";

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
                'rules' => ['permit_empty', 'trim', 'max_length[100]', 'regex_match[/^[a-zA-Z0-9@._+\-]+$/]'],
            ],
            'searchFields.token_type' => [
                'label' => lang('backend/tokens.labels.tokenType'),
                'rules' => ['permit_empty', 'in_list[activation,session,cookie]'],
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
     * Costruisce i filtri testuali usando le colonne fisiche corrette delle tabelle coinvolte.
     *
     * @param array $searchFields Valori di ricerca validati provenienti dalla lista Tokens
     * @param array &$params Parametri associati alla query preparata
     * @return string Clausole SQL da aggiungere alla query principale e alla query di conteggio
     */
    protected function buildFilters(array $searchFields, array &$params): string
    {
        $whereClause = '';
        $columns = [
            'email' => 'admins.email',
            'token_type' => 'admins_tokens.token_type'
        ];

        foreach ($searchFields as $key => $value):
            if (isset($columns[$key])):
                $whereClause .= ' and ' . $columns[$key] . ' like ?';
                $params[] = '%' . $value . '%';
            endif;
        endforeach;

        return $whereClause;
    }

    /**
     * Fornisce le regole di validazione per l'eliminazione di un token.
     * 
     * Assicura che la richiesta contenga un UUID nel formato corretto (versioni standard da 1 a 5)
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
        $transactionStarted = false;

        try 
        {
            if ( ! $this->db->transBegin()):
                throw new \RuntimeException('hardDelete: impossibile avviare la transazione.');
            endif;

            $transactionStarted = true;

            /* Recupero e blocco il token verificando contemporaneamente ID e proprietario */
            $tokenSql = "select at.id, at.admin_uuid, at.last_activity, at.token_type, a.uuid, a.firstname, a.lastname, a.deleted_at, a.superadmin
                         from admins_tokens as at
                         join admins as a
                         on a.uuid = at.admin_uuid
                         where at.id = ? and at.admin_uuid = ?
                         for update";
            $tokenRow = $this->db->query($tokenSql, [$posts['id'], $posts['uuid']])->getRow();

            if ( ! $tokenRow):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($tokenRow->deleted_at !== null):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/tokens.messages.cannotModifyDeleted')];
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $tokenRow->superadmin === 1):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result'  => false, 'message' => lang('backend/tokens.messages.protectedAdmin')];
            endif;

            /* Aggiorno il log aperto registrando la forzatura */
            if (in_array($tokenRow->token_type, ['cookie', 'session'], true)):
                $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ? and logout is null";
                $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
            endif;

            /* Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$tokenRow->admin_uuid, $tokenRow->id]);

            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false):
                $this->db->transRollback();
                $transactionStarted = false;
                return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];
            endif;

            if ( ! $this->db->transCommit()):
                throw new \RuntimeException('hardDelete: commit della transazione non riuscito.');
            endif;

            $transactionStarted = false;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('DELETE_TOKEN', 'tokens', sprintf(lang('backend/tokens.audits.deleteToken'), esc($tokenRow->firstname), esc($tokenRow->lastname)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/tokens.messages.deleteTokenSuccess'), esc($tokenRow->firstname), esc($tokenRow->lastname)), 'admin' => $tokenRow];

        } catch(\Throwable $e) {

            if ($transactionStarted):
                $this->db->transRollback();
            endif;

            log_message('error', lang('backend/tokens.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        }
    }
}
