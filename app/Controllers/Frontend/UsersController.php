<?php declare(strict_types = 1); 

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Frontend\UsersModel;
use App\Libraries\Frontend\UsersClass;
use App\Controllers\Frontend\FrontendController; 

class UsersController extends FrontendController
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    public function index(): string
    {
        return view('frontend/users/welcome_message');
    }
}
