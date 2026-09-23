<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\UserAgent;

use App\Models\Backend\AuditsModel;
use App\Libraries\Backend\AuditsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Gestisce la visualizzazione e il filtraggio dei log di controllo (audit), tracciando le operazioni e le modifiche effettuate dagli utenti nel sistema.
 */
class AuditsController extends BackendController 
{
    /**
     * @var AuditsModel Istanza del modello dedicato all'interazione con il database per il recupero e la ricerca dei record di audit
     */
    protected AuditsModel $auditsModel;

    /**
     * @var AuditsClass Istanza della libreria contenente le logiche di formattazione e di supporto specifiche per gli audit
     */
    protected AuditsClass $auditsClass;

    /**
     * Inizializza il controller, configura i metadati della pagina e istanzia le dipendenze necessarie per la gestione degli audit.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'audits';
        $this->data['entity'] = 'audits';

        $this->auditsModel = model(AuditsModel::class);
        $this->auditsClass = new AuditsClass($this->auditsModel);
    }

    /**
     * Gestisce il caricamento e il filtraggio asincrono della tabella contenente lo storico degli audit, oppure renderizza l'interfaccia principale.
     *
     * @return string|ResponseInterface Risposta JSON con i dati filtrati o l'HTML renderizzato della vista generale
     */
    public function index(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            $rules = $this->auditsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/audits.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->auditsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/audits.messages.validationErrors')]);
            endif;

            /* Attiviamo il contesto audit e lasciamo che getData() faccia tutto il lavoro pesante */
            $this->data['data'] = $this->auditsModel->getData($posts);

            /* ... resto del controller invariato ... */

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;

                $this->data['userAgent'] = new UserAgent();

                $json['output'] = view('backend/audits/partials/index/indexPartial', $this->data);
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/audits.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-clock-rotate-left"></i>';

        return $this->render('backend/audits/indexView', $this->data);
    }
}