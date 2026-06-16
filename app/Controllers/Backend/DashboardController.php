<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\DashboardModel;
use App\Libraries\Backend\DashboardClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class DashboardController
 *
 * Controller principale del pannello di controllo (Dashboard) del Backend.
 * Coordina l'inizializzazione dei servizi di reportistica e la visualizzazione della pagina principale dell'area riservata.
 */
class DashboardController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla gestione dei dati della dashboard.
     * 
     * @var DashboardModel 
     */
    protected DashboardModel $dashboardModel;

    /**
     * Istanza della libreria logica associata per l'elaborazione dei dati del modulo.
     * 
     * @var DashboardClass 
     */
    protected DashboardClass $dashboardClass;

    /**
     * Inizializza il controller impostando il contesto, caricando il modello e la libreria logica specifica.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'dashboard';

        $this->dashboardModel = model(DashboardModel::class);
        $this->dashboardClass = new DashboardClass($this->dashboardModel);
    }

    /**
     * Renderizza la pagina principale (Home/Index) della dashboard amministrativa.
     *
     * @return string La vista HTML complessiva della dashboard.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/dashboard.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/dashboard/indexView', $this->data);
    }
}
