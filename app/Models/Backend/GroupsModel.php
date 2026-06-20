<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class GroupsModel extends BackendModel
{
	/**
	 * Identificativo testuale del modulo associato per la gestione dei permessi e delle rotte.
	 *
	 * @var string|null
	 */
	protected ?string $module = 'groups';

    /**
     * Elenco dei campi anagrafici e relazionali consentiti durante la fase di inserimento di un nuovo gruppo.
     *
     * @var array
     */
    protected array $addAllowedFields = ['name', 'description', 'permissions'];

    /**
     * Elenco dei campi consentiti per la persistenza dei dati durante la fase di aggiornamento di un gruppo esistente.
     *
     * @var array
     */
    protected array $editAllowedFields = ['id', 'name', 'description', 'permissions'];

    /**
     * Campi di input autorizzati per l'identificazione e l'esecuzione della procedura di cancellazione.
     *
     * @var array
     */
    protected array $delAllowedFields = ['id'];

    /**
     * Stringa SQL per l'estrazione massiva dei gruppi.
     *
     * @var string|null
     */
    protected ?string $getDataQuery = "select id, name, description, created_at from admins_groups order by name asc";

    /**
     * Stringa SQL per il recupero puntuale dei dettagli di un gruppo.
     *
     * @var string|null
     */
    protected ?string $getUUIDQuery = "";

    /**
	 * Inizializza il modello eseguendo le configurazioni di base ereditate dalla classe madre.
	 *
	 * Sincronizza lo stato del modello impostando le dipendenze native e i driver di connessione
	 * necessari al funzionamento del modulo gruppi.
	 *
	 * @return void
	 */
	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
	 * Stabilisce i criteri di validazione per la registrazione iniziale di un nuovo gruppo.
	 *
	 * Blinda i moduli di inserimento verificando l'univocità del nome del gruppo nel database.
	 *
	 * @return array Mappa di validazione per la creazione delle entità.
	 */
	public function addValidationRules(): array
	{
	    return [
	        'name' => [
	            'label' => lang('backend/groups.labels.name'),
	            'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
	        ],
	        'description' => [
	            'label' => lang('backend/groups.labels.description'),
	            'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
	        ],
	    ];
	}

	/**
     * Configura i vincoli di convalida per l'aggiornamento dei gruppi esistenti.
     *
     * Riceve i dati correnti per isolare le regole di univocità (is_unique) condizionate tramite l'id,
     * blinda il formato dell'identificativo e controlla la formattazione dei dati modificati.
     *
     * @param array $posts Dataset dei parametri inviati dal modulo di modifica.
     * @return array Set di regole contestuali basate sullo stato dell'entità corrente.
     */
    public function editValidationRules(array $posts): array
    {
        return [
            'id' => [
                'label' => lang('backend/groups.labels.id'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'name' => [
                'label' => lang('backend/groups.labels.name'),
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'description' => [
                'label' => lang('backend/groups.labels.description'),
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
        ];
    }

    /**
     * Valida i parametri per l'invocazione della procedura di cancellazione sicura.
     *
     * Assicura che l'id fornito per l'eliminazione dell'amministratore sia presente.
     *
     * @return array Criteri di validazione per la revoca e rimozione del record.
     */
    public function delValidationRules(): array
    {
        return [
            'id' => [
                'label' => lang('backend/groups.labels.id'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
        ];
    }

    /**
     * Estrae tutti i gruppi presenti nel database per il primo livello dell'accordion.
     *
     * @return array Elenco dei gruppi trovati.
     */
    public function getGroups(): array
    {
        try 
        {
            $query = $this->db->query($this->getDataQuery);
            return $query->getResult();
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero gruppi: ' . $e);
            return [];
        }
    }

    /**
     * Estrae i codici dei permessi associati a un determinato gruppo.
     *
     * @param int $groupId ID del gruppo.
     * @return array Array piatto dei codici permesso (es. ['users_index', 'users_create']).
     */
    public function getGroup(int $groupId): array
    {
        try 
        {
            $sql = "select permission from admins_group_permissions where group_id = ?";
            $query = $this->db->query($sql, [$groupId]);
            
            $result = $query->getResultArray();
            return array_column($result, 'permission');
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero permessi gruppo: ' . $e);
            return [];
        }
    }

    /**
     * Recupera i dettagli di un singolo gruppo tramite il suo ID.
     *
     * @param int $id ID del gruppo.
     * @return object|null I dati del gruppo o null se non trovato.
     */
    public function getGroupById(int $id): ?object
    {
        try 
        {
            $sql = "select id, name, description from admins_groups where id = ? limit 1";
            $query = $this->db->query($sql, [$id]);
            
            return $query->getRow() ?: null;
        } 
        catch (\Throwable $e) 
        {
            log_message('error', 'Errore recupero dettagli gruppo: ' . $e);
            return null;
        }
    }

    /* Aggiunge un gruppo */
    public function add(array $posts): array
    {

    }

    /* Aggiorna un gruppo */
    public function edit(array $posts): array
    {

    }

    /* Elimina un gruppo */
    public function del(array $posts): array
    {

    }
}