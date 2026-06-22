<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\UserAgent;

use App\Models\Backend\AdminsModel;
use App\Libraries\Backend\AdminsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class AdminsController
 *
 * Controller centrale per la gestione completa delle utenze amministrative (Admins).
 * Coordina le operazioni CRUD, l'assegnazione dei permessi RBAC granulari, la sicurezza 
 * delle sessioni, la revoca dei token e i caricamenti dinamici delle viste asincrone via AJAX.
 */
class AdminsController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza e manipolazione dei dati degli amministratori.
     * 
     * @var AdminsModel 
     */
    protected AdminsModel $adminsModel;

    /**
     * Istanza della libreria logica per l'elaborazione dei flussi e delle operazioni del modulo.
     * 
     * @var AdminsClass 
     */
    protected AdminsClass $adminsClass;

    /**
     * Inizializza il controller impostando il contesto del modulo e istanziando modello e libreria specifici.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'admins';

        $this->adminsModel = model(AdminsModel::class);
        $this->adminsClass = new AdminsClass($this->adminsModel);
    }

    /**
     * Renderizza la pagina principale del modulo di gestione degli amministratori.
     *
     * @return string La vista HTML iniziale dell'indice.
     */
    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/admins.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render('backend/admins/indexView', $this->data);
    }

    /**
     * Gestisce la visualizzazione della tabella degli amministratori (GET) e il caricamento asincrono filtrato dei record (POST AJAX).
     *
     * @return string|ResponseInterface La vista HTML completa o la risposta JSON parziale con i dati tabellari.
     */
    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
        
            $rules = $this->adminsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $rules = $this->adminsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):

                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());

                return $this->response->setJSON(['errors' => $formattedErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;
            
            $this->data['data'] = $this->adminsModel->getData($posts);

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;

                $json['output'] = view('backend/admins/partials/showAll/showAllPartial', $this->data);
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/admins.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-users"></i>';

        return $this->render('backend/admins/showAllView', $this->data);
    }

    /**
     * Gestisce la maschera di inserimento di un nuovo amministratore (GET), l'azione di reset (AJAX) e il salvataggio dei dati (POST AJAX).
     *
     * @return string|ResponseInterface La vista HTML completa o la risposta JSON parziale con l'esito dell'operazione.
     */
    public function add(): string|ResponseInterface
    {
        $this->data['groups'] = $this->adminsModel->getGroups();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->response->setJSON(['result' => true,'output' => view('backend/admins/partials/add/addPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->add($posts, $this->request);

            if ($json['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $json['message']]);
            endif;

            if($json['result'] === true):
                $json['output'] = view('backend/admins/partials/add/addPartial', $this->data);
            endif;

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'add';
        
        $this->data['title'] = lang('backend/admins.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-user-plus"></i>';

        return $this->render('backend/admins/addView', $this->data);
    }

    /**
     * Gestisce la maschera di modifica di un amministratore esistente (GET), il refresh parziale (AJAX) e il salvataggio dei dati (POST AJAX).
     *
     * @param string|null $uuid L'identificativo unico dell'amministratore da modificare (richiesto per GET).
     * @return string|ResponseInterface La vista HTML completa o la risposta JSON parziale con l'esito dell'operazione.
     */
    public function edit(string $uuid = null): string|ResponseInterface
    {
        $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

        /* Recupero la lista dei gruppi disponibili per la select */
        $this->data['groups'] = $this->adminsModel->getGroups();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            if(( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/admins.global.wrongUUIDFormat')]);
            endif;

            $admin = $this->adminsModel->getByUUID($posts['uuid']);

            if($admin['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $admin['message']]);
            endif;

            /* Caso 1: Refresh della vista parziale */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                /* Carico sia i permessi del gruppo sia le eccezioni dell'utente */
                $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $admin['row']->group_id);
                $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($posts['uuid']);
                $this->data['admin'] = $admin['row'];

                return $this->response->setJSON(['result' => true,'output' => view('backend/admins/partials/edit/editPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->editValidationRules($posts);
                        
            if ( ! $this->validateData($posts, $rules)):
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->response->setJSON(['errors' => $cleanErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $result = $this->adminsModel->edit($posts);

            $json = ['result' => $result['result'], 'message' => $result['message']];

            /* Caso 2: Salvataggio riuscito */
            if ($result['result'] === true):
                $this->data['admin'] = $result['row'];
                /* Rigenero i dati corretti per la matrice aggiornata interpellando il Model */
                $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $result['row']->group_id);
                $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($posts['uuid']);
                
                $json['output'] = view('backend/admins/partials/edit/editPartial', $this->data);
            endif;

            return $this->response->setJSON($json);

        endif;

        /* GET Request - Caricamento iniziale della pagina */
        if(( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.global.wrongUUIDFormat'))->with('class', 'danger');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'danger');
        endif;
        
        $this->data['action'] = 'edit';
        $this->data['title'] = lang('backend/admins.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-user-pen"></i>';

        $this->data['admin'] = $admin['row'] ?? null;
        $this->data['uuid'] = $uuid;

        /* Caso Caricamento standard: passo le matrici separate alla vista */
        $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $admin['row']->group_id);
        $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($uuid);

        return $this->render('backend/admins/editView', $this->data);
    }

    /**
     * Gestisce il cambio dinamico del gruppo tramite richiesta AJAX.
     *
     * Il metodo intercetta la selezione di un nuovo gruppo dall'interfaccia,
     * ne valida i dati e restituisce il codice HTML parziale della matrice dei permessi
     * allineata ai poteri nativi del nuovo gruppo, azzerando visivamente le eccezioni.
     *
     * @return ResponseInterface Risposta JSON contenente il parziale HTML aggiornato o gli errori di validazione.
     */
    public function changeGroup(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changeGroupValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* 1. Recuperiamo la configurazione globale di tutti i permessi atomici esistenti */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

            /* 2. Recuperiamo i permessi nativi associati unicamente al nuovo gruppo selezionato */
            $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $posts['group_id']);

            /* 3. Svuotiamo le eccezioni dell'utente: il nuovo gruppo deve mostrare la sua configurazione pulita */
            $this->data['user_exceptions'] = [];

            /* Generiamo il JSON di risposta contenente il partial HTML aggiornato */
            return $this->response->setJSON(['result' => true, 'output' => view('backend/admins/partials/edit/permissionsPartial', $this->data)]);

        endif;
    }

    /**
     * Mostra la scheda informativa completa di un amministratore, dettagliando permessi, dispositivi e token attivi.
     *
     * @param string $uuid L'identificativo univoco dell'amministratore da visualizzare.
     * @return RedirectResponse|string Oggetto di reindirizzamento in caso di errore o la vista HTML dei dettagli.
     */
    public function show(string $uuid): RedirectResponse|string
    {
        if (( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.global.wrongUUIDFormat'))->with('class', 'danger');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if ($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'danger');
        endif;
        
        $this->data['action'] = 'show';
        $this->data['title'] = lang('backend/admins.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        $adminRow = $admin['row'];
        $this->data['admin'] = $adminRow;
        $this->data['uuid'] = $uuid;

        /* Struttura per mappare i gruppi e le eccezioni */
        $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
        $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $adminRow->group_id);
        $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($adminRow->uuid);

        $this->data['userAgent'] = new UserAgent();
        $this->data['tokens'] = $this->adminsModel->getTokens($uuid);

        return $this->render('backend/admins/showView', $this->data);
    }

    /**
     * Esegue la rimozione o cancellazione di un amministratore tramite richiesta asincrona.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito dell'operazione.
     */
    public function delete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->del($posts);

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Avvia la procedura amministrativa di invio o rigenerazione guidata della password di un operatore.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione.
     */
    public function resetPassword(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->resetPasswordValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->resetPassword($posts, $this->request);

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Modifica lo stato di attivazione (attivo/sospeso) di un amministratore e ne aggiorna i relativi partial grafici.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito e i frammenti HTML aggiornati.
     */
    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->changeStatus($posts);

            if($json['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $json['message']]);
            endif;

            if(isset($posts['context']) && $posts['context'] === 'show'):

                $this->data['admin'] = $json['admin'];

                $json['statusView'] = view('backend/admins/partials/show/changeStatusPartial', $this->data);
                $json['metaView'] = view('backend/admins/partials/common/metaDataPartial', $this->data); 

            endif;

            unset($json['admin']);

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Aggiorna o alterna l'assegnazione di un singolo permesso RBAC per l'utente selezionato.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito e le viste parziali dei permessi e metadati aggiornate.
     */
    public function changePermission(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changePermissionValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->changePermission($posts);

            if ($json['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $json['message']]);
            endif;

            $adminRow = $json['admin'];
            $this->data['admin'] = $adminRow;

            /* Ricarichiamo la situazione aggiornata dopo l'operazione sul database */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $adminRow->group_id);
            $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($adminRow->uuid);

            $json['permissionsView'] = view('backend/admins/partials/show/permissionsPartial', $this->data);
            $json['metaView'] = view('backend/admins/partials/common/metaDataPartial', $this->data); 

            unset($json['admin']);

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Recupera e renderizza asincronamente il frammento HTML dei dati anagrafici in base al contesto operativo richiesto (show/edit).
     *
     * @return ResponseInterface Risposta JSON con il codice HTML parziale renderizzato.
     */
    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $record = $this->adminsModel->getByUUID($posts['uuid']);

            if($record['result'] === true):

                $json = ['result' => true];

                $this->data['admin'] = $record['row'];

                if(isset($posts['context']) && $posts['context'] === 'show'):
                    $json['output'] = view('backend/admins/partials/show/generalDataPartial', $this->data);
                endif;

                if(isset($posts['context']) && $posts['context'] === 'edit'):
                    $this->data['groups'] = $this->adminsModel->getGroups();
                    $json['output'] = view('backend/admins/partials/edit/generalDataPartial', $this->data);
                endif;

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Recupera e renderizza asincronamente il frammento HTML relativo ai metadati cronologici di tracciamento del record.
     *
     * @return ResponseInterface Risposta JSON con il codice HTML parziale renderizzato.
     */
    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $record = $this->adminsModel->getByUUID($posts['uuid']);

            if($record['result'] === true):

                $json = ['result' => true];

                $this->data['admin'] = $record['row'];

                $json['output'] = view('backend/admins/partials/common/metaDataPartial', $this->data);

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Recupera e renderizza asincronamente il blocco HTML contenente l'elenco dei permessi dell'utente (show/edit).
     *
     * @return ResponseInterface Risposta JSON con il codice HTML parziale renderizzato.
     */
    public function getPermissions(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->getPermissionsValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $record = $this->adminsModel->getByUUID($posts['uuid']);

            if ($record['result'] === true):

                $json = ['result' => true];
                $adminRow = $record['row'];

                $this->data['admin'] = $adminRow;

                /* 1. Recuperiamo la mappa globale dei permessi atomici */
                $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

                /* 2. Recuperiamo i permessi nativi del gruppo dell'utente */
                $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $adminRow->group_id);

                /* 3. Recuperiamo le eccezioni specifiche memorizzate nel DB per questo utente */
                $this->data['user_exceptions'] = $this->adminsModel->getUserExceptions($adminRow->uuid);

                /* Renderizzazione differenziata in base al contesto della richiesta */
                if (isset($posts['context']) && $posts['context'] === 'show'):
                    $json['output'] = view('backend/admins/partials/show/permissionsPartial', $this->data);
                endif;

                if (isset($posts['context']) && $posts['context'] === 'edit'):
                    $json['output'] = view('backend/admins/partials/edit/permissionsPartial', $this->data);
                endif;

            else:

                $json = ['result' => false, 'message' => $record['message']];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Recupera e renderizza asincronamente il frammento HTML della tabella dei token di sicurezza attivi dell'utente.
     *
     * @return ResponseInterface Risposta JSON con il codice HTML parziale renderizzato.
     */
    public function getTokens(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->getTokensValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $record = $this->adminsModel->getByUUID($posts['uuid']);

            if($record['result'] === true):

                $json = ['result' => true];

                $this->data['admin'] = $record['row']; 

                $this->data['userAgent'] = new UserAgent();
                $this->data['tokens'] = $this->adminsModel->getTokens($posts['uuid']);

                $json['output'] = view('backend/admins/partials/show/tokensPartial', $this->data);

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Revoca e rimuove in modo permanente un determinato token (sessione o cookie persistente) associato all'amministratore.
     *
     * @return ResponseInterface Risposta JSON con l'esito e la tabella parziale dei token aggiornata.
     */
    public function deleteToken(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->deleteTokenValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $result = $this->adminsModel->deleteToken($posts);

            if($result['result'] === true):

                $this->data['admin'] = $result['admin'];
                $this->data['userAgent'] = new UserAgent();
                $this->data['tokens'] = $this->adminsModel->getTokens($posts['uuid']);

                $json = ['result' => true, 'message' => $result['message']];
                $json['tokensView'] = view('backend/admins/partials/show/tokensPartial', $this->data);

            else:

                $json = ['result' => false, 'message' => $result['message']];

            endif;

            return $this->response->setJSON($json);

        endif;
    }
}
