<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\GroupsModel;
use App\Libraries\Backend\GroupsClass;
use App\Controllers\Backend\BackendController; 

/**
 * Controller per la gestione dei gruppi di amministratori e dei relativi livelli di accesso (ACL).
 * 
 * Permette la creazione, modifica ed eliminazione dei gruppi, l'assegnazione dei permessi di base 
 * e la configurazione di eccezioni specifiche per singoli amministratori.
 */
class GroupsController extends BackendController 
{
    /**
     * @var GroupsModel Istanza del modello per le operazioni CRUD sui gruppi e la gestione delle tabelle dei permessi associati.
     */
    protected GroupsModel $groupsModel;

    /**
     * @var GroupsClass Istanza della libreria helper contenente funzioni per la formattazione dei dati relativi ai gruppi.
     */ 
    protected GroupsClass $groupsClass;

    /**
     * Inizializza le dipendenze specifiche per la gestione dei gruppi, caricando le relative classi e modelli.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP.
     * @param ResponseInterface $response L'oggetto rappresentante la risposta HTTP.
     * @param LoggerInterface $logger Istanza del sistema di log.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'groups';

        $this->groupsModel = model(GroupsModel::class);
        $this->groupsClass = new GroupsClass($this->groupsModel);
    }

    /**
     * Genera la vista principale per il modulo gruppi, predisponendo i metadati della pagina.
     *
     * @return string HTML compilato della vista index.
     */
    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/groups.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-user-shield"></i>';

