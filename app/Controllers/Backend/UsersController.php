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
        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Users';
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        return $this->render('backend/users/indexView', $this->data);
    }
}
