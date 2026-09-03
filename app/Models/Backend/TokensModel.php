<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello di gestione e persistenza delle anagrafiche e dei privilegi degli amministratori.
 *
 * Questa classe estende le funzionalità base del backend model per governare il ciclo di vita completo
 * (CRUD) degli utenti amministrativi. Gestisce la mappatura dei campi autorizzati per ogni singola
 * operazione, isola i criteri di ricerca e ordinamento per le visualizzazioni tabellari, orchestra
 * le sotto-query per il recupero degli asset polimorfi (immagini e documenti) e centralizza le query
 * native per l'estrazione dei record al netto delle eccezioni di sicurezza.
 */
class TokensModel extends BackendModel
{
    /**
     * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
     *
     * @var string|null
     */
    protected ?string $module = 'admins_tokens';

    /**
     * Colonna di ordinamento predefinita utilizzata nelle query di estrazione se non specificata.
     *
     * @var string|null
     */
    protected ?string $defaultColumn = 'token_create';

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
    protected array $allowedOrderColumns = ['email', 'token_create', 'token_expire', 'token_type']; 

    protected array $delAllowedFields = ['id', 'uuid'];

    /**
     * Elenco dei campi su cui è consentita l'applicazione dei filtri di ricerca testuale nella vista globale.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['email', 'token_type']; 

    protected array $showAllSearchAllowedDates = ['token_create'];

    protected ?string $getDataQuery = "select at.*, a.uuid, a.firstname, a.lastname, a.email from admins_tokens as at join admins as a on a.uuid = at.admin_uuid where 1 = 1";

    protected ?string $getNumRowsQuery = "select count(*) as count from admins_tokens as at join admins as a on a.uuid = at.admin_uuid where 1 = 1";

    protected ?string $getUUIDQuery = "select at.id, a.uuid, a.firstname, a.lastname, a.deleted_at, a.superadmin from admins_tokens as at join admins as a on a.uuid = at.admin_uuid where at.id = ?";

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

    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.email' => [
                'label' => lang('backend/tokens.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.token_type' => [
                'label' => lang('backend/tokens.labels.token_type'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
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

            /* Query per eliminare il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$posts['uuid'], $posts['id']]);

            if($this->db->affectedRows() > 0):

                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN', 'tokens', sprintf('Delete token %s %s', esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/tokens.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/tokens.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        }
    }
}