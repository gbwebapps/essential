<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\SettingsModel;
use App\Libraries\Backend\SettingsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class SettingsController
 *
 * Controller parametrizzato, ottimizzato e blindato contro manomissioni esterne dei parametri di configurazione.
 */
class SettingsController extends BackendController 
{
    /**
     * @var SettingsModel 
     */
    protected SettingsModel $settingsModel;

    /**
     * @var SettingsClass 
     */
    protected SettingsClass $settingsClass;

    /**
     * Whitelist dei moduli di configurazione autorizzati nel sistema.
     * Impedisce attacchi di iniezione di codice e path traversal.
     *
     * @var array
     */
    protected array $allowedEnvs = ['general', 'auth', 'upload', 'email'];

    /**
     * Inizializza il controller impostando il contesto operativo.
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
     * Renderizza la pagina principale caricando i valori di fallback iniziali.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/settings.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-sliders"></i>';

        /* Recupera il modulo da riaprire salvato in sessione dopo il reload */
        $this->data['openAccordion'] = session()->getFlashdata('open_accordion');

        return $this->render('backend/settings/indexView', $this->data);
    }

    /**
     * Forza l'apertura e il rendering asincrono del pannello accordion basato sul parametro env.
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
     * Convalida e memorizza in modo massivo o mirato i parametri di configurazione inviati in POST.
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
                return $this->jsonResponse(['result'  => false, 'message' => $saveResult['message']]);
            endif;

            /* 2. Gestione Reload per campi critici */
            if (isset($saveResult['requires_reload']) && $saveResult['requires_reload'] === true) :
                
                /* Estraiamo la lingua attualmente configurata (ora aggiornata nel DB/Config) */
                $language = setting('Backend\General')->language;

                /* Forziamo la generazione del messaggio passando la nuova lingua come 3° parametro */
                $localizedMessage = lang('backend/settings.messages.saveSuccess', [], $language);

                session()->setFlashdata('message', $localizedMessage);
                session()->setFlashdata('class', 'light text-success fw-bold'); 
                session()->setFlashdata('icon', '<i class="fa-solid fa-check"></i>');
                session()->setFlashdata('open_accordion', $env);

                return $this->jsonResponse(['result' => true, 'action' => 'reload']);
            endif;

            /* 3. Flusso standard senza reload */
            $this->data['isFromDatabase'] = $this->settingsModel->hasDatabaseSettings($namespace);
            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace);

            if ($env === 'general'):
                $this->data['timezones'] = $this->settingsClass->getTimezones();
                $this->data['languages'] = $this->settingsClass->getLanguages();
                $this->data['dateFormats'] = $this->settingsClass->getDateFormats();
            endif;

            return $this->jsonResponse([
                'result'  => true, 
                'message' => $saveResult['message'], 
                'output'  => view('backend/settings/partials/index/' . $env . 'SettingsPartial', $this->data)
            ]);

        endif;
    }

    /**
     * Recupera una selezione mirata di chiavi configurate o l'intero set.
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
     * Rimuove elementi di configurazione specifici o pulisce l'intero namespace.
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
     * Verifica preventivamente l'esistenza di impostazioni nel database per il modulo richiesto.
     * Utilizzato per bloccare il modale lato client se non ci sono dati da eliminare.
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