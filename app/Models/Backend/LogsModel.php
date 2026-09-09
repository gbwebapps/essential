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
    protected array $allowedOrderColumns = ['email', 'login', 'logout', 'token_type']; 

    /**
     * Elenco dei campi su cui è consentita l'applicazione dei filtri di ricerca testuale nella vista globale.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['email', 'token_type']; 

    protected array $showAllSearchAllowedDates = ['login', 'logout'];

    protected ?string $getDataQuery = "select al.*, a.uuid, a.firstname, a.lastname, a.email from admins_logs as al join admins as a on a.uuid = al.admin_uuid where 1 = 1";

    protected ?string $getNumRowsQuery = "select count(*) as count from admins_logs as al join admins as a on a.uuid = al.admin_uuid where 1 = 1";

    protected ?string $getUUIDQuery = "select al.id, a.uuid, a.firstname, a.lastname, a.deleted_at, a.superadmin from admins_logs as al join admins as a on a.uuid = al.admin_uuid where al.id = ?";

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
            'searchDates.log_create-from' => [
                'label' => lang('backend/logs.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.log_create-to' => [
                'label' => lang('backend/logs.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }
}