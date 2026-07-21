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
class AuditsModel extends BackendModel
{
    /**
     * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
     *
     * @var string|null
     */
    protected ?string $module = 'audits';

    protected ?string $entity = 'audits';

    /**
     * Colonna di ordinamento predefinita utilizzata nelle query di estrazione se non specificata.
     *
     * @var string|null
     */
    protected ?string $defaultColumn = 'created_at';

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
    protected array $allowedOrderColumns = ['username', 'action', 'section', 'details']; 

    /**
     * Elenco dei campi su cui è consentita l'applicazione dei filtri di ricerca testuale nella vista globale.
     *
     * @var array
     */
    protected array $showAllSearchAllowedFields = ['username', 'section', 'action', 'details']; 

    protected array $showAllSearchAllowedDates = ['created_at'];

    protected ?string $getDataQuery = "select id, admin_uuid, username, action, section, details, ip_address, user_agent, created_at from admins_audits where 1 = 1";

    protected ?string $getNumRowsQuery = 'select count(*) as count from admins_audits where 1 = 1';

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
            'searchFields.username' => [
                'label' => lang('backend/admins.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.action' => [
                'label' => lang('backend/admins.labels.action'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.section' => [
                'label' => lang('backend/admins.labels.section'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'], 
            ],
            'searchFields.details' => [
                'label' => lang('backend/admins.labels.details'), 
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/audits.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/audits.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i]'],
            ],
        ];
    }

    public function logActivity(string $action, string $section, string $details, ?object $identity = null): bool
    {
        $request = \Config\Services::request();

        $adminUuid = $identity ? ($identity->uuid ?: null) : null;
        $username = $identity ? ($identity->email ?: 'Ospite') : 'Ospite';
        $ipAddress = $request->getIPAddress();
        $userAgent = (string) $request->getUserAgent();
        $createdAt = date('Y-m-d H:i:s');

        /* Scriviamo la query SQL nativa utilizzando i segnaposto ? */
        $sql = "insert into `admins_audits` (`admin_uuid`, `username`, `action`, `section`, `details`, `ip_address`, `user_agent`, `created_at`) values (?, ?, ?, ?, ?, ?, ?, ?)";

        /* Eseguiamo la query passando i parametri nell'array di binding */
        return $this->db->query($sql, [$adminUuid, $username, $action, $section, $details, $ipAddress, $userAgent, $createdAt]);
    }
}