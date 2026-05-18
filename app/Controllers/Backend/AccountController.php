<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AccountModel;
use App\Libraries\Backend\AccountClass;
use App\Controllers\Backend\BackendController; 

class AccountController extends BackendController 
{
    protected AccountModel $accountModel;
    protected AccountClass $accountClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'account';

        $this->accountModel = model(AccountModel::class);
        $this->accountClass = (new AccountClass())->withModel($this->accountModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Account';
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/account/indexView', $this->data);
    }
}
