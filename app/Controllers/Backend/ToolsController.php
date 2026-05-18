<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\ToolsModel;
use App\Libraries\Backend\ToolsClass;
use App\Controllers\Backend\BackendController; 

class ToolsController extends BackendController 
{
    protected ToolsModel $toolsModel;
    protected ToolsClass $toolsClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'tools';

        $this->toolsModel = model(ToolsModel::class);
        $this->toolsClass = (new ToolsClass())->withModel($this->toolsModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Tools';
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/tools/indexView', $this->data);
    }
}
