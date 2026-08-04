<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\UserAgent;

use App\Models\Backend\TokensModel;
use App\Libraries\Backend\TokensClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class TokensController
 *
 * Controller centrale per la gestione completa delle utenze amministrative (Tokens).
 * Coordina le operazioni CRUD, l'assegnazione dei permessi RBAC granulari, la sicurezza 
 * delle sessioni, la revoca dei token e i caricamenti dinamici delle viste asincrone via AJAX.
 */
class TokensController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e manipolazione dei dati degli amministratori.
     * 
     * @var TokensModel 
     */
    protected TokensModel $tokensModel;

    /**
     * Istanza della libreria logica per l'elaborazione dei flussi e delle operazioni del modulo.
     * 
     * @var TokensClass 
     */
    protected TokensClass $tokensClass;

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

        $this->data['controller'] = 'tokens';
        $this->data['entity'] = 'tokens';

        $this->tokensModel = model(TokensModel::class);
        $this->tokensClass = new TokensClass($this->tokensModel);
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

            $rules = $this->tokensModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/tokens.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->tokensModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/tokens.messages.validationErrors')]);
            endif;

            /* Attiviamo il contesto token e lasciamo che getData() faccia tutto il lavoro pesante */
            $this->data['data'] = $this->tokensModel->getData($posts);

            /* ... resto del controller invariato ... */

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;

                $this->data['userAgent'] = new UserAgent();

                $json['output'] = view('backend/tokens/partials/index/indexPartial', $this->data);
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/tokens.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-solid fa-chain"></i>';

        return $this->render('backend/tokens/indexView', $this->data);
    }

    public function hardDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->tokensModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/tokens.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->tokensModel->hardDelete($posts);

            return $this->jsonResponse($json);

        endif;
    }
}