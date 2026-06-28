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

    /**
     * Renderizza la pagina principale del modulo di gestione degli utenti.
     *
     * @return string La vista HTML iniziale dell'indice.
     */
    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/users.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render('backend/users/indexView', $this->data);
    }

    public function showAll(): string
    {
        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/users.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-list"></i>';

        return $this->render('backend/users/indexView', $this->data);
    }

    public function show(): string
    {
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/users.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-file"></i>';

        return $this->render('backend/users/indexView', $this->data);
    }
}
