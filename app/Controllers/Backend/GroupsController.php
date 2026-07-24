<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\GroupsModel;
use App\Libraries\Backend\GroupsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class GroupsController
 *
 * Controller dedicato alla gestione delle impostazioni globali e delle configurazioni di sistema del Backend.
 */
class GroupsController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza delle impostazioni di sistema.
     * 
     * @var GroupsModel 
     */
    protected GroupsModel $groupsModel;

    /**
     * Istanza della libreria logica per l'elaborazione delle configurazioni.
     * 
     * @var GroupsClass 
     */
    protected GroupsClass $groupsClass;

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

        $this->data['controller'] = 'groups';

        $this->groupsModel = model(GroupsModel::class);
        $this->groupsClass = new GroupsClass($this->groupsModel);
    }

    /**
     * Renderizza la pagina principale (Dashboard) del modulo gruppi.
     * Carica in sincrono solo la struttura e i permessi vuoti per la sezione di aggiunta.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/groups.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-user-shield"></i>';

        return $this->render('backend/groups/indexView', $this->data);
    }

    /* Effettua la chiamata asincrona per l'apertura del pannello di aggiunta nuovo gruppo */
    public function openAdd(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

            /* Generiamo la vista parziale che contiene il form per creare un nuovo gruppo */
            $output = view('backend/groups/partials/index/addGroupPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Risponde alla chiamata AJAX (Primo livello) al click su "Lista gruppi".
     * Restituisce lo scheletro dell'accordion con l'elenco reale dei gruppi.
     */
    public function getGroups(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Carichiamo i gruppi dal model solo in questo momento */
            $this->data['groups'] = $this->groupsModel->getGroups();

            /* Generiamo la vista parziale che contiene l'elenco dei soli macro-bottoni dell'accordion */
            $output = view('backend/groups/partials/index/getGroupsPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Risponde alla chiamata AJAX (Secondo livello) al click sul singolo gruppo.
     * Restituisce il form di modifica pre-popolato con la matrice dei permessi del gruppo.
     */
    public function getGroup(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->groupsModel->getGroupByIdValidationRules(); 

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/groups.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Recuperiamo i dati del gruppo (nome e descrizione) */
            $group = $this->groupsModel->getGroupById($posts);
            if ( ! $group):
                return $this->jsonResponse(['result' => false, 'message' => 'Gruppo non trovato.']);
            endif; 

            $this->data['group'] = $group;

            /* Recuperiamo la mappa globale dei permessi e i permessi attivi di questo gruppo */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            $this->data['group_perms'] = $this->groupsModel->getGroup((int) $posts['id']);

            /* Generiamo il sotto-parziale di modifica */
            $output = view('backend/groups/partials/index/getGroupPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    /* Gestisce la chiamata ajax per l'aggiunta di un nuovo gruppo con nome, descrizione e permessi, poi vediamo cosa fargli restituire */
    public function add(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                
                $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
                $output = view('backend/groups/partials/index/addGroupPartial', $this->data);

                return $this->jsonResponse(['result' => true, 'output' => $output]);
            endif;

            $rules = $this->groupsModel->addValidationRules();

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->jsonResponse(['errors' => $cleanErrors, 'message' => lang('backend/groups.messages.validationErrors')]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->add($posts);

            return $this->jsonResponse($result);

        endif;
    }

    /* Gestisce la chiamata ajax per l'aggiornamento di un gruppo con nome, descrizione e permessi, poi vediamo cosa fargli restituire */
    public function edit(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();

            /* CASO 1: Ripristino dei dati originali dal database (Refresh) */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                /* Recuperiamo i record freschi dal Model usando l'ID del gruppo */
                $groupRow = $this->groupsModel->getGroupById($posts);
                
                if ( ! $groupRow):
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.messages.notFound')]);
                endif;

                $this->data['group'] = $groupRow;
                $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
                $this->data['group_perms'] = $this->groupsModel->getGroup((int) $groupRow->id);

                /* Rigeneriamo lo stesso parziale HTML usato per il caricamento iniziale */
                $output = view('backend/groups/partials/index/getGroupPartial', $this->data);

                return $this->jsonResponse(['result' => true, 'output' => $output]);

            endif;

            /* CASO 2: Salvataggio standard dei dati */
            $rules = $this->groupsModel->editValidationRules($posts);

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->jsonResponse(['errors' => $cleanErrors, 'message' => lang('backend/groups.messages.validationErrors')]);
            endif;

            /* Esecuzione della logica di modifica con sbarramento interno */
            $result = $this->groupsModel->edit($posts);

            return $this->jsonResponse($result);

        endif;
    }

    /* Gestisce la chiamata ajax per l'eliminazione di un gruppo */
    public function del(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->groupsModel->delValidationRules();

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/groups.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->del($posts);

            return $this->jsonResponse($result);

        endif;
    }

    /* Chiamata asincrona con click sulla barra Eccezioni per visualizzare il campo di autocomplete per la ricerca di un admin  */
    public function openExceptions(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Generiamo la vista parziale che contiene il campo per cercare un utente */
            $output = view('backend/groups/partials/index/exceptionsPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function getDropdownAdmins(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->groupsModel->dropdownAdminsRules();

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/groups.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Carichiamo gli amministratori filtrati dal model passando la query */
            $this->data['admins'] = $this->groupsModel->getDropdownAdmins($posts);

            /* Generiamo la vista parziale che conterrà il ciclo foreach per il dropdown */
            $output = view('backend/groups/partials/index/getDropdownAdminsPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function getAdminPermissions(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->groupsModel->adminPermissionsValidationRules();

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/groups.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Recuperiamo i dettagli dell'amministratore (ci servirà il suo group_id) */
            $admin = $this->groupsModel->getAdminByUuid($posts);
            if ( ! $admin):
                return $this->jsonResponse(['result' => false, 'message' => 'Amministratore non trovato.']);
            endif;

            $this->data['uuid'] = $posts['uuid'];

            $this->data['name'] = $admin['name'];
            
            /* 1. Mappa globale dei permessi dal file di configurazione */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            
            /* 2. Permessi che il suo gruppo possiede già di base sul database */
            $this->data['group_perms'] = $this->groupsModel->getGroupPermissionsArray((int) $admin['group_id']);
            
            /* 3. Eccezioni specifiche già salvate per questo amministratore sul database */
            $this->data['admin_exceptions'] = $this->groupsModel->getAdminExceptionsArray($posts['uuid']);

            /* Generiamo la vista parziale con la griglia completa */
            $output = view('backend/groups/partials/index/getAdminPermissionsPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function saveExceptions(): ResponseInterface
    {
        /* Verifichiamo che la richiesta sia esclusivamente AJAX e POST */
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            /* Validazione preliminare dell'UUID dell'amministratore */
            if (( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/global.messages.wrongUUIDFormat')]);
            endif;

            /* Recuperiamo le regole di validazione specifiche per le eccezioni dal Model */
            $rules = $this->groupsModel->saveExceptionsValidationRules($posts);
            
            if ( ! $this->validateData($posts, $rules)):

                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-errors di 'permissions.*' sotto la chiave unica per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->jsonResponse(['errors' => $cleanErrors, 'message' => lang('backend/global.messages.validationErrors')]);
            endif;

            /* Eseguiamo il salvataggio dei delta sul database tramite il Model */
            $result = $this->groupsModel->saveExceptions($posts);

            return $this->jsonResponse(['result'  => $result['result'], 'message' => $result['message']]);

        endif;
    }
}
