<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AuthModel;
use App\Libraries\Backend\AuthClass;
use App\Controllers\Backend\BackendController; 

/**
 * Gestisce l'autenticazione degli amministratori nel pannello di controllo, includendo login, recupero password, verifica a due fattori (2FA) e disconnessione.
 */
class AuthController extends BackendController 
{
    /**
     * Istanza del modello responsabile delle logiche di accesso, validazione credenziali e gestione dei token
     * @var AuthModel 
     */
    protected AuthModel $authModel;

    /**
     * Istanza della libreria per funzioni di supporto, criptazione o formattazione legate all'autenticazione
     * @var AuthClass 
     */
    protected AuthClass $authClass;

    /**
     * Inizializza il controller, configura il layout centrato per le maschere di accesso e istanzia le dipendenze del modulo di autenticazione.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
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
     * Renderizza la pagina di atterraggio principale che offre i collegamenti alle maschere di accesso e recupero password.
     *
     * @return string HTML renderizzato della vista index
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/auth.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-handshake-angle"></i>';

        $this->data['sections'] = [
            'login' => [
                'title' => lang('backend/auth.titles.login'),
                'class' => 'col-12 col-md-4',
                'icon_2x' => '<i class="fa-solid fa-right-to-bracket fa-2x"></i>',
                'route' => 'backend/auth/login',
            ],
            'recovery' => [
                'title' => lang('backend/auth.titles.resetPassword'),
                'class' => 'col-12 col-md-4',
                'icon_2x' => '<i class="fa-solid fa-unlock fa-2x"></i>',
                'route' => 'backend/auth/resetPassword',
            ],
        ];

        return $this->render('backend/auth/indexView', $this->data);
    }

    /**
     * Gestisce il caricamento del modulo di accesso e l'elaborazione asincrona delle credenziali (login), inclusi redirect condizionali o invio al processo 2FA.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito dell'autenticazione e redirect, oppure l'HTML della maschera di login
     */
    public function login()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateLoginRules();

            if (! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->login($posts, $this->request);

            /* Recupera l'URL salvato dal filtro, altrimenti usa la dashboard di default */
            $redirectUrl = session()->get('intended_url') ?? base_url('backend/dashboard');
            
            /* Pulisce la variabile di sessione */
            session()->remove('intended_url');
            
            /* Aggiunge la destinazione alla risposta per far eseguire il redirect al JS */
            $json['redirect'] = $redirectUrl;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'login';
        
        $this->data['title'] = lang('backend/auth.titles.login');
        $this->data['icon'] = '<i class="fa-solid fa-right-to-bracket"></i>';

        return $this->render('backend/auth/loginView', $this->data);
    }

    /**
     * Gestisce il caricamento del modulo per il recupero password e l'invio asincrono dell'email contenente il token di sblocco.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito della richiesta oppure l'HTML della maschera di recupero
     */
    public function resetPassword()
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateResetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->resetPassword($posts, $this->request);

            /* Restituisce l'ok al Javascript */
            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'resetPassword';
        
        $this->data['title'] = lang('backend/auth.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-unlock"></i>';

        return $this->render('backend/auth/resetPasswordView', $this->data);
    }

    /**
     * Gestisce il caricamento del modulo, verifica la validità temporale e l'esistenza del token di sblocco, permettendo all'utente di definire una nuova password di accesso.
     *
     * @param string|null $token Codice univoco di sicurezza per autorizzare il reset della password
     * @return string|ResponseInterface Risposta JSON post-aggiornamento, HTML della maschera di reset o redirect in caso di token non valido
     */
    public function setPassword(?string $token = null)
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateSetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->setPassword($posts);

            /* Restituisce l'ok al Javascript */
            return $this->jsonResponse($json);

        endif;

        if($token && $this->authModel->checkAuthToken($token)):
        
            $this->data['action'] = 'setPassword';
            
            $this->data['title'] = lang('backend/auth.titles.setPassword');
            $this->data['icon'] = '<i class="fa-solid fa-key"></i>';

            $this->data['token'] = $token;

            return $this->render('backend/auth/setPasswordView', $this->data);

        endif;

        return redirect()->to('backend/auth')->with('class', 'light text-danger fw-bold')->with('message', lang('backend/auth.messages.checkAuthError'))->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
    }

    /**
     * Gestisce la maschera di convalida e il controllo asincrono del codice OTP (email o app) richiesto per completare l'autenticazione a due fattori.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito della verifica oppure l'HTML della maschera di inserimento codice 2FA
     * @throws \CodeIgniter\Exceptions\PageNotFoundException Se la funzionalità 2FA è disabilitata a livello di configurazione globale
     */
    public function verify()
    {
        /* SBARRAMENTO GLOBALE: Se la feature 2FA è spenta, la rotta non esiste */
        if ( ! setting('Backend\Auth')->twoFactor):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateVerifyRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            /* Il controllo sul database, l'eventuale blocco brute-force e la pulizia della 
               sessione in caso di successo avverranno tutti atomicamente dentro il Model */
            $json = $this->authModel->verify($posts, $this->request);

            return $this->jsonResponse($json);

        endif;

        if (empty(session()->get('auth_2fa_pending'))):
            return redirect()->to(base_url('backend/auth'));
        endif;

        $this->data['action'] = 'verify';
        $this->data['title'] = lang('backend/auth.titles.verify');
        $this->data['icon'] = '<i class="fa-solid fa-mobile-screen"></i>';

        return $this->render('backend/auth/verifyView', $this->data);
    }

    /**
     * Termina esplicitamente la sessione di lavoro dell'amministratore (logout), invalidando i token attivi (sessione o "remember me") e reindirizzando al login.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse Redirect alla pagina di accesso con i messaggi di conferma in flashdata
     */
    public function logout()
    {
        $cookie = $this->request->getCookie('backendRememberMe');

        /* 1. Legge i dati utente PRIMA di scollegarlo */
        $firstname = $this->currentAdmin->firstname ?? '';
        $lastname = $this->currentAdmin->lastname ?? '';

        /* 2. Esegue il logout corrispondente tramite il Model */
        if ($cookie !== null):
            $this->authModel->logoutByCookie($cookie, 'manual');
        else:
            $this->authModel->logoutBySession('manual');
        endif;

        /* 3. Prepara il messaggio di saluto utilizzando i dati appena salvati */
        $message = sprintf(lang('backend/auth.messages.goodbye'), $firstname, $lastname);

        /* 4. Imposta i flashdata nativi di CI4 */
        $this->session->setFlashdata('message', $message);
        $this->session->setFlashdata('class', 'light text-success fw-bold');
        $this->session->setFlashdata('icon', '<i class="fa-solid fa-handshake"></i>');

        /* 5. Esegue un redirect pulito in GET verso la pagina di login */
        return redirect()->to(base_url('backend/auth'))->withCookies();
    }
}
