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
 * Controller dedicato alla gestione globale dei token di sessione e dei dispositivi connessi.
 * Consente agli amministratori di monitorare le sessioni attive nel pannello di controllo, 
 * verificare i dettagli dei client connessi (es. user agent, indirizzi IP) e revocare selettivamente 
 * gli accessi al sistema invalidando i token specifici.
 */
class TokensController extends BackendController 
{
    /**
     * Istanza del modello di riferimento per le operazioni sui token. Si occupa dell'interrogazione 
     * del database, dell'applicazione dei filtri di ricerca e della cancellazione fisica dei record.
     * @var TokensModel 
     */
    protected TokensModel $tokensModel;

    /**
     * Istanza della libreria di supporto contenente logiche dedicate ai token, utile per 
     * isolare dal controller le operazioni di formattazione e preparazione avanzata dei dati.
     * @var TokensClass 
     */
    protected TokensClass $tokensClass;

    /**
     * Metodo di inizializzazione che estende le funzionalità di base del BackendController.
     * Configura le variabili di contesto ('controller' ed 'entity') fondamentali per il corretto 
     * instradamento delle risorse nelle viste, e istanzia i modelli e le classi necessarie al modulo.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param ResponseInterface $response L'oggetto deputato alla generazione della risposta HTTP
     * @param LoggerInterface $logger L'interfaccia per la registrazione degli eventi nel sistema di log
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
     * Gestisce la visualizzazione della tabella principale contenente l'elenco storico dei token.
     * Se invocato tramite richiesta AJAX (es. durante una ricerca), valida i filtri immessi, interroga il modello 
     * e restituisce in JSON il frammento HTML della tabella parziale, consentendo un aggiornamento asincrono dell'interfaccia.
     *
     * @return string|ResponseInterface Codice HTML della vista index (su GET) o risposta JSON con la tabella ricalcolata (su POST)
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

    /**
     * Elabora le richieste asincrone per l'eliminazione definitiva e irreversibile di un singolo token dal database.
     * Previa validazione formale dei dati in ingresso, forza la rimozione del record, revocando conseguentemente 
     * l'accesso al client o dispositivo remoto associato a tale sessione.
     *
     * @return ResponseInterface Risposta JSON indicante l'esito dell'operazione di cancellazione e gli eventuali errori
     */
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