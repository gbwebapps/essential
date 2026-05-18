<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\MessagesModel;
use App\Libraries\Backend\MessagesClass;
use App\Controllers\Backend\BackendController; 

class MessagesController extends BackendController 
{
    protected MessagesModel $messagesModel;
    protected MessagesClass $messagesClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'messages';

        $this->messagesModel = model(MessagesModel::class);
        $this->messagesClass = (new MessagesClass())->withModel($this->messagesModel);
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = 'Messages';
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/messages/indexView', $this->data);
    }
}