        return $this->render('backend/groups/indexView', $this->data);
    }

    /**
     * Gestisce la richiesta AJAX per l'apertura del modulo di inserimento di un nuovo gruppo.
     * Estrae la mappa globale dei permessi per generare i controlli dell'interfaccia.
     *
     * @return ResponseInterface Risposta JSON contenente la porzione di codice HTML del modulo.
     */
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
     * Recupera asincronamente l'elenco completo dei gruppi dal database.
     * 
     * Viene impiegato per generare la struttura ad accordion nella vista principale senza forzare un refresh della pagina.
     *
     * @return ResponseInterface Risposta JSON con l'HTML contenente l'elenco dei gruppi.
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
     * Richiede i dettagli di uno specifico gruppo (identificato tramite ID) per il popolamento del modulo di modifica.
     * Incrocia la mappa dei permessi globali con le impostazioni attuali del gruppo.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML del modulo di aggiornamento precompilato.
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

    /**
     * Processa l'inserimento di un nuovo record di gruppo nel database previa validazione.
     * Prevede anche un percorso alternativo per resettare e ricaricare il form iniziale.
     *
     * @return ResponseInterface Risposta JSON indicante il successo, il fallimento o gli errori di validazione del payload.
     */
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

    /**
     * Gestisce il salvataggio delle modifiche applicate a un gruppo esistente.
     * Include una direttiva specifica per annullare l'azione e ricaricare asincronamente i dati originali persistenti nel DB.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione di aggiornamento o il rinfresco del blocco HTML.
     */
    public function edit(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Raccolta dati e regole per la validazione standardizzata */ 
            $posts = $this->request->getPost();

            /* CASO 1: Ripristino dei dati originali dal database (Refresh) */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                if ( ! isset($posts['id']) || ( ! is_numeric($posts['id']) ) || (int) $posts['id'] <= 0):
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.errors.wrongID')]);
                endif;

                /* Recuperiamo i record freschi dal Model usando l'ID del gruppo */
                $groupRow = $this->groupsModel->getGroupById($posts);
                
                if ( ! $groupRow):
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.messages.noGroupFound')]);
                endif;

                $this->data['group'] = $groupRow;
                $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
                $this->data['group_perms'] = $this->groupsModel->getGroup((int) $groupRow->id);

                /* Rigeneriamo lo stesso parziale HTML usato per il caricamento iniziale */
                $output = view('backend/groups/partials/index/getGroupPartial', $this->data);

                return $this->jsonResponse(['result' => true, 'output' => $output]);

            endif;

            /* Sbarramento preliminare per proteggere la regola is_unique da ID manomessi */
            if ( ! isset($posts['id']) || ! is_numeric($posts['id']) || (int) $posts['id'] <= 0):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.errors.wrongID')]);
            endif;

            /* Cast sicuro per la generazione delle regole successive */
            $posts['id'] = (int) $posts['id'];

            /* Recuperiamo i record freschi dal Model usando l'ID del gruppo */
            $groupRow = $this->groupsModel->getGroupById($posts);
            
            if ( ! $groupRow):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.messages.noGroupFound')]);
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

    /**
     * Procede all'eliminazione permanente di un record di gruppo dal sistema.
     * Effettua controlli preliminari per impedire l'eliminazione se risultano amministratori tuttora associati all'ID target.
     *
     * @return ResponseInterface Risposta JSON con l'esito della cancellazione o il dettaglio del vincolo violato.
     */
    public function del(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->groupsModel->delValidationRules();

            if ( ! isset($posts['id']) || ( ! is_numeric($posts['id']) ) || (int) $posts['id'] <= 0):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.errors.wrongID')]);
            endif;

            /* Validazione dei posts */
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/groups.messages.validateToastErrors'), $errorMessage)]);
            endif;

            if ($this->groupsModel->hasAdminsAttached((int) $posts['id'])):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.messages.hasAdminsAttached')]);
            endif;

            /* Esecuzione della logica di inserimento con sbarramento interno */
            $result = $this->groupsModel->del($posts);

            return $this->jsonResponse($result);

        endif;
    }

    /**
     * Esegue un controllo di integrità referenziale preliminare sull'ID del gruppo fornito, 
     * verificando l'assenza di amministratori associati, per autorizzare un'eventuale azione distruttiva.
     *
     * @return ResponseInterface Risposta JSON booleana (true se la cancellazione è permessa, false con messaggio di errore se bloccata).
     */
    public function checkDeleteConstraints(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            if ( ! isset($posts['id']) || ( ! is_numeric($posts['id']) ) || (int) $posts['id'] <= 0):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.errors.wrongID')]);
            endif;

            /* Controllo vincoli: se ci sono admin associati, blocca l'operazione restituendo esito negativo */
            if ($this->groupsModel->hasAdminsAttached((int) $posts['id'])):
                return $this->jsonResponse(['result' => false,'message' => lang('backend/groups.messages.hasAdminsAttached')]);
            endif;

            /* Via libera: nessun vincolo rilevato */
            return $this->jsonResponse(['result' => true]);

        endif;
    }

    /**
     * Fornisce asincronamente il frammento HTML contenente il campo di ricerca iniziale per la sezione "Eccezioni".
     *
     * @return ResponseInterface Risposta JSON con l'interfaccia di inserimento ricerca.
     */
    public function openExceptions(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Generiamo la vista parziale che contiene il campo per cercare un utente */
            $output = view('backend/groups/partials/index/exceptionsPartial', $this->data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Elabora le stringhe immesse dall'utente e restituisce in risposta una tendina interattiva contenente l'elenco 
     * degli amministratori i cui dati anagrafici corrispondono al parametro di ricerca.
     *
     * @return ResponseInterface Risposta JSON contenente la visualizzazione parziale dei risultati trovati.
     */
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

    /**
     * Fornisce il dettaglio della configurazione attuale dei permessi per un amministratore specifico.
     * Svolge una comparazione tecnica fra i permessi concessi al gruppo di appartenenza e le variazioni 
     * (delta/eccezioni) precedentemente registrate a livello di singolo utente.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML con la griglia dettagliata dei permessi utente.
     */
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

    /**
     * Sottopone a validazione e memorizza sul database le modifiche ai permessi (eccezioni) impostate 
     * per l'amministratore identificato dall'UUID fornito nella richiesta AJAX.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito della transazione.
     */
    public function saveExceptions(): ResponseInterface
    {
        /* Verifichiamo che la richiesta sia esclusivamente AJAX e POST */
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            /* Validazione preliminare dell'UUID dell'amministratore */
            if (( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/groups.errors.wrongUUID')]);
            endif;

            /* Recuperiamo le regole di validazione specifiche per le eccezioni dal Model */
            $rules = $this->groupsModel->saveExceptionsValidationRules($posts);
            
            if ( ! $this->validateData($posts, $rules)):

                $rawErrors = $this->validator->getErrors();

                /* Raggruppiamo i dot-errors di 'permissions.*' sotto la chiave unica per il DOM */
                $cleanErrors = removeDotPermissions('permissions', $rawErrors);

                return $this->jsonResponse(['errors' => $cleanErrors, 'message' => lang('backend/groups.messages.validationErrors')]);
            endif;

            /* Eseguiamo il salvataggio dei delta sul database tramite il Model */
            $result = $this->groupsModel->saveExceptions($posts);

            return $this->jsonResponse(['result'  => $result['result'], 'message' => $result['message']]);

        endif;
    }
}
