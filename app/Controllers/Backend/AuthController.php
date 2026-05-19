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
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Auth';
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
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'login';
        
        $this->data['title'] = lang('backend/auth.titles.login');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/loginView', $this->data);
    }

    public function resetPassword()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'resetPassword';
        
        $this->data['title'] = lang('backend/auth.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/resetPasswordView', $this->data);
    }

    public function setPassword()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;
        
        $this->data['action'] = 'setPassword';
        
        $this->data['title'] = lang('backend/auth.titles.setPassword');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/setPasswordView', $this->data);
    }

    public function logout()
    {
        return redirect()->to('backend/auth')->with('class', 'success')->with('message', lang('backend/global.messages.logoutSuccess'))->with('icon', '<i class="fa-solid fa-hand-wave"></i>');
    }
}
