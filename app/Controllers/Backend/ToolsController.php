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
     * Istanza del modello dedicato alla gestione dei dati degli strumenti.
     * 
     * @var ToolsModel 
     */
    protected ToolsModel $toolsModel;

    /**
     * Istanza della libreria logica per l'esecuzione dei tool di utilità del sistema.
     * 
     * @var ToolsClass 
     */
    protected ToolsClass $toolsClass;

    /**
     * Whitelist dei moduli di strumenti autorizzati nel sistema.
     * Impedisce attacchi di iniezione di codice e path traversal.
     *
     * @var array
     */
    protected array $allowedEnvs = ['manageAudits', 'dbMaintenance', 'backups'];

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
        $this->data['icon'] = '<i class="fa-solid fa-screwdriver-wrench"></i>';

        return $this->render('backend/tools/indexView', $this->data);
    }

    /**
     * Forza l'apertura e il rendering asincrono del pannello accordion basato sul parametro env.
     */
    public function openTools(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $env = $this->request->getPost('env');

            /* Protezione: Blocco immediato se l'ambiente non è rigorosamente autorizzato */
            if ( ! in_array($env, $this->allowedEnvs, true)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            /* Ottimizzazione: uso di if / elseif per evitare controlli a vuoto */
            if ($env === 'manageAudits'):
                $this->data['minAuditYear'] = $this->toolsModel->getMinAuditYear();
                $this->data['stats'] = $this->toolsModel->getAuditsStats(); 
                $this->data['columns'] = $this->toolsModel->getAuditColumns();
            elseif ($env === 'dbMaintenance'):
                $this->data['database'] = $this->toolsModel->getDatabase();
                $this->data['tables'] = $this->toolsModel->getTablesStatus();
            elseif ($env === 'backups'):
                $this->data['backups'] = $this->toolsModel->getBackups();
            endif;

            return $this->jsonResponse(['result' => true, 'output' => view('backend/tools/partials/index/' . $env . 'ToolsPartial', $this->data)]);

        endif;
    }

    /**
     * Esegue la validazione preventiva delle date prima di aprire modali o eseguire azioni.
     */
    public function validateAuditsDateRequest(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageAuditsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            return $this->jsonResponse(['result' => true]);

        endif;
    }

    /**
     * Esegue la cancellazione degli audit in base ai parametri inviati.
     */
    public function deleteAudits(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageAuditsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            $json = $this->toolsModel->deleteAudits($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Esegue l'esportazione degli audit in base ai parametri inviati.
     */
    public function exportAudits(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageAuditsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            $json = $this->toolsModel->exportAudits($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Scarica il file esportato in modo sicuro dalla cartella writable.
     *
     * @param string|null $fileName Nome del file da scaricare.
     */
    public function downloadExport(?string $fileName = null): ResponseInterface
    {
        if (empty($fileName)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        /* Assicurati che il percorso combaci con la cartella che hai scelto */
        $filePath = WRITEPATH . 'exports/' . $fileName;

        if ( ! is_file($filePath)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        return $this->response->download($filePath, null);
    }

    /**
     * Esegue le operazioni di manutenzione sul database e le tabelle.
     */
    public function optimizeTable(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            
            $table = $this->request->getPost('table');

            $ruleKey = is_array($table) ? 'table.*' : 'table';
            
            $rules = [
                $ruleKey => 'required|regex_match[/^[a-zA-Z0-9_]+$/]'
            ];

            if ( ! $this->validateData($this->request->getPost(), $rules)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            $optimizationResult = $this->toolsModel->runOptimization($table);

            if ($optimizationResult === false):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.optimizeError')]);
            endif;

            /* Scegliamo il messaggio corretto */
            $message = is_array($table) ? lang('backend/tools.messages.optimizeAllSuccess') : lang('backend/tools.messages.optimizeSuccess');

            /* tableData conterrà sempre un array (di 1 elemento o di N elementi) */
            return $this->jsonResponse(['result' => true, 'message'   => $message, 'tableData' => $optimizationResult]);

        endif;
    }

    /**
     * Esegue la generazione del backup per DB e/o file.
     */
    public function backups()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $action = $this->request->getPost('action');
            
            $rules = [
                'action' => 'required|in_list[generateBackups,deleteBackups,downloadBackups]'
            ];

            if ($action === 'deleteBackups' || $action === 'downloadBackups'):
                $rules['filename'] = 'required|regex_match[/^[a-zA-Z0-9_\-\.]+$/]';
            endif;

            /* 3. Esecuzione della validazione sicura sui dati in ingresso */
            if ( ! $this->validateData($this->request->getPost(), $rules)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            /* Intercetta l'azione di generazione inviata da JavaScript */
            if ($action === 'generateBackups'):
                
                /* Affidiamo al Model la creazione, compressione e rotazione dei file */
                $backupCreated = $this->toolsModel->generateDatabaseBackups();

                if ($backupCreated):
                    return $this->jsonResponse(['result'  => true, 'message' => lang('backend/tools.messages.generateBackupsSuccess')]);
                else:
                    return $this->jsonResponse(['result'  => false, 'message' => lang('backend/tools.messages.generateBackupsError')]);
                endif;

            /* Intercetta l'azione di eliminazione */
            elseif ($action === 'deleteBackups'):
                
                $filename = $this->request->getPost('filename');
                                
                if ($this->toolsModel->deleteBackups($filename)):
                    return $this->jsonResponse(['result'  => true, 'message' => lang('backend/tools.messages.deleteBackupsSuccess')]);
                else:
                    return $this->jsonResponse(['result'  => false, 'message' => lang('backend/tools.messages.deleteBackupsError')]);
                endif;

            elseif ($action === 'downloadBackups'):
                
                $filename = $this->request->getPost('filename');
                
                $path = WRITEPATH . 'backups/' . basename($filename);
                
                if (file_exists($path) && is_file($path)):
                    return $this->jsonResponse(['result' => true, 'downloadUrl' => base_url('backend/tools/downloadBackups/' . $filename)]);
                else:
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.backupNotFoundError')]);
                endif;

            endif;

        endif;
    }

    /**
     * Gestisce lo scaricamento fisico del file di backup
     */
    public function downloadBackups(string $filename)
    {
        /* basename() impedisce tentativi di directory traversal (sicurezza) */
        $path = WRITEPATH . 'backups/' . basename($filename);
        
        if (file_exists($path) && is_file($path)):

            /* Registrazione attività (da inserire nel Controller) */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('DOWNLOAD_BACKUP', 'tools', sprintf('Scaricato backup del database: %s', $fileName), $currentAdmin);

            return $this->response->download($path, null);
        endif;

        /* Fallback in caso di file inesistente */
        return redirect()->to(base_url('backend/dashboard'))->with('error', lang('backend/tools.messages.backupNotFoundError'));
    }
}