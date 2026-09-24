<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\SettingsModel;
use App\Libraries\Backend\SettingsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Controller per la configurazione globale delle variabili e dei parametri del sistema.
 * 
 * Gestisce l'interfaccia preposta all'impostazione di parametri applicativi (generali, autenticazione, upload, email), 
 * applicando un pattern in cui i dati persistenti a livello di DB sovrascrivono i default cablati nei file di configurazione.
 */
class SettingsController extends BackendController 
{
    /**
     * Istanza del modello che esegue query SQL di base (senza l'impiego dell'ORM) per manipolare la tabella delle configurazioni.
     * @var SettingsModel 
     */
    protected SettingsModel $settingsModel;

    /**
     * Istanza della classe di supporto che genera o estrae dizionari fissi, quali elenchi dei fusi orari, delle lingue o formati ammessi.
     * @var SettingsClass 
     */
    protected SettingsClass $settingsClass;

    /**
     * Elenco che definisce i soli ambiti (namespace) consentiti dalla logica del controller, atto a respingere richieste arbitrarie di configurazione.
     * @var array 
     */
    protected array $allowedEnvs = ['general', 'auth', 'upload', 'email'];

    /**
     * Esegue le procedure di inizializzazione standard estendendo BackendController, instanziando il modello base 
     * e la classe helper per le configurazioni.
     *
     * @param RequestInterface $request L'oggetto contenente la richiesta in arrivo.
     * @param ResponseInterface $response L'oggetto per la risposta.
     * @param LoggerInterface $logger Il gestore dei file di logging locale.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'settings';

        /* Utilizzo del modello nativo privo di ORM, basato su RAW SQL */
        $this->settingsModel = model(SettingsModel::class);
        $this->settingsClass = new SettingsClass($this->settingsModel);
    }

    /**
     * Elabora la struttura HTML iniziale della pagina impostazioni, fornendo i collegamenti per il caricamento asincrono 
     * dei singoli pannelli funzionali.
     *
     * @return string HTML elaborato della pagina principale.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/settings.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-sliders"></i>';

        return $this->render('backend/settings/indexView', $this->data);
    }

    /**
     * Riceve una richiesta AJAX per l'ambiente specificato, controlla la legittimità del payload e recupera i dati attivi 
     * (identificando l'origine, DB o file base) restituendo quindi l'HTML parziale del modulo dedicato.
     *
     * @return ResponseInterface Risposta JSON che include il codice del form precompilato.
     */
    public function openSettings(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $env = $this->request->getPost('env');

            /* Protezione: Blocco immediato se l'ambiente non è rigorosamente autorizzato */
            if ( ! in_array($env, $this->allowedEnvs, true)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);

            /* Verifica l'origine dei dati per informare l'interfaccia (DB o Config File) */
            $this->data['isFromDatabase'] = $this->settingsModel->hasDatabaseSettings($namespace);

            /* Estrazione globale dell'intero gruppo */
            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace);

            /* Carica i dati specifici solo se apriamo la sezione 'general' */
            if ($env === 'general'):
                $this->data['timezones'] = $this->settingsClass->getTimezones();
                $this->data['languages'] = $this->settingsClass->getLanguages();
                $this->data['dateFormats'] = $this->settingsClass->getDateFormats();
            endif;

            return $this->jsonResponse(['result' => true, 'output' => view('backend/settings/partials/index/' . $env . 'SettingsPartial', $this->data)]);

        endif;
    }

    /**
     * Sottopone a validazione (mediante regole dinamiche definite sul modello) l'insieme di valori per uno specifico ambiente di sistema,
     * per poi delegarne l'archiviazione. Gestisce automaticamente la rigenerazione in tempo reale di frammenti d'interfaccia 
     * se i cambiamenti (es. localizzazione) impattano aree critiche dell'applicativo.
     *
     * @return ResponseInterface Risposta JSON includente l'esito dell'operazione e i potenziali snippet per l'aggiornamento del DOM.
     */
    public function saveSettings(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $env = $posts['env'] ?? '';

            if ( ! in_array($env, $this->allowedEnvs, true)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $method = $env . 'SettingsValidateRules';
            $rules = $this->settingsModel->{$method}($posts);

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);

            /* 1. Salvataggio via Model */
            $saveResult = $this->settingsModel->saveSettings($namespace, $posts);

            if ($saveResult !== null && $saveResult['result'] === false) :
                return $this->jsonResponse(['result' => false, 'message' => $saveResult['message']]);
            endif;

            /* 3. Flusso standard senza reload */
            $this->data['isFromDatabase'] = $this->settingsModel->hasDatabaseSettings($namespace);
            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace);

            $json = [];

            if ($env === 'general'):
                
                /* Le traduzioni nel controller useranno automaticamente la lingua corretta */
                $this->data['timezones'] = $this->settingsClass->getTimezones();
                $this->data['languages'] = $this->settingsClass->getLanguages();
                $this->data['dateFormats'] = $this->settingsClass->getDateFormats();

                $this->data['action'] = 'index';
                $this->data['title'] = lang('backend/settings.titles.index');
                $this->data['icon'] = '<i class="fa-solid fa-sliders"></i>';

                /* Le viste eseguiranno i loro lang() usando il Locale appena intercettato */
                $json['fragments'] = [
                    '#navbar-top-view' => view('backend/template/navbarTopView', $this->data),
                    '#section-view' => view('backend/template/sectionView', $this->data),
                    '#links-bar-view' => view('backend/template/linksBarView', $this->data), 
                    '#navbar-bottom-view' => view('backend/template/navbarBottomView', $this->data), 
                    '#lang-definitions-view' => view('backend/template/langDefinitionsView', $this->data), 
                    '#modal-alert-view' => view('backend/template/alertView', $this->data), 
                    '#scroll-up-text' => lang('backend/global.buttons.backToTop')
                ];

                /* Cicliamo gli ambienti per aggiornare solo i testi dei titoli */
                foreach($this->allowedEnvs as $v):
                    $json['fragments']['#lang_' . $v] = lang('backend/settings.panels.' . $v . 'Setting');
                endforeach;

            endif;

            /* Corretti gli errori di sintassi qui sotto */
            $json['result'] = true;
            $json['message'] = $saveResult['message'];
            $json['output'] = view('backend/settings/partials/index/' . $env . 'SettingsPartial', $this->data);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Restituisce tramite JSON puro l'insieme delle configurazioni attive appartenenti a uno specifico ambiente.
     * 
     * Implementa controlli sulla validità del parametro ambientale e accetta array filtro opzionali (keys) per ridurre l'output.
     *
     * @return ResponseInterface Payload asincrono contenente il dizionario chiave/valore dei settings richiesti.
     */
    public function getSettings(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $env = $posts['env'] ?? '';

            /* Protezione: Impedisce l'estrazione di informazioni da namespace di sistema non autorizzati */
            if ( ! in_array($env, $this->allowedEnvs, true)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);
            $keysFilter = $posts['keys'] ?? null;

            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace, $keysFilter);

            /* Carica i dati specifici solo se apriamo la sezione 'general' */
            if ($env === 'general'):
                $this->data['timezones'] = $this->settingsClass->getTimezones();
                $this->data['languages'] = $this->settingsClass->getLanguages();
                $this->data['dateFormats'] = $this->settingsClass->getDateFormats();
            endif;

            return $this->jsonResponse(['result' => true, 'data' => $this->data[$env . 'Settings']]);

        endif;
    }

    /**
     * Esegue un comando di rimozione per annullare ogni preferenza archiviata a livello di database per il dato ambiente, 
     * ripristinando in modo forzato il sistema ad aderire ai valori di default definiti a monte dall'applicazione.
     *
     * @return ResponseInterface Esito dell'operazione di pulizia restituito in formato JSON.
     */
    public function deleteSettings(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            $posts = $this->request->getPost();
            $env = $posts['env'] ?? '';

            /* Protezione: Impedisce la cancellazione arbitraria di dati sul database tramite manipolazione di env */
            if ( ! in_array($env, $this->allowedEnvs, true)) :
                return $this->jsonResponse(['result'  => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);

            /* Elimina interamente la sezione configurata */
            $deleted = $this->settingsModel->deleteSettings($namespace);

            if ( ! $deleted) :
                return $this->jsonResponse(['result'  => false, 'message' => lang('backend/settings.messages.alreadyDefault')]);
            endif;

            return $this->jsonResponse(['result'  => true, 'message' => lang('backend/settings.messages.deleteSuccess')]);

        endif;
    }

    /**
     * Interroga in modo preliminare il modello per individuare la presenza effettiva di customizzazioni 
     * nell'ambiente specificato, permettendo al client di determinare se attivare o precludere la comparsa dell'avviso modale di ripristino.
     *
     * @return ResponseInterface Risposta JSON di controllo logico (true: record rinvenuti, false: database vuoto per tale dominio).
     */
    public function checkDeleteSettings(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            $env = $this->request->getPost('env');

            if ( ! in_array($env, $this->allowedEnvs, true)) :
                return $this->jsonResponse(['result'  => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);

            /* Se non ci sono dati, blocca e restituisce il messaggio informativo */
            if ( ! $this->settingsModel->hasDatabaseSettings($namespace)) :
                return $this->jsonResponse([
                    'result'  => false, 
                    'message' => lang('backend/settings.messages.alreadyDefault')
                ]);
            endif;

            /* Se i dati esistono, restituisce true dando il via libera al modale JS */
            return $this->jsonResponse(['result' => true]);

        endif;
    }
}