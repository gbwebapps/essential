<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AdminsModel;
use App\Libraries\Backend\AdminsClass;
use App\Controllers\Backend\BackendController; 

class AdminsController extends BackendController 
{
    protected AdminsModel $adminsModel;
    protected AdminsClass $adminsClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'admins';

        $this->adminsModel = model(AdminsModel::class);
        $this->adminsClass = (new AdminsClass())->withModel($this->adminsModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Admins';
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/indexView', $this->data);
    }
}
