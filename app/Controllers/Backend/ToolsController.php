<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\ToolsModel;
use App\Libraries\Backend\ToolsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Controller per la gestione degli strumenti e della manutenzione del sistema.
 * Fornisce funzionalità per l'ottimizzazione del database, la rotazione dei log, la gestione dei backup,
 * e il monitoraggio o la pulizia dello spazio occupato dalle cartelle temporanee dell'applicativo.
 */
class ToolsController extends BackendController 
{
    /**
     * @var ToolsModel Istanza del modello responsabile dell'estrazione dei dati di monitoraggio, 
     * della compattazione delle tabelle, e delle operazioni di pulizia fisica sul file system.
     */
    protected ToolsModel $toolsModel;

    /**
     * @var ToolsClass Istanza della libreria contenente routine di calcolo (es. conversioni byte) 
     * e formattazione tecnica per i dati esposti nel pannello strumenti.
     */
    protected ToolsClass $toolsClass;

    /**
     * @var array Struttura di sbarramento: elenca unicamente le sezioni (ambienti) 
     * che possono essere invocate e caricate asincronamente dall'utente.
     */
    protected array $allowedEnvs = ['system', 'manageAudits', 'dbMaintenance', 'backups', 'cleanSpace', 'manageLogs'];

    /**
     * Inizializzazione standard che estende il BackendController, configurando il namespace di lavoro
     * (controller = 'tools') e instanziando i modelli preposti alle funzioni di diagnostica e manutenzione.
     *
     * @param RequestInterface $request Oggetto contenente i dati della richiesta.
     * @param ResponseInterface $response Oggetto deputato alla generazione della risposta HTTP.
     * @param LoggerInterface $logger Sistema di tracciamento degli eventi.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'tools';

        $this->toolsModel = model(ToolsModel::class);
        $this->toolsClass = new ToolsClass($this->toolsModel);
    }

    /**
     * Elabora la vista di atterraggio principale per l'area strumenti.
     * Configura il layout base in attesa delle richieste asincrone che popoleranno i singoli pannelli di servizio.
     *
     * @return string Layout HTML iniziale della sezione.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/tools.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-screwdriver-wrench"></i>';

        return $this->render('backend/tools/indexView', $this->data);
    }

    /**
     * Intercetta le richieste AJAX per l'apertura dinamica di un determinato ambiente di manutenzione (es. dbMaintenance, cleanSpace).
     * Valida la legittimità della richiesta e interroga il Model pertinente per estrarre le statistiche correnti, 
     * restituendo il parziale HTML pronto per l'iniezione nel DOM.
     *
     * @return ResponseInterface Risposta JSON con l'HTML del pannello popolato con i dati in tempo reale.
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
                $this->data['stats'] = $this->toolsModel->getAuditsStats(); 
            elseif ($env === 'manageLogs'):
                $this->data['stats'] = $this->toolsModel->getLogsStats(); 
            elseif ($env === 'dbMaintenance'):
                $this->data['database'] = $this->toolsModel->getDatabase();
                $this->data['tables'] = $this->toolsModel->getTablesStatus();
            elseif ($env === 'backups'):
                $this->data['backups'] = $this->toolsModel->getBackups();
            elseif ($env === 'cleanSpace'):
                $this->data['folders'] = $this->toolsModel->getWritableFoldersStatus();
            elseif ($env === 'system'):
                $this->data['sysInfo'] = $this->toolsModel->getSystemInfo();
            endif;

            return $this->jsonResponse(['result' => true, 'output' => view('backend/tools/partials/index/' . $env . 'ToolsPartial', $this->data)]);

        endif;
    }

    /**
     * Esegue una validazione preventiva sulle date fornite per la cancellazione degli Audit Log.
     * Controlla l'esistenza di record nel periodo indicato e restituisce il totale calcolato 
     * per consentire all'interfaccia client (JS) di richiedere conferma all'amministratore prima di procedere.
     *
     * @return ResponseInterface Risposta JSON contenente il conteggio dei record identificati e il testo localizzato per il prompt di conferma.
     */
    public function validateAuditsDateRequest(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageAuditsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            /* Esecuzione query preventiva e recupero del payload (conteggio e date) */
            $auditData = $this->toolsModel->countAuditsToDelete($posts);

            if ($auditData === false):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.startDateAfterEndDate')]);
            endif;

            /* Formattazione umana delle date delegata al livello di presentazione */
            $from = convertDate($auditData['from'], 'conversational');
            $to = convertDate($auditData['to'], 'conversational');

            /* Iniezione dinamica dei parametri: 1° %s (da), 2° %s (a), 3° %d (totale) */
            $confirmMessage = sprintf(lang('backend/tools.messages.areYouSureToDeleteAudits'), $from, $to, $auditData['count']);

            $json = ['result' => true, 'count' => $auditData['count'], 'confirmMessage' => $confirmMessage];
            
            if ($auditData['count'] === 0):
                $json['noDataMessage'] = lang('backend/tools.messages.noAuditsFound');
            endif;

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Procedura esecutiva per l'eliminazione massiva dei record di Audit all'interno dell'intervallo temporale validato.
     * Interfaccia direttamente con il Model per l'esecuzione della query distruttiva.
     *
     * @return ResponseInterface Risposta JSON indicante il successo dell'operazione di pulizia.
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
     * Esegue una validazione sulle date immesse per la procedura di rimozione dei file di Log fisici.
     * Identifica quanti documenti corrispondono ai parametri e trasmette i dati al client per innescare 
     * la finestra modale di sicurezza (Double Opt-In).
     *
     * @return ResponseInterface Risposta JSON con le statistiche dei file rintracciati.
     */
    public function validateLogsDateRequest(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageLogsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            /* Esecuzione query preventiva e recupero del payload (conteggio e date) */
            $logData = $this->toolsModel->countLogsToDelete($posts);

            if ($logData === false):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.startDateAfterEndDate')]);
            endif;

            /* Formattazione umana delle date delegata al livello di presentazione */
            $from = convertDate($logData['from'], 'conversational');
            $to = convertDate($logData['to'], 'conversational');

            /* Iniezione dinamica dei parametri: 1° %s (da), 2° %s (a), 3° %d (totale) */
            $confirmMessage = sprintf(lang('backend/tools.messages.areYouSureToDeleteLogs'), $from, $to, $logData['count']);

            $json = ['result' => true, 'count' => $logData['count'], 'confirmMessage' => $confirmMessage];
            
            if ($logData['count'] === 0):
                $json['noDataMessage'] = lang('backend/tools.messages.noLogsFound');
            endif;

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Processa l'eliminazione fisica dei file di Log applicativi (su file system) generati nel periodo richiesto.
     *
     * @return ResponseInterface Risposta JSON con l'esito della cancellazione dei file.
     */
    public function deleteLogs(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->toolsModel->validateManageLogsRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            $json = $this->toolsModel->deleteLogs($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Gestisce la richiesta asincrona per l'ottimizzazione e la deframmentazione delle tabelle del database MySQL.
     * Supporta sia il comando rivolto a una singola tabella sia un array per processi massivi (es. "ottimizza tutto").
     *
     * @return ResponseInterface Risposta JSON contenente lo status dell'operazione e i dati post-ottimizzazione.
     */
    public function optimizeTable(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            
            $table = $this->request->getPost('table');

            $ruleKey = is_array($table) ? 'table.*' : 'table';
            
            $rules = [$ruleKey => 'required|regex_match[/^[a-zA-Z0-9_]+$/]'];

            if ( ! $this->validateData($this->request->getPost(), $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/tools.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $optimizationResult = $this->toolsModel->runOptimization($table);

            if ($optimizationResult === false):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.optimizeError')]);
            endif;

            /* Scegliamo il messaggio corretto */
            $message = is_array($table) ? lang('backend/tools.messages.optimizeAllSuccess') : sprintf(lang('backend/tools.messages.optimizeSuccess'), $table);

            /* tableData conterrà sempre un array (di 1 elemento o di N elementi) */
            return $this->jsonResponse(['result' => true, 'message'   => $message, 'tableData' => $optimizationResult]);

        endif;
    }

    /**
     * Sistema di smistamento (router interno) per le operazioni sui backup del database.
     * Tramite la direttiva 'action', gestisce validazioni e reindirizza logicamente il flusso verso:
     * creazione (dump SQL + rotazione), cancellazione, o validazione preparatoria per il download.
     *
     * @return ResponseInterface Payload JSON strutturato in base all'esito dell'azione specificata.
     */
    public function backups()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $action = $this->request->getPost('action');
            
            $rules = ['action' => 'required|in_list[generateBackups,deleteBackups,downloadBackups]'];

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
                
                /* Sanificazione immediata dell'input */
                $filename = basename($this->request->getPost('filename'));
                                
                if ($this->toolsModel->deleteBackups($filename)):
                    return $this->jsonResponse(['result'  => true, 'message' => lang('backend/tools.messages.deleteBackupsSuccess')]);
                else:
                    return $this->jsonResponse(['result'  => false, 'message' => lang('backend/tools.messages.deleteBackupsError')]);
                endif;

            elseif ($action === 'downloadBackups'):
                
                /* Sanificazione immediata dell'input */
                $filename = basename($this->request->getPost('filename'));
                
                $path = WRITEPATH . 'backups/database/' . $filename;
                
                if (is_file($path)):
                    return $this->jsonResponse(['result' => true, 'downloadUrl' => base_url('backend/tools/downloadBackups/' . $filename)]);
                else:
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.backupNotFoundError')]);
                endif;

            endif;

        endif;
    }

    /**
     * Metodo di endpoint puro (GET) che gestisce l'output in streaming del file compresso di backup.
     * Implementa restrizioni di base contro il directory traversal (basename) e logga l'azione di scaricamento a scopo di tracciatura.
     *
     * @param string $filename Il nome testuale dell'archivio da trasferire.
     * @return ResponseInterface Risposta HTTP configurata per il download (o redirect in caso di file compromesso/assente).
     */
    public function downloadBackups(string $filename)
    {
        /* basename() impedisce tentativi di directory traversal (sicurezza) */
        $path = WRITEPATH . 'backups/database/' . basename($filename);
        
        if (file_exists($path) && is_file($path)):

            /* Registrazione attività (da inserire nel Controller) */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('DOWNLOAD_BACKUP', 'tools', sprintf('Scaricato backup del database: %s', $fileName), $currentAdmin);

            return $this->response->download($path, null);
        endif;

        /* Fallback in caso di file inesistente */
        return redirect()->to(base_url('backend/dashboard'))->with('error', lang('backend/tools.messages.backupNotFoundError'));
    }

    /**
     * Coordina le operazioni di svuotamento (flush) delle directory temporanee o di staging (es. /writable/uploads/staging).
     * Impiega un filtro regex per evitare l'esecuzione di cancellazioni fuori perimetro (directory traversal escape).
     *
     * @return ResponseInterface Risposta JSON ritornata dal Model attestante l'esito della pulizia della cartella.
     */
    public function cleanFolder(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            
            $folder = $this->request->getPost('folder');
            
            /* La regex consente lettere, numeri, underscore, trattini e lo slash per le sottocartelle */
            $rules = ['folder' => 'required|regex_match[/^[a-zA-Z0-9_\-\/]+$/]'];

            if ( ! $this->validateData($this->request->getPost(), $rules)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/tools.messages.validationErrors')]);
            endif;

            /* L'esito e il messaggio arrivano direttamente dal Model */
            $cleanResult = $this->toolsModel->cleanWritableFolder($folder);

            return $this->jsonResponse($cleanResult);

        endif;
    }
}