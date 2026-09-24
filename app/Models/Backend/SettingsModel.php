<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/*
 * Modello principale per la gestione delle Impostazioni globali del sistema (Settings).
 * 
 * Centralizza il recupero, la validazione e il salvataggio massivo delle configurazioni. 
 * Implementa un livello di Cache in memoria per evitare query multiple al database 
 * durante lo stesso caricamento di pagina, e garantisce che i dati inviati dai moduli 
 * rispettino regole rigorose prima di sovrascrivere le impostazioni.
 */
class SettingsModel extends BackendModel
{
    /*
     * @var array Whitelist dei campi consentiti per le impostazioni Generali (lingua, data, fuso orario).
     */
    private array $allowedGeneralFields = [
        'timezone',
        'language',
        'dateFormat'
    ];

    /*
     * @var array Whitelist dei campi consentiti per le impostazioni di Autenticazione (sicurezza e sessioni).
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

    /*
     * @var array Whitelist dei campi consentiti per le impostazioni di Upload (dimensionamento, policy immagini).
     */
    private array $allowedUploadFields = [
        'renameImages',
        'overwriteImages',
        'resizeMediumX',
        'resizeMediumY',
        'resizeSmallX',
        'resizeSmallY',
        'maxFileSize',
        'maxImageX', 
        'maxImageY', 
        'allowedExtensions'
    ];

    /*
     * @var array Whitelist dei campi consentiti per le impostazioni E-mail (configurazione SMTP).
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

    /*
     * @var array Cache in memoria. Conserva i dati letti dal database per ogni sezione (namespace). 
     * Se viene richiesto più volte lo stesso gruppo di impostazioni, il sistema lo legge da qui senza interrogare di nuovo il DB.
     */
    protected array $settingsCache = [];

    /*
     * @var array Elenco di chiavi (impostazioni) critiche che, se modificate, richiedono 
     * un ricaricamento forzato o particolare attenzione da parte del frontend (es. cambio lingua).
     */
    protected array $requiresReloadFields = [
        'language',
        'timezone'
    ];

    /*
     * Metodo di inizializzazione nativo di CodeIgniter.
     * 
     * Prepara il modello caricando le dipendenze essenziali ereditate dal BackendModel padre.
     */
	protected function initModel(): void 
	{
		parent::initModel();
	}

    /*
     * Fornisce le regole di validazione per i parametri di Autenticazione.
     * 
     * Verifica che i tempi di sessione, limiti di tentativi e parametri 2FA inviati 
     * dall'amministratore siano numeri, mail valide o booleani sicuri.
     *
     * @param array $posts I dati inviati dal form (opzionali per permettere controlli condizionali in futuro)
     * @return array Regole native di CI4
     */
    public function authSettingsValidateRules(array $posts = []): array
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
                'rules' => ['required', 'trim', 'max_length[255]', 'regex_match[/^[a-zA-Z0-9\s\-_.]+$/]'],
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
     * Fornisce le regole di validazione per i parametri di Upload.
     * 
     * Verifica, ad esempio, che i limiti di peso siano numeri validi, o che le estensioni 
     * consentite siano stringhe alfanumeriche (senza caratteri strani).
     *
     * @param array $posts I dati inviati dal form
     * @return array Regole native di CI4
     */
    public function uploadSettingsValidateRules(array $posts = []): array
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
     * Fornisce le regole di validazione per i parametri E-mail (SMTP).
     * 
     * Contiene una logica dinamica: se l'operatore seleziona "smtp" come protocollo, 
     * i parametri di connessione (host, porta, tipo di auth) diventano improvvisamente obbligatori ('required').
     *
     * @param array $posts I dati inviati dal form (necessari per rilevare il tipo di protocollo scelto)
     * @return array Regole native di CI4
     */
    public function emailSettingsValidateRules(array $posts = []): array
    {
        /* Verifichiamo se il protocollo inviato dal form è smtp */
        $isSmtp = (isset($posts['protocol']) && $posts['protocol'] === 'smtp');

        return [
            'fromEmail' => [
                'label' => lang('backend/settings.labels.fromEmail'),
                'rules' => ['required', 'valid_email', 'max_length[255]'],
            ],
            'fromName' => [
                'label' => lang('backend/settings.labels.fromName'),
                'rules' => ['required', 'trim', 'max_length[255]', 'regex_match[/^[a-zA-Z0-9\s\-_.]+$/]'],
            ],
            'recipients' => [
                'label' => lang('backend/settings.labels.recipients'),
                'rules' => ['permit_empty', 'valid_emails', 'max_length[500]'],
            ],
            'protocol' => [
                'label' => lang('backend/settings.labels.protocol'),
                'rules' => ['required', 'in_list[smtp,mail,sendemail]'],
            ],
            'SMTPHost' => [
                'label' => lang('backend/settings.labels.SMTPHost'),
                /* Se è smtp forziamo required, altrimenti permit_empty */
                'rules' => [$isSmtp ? 'required' : 'permit_empty', 'regex_match[/^[a-zA-Z0-9.-]+$/]', 'max_length[255]'],
            ],
            'SMTPPort' => [
                'label' => lang('backend/settings.labels.SMTPPort'),
                'rules' => [$isSmtp ? 'required' : 'permit_empty', 'is_natural_no_zero', 'less_than_equal_to[65535]'],
            ],
            'SMTPCrypto' => [
                'label' => lang('backend/settings.labels.SMTPCrypto'),
                'rules' => [$isSmtp ? 'required' : 'permit_empty', 'in_list[none,tls,ssl]'],
            ],
            'SMTPUser' => [
                'label' => lang('backend/settings.labels.SMTPUser'),
                'rules' => ['permit_empty', 'max_length[255]', 'regex_match[/^[a-zA-Z0-9@.\-_]+$/]'],
            ],
            'SMTPPass' => [
                'label' => lang('backend/settings.labels.SMTPPass'),
                'rules' => ['permit_empty', 'max_length[255]', 'regex_match[/^[^<>]+$/]'],
            ],
            'SMTPAuthMethod' => [
                'label' => lang('backend/settings.labels.SMTPAuthMethod'),
                'rules' => [$isSmtp ? 'required' : 'permit_empty', 'in_list[LOGIN,PLAIN]'],
            ],
            'mailType' => [
                'label' => lang('backend/settings.labels.mailType'),
                'rules' => ['required', 'in_list[html,text]'],
            ],
            'charset' => [
                'label' => lang('backend/settings.labels.charset'),
                'rules' => ['required', 'max_length[30]', 'regex_match[/^[a-zA-Z0-9\-]+$/]'],
            ],
            'priority' => [
                'label' => lang('backend/settings.labels.priority'),
                'rules' => ['required', 'in_list[1,3,5]'],
            ],
        ];
    }

