<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class SettingsModel extends BackendModel
{
    /**
     * Elenco dei campi POST autorizzati per la configurazione General.
     * Evita tentativi di mass-assignment (assegnazione massiva).
     *
     * @var array
     */
    private array $allowedGeneralFields = [
        'timezone',
        'language',
        'dateFormat'
    ];

	/**
     * Elenco dei campi POST autorizzati per la configurazione Auth.
     * Evita tentativi di mass-assignment (assegnazione massiva).
     *
     * @var array
     */
    private array $allowedAuthFields = [
        'attempts',
        'attemptsLimit',
        'attemptsInterval',
        'twoFactor',
        'twoFactorLimit',
        'twoFactorTime',
        'twoFactorIssuer',
        'twoFactorDigits',
        'twoFactorWindow',
        'twoFactorEmailExpiry',
        'twoFactorEmailFrom',
        'sessionTime',
        'rememberMeTime',
        'activationTime',
    ];

    /**
     * Elenco dei campi POST autorizzati per la configurazione Upload.
     * Evita tentativi di mass-assignment (assegnazione massiva).
     *
     * @var array
     */
    private array $allowedUploadFields = [
        'renameImages',
        'overwriteImages',
        'cropCenter',
        'resizeMediumX',
        'resizeMediumY',
        'resizeSmallX',
        'resizeSmallY',
        'maxFileSize',
        'maxImageX', 
        'maxImageY', 
        'allowedExtensions'
    ];

    /**
     * Elenco dei campi POST autorizzati per la configurazione email.
     * Evita tentativi di mass-assignment (assegnazione massiva).
     *
     * @var array
     */
    private array $allowedEmailFields = [
        'fromEmail',
        'fromName',
        'recipients',
        'protocol',
        'SMTPHost',
        'SMTPPort',
        'SMTPCrypto',
        'SMTPUser',
        'SMTPPass',
        'SMTPAuthMethod',
        'mailType',
        'charset',
        'priority',
    ];

    /**
     * Cache in-memory per memorizzare i gruppi già estratti durante la singola esecuzione.
     *
     * @var array
     */
    protected array $settingsCache = [];

	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
     * Ritorna le regole di validazione specifiche per il form Auth Settings.
     *
     * @return array
     */
    public function authSettingsValidateRules(): array
    {
        return [
            'attempts' => [
                'label' => lang('backend/settings.labels.attempts'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'attemptsLimit' => [
                'label' => lang('backend/settings.labels.attemptsLimit'),
                'rules' => ['required', 'is_natural_no_zero', 'less_than_equal_to[20]'],
            ],
            'attemptsInterval' => [
                'label' => lang('backend/settings.labels.attemptsInterval'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[10]'],
            ],
            'twoFactor' => [
                'label' => lang('backend/settings.labels.twoFactor'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'twoFactorLimit' => [
                'label' => lang('backend/settings.labels.twoFactorLimit'),
                'rules' => ['required', 'is_natural_no_zero', 'less_than_equal_to[10]'],
            ],
            'twoFactorTime' => [
                'label' => lang('backend/settings.labels.twoFactorTime'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[10]'],
            ],
            'twoFactorIssuer' => [
                'label' => lang('backend/settings.labels.twoFactorIssuer'),
                'rules' => ['required', 'string', 'max_length[255]'],
            ],
            'twoFactorDigits' => [
                'label' => lang('backend/settings.labels.twoFactorDigits'),
                'rules' => ['required', 'in_list[6,8]'],
            ],
            'twoFactorWindow' => [
                'label' => lang('backend/settings.labels.twoFactorWindow'),
                'rules' => ['required', 'integer', 'greater_than_equal_to[0]', 'less_than_equal_to[2]'],
            ],
            'twoFactorEmailExpiry' => [
                'label' => lang('backend/settings.labels.twoFactorEmailExpiry'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[10]'],
            ],
            'twoFactorEmailFrom' => [
                'label' => lang('backend/settings.labels.twoFactorEmailFrom'),
                'rules' => ['required', 'valid_email', 'max_length[255]'],
            ],
            'sessionTime' => [
                'label' => lang('backend/settings.labels.sessionTime'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[60]'],
            ],
            'rememberMeTime' => [
                'label' => lang('backend/settings.labels.rememberMeTime'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[3600]'],
            ],
            'activationTime' => [
                'label' => lang('backend/settings.labels.activationTime'),
                'rules' => ['required', 'is_natural_no_zero', 'greater_than_equal_to[3600]'],
            ],
        ];
    }

    /*
     * Ritorna le regole di validazione specifiche per il form Upload Settings.
     *
     * @return array
     */
    public function uploadSettingsValidateRules(): array
    {
        return [
            'renameImages' => [
                'label' => lang('backend/settings.labels.renameImages'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'overwriteImages' => [
                'label' => lang('backend/settings.labels.overwriteImages'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'cropCenter' => [
                'label' => lang('backend/settings.labels.cropCenter'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'resizeMediumX' => [
                'label' => lang('backend/settings.labels.resizeMediumX'),
                'rules' => ['required', 'is_natural'],
            ],
            'resizeMediumY' => [
                'label' => lang('backend/settings.labels.resizeMediumY'),
                'rules' => ['required', 'is_natural'],
            ],
            'resizeSmallX' => [
                'label' => lang('backend/settings.labels.resizeSmallX'),
                'rules' => ['required', 'is_natural'],
            ],
            'resizeSmallY' => [
                'label' => lang('backend/settings.labels.resizeSmallY'),
                'rules' => ['required', 'is_natural'],
            ],
            'maxFileSize' => [
                'label' => lang('backend/settings.labels.maxFileSize'),
                'rules' => ['required', 'is_natural_no_zero'],
            ],
            'maxImageX' => [
                'label' => lang('backend/settings.labels.maxImageX'),
                'rules' => ['required', 'is_natural'],
            ],
            'maxImageY' => [
                'label' => lang('backend/settings.labels.maxImageY'),
                'rules' => ['required', 'is_natural'],
            ],
            'allowedExtensions' => [
                'label' => lang('backend/settings.labels.allowedExtensions'),
                'rules' => ['required'],
            ],
            'allowedExtensions.*' => [
                'label' => lang('backend/settings.labels.allowedExtensions'),
                'rules' => ['alpha', 'max_length[10]'],
            ],
        ];
    }

    /*
     * Ritorna le regole di validazione specifiche per il form Email Settings.
     *
     * @return array
     */
    public function emailSettingsValidateRules(): array
    {
        return [
            'fromEmail' => [
                'label' => lang('backend/settings.labels.fromEmail'),
                'rules' => ['required', 'valid_email', 'max_length[255]'],
            ],
            'fromName' => [
                'label' => lang('backend/settings.labels.fromName'),
                'rules' => ['required', 'max_length[255]'],
            ],
            'recipients' => [
                'label' => lang('backend/settings.labels.recipients'),
                'rules' => ['permit_empty', 'valid_emails', 'max_length[500]'],
            ],
            'protocol' => [
                'label' => lang('backend/settings.labels.protocol'),
                'rules' => ['required', 'in_list[smtp,mail,sendmail]'],
            ],
            'SMTPHost' => [
                'label' => lang('backend/settings.labels.SMTPHost'),
                'rules' => ['required_if_field[protocol,smtp]', 'permit_empty', 'regex_match[/^[a-zA-Z0-9.-]+$/]', 'max_length[255]'],
            ],
            'SMTPPort' => [
                'label' => lang('backend/settings.labels.SMTPPort'),
                'rules' => ['required_if_field[protocol,smtp]', 'permit_empty', 'is_natural_no_zero', 'less_than_equal_to[65535]'],
            ],
            'SMTPCrypto' => [
                'label' => lang('backend/settings.labels.SMTPCrypto'),
                'rules' => ['required_if_field[protocol,smtp]', 'permit_empty', 'in_list[none,tls,ssl]'],
            ],
            'SMTPUser' => [
                'label' => lang('backend/settings.labels.SMTPUser'),
                'rules' => ['permit_empty', 'max_length[255]'],
            ],
            'SMTPPass' => [
                'label' => lang('backend/settings.labels.SMTPPass'),
                'rules' => ['permit_empty', 'max_length[255]'],
            ],
            'SMTPAuthMethod' => [
                'label' => lang('backend/settings.labels.SMTPAuthMethod'),
                'rules' => ['required_if_field[protocol,smtp]', 'permit_empty', 'in_list[LOGIN,PLAIN]'],
            ],
            'mailType' => [
                'label' => lang('backend/settings.labels.mailType'),
                'rules' => ['required', 'in_list[html,text]'],
            ],
            'charset' => [
                'label' => lang('backend/settings.labels.charset'),
                'rules' => ['required', 'max_length[30]'],
            ],
            'priority' => [
                'label' => lang('backend/settings.labels.priority'),
                'rules' => ['required', 'in_list[1,3,5]'],
            ],
        ];
    }

    public function generalSettingsValidateRules(): array
    {
        return [
            'timezone' => [
                'label' => lang('backend/settings.labels.timezone'),
                'rules' => ['required', 'timezone'],
            ],
            'language' => [
                'label' => lang('backend/settings.labels.language'),
                'rules' => ['required', 'in_list[it,en-US,en-GB,es,fr,de,zh]'],
            ],
            'dateFormat' => [
                'label' => lang('backend/settings.labels.dateFormat'),
                'rules' => ['required', 'in_list[d MMMM yyyy HH:mm:ss,dd/MM/yyyy HH:mm,MM/dd/yyyy h:mm a,yyyy-MM-dd HH:mm:ss]'],
            ],
        ];
    }

    /**
     * Recupera le impostazioni combinando il Database con i valori di default dei file Config.
     * Sfrutta una variabile interna come cache al volo per la singola richiesta HTTP.
     *
     * @param string     $namespace Es. 'Backend\Auth'
     * @param array|null $keys      Es. ['attemptsLimit'] o null per tutto il gruppo
     * @return array
     */
    public function getSettings(string $namespace, ?array $keys = null): array
    {
        /* Se il gruppo non è ancora presente nella nostra cache in-memory, lo estraiamo dal DB */
        if ( ! isset($this->settingsCache[$namespace])) :
            
            $sql = "SELECT `key`, `value` FROM `settings` WHERE `class` = ?";
            $query = $this->db->query($sql, [$namespace]);
            $rows = $query->getResultArray();

            $dbSettings = [];
            foreach ($rows as $row) :
                $dbSettings[$row['key']] = $row['value'];
            endforeach;

            /* Salviamo il blocco intero nella variabile di classe */
            $this->settingsCache[$namespace] = $dbSettings;

        endif;

        /* Recuperiamo i valori memorizzati nella nostra variabile del modello */
        $cachedData = $this->settingsCache[$namespace];

        /* Carichiamo i valori nativi di fallback presenti nel file Config di CodeIgniter */
        $configClass = '\\Config\\' . $namespace;
        $defaultSettings = [];

        if (class_exists($configClass)) :
            $configInstance = new $configClass();
            $defaultSettings = get_object_vars($configInstance);
        endif;

        /* Uniamo i default con i dati in-memory (il DB vince sui default) */
        $finalSettings = array_merge($defaultSettings, $cachedData);

        /* Se sono state richieste chiavi specifiche, filtriamo l'array */
        if ($keys !== null) :
            $finalSettings = array_intersect_key($finalSettings, array_flip($keys));
        endif;

        return $finalSettings;
    }

    /**
     * Verifica se esistono già record salvati nel database per il namespace specificato.
     *
     * @param string $namespace Es. 'Backend\Auth'
     * @return bool
     */
    public function hasDatabaseSettings(string $namespace): bool
    {
        $sql = "SELECT COUNT(*) as total FROM `settings` WHERE `class` = ?";
        $query = $this->db->query($sql, [$namespace]);
        $row = $query->getRowArray();

        return isset($row['total']) && (int) $row['total'] > 0;
    }

    /**
     * Salva o aggiorna le impostazioni nel database e svuota la cache in-memory del gruppo.
     */
    public function saveSettings(string $namespace, array $posts): ?array
    {
        /* 1. Recuperiamo la lista dei campi consentiti in base al namespace */
        $section = str_replace('Backend\\', '', $namespace);
        $propertyName = 'allowed' . $section . 'Fields';
        $allowedFields = isset($this->{$propertyName}) ? $this->{$propertyName} : [];

        /* 2. Filtriamo immediatamente l'input lasciando solo i campi autorizzati */
        $posts = $this->checkAllowedFields($posts, $allowedFields);

        /* 
           3. Controllo di sbarramento: 
              Se esistono già record a DB, controlliamo se è cambiato qualcosa.
              Se NON esistono record a DB, saltiamo il controllo ed eseguiamo l'insert.
        */
        if ($this->hasDatabaseSettings($namespace)) :
            if ( ! $this->hasSettingsChanged($namespace, $posts)) :
                return ['result' => false, 'message' => lang('backend/settings.messages.noDataChanged')];
            endif;
        endif;

        /* 4. Svuota la cache locale poiché i dati stanno per cambiare */
        if (isset($this->settingsCache[$namespace])) :
            unset($this->settingsCache[$namespace]);
        endif;

        /* Gestione centralizzata: se allowedExtensions è un array, lo convertiamo in stringa */
        if (isset($posts['allowedExtensions']) && is_array($posts['allowedExtensions'])) :
            $posts['allowedExtensions'] = implode('|', $posts['allowedExtensions']);
        endif;

        /* 5. Costruzione della scrittura massiva (Single Bulk Insert/Update Query) */
        $valuesQueries = [];
        $params = [];

        foreach ($posts as $key => $value) :
            $valuesQueries[] = '(?, ?, ?)';
            array_push($params, $namespace, $key, $value);
        endforeach;

        $sql = "insert into `settings` (`class`, `key`, `value`) 
                values " . implode(', ', $valuesQueries) . " 
                on duplicate key update `value` = values(`value`), `updated_at` = CURRENT_TIMESTAMP";

        $this->db->query($sql, $params);

        $currentAdmin = service('authorization')->currentAdmin();
        log_admin_activity('SAVE_SETTINGS', 'settings', 'Salvataggio impostazioni.', $currentAdmin);

        return ['result' => true, 'message' => lang('backend/settings.messages.saveSuccess')];
    }

    /**
     * Rimuove tutti i settaggi del rispettivo namespace e ne svuota la cache in-memory.
     * Ritorna true se i record sono stati eliminati, false se non era presente nulla a DB.
     *
     * @param string $namespace Es. 'Backend\Auth'
     * @return bool
     */
    public function deleteSettings(string $namespace): bool
    {
        /* Verifica preliminare se ci sono effettivamente dati da cancellare */
        if ( ! $this->hasDatabaseSettings($namespace)):
            return false;
        endif;

        /* Svuota la cache locale in memoria per questo namespace */
        if (isset($this->settingsCache[$namespace])) :
            unset($this->settingsCache[$namespace]);
        endif;

        /* Esegue l'eliminazione globale del namespace */
        $sql = "delete from `settings` where `class` = ?";
        $this->db->query($sql, [$namespace]);

        $currentAdmin = service('authorization')->currentAdmin();
        log_admin_activity('DELETE_SETTINGS', 'settings', 'Eliminazione impostazioni.', $currentAdmin);

        return true;
    }

    /* 
       Verifica se i dati inviati dal form differiscono da quelli attualmente salvati.
       Utilizza la cache interna del modello per azzerare le query.
    */
    public function hasSettingsChanged(string $namespace, array $posts): bool
    {
        $current = $this->getSettings($namespace);

        foreach ($posts as $key => $value) :
            if ( ! array_key_exists($key, $current)) :
                continue;
            endif;

            /* Normalizzazione immediata: se è un array, lo ordina e lo unisce con il pipe */
            if (is_array($value)) :
                $filtered = array_filter($value);
                sort($filtered);
                $valPost = implode('|', $filtered);

                $dbArray = array_filter(explode('|', $current[$key]));
                sort($dbArray);
                $valDb = implode('|', $dbArray);
            else :
                $valPost = trim((string) $value);
                $valDb   = trim((string) $current[$key]);
            endif;

            if ($valPost !== $valDb) :
                return true;
            endif;
        endforeach;

        return false;
    }
}