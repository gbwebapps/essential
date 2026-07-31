<?php declare(strict_types = 1); 

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * Controller di base dell'applicazione utilizzato per centralizzare
 * i servizi e le configurazioni globali ereditate dai moduli.
 */
abstract class BaseController extends Controller
{
    /**
     * Inizializza i componenti core del framework all'avvio del controller.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del servizio di logging.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        /* Esecuzione dell'inizializzazione nativa del controller padre */
        parent::initController($request, $response, $logger);
    }

    /**
     * Restituisce una risposta JSON standard con il CSRF sempre aggiornato.
     */
    protected function jsonResponse(array $data, int $statusCode = 200)
    {
        /* Iniezione dei dati CSRF rigenerati da CodeIgniter 4 */
        $csrfData = ['csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),];

        /* Unione dei dati originali con le chiavi CSRF */
        $payload = array_merge($data, $csrfData);

        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }
}
