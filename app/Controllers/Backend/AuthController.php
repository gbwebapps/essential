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
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateLogin($posts);

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validation_errors')]);
            endif;

            $json = $this->authModel->login($posts, $this->request);
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
            $rules = $this->authModel->validateResetpassword($posts);

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validation_errors')]);
            endif;

            $json = $this->authModel->resetPassword($posts);
            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'resetPassword';
        
        $this->data['title'] = lang('backend/auth.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/resetPasswordView', $this->data);
    }

    public function setPassword(string $authCode)
    {
        if($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->authModel->validateSetpassword($posts);

            if( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/auth.messages.validation_errors')]);
            endif;

            $json = $this->authModel->setPassword($posts);
            return $this->response->setJSON($json);

        endif;

        if($authCode && $this->authModel->checkAuthCode($authCode)):
        
            $this->data['action'] = 'setPassword';
            
            $this->data['title'] = lang('backend/auth.titles.setPassword');
            $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

            return $this->render('backend/auth/setPasswordView', $this->data);

        endif;

        return redirect()->to('backend/auth')->with('class', 'danger')->with('message', lang('backend/auth.messages.checkAuthError'))->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
    }

    public function logout()
    {
        helper('cookie');
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
        return redirect()->to(base_url('backend/auth'));
    }
}
