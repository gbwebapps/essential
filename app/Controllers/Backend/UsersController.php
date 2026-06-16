<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\UsersModel;
use App\Libraries\Backend\UsersClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class UsersController
 *
 * Controller dedicato alla gestione completa, alla profilazione e alle operazioni
 * anagrafiche relative agli utenti dell'applicazione (Users) all'interno del Backend.
 */
class UsersController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e alla gestione dei dati degli utenti.
     * 
     * @var UsersModel 
     */
    protected UsersModel $usersModel;

    /**
     * Istanza della libreria logica per l'elaborazione delle funzionalità del modulo utenti.
     * 
     * @var UsersClass 
     */
    protected UsersClass $usersClass;

    /**
     * Inizializza il controller impostando il contesto operativo e istanziando modello e libreria specifici.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'users';

        $this->usersModel = model(UsersModel::class);
        $this->usersClass = new UsersClass($this->usersModel);
    }
}
