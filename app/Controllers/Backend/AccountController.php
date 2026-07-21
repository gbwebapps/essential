<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\UserAgent;

use App\Models\Backend\AccountModel;
use App\Libraries\Backend\AccountClass;
use App\Controllers\Backend\BackendController; 

use App\Models\Backend\Components\GalleryOneImgModel;

/**
 * Class AccountController
 *
 * Controller dedicato alla gestione completa del profilo, delle impostazioni personali,
 * dei permessi, della sicurezza e dei token dell'operatore correntemente autenticato nel Backend.
 */
class AccountController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla persistenza dei dati del profilo utente.
     * 
     * @var AccountModel 
     */
    protected AccountModel $accountModel;

    /**
     * Istanza della libreria logica per l'elaborazione delle funzionalità dell'account.
     * 
     * @var AccountClass 
     */
    protected AccountClass $accountClass;

    protected GalleryOneImgModel $galleryOneImgModel;

    /**
     * Inizializza il controller impostando l'albero di navigazione interno (sub-menu) e istanziando i relativi componenti core.
     *
     * @param RequestInterface  $request  Oggetto della richiesta HTTP corrente.
     * @param ResponseInterface $response Oggetto della risposta HTTP corrente.
     * @param LoggerInterface   $logger   Istanza del sistema di tracciamento log.
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'account';
        $this->data['entity'] = 'admins';

        $this->data['icon'] = '<i class="fa-solid fa-user-gear"></i>';
        $this->data['title'] = lang('backend/account.titles.index');

        $this->accountModel = model(AccountModel::class);
        $this->accountClass = new AccountClass($this->accountModel);

        $this->galleryOneImgModel = model(GalleryOneImgModel::class);

        $this->data['sections'] = [
            'general' => [
                'title' => lang('backend/account.leftMenu.general'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-id-card"></i>',
                'icon_3x' => '<i class="fa-solid fa-id-card fa-3x"></i>',
                'route' => 'backend/account/general',
            ],
            'edit' => [
                'title' => lang('backend/account.leftMenu.edit'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-user-edit"></i>',
                'icon_3x' => '<i class="fa-solid fa-user-edit fa-3x"></i>',
                'route' => 'backend/account/edit',
            ],
            'permissions' => [
                'title' => lang('backend/account.leftMenu.permissions'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-check-circle"></i>',
                'icon_3x' => '<i class="fa-solid fa-check-circle fa-3x"></i>',
                'route' => 'backend/account/permissions',
            ],
             'images' => [
                'title' => lang('backend/account.leftMenu.images'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-images"></i>',
                'icon_3x' => '<i class="fa-solid fa-images fa-3x"></i>',
                'route' => 'backend/account/images',
            ], 
            'tokens' => [
                'title' => lang('backend/account.leftMenu.tokens'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-chain"></i>',
                'icon_3x' => '<i class="fa-solid fa-chain fa-3x"></i>',
                'route' => 'backend/account/tokens',
            ],
            'resetPassword' => [
                'title' => lang('backend/account.leftMenu.resetPassword'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-unlock"></i>',
                'icon_3x' => '<i class="fa-solid fa-unlock fa-3x"></i>',
                'route' => 'backend/account/resetPassword',
            ],
            'security' => [
                'title' => lang('backend/account.leftMenu.security'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-user-shield"></i>',
                'icon_3x' => '<i class="fa-solid fa-user-shield fa-3x"></i>',
                'route' => 'backend/account/security',
            ],
        ];
    }

    /**
     * Mostra la pagina principale (hub di navigazione) del pannello di gestione dell'account.
     *
     * @return string La vista HTML dell'indice dell'account.
     */
    public function index()
    {
        $this->data['action'] = 'index';

        $this->data['centerContent'] = true;

        return $this->render('backend/account/indexView', $this->data);
    }

    /**
     * Mostra la sezione contenente i dati anagrafici e le informazioni generali del profilo.
     *
     * @return string La vista HTML dei dati generali dell'account.
     */
    public function general()
    {
        $this->data['action'] = 'general';

        return $this->render('backend/account/generalView', $this->data);
    }

    /**
     * Mostra la maschera di configurazione e modifica dei dati del profilo dell'operatore.
     *
     * @return string La vista HTML del form di modifica.
     */
    public function edit(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            if (isset($posts['action']) && $posts['action'] === 'refresh'):
                return $this->response->setJSON(['result' => true, 'output' => view('backend/account/partials/edit/editPartial', $this->data)]);
            endif;

            /* Passiamo l'UUID sicuro ricavato dall'oggetto dell'admin loggato */
            $rules = $this->accountModel->editValidationRules($this->currentAdmin->uuid);

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/account.messages.validationErrors')]);
            endif;

            $json = $this->accountModel->edit($posts, $this->currentAdmin);

            if ($json['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $json['message']]);
            endif;

            if ($json['result'] === true):
                $this->data['currentAdmin'] = $json['currentAdmin'];
                $json['output'] = view('backend/account/partials/edit/editPartial', $this->data);

                /* Inserisco anche la vista del menu in alto nel caso admin corrente abbia modificato il nome o il cognome */
                $json['navBarTop'] = view('backend/template/navbarTopView', $this->data);
            endif;

            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'edit';

        return $this->render('backend/account/editView', $this->data);
    }

    /**
     * Mostra l'elenco e il riepilogo dei permessi RBAC associati e attivi per l'operatore corrente.
     *
     * @return string|\CodeIgniter\HTTP\Response La vista HTML o la risposta JSON per AJAX.
     */
    public function permissions(): string|ResponseInterface
    {
        /* Se la richiesta è AJAX e in POST, gestiamo il rinfresco asincrono */
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Svuotiamo la cache del Service e riassegniamo la proprietà del controller con l'istanza fresca */
            $this->currentAdmin = service('authorization')->refresh()->currentAdmin();
            $this->data['currentAdmin'] = $this->currentAdmin;

            /* Ricarichiamo i dati necessari per la vista parziale */
            $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
            $this->data['group_perms'] = $this->accountModel->getGroupPermissions((int) $this->currentAdmin->group_id);
            $this->data['admin_exceptions'] = $this->accountModel->getAdminExceptions($this->currentAdmin->uuid);

            return $this->response->setJSON([
                'result' => true,
                'output' => view('backend/account/partials/permissions/permissionsPartial', $this->data)
            ]);

        endif;

        /* Flusso standard al primo caricamento sincrono (GET) */
        $this->data['action'] = 'permissions';
        $this->data['permissions'] = config(\Config\Backend\Permissions::class)->getPermissions();
        $this->data['group_perms'] = $this->accountModel->getGroupPermissions((int) $this->currentAdmin->group_id);
        $this->data['admin_exceptions'] = $this->accountModel->getAdminExceptions($this->currentAdmin->uuid);

        return $this->render('backend/account/permissionsView', $this->data);
    }

    /**
     * Mostra la sezione dedicata alla gestione dell'avatar e dei file multimediali associati al profilo.
     *
     * @return string La vista HTML della gestione immagini.
     */
    public function images()
    {
        $this->data['action'] = 'images';

        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneImgModel->getImages(['entity' => 'admins', 'uuid' => $this->currentAdmin->uuid]);

        $this->data['saveImages'] = true;
        $this->data['uuid'] = $this->currentAdmin->uuid;

        return $this->render('backend/account/imagesView', $this->data);
    }

    /**
     * Mostra l'elenco, lo stato di validità e la cronologia dei token di sicurezza legati all'account.
     *
     * @return string La vista HTML della sezione token.
     */
    public function tokens(): string|ResponseInterface
    {
        $this->data['userAgent'] = new UserAgent();
        $this->data['tokens'] = $this->accountModel->getTokens($this->currentAdmin->uuid);

        /* Se la richiesta è AJAX e in POST, gestiamo il rinfresco asincrono */
        if ($this->request->isAJAX() && $this->request->is('post')):

            return $this->response->setJSON([
                'result' => true,
                'output' => view('backend/account/partials/tokens/tokensPartial', $this->data)
            ]);

        endif;

        $this->data['action'] = 'tokens';

        return $this->render('backend/account/tokensView', $this->data);
    }

    /**
     * Revoca e rimuove in modo permanente un determinato token (sessione o cookie persistente) associato all'amministratore corrente.
     *
     * @return ResponseInterface Risposta JSON con l'esito e la tabella parziale dei token aggiornata.
     */
    public function deleteToken(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->accountModel->deleteTokenValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->response->setJSON(['result' => false, 'message' => sprintf(lang('backend/account.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $result = $this->accountModel->deleteToken($posts, $this->currentAdmin);

            if($result['result'] === true):

                $this->data['userAgent'] = new UserAgent();
                $this->data['tokens'] = $this->accountModel->getTokens($this->currentAdmin->uuid);

                $json = ['result' => true, 'message' => $result['message']];
                $json['output'] = view('backend/account/partials/tokens/tokensPartial', $this->data);

            else:

                $json = ['result' => false, 'message' => $result['message']];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    /**
     * Avvia la procedura amministrativa di invio o rigenerazione guidata della password di un operatore.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione.
     */
    public function resetPassword(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $result = $this->accountModel->resetPassword($this->currentAdmin, $this->request);

            $this->data['expiringDate'] = $this->accountModel->getExpiringDate($this->currentAdmin);

            if($result['result'] === false):

                $json = ['result' => $result['result'], 'message' => $result['message']];

            else:

                $output = view('backend/account/partials/resetPassword/resetPasswordPartial', $this->data);
                $json = ['result' => $result['result'], 'message' => $result['message'], 'output' => $output];

            endif;


            return $this->response->setJSON($json);

        endif;

        $this->data['action'] = 'resetPassword';

        $this->data['expiringDate'] = $this->accountModel->getExpiringDate($this->currentAdmin);

        return $this->render('backend/account/resetPasswordView', $this->data);
    }

    /**
     * Mostra i log di controllo degli accessi, le sessioni attive e i parametri di sicurezza del profilo.
     *
     * @return string La vista HTML della sezione sicurezza.
     */
    public function security()
    {
        $this->data['action'] = 'security';

        $this->data['activeMethod'] = $this->accountModel->getActiveMethod($this->currentAdmin->uuid);
        
        return $this->render('backend/account/securityView', $this->data);
    }

    public function saveBasicMethod()
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            $method = (string) $this->request->getPost('twoFactorMethod');
            $allowedMethods = setting('Backend\Auth')->twoFactorMethods;

            /* Validazione dell'input */
            if ( ! in_array($method, $allowedMethods, true) || $method === 'totp') :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.methodNotValid')]);
            endif;

            /* Eseguo l'aggiornamento nel Model */
            $updated = $this->accountModel->setBasicMethod($this->currentAdmin, $method);

            if ( ! $updated) :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.updateSecuritySettingsError')]);
            endif;

            return $this->response->setJSON(['result' => true, 'message' => lang('backend/account.messages.updateSecuritySettingsSuccess')]);

        endif;
    }

    /**
     * Inizializza la configurazione del secondo fattore tramite TOTP.
     * * Genera un codice segreto univoco e temporaneo, lo registra nel database
     * in uno stato non ancora attivo (enabled = 0) e restituisce la vista parziale
     * contenente il QR Code e il secret in chiaro per l'applicazione di autenticazione.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface Risposta JSON con l'esito dell'operazione e il codice HTML della vista parziale.
     */
    public function setupTotp()
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $adminUuid = $this->currentAdmin->uuid;

            /* Istanzio il servizio per la gestione dei codici OTP */
            $otpService = new \App\Libraries\AppOtpService();
            $secret = $otpService->generateSecret();

            /* Salvo il secret temporaneo nel DB (imposta enabled = 0) */
            $saved = $this->accountModel->saveTemporarySecret($adminUuid, $secret);

            if ( ! $saved) :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.configurationInitializeError')]);
            endif;

            /* Genero l'URI stringa usando il servizio esistente */
            $uri = $otpService->getProvisioningUri($secret, $this->currentAdmin->email);
            
            /* Sintassi ufficiale Endroid v6: parametri passati direttamente nel costruttore */
            $builder = new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\PngWriter(),
                data: $uri,
                size: 250,
                margin: 0
            );

            $result = $builder->build();

            $data['qrCode'] = $result->getDataUri();
            $data['totpSecret'] = $secret;

            /* Renderizzo la vista parziale per l'attivazione del TOTP */
            $output = view('backend/account/partials/security/totpSetupPartial', $data);

            return $this->response->setJSON(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Valida il codice di verifica OTP e attiva definitivamente il metodo TOTP.
     * * Recupera il secret temporaneo associato all'amministratore (enabled = 0), 
     * ne verifica la validità tramite il codice inviato dall'utente e, in caso di 
     * esito positivo, attiva il TOTP (enabled = 1) disattivando gli altri canali.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface Risposta JSON con l'esito dell'operazione.
     */
    public function confirmTotp()
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            /* Definisco i soli campi consentiti in questa richiesta */
            $allowedFields = ['otp'];
            
            /* Sanifico l'input filtrando l'array $_POST */
            $posts = array_intersect_key($this->request->getPost(), array_flip($allowedFields));

            if ( ! $this->validateData($posts, ['otp' => 'required|is_natural_no_zero|exact_length[6]'])) :
                return $this->response->setJSON(['result'  => false, 'message' => lang('backend/account.messages.validationErrors')]);
            endif;

            /* Da qui in poi lavoriamo solo sul parametro sanificato e validato */
            $otpCode = (string) $posts['otp'];
            $adminUuid = $this->currentAdmin->uuid;

            /* Recupero il secret temporaneo */
            $secret = $this->accountModel->getTemporarySecret($adminUuid);

            if ( ! $secret) :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.noConfigurationSession')]);
            endif;

            /* Istanzio il servizio e valido il codice */
            $otpService = new \App\Libraries\AppOtpService();
            if ( ! $otpService->verify($secret, $otpCode)) :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.wrongCode')]);
            endif;

            /* Attivo definitivamente il TOTP nel DB */
            $activated = $this->accountModel->activateTotpMethod($adminUuid, $this->currentAdmin);

            if ( ! $activated) :
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/account.messages.totpActivationNotPossible')]);
            endif;

            return $this->response->setJSON(['result' => true, 'message' => lang('backend/account.messages.totpConfigurationSuccess')]);

        endif;
    }
}
