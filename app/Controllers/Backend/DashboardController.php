<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\DashboardModel;
use App\Libraries\Backend\DashboardClass;
use App\Controllers\Backend\BackendController; 

class DashboardController extends BackendController 
{
    protected DashboardModel $dashboardModel;
    protected DashboardClass $dashboardClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'dashboard';

        $this->dashboardModel = model(DashboardModel::class);
        $this->dashboardClass = (new DashboardClass())->withModel($this->dashboardModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/dashboard.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/dashboard/indexView', $this->data);
    }
}
