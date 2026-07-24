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
    protected array $allowedEnvs = ['auth', 'upload', 'email'];

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

            /* Estrazione globale dell'intero gruppo */
            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace);

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

            /* Utilizzo della proprietà centralizzata per la whitelist */
            if ( ! in_array($env, $this->allowedEnvs, true)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            /* Generazione dinamica sicura del metodo di validazione dopo il controllo in whitelist */
            $method = $env . 'SettingsValidateRules';
            $rules = $this->settingsModel->{$method}();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/settings.messages.validationErrors')]);
            endif;

            $namespace = 'Backend\\' . ucfirst($env);

            /* 1. Esegui il salvataggio e cattura il risultato del model */
            $saveResult = $this->settingsModel->saveSettings($namespace, $posts);

            /* 2. Se il salvataggio restituisce un esito negativo (es. sbarramento nessuna modifica), lo restituiamo subito */
            if ($saveResult !== null && $saveResult['result'] === false) :
                return $this->jsonResponse(['result'  => false, 'message' => $saveResult['message']]);
            endif;

            /* 3. Ricarica i settaggi aggiornati (ora puliti e rinfrescati) per la vista */
            $this->data[$env . 'Settings'] = $this->settingsModel->getSettings($namespace);

            /* 4. Restituisci la risposta di successo con il partial aggiornato */
            return $this->jsonResponse(['result'  => true, 'message' => lang('backend/settings.messages.saveSuccess'), 'output'  => view('backend/settings/partials/index/' . $env . 'SettingsPartial', $this->data)]);

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
}