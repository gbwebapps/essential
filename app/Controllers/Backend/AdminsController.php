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

use App\Models\Backend\Components\GalleryOneImgModel;

/**
 * Gestisce la sezione amministrativa dedicata alla gestione degli utenti con privilegi di sistema (amministratori), controllandone permessi, sessioni e anagrafiche.
 */
class AdminsController extends BackendController 
{
    /**
     * Istanza del modello dedicato all'interazione con il database per i dati degli amministratori
     * 
     * @var AdminsModel 
     */
    protected AdminsModel $adminsModel;

    /**
     * Istanza della libreria contenente logiche di controllo e formattazione specifiche per gli amministratori
     * 
     * @var AdminsClass 
     */
    protected AdminsClass $adminsClass;

    /**
     * Istanza del modello responsabile della gestione dell'immagine di profilo (avatar) dell'amministratore
     * 
     * @var GalleryOneImgModel 
     */
    protected GalleryOneImgModel $galleryOneImgModel;

    /**
     * Inizializza il controller, configura i metadati della pagina e istanzia i modelli e le classi necessarie.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'admins';
        $this->data['entity'] = 'admins';

        $this->adminsModel = model(AdminsModel::class);
        $this->adminsClass = new AdminsClass($this->adminsModel);

        $this->galleryOneImgModel = model(GalleryOneImgModel::class);
    }

    /**
     * Renderizza la vista iniziale (dashboard/riepilogo) della sezione amministratori.
     *
     * @return string|ResponseInterface HTML renderizzato della vista index
     */
    public function index(): string|ResponseInterface
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/admins.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render('backend/admins/indexView', $this->data);
    }

    /**
     * Gestisce il caricamento e il filtraggio asincrono della tabella contenente l'elenco di tutti gli amministratori.
     *
     * @return string|ResponseInterface Risposta JSON con i dati filtrati o l'HTML della vista per l'elenco generale
     */
    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
        
            $rules = $this->adminsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->adminsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
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

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/admins.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-users"></i>';

        return $this->render('backend/admins/showAllView', $this->data);
    }

    /**
     * Gestisce la creazione di un nuovo amministratore, includendo la validazione dei dati anagrafici e l'upload dell'immagine profilo.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito del salvataggio o l'HTML del modulo di inserimento
     */
    public function add(): string|ResponseInterface
    {
        $this->data['groups'] = $this->adminsModel->getGroups();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->jsonResponse(['result' => true,'output' => view('backend/admins/partials/add/addPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->add($posts, $this->request);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if($json['result'] === true):
                $json['output'] = view('backend/admins/partials/add/addPartial', $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'add';
        
        $this->data['title'] = lang('backend/admins.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-user-plus"></i>';

        return $this->render('backend/admins/addView', $this->data);
    }

    /**
     * Gestisce la modifica di un amministratore esistente, verificandone i privilegi (protezione superadmin e cestino) e aggiornando permessi o anagrafica.
     *
     * @param string|null $uuid Identificativo univoco dell'amministratore da modificare
     * @return string|ResponseInterface Risposta JSON con l'esito dell'aggiornamento o l'HTML del modulo di modifica
     * @throws \CodeIgniter\Exceptions\PageNotFoundException Se l'identificativo non è valido o l'utente non esiste
     */
    public function edit(string $uuid = null): string|ResponseInterface
    {
        $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

        /* Recupero la lista dei gruppi disponibili per la select */
        $this->data['groups'] = $this->adminsModel->getGroups();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []]);

            if(( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/admins.errors.uuid')]);
            endif;

            $admin = $this->adminsModel->getByUUID($posts['uuid']);

            if($admin['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $admin['message']]);
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $admin['row']->superadmin === 1):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/admins.messages.protectedAdmin')]);
            endif;

            /* Caso 1: Refresh della vista parziale */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                /* Carico sia i permessi del gruppo sia le eccezioni dell'utente */
                $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $admin['row']->group_id);
                $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($posts['uuid']);

                $this->data['admin'] = $admin['row'] ?? null;
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneImgModel->getImages(['entity' => 'admins', 'uuid' => $posts['uuid']]);

                return $this->jsonResponse(['result' => true,'output' => view('backend/admins/partials/edit/editPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->editValidationRules($posts);
                        
            if ( ! $this->validateData($posts, $rules)):
                /* Catturiamo gli errori grezzi (compresi eventuali permissions.0, permissions.1) */
                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-permissions sotto la chiave unica 'permissions' per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->jsonResponse(['errors' => $cleanErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $result = $this->adminsModel->edit($posts);

            $json = ['result' => $result['result'], 'message' => $result['message']];

            /* Caso 2: Salvataggio riuscito */
            if ($result['result'] === true):

                /* Carico sia i permessi del gruppo sia le eccezioni dell'utente */
                $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $result['row']->group_id);
                $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($posts['uuid']);

                $this->data['admin'] = $result['row'];
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneImgModel->getImages(['entity' => 'admins', 'uuid' => $posts['uuid']]);
                
                $json['output'] = view('backend/admins/partials/edit/editPartial', $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        /* GET Request - Caricamento iniziale della pagina */
        if(( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.errors.uuid'))->with('class', 'light text-danger fw-bold');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'light text-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($admin['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.messages.cannotModifyDeleted'))->with('class', 'light text-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
        if ((int) $admin['row']->superadmin === 1):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.messages.protectedAdmin'))->with('class', 'light text-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;
        
        $this->data['action'] = 'edit';
        
        $this->data['title'] = lang('backend/admins.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-user-pen"></i>';

        $this->data['admin'] = $admin['row'] ?? null;
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneImgModel->getImages(['entity' => 'admins', 'uuid' => $uuid]);

        /* Caso Caricamento standard: passo le matrici separate alla vista */
        $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $admin['row']->group_id);
        $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($uuid);

        return $this->render('backend/admins/editView', $this->data);
    }

    /**
     * Ricalcola dinamicamente e restituisce l'interfaccia dei permessi quando viene selezionato un nuovo gruppo di appartenenza durante la fase di modifica.
     *
     * @return ResponseInterface Risposta JSON contenente la vista aggiornata dei permessi assegnabili
     */
    public function changeGroup(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changeGroupValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            /* 1. Recuperiamo la configurazione globale di tutti i permessi atomici esistenti */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();

            /* 2. Recuperiamo i permessi nativi associati unicamente al nuovo gruppo selezionato */
            $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $posts['group_id']);

            /* 3. Controllo di sbarramento: recuperiamo i veri dati dell'admin dal DB */
            $dbAdmin = $this->adminsModel->getByUUID($posts['uuid']);
            
            if ($dbAdmin['result'] === true && (int) $dbAdmin['row']->group_id === (int) $posts['group_id']):

                /* Se il gruppo selezionato è quello di partenza, ricarichiamo le sue vere eccezioni */
                $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($posts['uuid']);
            else:

                /* Se il gruppo è cambiato, mostriamo la configurazione pulita del nuovo gruppo */
                $this->data['admin_exceptions'] = [];
            endif;

            return $this->jsonResponse(['result' => true, 'output' => view('backend/admins/partials/edit/permissionsPartial', $this->data)]);

        endif;
    }

    /**
     * Renderizza la vista di dettaglio di un singolo amministratore, mostrando anagrafica, permessi attuali e sessioni attive.
     *
     * @param string $uuid Identificativo univoco dell'amministratore
     * @return RedirectResponse|string Redirect in caso di parametri non validi o l'HTML renderizzato della vista di dettaglio
     */
    public function show(string $uuid): RedirectResponse|string
    {
        if (( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.errors.uuid'))->with('class', 'light text-danger fw-bold');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if ($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'light text-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($admin['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.messages.cannotModifyDeleted'))->with('class', 'light text-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
        if ((int) $admin['row']->superadmin === 1):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.messages.protectedAdmin'))->with('class', 'light text-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;
        
        $this->data['action'] = 'show';

        $this->data['title'] = lang('backend/admins.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-user"></i>';

        $this->data['admin'] = $admin['row'] ?? null;
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'show';
        $this->data['images'] = $this->galleryOneImgModel->getImages(['entity' => 'admins', 'uuid' => $uuid]);

        /* Struttura per mappare i gruppi e le eccezioni */
        $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
        $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $admin['row']->group_id);
        $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($admin['row']->uuid);

        $this->data['userAgent'] = new UserAgent();
        $this->data['tokens'] = $this->adminsModel->getTokens($uuid);

        return $this->render('backend/admins/showView', $this->data);
    }

    /**
     * Elimina in modo irreversibile dal database i dati dell'amministratore e i record ad esso associati.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'eliminazione definitiva
     */
    public function hardDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->hardDelete($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Sposta temporaneamente un amministratore nel cestino, revocandone l'accesso senza cancellare i dati dal database (eliminazione logica).
     *
     * @return ResponseInterface Risposta JSON con l'esito della disattivazione
     */
    public function softDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->softDelete($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Ripristina un amministratore precedentemente inserito nel cestino, riabilitandone l'account.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione di ripristino
     */
    public function restoreDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->restoreDelete($posts);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Genera e invia via email un link temporaneo per consentire all'amministratore di reimpostare la propria password.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'invio della richiesta
     */
    public function resetPassword(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->resetPasswordValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->resetPassword($posts, $this->request);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Modifica asincronamente lo stato di un amministratore (es. da attivo a sospeso) e aggiorna la porzione di interfaccia interessata.
     *
     * @return ResponseInterface Risposta JSON con l'esito del cambio di stato e l'HTML aggiornato
     */
    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->changeStatus($posts);

            if($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if(isset($posts['context']) && $posts['context'] === 'show'):

                $this->data['admin'] = $json['admin'];

                $json['statusView'] = view('backend/admins/partials/show/changeStatusPartial', $this->data);
                $json['metaView'] = view('backend/admins/partials/common/metaDataPartial', $this->data); 

            endif;

            unset($json['admin']);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Aggiunge o rimuove un permesso specifico, generando un'eccezione rispetto alle regole predefinite del gruppo di appartenenza dell'amministratore.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione e l'interfaccia dei permessi aggiornata
     */
    public function changePermission(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changePermissionValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->adminsModel->changePermission($posts);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            $adminRow = $json['admin'];
            $this->data['admin'] = $adminRow;

            /* Ricarichiamo la situazione aggiornata dopo l'operazione sul database */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $adminRow->group_id);
            $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($adminRow->uuid);

            $json['permissionsView'] = view('backend/admins/partials/show/permissionsPartial', $this->data);
            $json['metaView'] = view('backend/admins/partials/common/metaDataPartial', $this->data); 

            unset($json['admin']);

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Recupera e ricarica i dati anagrafici e generali dell'amministratore aggiornando la singola porzione della vista di dettaglio o di modifica.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei dati generali
     */
    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
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

                    /* Generiamo i dati completi e la vista anche per il pannello permessi */
                    $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
                    $this->data['group_perms'] = $this->adminsModel->getGroupPermissions((int) $record['row']->group_id);
                    $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($posts['uuid']);
                    
                    $json['permissions_output'] = view('backend/admins/partials/edit/permissionsPartial', $this->data);
                endif;

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Ricarica i metadati di sistema dell'amministratore (date di creazione, modifica, ultimo accesso, ecc.) per l'aggiornamento dell'interfaccia.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei metadati
     */
    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
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

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Ricarica l'intera struttura dei permessi, calcolando dinamicamente la fusione tra i permessi di gruppo e le eccezioni dell'utente.
     *
     * @return ResponseInterface Risposta JSON contenente l'interfaccia aggiornata dei permessi assegnati
     */
    public function getPermissions(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->getPermissionsValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
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
                $this->data['admin_exceptions'] = $this->adminsModel->getAdminExceptions($adminRow->uuid);

                /* Renderizzazione differenziata in base al contesto della richiesta */
                if (isset($posts['context']) && $posts['context'] === 'show'):
                    $json['output'] = view('backend/admins/partials/show/permissionsPartial', $this->data);
                endif;

                if (isset($posts['context']) && $posts['context'] === 'edit'):
					/* Inseriamo l'ID del gruppo reale corrente nel JSON di risposta per sincronizzare la select lato JS */
                    $json['group_id'] = (int) $adminRow->group_id;
                    $json['output'] = view('backend/admins/partials/edit/permissionsPartial', $this->data);
                endif;

            else:

                $json = ['result' => false, 'message' => $record['message']];

            endif;

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Ricarica e restituisce la lista dei dispositivi attualmente connessi e delle sessioni attive dell'amministratore.
     *
     * @return ResponseInterface Risposta JSON contenente l'interfaccia aggiornata con l'elenco dei token
     */
    public function getTokens(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->getTokensValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
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

            return $this->jsonResponse($json);

        endif;
    }

    /**
     * Disconnette forzatamente un dispositivo remoto, invalidando ed eliminando il token di sessione corrispondente.
     *
     * @return ResponseInterface Risposta JSON con l'esito della disconnessione e l'elenco aggiornato dei dispositivi attivi
     */
    public function deleteToken(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->deleteTokenValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/admins.messages.validationToastErrors'), $errorMessage)]);
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

            return $this->jsonResponse($json);

        endif;
    }
}
