<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\UserAgent;

use App\Models\Backend\LogsModel;
use App\Libraries\Backend\LogsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class LogsController
 *
 * Controller centrale per la gestione completa delle utenze amministrative (Logs).
 * Coordina le operazioni CRUD, l'assegnazione dei permessi RBAC granulari, la sicurezza 
 * delle sessioni, la revoca dei log e i caricamenti dinamici delle viste asincrone via AJAX.
 */
class LogsController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e manipolazione dei dati degli amministratori.
     * 
     * @var LogsModel 
     */
    protected LogsModel $logsModel;

    /**
     * Istanza della libreria logica per l'elaborazione dei flussi e delle operazioni del modulo.
     * 
     * @var LogsClass 
     */
    protected LogsClass $logsClass;

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

        $this->data['controller'] = 'logs';
        $this->data['entity'] = 'logs';

        $this->logsModel = model(LogsModel::class);
        $this->logsClass = new LogsClass($this->logsModel);
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

            $rules = $this->logsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/logs.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->logsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/logs.messages.validationErrors')]);
            endif;

            /* Attiviamo il contesto log e lasciamo che getData() faccia tutto il lavoro pesante */
            $this->data['data'] = $this->logsModel->getData($posts);

            /* ... resto del controller invariato ... */

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;

                $this->data['userAgent'] = new UserAgent();

                $json['output'] = view('backend/logs/partials/index/indexPartial', $this->data);
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/logs.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-user-clock"></i>';

        return $this->render('backend/logs/indexView', $this->data);
    }

    public function hardDelete(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = ['tokenId' => 'required|is_natural_no_zero'];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/logs.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->logsModel->deleteToken((int) $posts['tokenId']);

            return $this->jsonResponse($json);

        endif;
    }
}