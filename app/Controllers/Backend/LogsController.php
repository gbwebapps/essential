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
 * Controller dedicato alla consultazione dei registri (log) di sistema.
 * 
 * Permette di monitorare la cronologia degli accessi (login/logout), le sessioni utente attive 
 * e di esaminare i metadati relativi ai dispositivi o ai browser utilizzati per accedere all'applicazione.
 */
class LogsController extends BackendController 
{
    /**
     * Istanza del modello predisposto all'estrazione, filtraggio e gestione dei record dei log dal database.
     * @var LogsModel 
     */
    protected LogsModel $logsModel;

    /**
     * Istanza della libreria che fornisce routine specifiche per la formattazione visiva dei log e l'analisi del client.
     * @var LogsClass 
     */
    protected LogsClass $logsClass;

    /**
     * Instanzia e prepara le dipendenze essenziali del controller impostando l'identificativo entità 
     * per garantire una corretta interpretazione e un routing accurato delle viste.
     *
     * @param RequestInterface $request L'oggetto della richiesta HTTP.
     * @param ResponseInterface $response L'oggetto della risposta HTTP.
     * @param LoggerInterface $logger L'interfaccia di logging di sistema.
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
     * Renderizza l'interfaccia iniziale del pannello logs o gestisce le richieste AJAX per aggiornare in modo asincrono la tabella dei risultati.
     * Il metodo si occupa di validare i criteri di ricerca passati come payload prima di interrogare il modello.
     *
     * @return string|ResponseInterface Codice HTML della pagina o risposta JSON con la porzione di tabella ricalcolata.
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

    /**
     * Provvede alla rimozione definitiva di un singolo record di accesso (log session) identificato in modo esplicito dal suo token ID.
     *
     * @return string|ResponseInterface Risposta JSON contenente l'esito dell'operazione di cancellazione.
     */
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