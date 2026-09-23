<?php declare(strict_types = 1); 

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller di base astratto per la gestione dell'inizializzazione comune e dei servizi condivisi dell'applicazione.
 */
abstract class BaseController extends Controller
{
    /**
     * Inizializza i componenti principali del framework, le richieste, le risposte e il logger per il controller.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }
}
