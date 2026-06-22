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

    public function openAdd(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

            /* Generiamo la vista parziale che contiene il form per creare un nuovo gruppo */
            $output = view('backend/groups/partials/index/addGroupPartial', $this->data);

            return $this->response->setJSON(['result' => true, 'output' => $output]);

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

            return $this->response->setJSON(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Risponde alla chiamata AJAX (Secondo livello) al click sul singolo gruppo.
     * Restituisce il form di modifica pre-popolato con la matrice dei permessi del gruppo.
     */
    public function getGroup(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            // qui va fatto il controllo posts

            $groupId = (int) $this->request->getPost('id');

            /* Recuperiamo i dati del gruppo (nome e descrizione) */
            $group = $this->groupsModel->getGroupById($groupId);
            if ( ! $group):
                return $this->response->setJSON(['result' => false, 'message' => 'Gruppo non trovato.']);
            endif; 

            $this->data['group'] = $group;

            /* Recuperiamo la mappa globale dei permessi e i permessi attivi di questo gruppo */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            $this->data['group_perms'] = $this->groupsModel->getGroup($groupId);

            /* Generiamo il sotto-parziale di modifica */
            $output = view('backend/groups/partials/index/getGroupPartial', $this->data);

            return $this->response->setJSON(['result' => true, 'output' => $output]);

        endif;
    }

    /* Gestisce la chiamata ajax per l'aggiunta di un nuovo gruppo con nome, descrizione e permessi, poi vediamo cosa fargli restituire */
    public function add(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();
            $rules = $this->groupsModel->addValidationRules();

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->response->setJSON(['errors' => $cleanErrors, 'message' => lang('backend/groups.messages.validationErrors')]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->add($posts);

            return $this->response->setJSON($result);

        endif;
    }

    /* Gestisce la chiamata ajax per l'aggiornamento di un gruppo con nome, descrizione e permessi, poi vediamo cosa fargli restituire */
    public function edit(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();
            $rules = $this->groupsModel->editValidationRules($posts);

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->response->setJSON(['errors' => $cleanErrors, 'message' => lang('backend/groups.messages.validationErrors')]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->edit($posts);

            return $this->response->setJSON($result);

        endif;
    }

    /* Gestisce la chiamata ajax per l'eliminazione di un gruppo, poi vediamo cosa fargli restituire */
    public function del(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();
            $rules = $this->groupsModel->delValidationRules($posts);

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->del($posts);

            return $this->response->setJSON($result);

        endif;
    }
}