    /*
     * Fornisce le regole di validazione per le impostazioni Generali.
     * 
     * Controlla che le stringhe inviate (fuso orario, formato data, lingua) corrispondano 
     * esattamente a quelle previste dalle liste interne di CodeIgniter o del progetto.
     *
     * @param array $posts I dati inviati dal form
     * @return array Regole native di CI4
     */
    public function generalSettingsValidateRules(array $posts = []): array
    {
        return [
            'timezone' => [
                'label' => lang('backend/settings.labels.timezone'),
                'rules' => ['required', 'timezone'],
            ],
            'language' => [
                'label' => lang('backend/settings.labels.language'),
                'rules' => ['required', 'in_list[it,en,es,fr,de,zh]'],
            ],
            'dateFormat' => [
                'label' => lang('backend/settings.labels.dateFormat'),
                'rules' => ['required', 'in_list[d MMMM yyyy HH:mm:ss,dd/MM/yyyy HH:mm,MM/dd/yyyy h:mm a,yyyy-MM-dd HH:mm:ss]'],
            ],
        ];
    }

    /*
     * Recupera e fonde le impostazioni lette dal database con i valori di default.
     * 
     * Il cuore di questo modello. Controlla prima se il gruppo di chiavi è già in memoria (cache). 
     * Se non c'è, fa una singola query al DB e salva i dati. Successivamente, carica la classe di 
     * configurazione nativa (`Config\NomeSezione`) e sovrascrive i suoi valori di default 
     * con quelli personalizzati trovati nel DB.
     *
     * @param string $namespace Il percorso/nome della classe (es. 'Backend\Auth')
     * @param array|null $keys (Opzionale) Permette di filtrare e restituire solo alcune chiavi specifiche dell'array risultante
     * @return array Array associativo con tutte le configurazioni finali pronte all'uso
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

    /*
     * Controlla fisicamente sul database se esiste almeno un salvataggio per un dato namespace.
     * 
     * Viene usato prima di provare a cancellare o aggiornare dati per capire 
     * se si tratta del primo salvataggio in assoluto o di una modifica.
     *
     * @param string $namespace Il nome della sezione (es. 'Backend\General')
     * @return bool True se c'è almeno un record nel DB, False altrimenti
     */
    public function hasDatabaseSettings(string $namespace): bool
    {
        $sql = "SELECT COUNT(*) as total FROM `settings` WHERE `class` = ?";
        $query = $this->db->query($sql, [$namespace]);
        $row = $query->getRowArray();

        return isset($row['total']) && (int) $row['total'] > 0;
    }

