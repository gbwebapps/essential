<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Session\Session;

use App\Controllers\BaseController;
use App\Libraries\Backend\BackendClass;
use App\Libraries\RegExp;

/**
 * Controller astratto di base per l'area amministrativa (backend).
 * 
 * Estende le funzionalità principali del framework occupandosi dell'inizializzazione 
 * dei servizi comuni come le sessioni, la validazione tramite espressioni regolari 
 * e l'identificazione dell'utente corrente. Prepara inoltre il contesto globale 
 * per la generazione dei menu e la gestione degli asset (CSS/JS) tramite l'uso della reflection.
 */
abstract class BackendController extends BaseController 
{
    /**
     * @var array Array di helper precaricati da CodeIgniter per essere disponibili in tutto il backend.
     */
    protected $helpers = ['date', 'array', 'cookie', 'settings'];

    /**
     * @var array Array associativo globale utilizzato per passare variabili e contesti strutturati alle viste.
     */
    protected array $data = [];

    /**
     * @var Session Istanza del gestore nativo delle sessioni di CodeIgniter.
     */
    protected Session $session;

    /**
     * @var BackendClass Istanza della classe helper contenente le logiche di supporto e formattazione generali.
     */
    protected BackendClass $backendClass;

    /**
     * @var RegExp Istanza del servizio dedicato alla validazione e manipolazione tramite espressioni regolari.
     */
    protected RegExp $regexp;

    /**
     * @var array Elenco dei percorsi per i fogli di stile (CSS) personalizzati che devono essere accodati al rendering.
     */
    protected array $customCss = [];

    /**
     * @var array Elenco dei percorsi per gli script (JS) personalizzati che devono essere accodati al rendering.
     */
    protected array $customJs  = [];

    /**
     * @var object|null Oggetto rappresentante l'amministratore attualmente autenticato.
     */
    protected ?object $currentAdmin;

    /**
     * Costruttore del controller. Inizializza le dipendenze, i servizi globali e popola
     * la variabile $data con le informazioni strutturali di base necessarie al layout (menu, titoli, utente).
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso.
     * @param ResponseInterface $response L'oggetto preposto a generare la risposta HTTP.
     * @param LoggerInterface $logger L'interfaccia per la registrazione degli eventi di sistema.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        /* Carichiamo il servizio sessione nativo di CI4 */
        $this->session = \Config\Services::session();

        /* Rendiamo la sessione disponibile a tutte le viste */
        $this->data['session'] = $this->session;

        /* Inizializziamo la nostra classe di supporto del Backend */
        $this->backendClass = new BackendClass();

        /* Carichiamo il servizio regexp */
        $this->regexp = service('regexp');

        /* Rendiamo la regexp disponibile a tutte le viste */
        $this->data['regexp'] = $this->regexp;

        /* Generazione degli arrays delle voci di menu per le viste */
        $this->data['menuTopRight']   = config(\Config\Backend\Menu::class)->topRight;
        $this->data['menuBottomLeft']  = config(\Config\Backend\Menu::class)->bottomLeft;
        $this->data['menuBottomRight'] = config(\Config\Backend\Menu::class)->bottomRight;

        /* Definizione del tag title */
        $this->data['title'] = 'Backend';
        $this->data['siteName'] = 'Essential';

        /* Per richiamare l'utente corrente */
        $this->currentAdmin = service('authorization')->currentAdmin();

