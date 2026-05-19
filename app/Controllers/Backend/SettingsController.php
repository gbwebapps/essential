<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\SettingsModel;
use App\Libraries\Backend\SettingsClass;
use App\Controllers\Backend\BackendController; 

class SettingsController extends BackendController 
{
    protected SettingsModel $settingsModel;
    protected SettingsClass $settingsClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'settings';

        $this->settingsModel = model(SettingsModel::class);
        $this->settingsClass = (new SettingsClass())->withModel($this->settingsModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/settings.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/settings/indexView', $this->data);
    }
}