    /*
     * Salva (o aggiorna) massivamente le impostazioni inviate dall'interfaccia web.
     * 
     * Il metodo prende i dati, li filtra in base alla Whitelist della sezione (scartando input pericolosi). 
     * Esegue un controllo rapido per assicurarsi che i dati siano davvero cambiati (evitando query inutili). 
     * Se ci sono novità, svuota la cache locale, prepara i dati (es. unendo le estensioni dei file in una stringa separata da '|') 
     * e lancia una velocissima "Bulk Insert" che inserisce i dati o, in caso esistano già, li aggiorna ("on duplicate key update"). 
     * Alla fine, registra l'operazione nei log di sistema.
     *
     * @param string $namespace Il nome della sezione (es. 'Backend\Upload')
     * @param array $posts I dati inviati dal modulo web
     * @return array|null Esito (result) e messaggio da visualizzare
     */
    public function saveSettings(string $namespace, array $posts): ?array
    {
        /* 1. Recuperiamo la lista dei campi consentiti in base al namespace */
        $section = str_replace('Backend\\', '', $namespace);
        $propertyName = 'allowed' . $section . 'Fields';
        $allowedFields = isset($this->{$propertyName}) ? $this->{$propertyName} : [];

        /* 2. Filtriamo immediatamente l'input lasciando solo i campi autorizzati */
        $posts = $this->checkAllowedFields($posts, $allowedFields);

        /* 3. Controllo di sbarramento e rilevazione campi critici */
        if ($this->hasDatabaseSettings($namespace)) :

            $changedKeys = $this->getChangedKeys($namespace, $posts);

            if (empty($changedKeys)) :
                return ['result' => false, 'message' => lang('backend/settings.messages.noDataChanged')];
            endif;

        endif;

        /* 4. Svuota la cache locale */
        if (isset($this->settingsCache[$namespace])) :
            unset($this->settingsCache[$namespace]);
        endif;

        if (isset($posts['allowedExtensions']) && is_array($posts['allowedExtensions'])) :
            $posts['allowedExtensions'] = implode('|', $posts['allowedExtensions']);
        endif;

        /* 5. Costruzione della scrittura massiva */
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
        log_admin_activity('SAVE_SETTINGS', 'settings', lang('backend/settings.audits.saveSettings'), $currentAdmin);

        return ['result' => true, 'message' => lang('backend/settings.messages.saveSuccess')];
    }

    /*
     * Confronta i dati in arrivo con quelli salvati e restituisce la lista esatta delle chiavi modificate.
     * 
     * Estrae le impostazioni attuali (compresi i default uniti col DB), prende i nuovi input 
     * e li confronta uno a uno. Svolge anche un lavoro di "normalizzazione" rapida: se una chiave 
     * è un array (es. la selezione multipla delle estensioni), la ordina e la trasforma in stringa prima 
     * di fare il paragone per evitare finti rilevamenti dovuti solo all'ordine disordinato degli elementi.
     *
     * @param string $namespace Il nome della sezione da analizzare
     * @param array $posts I nuovi dati proposti dal form
     * @return array Elenco delle chiavi che hanno subìto una reale modifica
     */
    public function getChangedKeys(string $namespace, array $posts): array
    {
        $current = $this->getSettings($namespace);
        $changed = [];

        foreach ($posts as $key => $value) :
            if ( ! array_key_exists($key, $current)) :
                continue;
            endif;

            /* Normalizzazione immediata */
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
                $changed[] = $key;
            endif;
        endforeach;

        return $changed;
    }

    /*
     * Ripristina un'intera sezione ai suoi valori di default ("Ripristina predefiniti").
     * 
     * Esegue un controllo rapido per capire se ci sono personalizzazioni nel DB. Se ci sono, 
     * cancella fisicamente tutti i record legati a quel namespace, svuota la cache locale, e 
     * scrive un evento nell'Audit log per mantenere traccia dell'operazione.
     *
     * @param string $namespace Il nome della sezione da ripulire
     * @return bool True se l'eliminazione è avvenuta con successo, False se non c'era nulla da eliminare
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
        log_admin_activity('DELETE_SETTINGS', 'settings', lang('backend/settings.audits.deleteSettings'), $currentAdmin);

        return true;
    }

    /*
     * Rilevatore rapido di cambiamenti (True/False).
     * 
     * Simile a `getChangedKeys()`, ma ottimizzato per le performance. Al primo campo che 
     * risulta diverso rispetto al database, interrompe il ciclo e risponde "True". 
     * Molto utile per decidere velocemente se fermare o meno l'azione di salvataggio.
     *
     * @param string $namespace Il nome della sezione da analizzare
     * @param array $posts I nuovi dati proposti
     * @return bool True se è stato trovato almeno un parametro modificato, False se è tutto identico
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