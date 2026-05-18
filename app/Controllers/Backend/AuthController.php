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
        
        $this->data['title'] = 'Auth';
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/auth/indexView', $this->data);
    }
}
