<?php declare(strict_types = 1); 

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Frontend\ContactsModel;
use App\Libraries\Frontend\ContactsClass;
use App\Controllers\Frontend\FrontendController; 

class ContactsController extends FrontendController
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    public function index(): string
    {
        return view('frontend/contacts/welcome_message');
    }
}
