<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\MessagesModel;
use App\Libraries\Backend\MessagesClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class MessagesController
 *
 * Controller dedicato alla gestione, alla visualizzazione e al monitoraggio 
 * dei messaggi, delle comunicazioni e delle notifiche all'interno del Backend.
 */
class MessagesController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e alla gestione dei messaggi.
     * 
     * @var MessagesModel 
     */
    protected MessagesModel $messagesModel;

    /**
     * Istanza della libreria logica per l'elaborazione delle funzionalità del modulo messaggi.
     * 
     * @var MessagesClass 
     */
    protected MessagesClass $messagesClass;

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

        $this->data['controller'] = 'messages';

        $this->messagesModel = model(MessagesModel::class);
        $this->messagesClass = new MessagesClass($this->messagesModel);
    }

    /**
     * Renderizza la pagina principale del modulo di gestione dei messaggi.
     *
     * @return string La vista HTML iniziale dell'indice.
     */
    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/messages.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render('backend/messages/indexView', $this->data);
    }

    public function showAll(): string
    {
        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/messages.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-list"></i>';

        return $this->render('backend/messages/indexView', $this->data);
    }

    public function show(): string
    {
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/messages.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-file"></i>';

        return $this->render('backend/messages/indexView', $this->data);
    }
}
