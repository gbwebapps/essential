<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AuthModel;
use App\Libraries\Backend\AuthClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class AuthController
 *
 * Controller centrale per la gestione del ciclo di vita delle sessioni di autenticazione.
 * Coordina i flussi di accesso (Login), recupero credenziali (Reset Password), 
 * inizializzazione account (Set Password) e disconnessione sicura (Logout).
 */
class AuthController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e validazione dei dati di autenticazione.
     * 
     * @var AuthModel 
     */
    protected AuthModel $authModel;

    /**
     * Istanza della libreria logica per l'elaborazione dei flussi di sicurezza.
     * 
     * @var AuthClass 
     */
    protected AuthClass $authClass;

    /**
     * Inizializza il controller impostando il contesto grafico di atterraggio e istanziando modello e libreria core.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'auth';
        $this->data['centerContent'] = true;

        $this->authModel = model(AuthModel::class);
        $this->authClass = new AuthClass($this->authModel);
    }

    /**
     * Mostra la pagina di selezione iniziale (hub) per le macro-funzionalità di autenticazione.
     *
     * @return string La vista HTML della dashboard di autenticazione.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/auth.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-handshake-angle"></i>';

        $this->data['sections'] = [
            'login' => [
                'title' => lang('backend/auth.titles.login'),
                'class' => 'col-4',
                'icon_3x' => '<i class="fa-solid fa-right-to-bracket fa-3x"></i>',
                'route' => 'backend/auth/login',
            ],
            'recovery' => [
                'title' => lang('backend/auth.titles.resetPassword'),
                'class' => 'col-4',
                'icon_3x' => '<i class="fa-solid fa-unlock fa-3x"></i>',
                'route' => 'backend/auth/resetPassword',
            ],
        ];

        return $this->render('backend/auth/indexView', $this->data);
    }

    /**
     * Gestisce la visualizzazione della maschera di accesso (GET) e l'elaborazione asincrona delle credenziali (POST AJAX).
     * Intercetta l'eventuale URL memorizzato dai filtri di protezione per effettuare il reindirizzamento post-login.
     *
     * @return ResponseInterface|string Risposta JSON con l'esito del login o la vista HTML della pagina di accesso.
     */
    public function login()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateLoginRules();

            if (! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->login($posts, $this->request);

            /* Recupera l'URL salvato dal filtro, altrimenti usa la dashboard di default */
            $redirectUrl = session()->get('intended_url') ?? base_url('backend/dashboard');
            
            /* Pulisce la variabile di sessione */
            session()->remove('intended_url');
            
            /* Aggiunge la destinazione alla risposta per far eseguire il redirect al JS */
            $json['redirect'] = $redirectUrl;

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'login';
        
        $this->data['title'] = lang('backend/auth.titles.login');
        $this->data['icon'] = '<i class="fa-solid fa-right-to-bracket"></i>';

        return $this->render('backend/auth/loginView', $this->data);
    }

    /**
     * Gestisce la richiesta di generazione del token per il ripristino della password (GET) e il relativo invio dati (POST AJAX).
     *
     * @return ResponseInterface|string Risposta JSON con l'esito della richiesta o la vista HTML del form di recupero.
     */
    public function resetPassword()
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateResetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->resetPassword($posts, $this->request);

            /* Imposta i dati in sessione per la pagina di destinazione */
            session()->setFlashdata('message', $json['message']);
            session()->setFlashdata('class', 'success');
            session()->setFlashdata('icon', '<i class="fa-solid fa-check"></i>');

            /* Restituisce l'ok al Javascript */
            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'resetPassword';
        
        $this->data['title'] = lang('backend/auth.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-unlock"></i>';

        return $this->render('backend/auth/resetPasswordView', $this->data);
    }

    /**
     * Gestisce la form di configurazione di una nuova password (GET) pre-validando il token e il salvataggio dei dati (POST AJAX).
     *
     * @param string|null $token Il token univoco di sicurezza passato nell'URL per autorizzare l'operazione.
     * @return ResponseInterface|string Risposta JSON in POST, vista HTML in GET o reindirizzamento forzato se il token è invalido.
     */
    public function setPassword(?string $token = null)
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateSetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->setPassword($posts);

            /* Imposta i dati in sessione per la pagina di destinazione */
            session()->setFlashdata('message', $json['message']);
            session()->setFlashdata('class', 'success');
            session()->setFlashdata('icon', '<i class="fa-solid fa-key"></i>');

            /* Restituisce l'ok al Javascript */
            return $this->response->setJSON($json);

        endif;

        if($token && $this->authModel->checkAuthToken($token)):
        
            $this->data['action'] = 'setPassword';
            
            $this->data['title'] = lang('backend/auth.titles.setPassword');
            $this->data['icon'] = '<i class="fa-solid fa-key"></i>';

            $this->data['token'] = $token;

            return $this->render('backend/auth/setPasswordView', $this->data);

        endif;

        return redirect()->to('backend/auth')->with('class', 'danger')->with('message', lang('backend/auth.messages.checkAuthError'))->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
    }

    /**
     * Esegue la disconnessione completa dell'amministratore corrente, invalidando in sicurezza cookie o sessioni attive.
     *
     * @return ResponseInterface Oggetto di redirect verso la radice di autenticazione con cookie aggiornati.
     */
    public function logout()
    {
        $cookie = service('request')->getCookie('backendRememberMe');

        /* 1. Legge i dati utente PRIMA di scollegarlo */
        $firstname = $this->currentAdmin->firstname ?? '';
        $lastname = $this->currentAdmin->lastname ?? '';

        /* 2. Esegue il logout corrispondente tramite il Model */
        if ($cookie !== null):
            $this->authModel->logoutByCookie($cookie);
        else:
            $this->authModel->logoutBySession();
        endif;

        /* 3. Prepara il messaggio di saluto utilizzando i dati appena salvati */
        $message = sprintf(lang('backend/auth.messages.goodbye'), $firstname, $lastname);

        /* 4. Imposta i flashdata nativi di CI4 */
        $this->session->setFlashdata('message', $message);
        $this->session->setFlashdata('class', 'success');
        $this->session->setFlashdata('icon', '<i class="fa-solid fa-handshake"></i>');

        /* 5. Esegue un redirect pulito in GET verso la pagina di login */
        return redirect()->to(base_url('backend/auth'))->withCookies();
    }
}