        /* Rendiamo admin corrente disponibile a tutte le viste */
        $this->data['currentAdmin'] = $this->currentAdmin;
    }

    /**
     * Accoda dinamicamente nuovi percorsi ai fogli di stile personalizzati da caricare nella vista.
     *
     * @param array $css Array contenente i percorsi dei file CSS aggiuntivi.
     */
    protected function addCss(array $css): void 
    {
        $this->customCss = array_merge($this->customCss, $css);
    }

    /**
     * Accoda dinamicamente nuovi percorsi agli script JavaScript personalizzati da caricare nella vista.
     *
     * @param array $js Array contenente i percorsi dei file JS aggiuntivi.
     */
    protected function addJs(array $js): void
    {
        $this->customJs  = array_merge($this->customJs, $js); 
    }

    /**
     * Motore di renderizzazione centrale.
     * 
     * Utilizza la reflection per individuare eventuali classi helper associate al controller corrente. 
     * Se trovate, ne estrae automaticamente asset specifici (CSS/JS) e frammenti di interfaccia 
     * (es. linksBar, options) basandosi sul nome dell'azione in corso, prima di compilare la vista finale.
     *
     * @param string $view Il percorso relativo della vista da elaborare.
     * @param array $data Dati opzionali aggiuntivi da iniettare nel contesto della vista.
     * @return string Il codice HTML elaborato e pronto per l'output.
     */
    protected function render(string $view, array $data = []): string
    {
        /* 1. Recuperiamo il nome del metodo che ha chiamato il render (es. "index") */
        $action = ucfirst($this->data['action'] ?? ''); 

        /* Recuperiamo l'uuid se presente nell'array $data, altrimenti null */
        $uuid = $data['uuid'] ?? null;

        /* 2. Verifichiamo se esiste una classe di helper (es. $this->adminsClass) */
        $helper = $this->getHelperClass();

        if ($helper):
            /* Automazione JS: cerca getJsIndex(), getJsEdit(), ecc. */
            if (method_exists($helper, "getJs{$action}")):
                $this->addJs($helper->{"getJs{$action}"}());
            endif;

            /* Automazione CSS: cerca getCssIndex(), ecc. */
            if (method_exists($helper, "getCss{$action}")):
                $this->addCss($helper->{"getCss{$action}"}());
            endif;

            /* Passiamo l'uuid al metodo dell'helper */
            if (method_exists($helper, "getLinksBar{$action}")):
                $this->data['linksBar'] = $helper->{"getLinksBar{$action}"}($uuid);
            endif;
            
            /* Lo stesso vale per getOptions */
            if (method_exists($helper, "getOptions{$action}")):
                $this->data['options'] = view('backend/template/optionsView', [
                    'options' => $helper->{"getOptions{$action}"}($uuid)
                ]);
            endif;
        endif;

        /* Procede con la normale compilazione degli asset fissi e il ritorno della vista */
        $finalData = array_merge($this->data, $data);
        
        $coreCss = \Config\Backend\Assets::getCoreCss();
        
        /* Passiamo il nome del controller per includere il JS specifico */
        $currentController = $this->data['controller'] ?? null;
        $coreJs = \Config\Backend\Assets::getCoreJs($currentController);

        $finalData['assets'] = [
            'css' => $this->backendClass->getOrderedAssets($coreCss, $this->customCss),
            'js'  => $this->backendClass->getOrderedAssets($coreJs, $this->customJs)
        ];

        return view($view, $finalData);
    }

    /**
     * Analizza le proprietà protette del controller tramite Reflection API per individuare 
     * e restituire l'eventuale istanza della classe helper dedicata (identificata dal suffisso 'Class').
     *
     * @return object|null L'istanza dell'helper trovato, oppure null.
     */
    private function getHelperClass(): ?object
    {
        /* Recuperiamo le proprietà dell'istanza corrente tramite Reflection */
        $reflection = new \ReflectionClass($this);
        
        /* Filtriamo solo le proprietà che non sono del BackendController stesso */
        foreach ($reflection->getProperties() as $property):
            $name = $property->getName();
            
            /* Verifichiamo il suffisso 'Class' */
            if (str_ends_with($name, 'Class')):
                /* In PHP 8.1+ getValue() accede automaticamente alle proprietà protected */
                $value = $property->getValue($this);
                
                if (is_object($value)):
                    return $value;
                endif;
            endif;
        endforeach;

        return null;
    }

    /**
     * Restituisce una risposta formattata in JSON.
     * 
     * Integra automaticamente nel payload di risposta i token CSRF rigenerati per mantenere 
     * la validità e la sicurezza delle successive richieste asincrone (AJAX).
     *
     * @param array $data L'array associativo contenente i dati da inviare.
     * @param int $statusCode Il codice di stato HTTP associato alla risposta (predefinito: 200).
     * @return ResponseInterface L'oggetto Response di CodeIgniter contenente il payload JSON.
     */
    protected function jsonResponse(array $data, int $statusCode = 200)
    {
        /* Iniezione dei dati CSRF rigenerati da CodeIgniter 4 */
        $csrfData = ['csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),];

        /* Unione dei dati originali con le chiavi CSRF */
        $payload = array_merge($data, $csrfData);

        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }
}
