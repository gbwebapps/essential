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
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/messages.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/messages/indexView', $this->data);
    }

    public function showAll()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/messages.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/messages/showAllView', $this->data);
    }

    public function show()
    {
        if($this->request->isAJAX()):

            // some code here...

        endif;
        
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/messages.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/messages/showView', $this->data);
    }
}
