<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AuthModel;
use App\Libraries\Backend\AuthClass;
use App\Controllers\Backend\BackendController; 

class AuthController extends BackendController 
{
    protected AuthModel $authModel;
    protected AuthClass $authClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'auth';
        $this->data['centerContent'] = true;

        $this->authModel = model(AuthModel::class);
        $this->authClass = (new AuthClass())->withModel($this->authModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/auth.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

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

    public function login()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateLoginRules();

            if (! $this->validateData($posts, $rules)):
                return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->login($posts, $this->request);

            if ($json['result'] === 'loginFailed'):
                return $this->response->setStatusCode(401)->setJSON($json);
            endif;

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
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/loginView', $this->data);
    }

    public function resetPassword()
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateResetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->resetPassword($posts, $this->request);

            if(($json['result'] === 'resetPasswordFailed') || ($json['result'] === 'emailFailed')):
                return $this->response->setStatusCode(401)->setJSON($json);
            endif;

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'resetPassword';
        
        $this->data['title'] = lang('backend/auth.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-lock"></i>';

        return $this->render('backend/auth/resetPasswordView', $this->data);
    }

    public function setPassword(?string $token = null)
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateSetPasswordRules();

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validationErrors')]);
            endif;

            $json = $this->authModel->setPassword($posts);

            if($json['result'] === 'setPasswordFailed'):
                return $this->response->setStatusCode(401)->setJSON($json);
            endif;

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

    public function logout()
    {
        $cookie = get_cookie('backendRememberMe');

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
        $this->session->setFlashdata('message_icon', '<i class="fa-solid fa-check"></i>');

        /* 5. Esegue un redirect pulito in GET verso la pagina di login */
        return redirect()->to(base_url('backend/auth'))->withCookies();
    }
}
