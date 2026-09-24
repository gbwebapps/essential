<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/*
 * Modello dedicato alla gestione dell'Audit Log (lo storico delle attività di sistema).
 * 
 * Estende il BackendModel per sfruttare in automatico le funzioni di paginazione, 
 * ricerca e ordinamento della tabella. Oltre a mostrare i dati, fornisce il metodo 
 * primario (logActivity) richiamato dall'helper globale per registrare fisicamente 
 * nel database ogni azione rilevante compiuta dagli amministratori.
 */
class AuditsModel extends BackendModel
{
    /**
     * Nome della tabella principale di riferimento per le interrogazioni del modello.
     * 
     * @var string 
     */
    protected ?string $module = 'admins_audits';

    /**
     * Colonna di default utilizzata per ordinare i risultati al caricamento della pagina.
     * 
     * @var string 
     */
    protected ?string $defaultColumn = 'id';

    /**
     * Whitelist dei parametri di paginazione accettati dal server (pagina, righe, ordine, colonna).
     * 
     * @var array 
     */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    /**
     * Elenco delle colonne su cui l'operatore è autorizzato a cliccare per ordinare la tabella.
     * 
     * @var array 
     */
    protected array $allowedOrderColumns = ['username', 'action', 'section', 'details']; 

    /**
     * Whitelist dei campi di database abilitati per la ricerca tramite testo libero.
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedFields = ['username', 'section', 'action', 'details']; 

    /**
     * Whitelist delle colonne di database filtrabili tramite un intervallo temporale (Da/A).
     * 
     * @var array 
     */
    protected array $showAllSearchAllowedDates = ['created_at'];

    /**
     * Query SQL principale per caricare la tabella dei dati. 
     * Esegue una JOIN con la tabella `admins` per recuperare i dettagli anagrafici e i permessi 
     * di chi ha eseguito l'azione, mostrando così un quadro completo dell'evento.
     * 
     * @var string 
     */
    protected ?string $getDataQuery = "select admins_audits.id, admin_uuid, username, action, section, details, ip_address, user_agent, admins_audits.created_at, superadmin 
                                       from admins_audits 
                                       join admins 
                                       on admins.uuid = admins_audits.admin_uuid 
                                       where 1 = 1";

    /**
     * Query SQL essenziale per calcolare il numero totale delle attività registrate, 
     * necessaria al frontend per creare i bottoni della paginazione.
     * 
     * @var string 
     */
    protected ?string $getNumRowsQuery = 'select count(*) as count from admins_audits where 1 = 1';

    /**
     * Metodo di inizializzazione nativo di CodeIgniter. 
     * Assicura che le impostazioni di base ereditate dal BackendModel vengano caricate.
     */
    protected function initModel(): void 
    {
        parent::initModel();
    }

    /**
     * Fornisce le regole di validazione per la struttura della tabella (DataTables).
     * 
     * Controlla che la richiesta indichi correttamente la colonna, l'ordine (solo asc/desc) 
     * e che la pagina e il numero di righe siano numeri interi positivi validi.
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
     * Fornisce le regole di validazione per i filtri di ricerca degli audit.
     * 
     * Assicura che l'input di ricerca testuale (username, action, section, details) contenga 
     * caratteri puliti e sicuri (solo lettere e punteggiatura base) e che le date inserite 
     * per il filtro temporale siano espresse in un formato valido per MySQL.
     *
     * @return array Regole per i filtri di ricerca testuali e temporali
     */
    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.username' => [
                'label' => lang('backend/admins.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.action' => [
                'label' => lang('backend/admins.labels.action'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.section' => [
                'label' => lang('backend/admins.labels.section'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.details' => [
                'label' => lang('backend/admins.labels.details'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/audits.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/audits.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    /*
     * Inserisce un nuovo record nello storico delle attività del sistema (Audit).
     * 
     * Recupera automaticamente i dettagli di connessione (Indirizzo IP e browser/User Agent) 
     * interrogando il servizio Request nativo. Se l'azione viene eseguita da un utente 
     * autenticato, salva il suo UUID e la sua email; altrimenti, registra l'azione sotto la voce "Ospite".
     * Esegue un inserimento sicuro utilizzando i segnaposto (?) per proteggere da SQL Injection.
     *
     * @param string $action Breve codice dell'azione eseguita (es. 'LOGIN', 'ADD_USER')
     * @param string $section Modulo o sezione in cui è avvenuta l'azione (es. 'admins', 'settings')
     * @param string $details Messaggio descrittivo completo per l'interfaccia (es. 'L'utente ha effettuato l'accesso')
     * @param object|null $identity Oggetto contenente i dati dell'amministratore (opzionale)
     * @return bool True se l'inserimento nel database va a buon fine, false altrimenti
     */
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