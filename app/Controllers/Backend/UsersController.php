<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\UsersModel;
use App\Libraries\Backend\UsersClass;
use App\Controllers\Backend\BackendController; 

class UsersController extends BackendController 
{
    protected UsersModel $usersModel;
    protected UsersClass $usersClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'users';

        $this->usersModel = model(UsersModel::class);
        $this->usersClass = (new UsersClass())->withModel($this->usersModel);
    }

    public function index()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/users.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        return $this->render('backend/users/indexView', $this->data);
    }

    public function showAll()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/users.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        return $this->render('backend/users/showAllView', $this->data);
    }

    public function show()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;
        
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/users.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        return $this->render('backend/users/showView', $this->data);
    }
}
