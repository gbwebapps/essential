<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Session\Session;

use App\Controllers\BaseController;
use App\Libraries\Backend\BackendClass;
use App\Libraries\RegExp;

abstract class BackendController extends BaseController 
{
    /* Gli helper inseriti qui vengono caricati automaticamente */
    protected $helpers = ['date', 'array', 'cookie'];

    /* @var array Array centralizzato per i dati delle viste */
    protected array $data = [];

    /* @var Session Istanza dell'oggetto Session */
    protected Session $session;

    /* @var BackendClass Proprietà per la classe BackendClass */
    protected BackendClass $backendClass;

    /* @var RegExp Istanza dell'oggetto RegExp */
    protected RegExp $regexp;

    /* @var array Proprietà per la gestione Assets CSS */
    protected array $customCss = [];

    /* @var array Proprietà per la gestione Assets JS */
    protected array $customJs  = [];

    /* @var mixed Oggetto per i dati utente corrente */
    protected $currentAdmin; // qui va il tipo di dato

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
        $this->data['menuTopRight']   = config('BackendMenu')->topRight;
        $this->data['menuBottomLeft']  = config('BackendMenu')->bottomLeft;
        $this->data['menuBottomRight'] = config('BackendMenu')->bottomRight;

        /* Definizione del tag title */
        $this->data['title'] = 'Backend';
        $this->data['siteName'] = 'Essential';

        /* Per richiamare l'utente corrente */
        $this->currentAdmin = service('authorization')->currentAdmin();

        /* Rendiamo admin corrente disponibile a tutte le viste */
        $this->data['currentAdmin'] = $this->currentAdmin;
    }

    protected function addCss(array $css): void 
    {
        $this->customCss = array_merge($this->customCss, $css);
    }

    protected function addJs(array $js): void
    {
        $this->customJs  = array_merge($this->customJs, $js); 
    }

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
        
        $coreCss = \Config\BackendAssets::getCoreCss();
        
        /* Passiamo il nome del controller per includere il JS specifico */
        $currentController = $this->data['controller'] ?? null;
        $coreJs = \Config\BackendAssets::getCoreJs($currentController);

        $finalData['assets'] = [
            'css' => $this->backendClass->getOrderedAssets($coreCss, $this->customCss),
            'js'  => $this->backendClass->getOrderedAssets($coreJs, $this->customJs)
        ];

        return view($view, $finalData);
    }

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
