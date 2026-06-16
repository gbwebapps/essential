<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\SettingsModel;
use App\Libraries\Backend\SettingsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class SettingsController
 *
 * Controller dedicato alla gestione delle impostazioni globali e delle configurazioni di sistema del Backend.
 */
class SettingsController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza delle impostazioni di sistema.
     * 
     * @var SettingsModel 
     */
    protected SettingsModel $settingsModel;

    /**
     * Istanza della libreria logica per l'elaborazione delle configurazioni.
     * 
     * @var SettingsClass 
     */
    protected SettingsClass $settingsClass;

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

        $this->data['controller'] = 'settings';

        $this->settingsModel = model(SettingsModel::class);
        $this->settingsClass = new SettingsClass($this->settingsModel);
    }

    /**
     * Renderizza la pagina principale delle impostazioni di configurazione generale del sistema.
     *
     * @return string La vista HTML complessiva del modulo settings.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/settings.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-sliders"></i>';

        return $this->render('backend/settings/indexView', $this->data);
    }
}
