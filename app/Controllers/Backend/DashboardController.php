<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\DashboardModel;
use App\Libraries\Backend\DashboardClass;
use App\Controllers\Backend\BackendController; 

/**
 * Controller per la gestione della dashboard principale.
 * 
 * Si occupa di fornire l'interfaccia di atterraggio (landing) a seguito dell'autenticazione, 
 * predisponendo la vista che ospita widget informativi, riepiloghi statistici e notifiche globali.
 */
class DashboardController extends BackendController 
{
    /**
     * @var DashboardModel Istanza del modello incaricato di recuperare eventuali dati statistici o di riepilogo dal database.
     */
    protected DashboardModel $dashboardModel;

    /**
     * @var DashboardClass Istanza della libreria di supporto per l'elaborazione di dati formattati specifici per la dashboard.
     */
    protected DashboardClass $dashboardClass;

    /**
     * Inizializza le dipendenze specifiche della dashboard, dichiarando l'identificativo del controller 
     * e istanziando il modello e la classe di supporto associati.
     *
     * @param RequestInterface $request Oggetto contenente la richiesta HTTP.
     * @param ResponseInterface $response Oggetto deputato alla costruzione della risposta HTTP.
     * @param LoggerInterface $logger L'interfaccia per il tracciamento dei log.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'dashboard';

        $this->dashboardModel = model(DashboardModel::class);
        $this->dashboardClass = new DashboardClass($this->dashboardModel);
    }

    /**
     * Elabora e restituisce la vista principale della dashboard, configurando i metadati essenziali (titolo e icona) 
     * prima di delegare la renderizzazione al BackendController.
     *
     * @return string HTML renderizzato della schermata di riepilogo.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/dashboard.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/dashboard/indexView', $this->data);
    }
}
