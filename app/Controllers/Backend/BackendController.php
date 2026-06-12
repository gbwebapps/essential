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
 * Class BackendController
 *
 * Controller astratto centrale per l'ambiente amministrativo di Essential.
 * Gestisce la sessione, l'autenticazione, la configurazione dei menu e l'automazione
 * del caricamento degli asset (CSS/JS) e dei link contestuali tramite Reflection.
 */
abstract class BackendController extends BaseController 
{
    /**
     * @var array Elenco degli helper nativi di CodeIgniter caricati automaticamente.
     */
    protected $helpers = ['date', 'array', 'cookie'];

    /**
     * @var array Array centralizzato contenente tutti i dati passati globalmente alle viste.
     */
    protected array $data = [];

    /**
     * @var Session Istanza del servizio di gestione della sessione utente.
     */
    protected Session $session;

    /**
     * @var BackendClass Istanza della libreria di utilità di backend per la manipolazione degli asset.
     */
    protected BackendClass $backendClass;

    /**
     * @var RegExp Istanza della libreria personalizzata per la gestione delle espressioni regolari.
     */
    protected RegExp $regexp;

    /**
     * @var array Elenco dei file CSS extra inseriti dinamicamente dai singoli controller.
     */
    protected array $customCss = [];

    /**
     * @var array Elenco dei file JavaScript extra inseriti dinamicamente dai singoli controller.
     */
    protected array $customJs  = [];

    /**
     * @var object|null Informazioni e dati dell'utente amministratore attualmente loggato.
     */
    protected ?object $currentAdmin;

    /**
     * Inizializza i servizi core, le variabili globali di vista, i menu e l'utente autenticato per il Backend.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP.
     * @param ResponseInterface $response Oggetto della risposta HTTP.
     * @param LoggerInterface   $logger   Istanza del sistema di logging.
     * @return void
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
     * Registra file CSS addizionali specifici per la vista corrente.
     *
     * @param array $css Array di asset CSS da aggiungere.
     * @return void
     */
    protected function addCss(array $css): void 
    {
        $this->customCss = array_merge($this->customCss, $css);
    }

    /**
     * Registra file JavaScript addizionali specifici per la vista corrente.
     *
     * @param array $js Array di asset JavaScript da aggiungere.
     * @return void
     */
    protected function addJs(array $js): void
    {
        $this->customJs  = array_merge($this->customJs, $js); 
    }

    /**
     * Intercetta i metodi dell'helper di settore tramite Reflection, organizza l'ordine 
     * chirurgico degli asset (CSS/JS) e restituisce la vista finale compilata.
     *
     * @param string $view Nome o percorso del file di vista da renderizzare.
     * @param array  $data Array opzionale di dati locali da unire a quelli globali.
     * @return string Output HTML della vista renderizzata.
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
     * Ispeziona dinamicamente il controller corrente tramite Reflection per individuare
     * e restituire l'istanza della libreria di settore (suffisso "Class").
     *
     * @return object|null L'istanza della classe helper trovata, oppure null se assente.
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
}
