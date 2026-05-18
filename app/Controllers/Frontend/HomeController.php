<?php declare(strict_types = 1); 

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Frontend\HomeModel;
use App\Libraries\Frontend\HomeClass;
use App\Controllers\Frontend\FrontendController; 

class HomeController extends FrontendController
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    public function index(): string
    {
        return view('frontend/home/welcome_message');
    }
}
