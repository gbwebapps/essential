<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\ToolsModel;
use App\Libraries\Backend\ToolsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class ToolsController
 *
 * Controller dedicato alla gestione degli strumenti di utilità, diagnostica e manutenzione del sistema di Backend.
 */
class ToolsController extends BackendController 
{
    /**
     * @var ToolsModel Istanza del modello dedicato alla persistenza e gestione dei dati degli strumenti.
     */
    protected ToolsModel $toolsModel;

    /**
     * @var ToolsClass Istanza della libreria logica per l'esecuzione dei tool di utilità del sistema.
     */
    protected ToolsClass $toolsClass;

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

        $this->data['controller'] = 'tools';

        $this->toolsModel = model(ToolsModel::class);
        $this->toolsClass = new ToolsClass($this->toolsModel);
    }

    /**
     * Renderizza la pagina principale contenente il set di strumenti e utilità di amministrazione.
     *
     * @return string La vista HTML complessiva del modulo tools.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/tools.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/tools/indexView', $this->data);
    }
}
