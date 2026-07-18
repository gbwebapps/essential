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
 * Class AuditsController
 *
 * Controller centrale per la gestione completa delle utenze amministrative (Audits).
 * Coordina le operazioni CRUD, l'assegnazione dei permessi RBAC granulari, la sicurezza 
 * delle sessioni, la revoca dei token e i caricamenti dinamici delle viste asincrone via AJAX.
 */
class AuditsController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e manipolazione dei dati degli amministratori.
     * 
     * @var AuditsModel 
     */
    protected AuditsModel $auditsModel;

    /**
     * Istanza della libreria logica per l'elaborazione dei flussi e delle operazioni del modulo.
     * 
     * @var AuditsClass 
     */
    protected AuditsClass $auditsClass;

    /**
     * Inizializza il controller impostando il contesto del modulo e istanziando modello e libreria specifici.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
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
     * Renderizza la pagina principale del modulo di gestione degli amministratori.
     *
     * @return string La vista HTML iniziale dell'indice.
     */
    public function index(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            $rules = $this->auditsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/audits.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->auditsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->response->setJSON(['errors' => $formattedErrors, 'message' => lang('backend/audits.messages.validationErrors')]);
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

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/audits.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-clock-rotate-left"></i>';

        return $this->render('backend/audits/indexView', $this->data);
    }
}