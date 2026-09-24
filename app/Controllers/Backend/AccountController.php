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
 * Gestisce il profilo, le preferenze personali, la sicurezza e le impostazioni dell'amministratore correntemente autenticato.
 */
class AccountController extends BackendController 
{
    /**
     * Istanza del modello dedicato alla gestione dei dati e delle operazioni sul database per l'account
     * @var AccountModel 
     */
    protected AccountModel $accountModel;

    /**
     * Istanza della libreria contenente le logiche operative e di formattazione specifiche dell'account
     * @var AccountClass 
     */
    protected AccountClass $accountClass;

    /**
     * Istanza del modello per la gestione e l'elaborazione dell'immagine di profilo (avatar)
     * @var GalleryOneImgModel 
     */
    protected GalleryOneImgModel $galleryOneImgModel;

    /**
     * Inizializza il controller, configura i metadati della pagina e definisce la struttura dinamica del menu di navigazione dell'account.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
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
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-id-card"></i>',
                'icon_2x' => '<i class="fa-solid fa-id-card fa-2x"></i>',
                'route' => 'backend/account/general',
            ],
            'edit' => [
                'title' => lang('backend/account.leftMenu.edit'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-user-edit"></i>',
                'icon_2x' => '<i class="fa-solid fa-user-edit fa-2x"></i>',
                'route' => 'backend/account/edit',
            ],
            'permissions' => [
                'title' => lang('backend/account.leftMenu.permissions'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-check-circle"></i>',
                'icon_2x' => '<i class="fa-solid fa-check-circle fa-2x"></i>',
                'route' => 'backend/account/permissions',
            ],
             'images' => [
                'title' => lang('backend/account.leftMenu.images'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-images"></i>',
                'icon_2x' => '<i class="fa-solid fa-images fa-2x"></i>',
                'route' => 'backend/account/images',
            ], 
            'tokens' => [
                'title' => lang('backend/account.leftMenu.tokens'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-chain"></i>',
                'icon_2x' => '<i class="fa-solid fa-chain fa-2x"></i>',
                'route' => 'backend/account/tokens',
            ],
            'resetPassword' => [
                'title' => lang('backend/account.leftMenu.resetPassword'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-unlock"></i>',
                'icon_2x' => '<i class="fa-solid fa-unlock fa-2x"></i>',
                'route' => 'backend/account/resetPassword',
            ],
            'security' => [
                'title' => lang('backend/account.leftMenu.security'),
                'class' => 'col-12 col-md-6 col-lg-4 mb-4',
                'icon' => '<i class="fa-solid fa-user-shield"></i>',
                'icon_2x' => '<i class="fa-solid fa-user-shield fa-2x"></i>',
                'route' => 'backend/account/security',
            ],
        ];
    }

    /**
     * Renderizza la vista principale di atterraggio del pannello di gestione dell'account.
     *
     * @return string HTML renderizzato della vista index
     */
    public function index()
    {
        $this->data['action'] = 'index';

        $this->data['centerContent'] = true;

        return $this->render('backend/account/indexView', $this->data);
    }

    /**
     * Renderizza la vista contenente le informazioni generali, anagrafiche e di riepilogo in sola lettura.
     *
     * @return string HTML renderizzato della vista generale
     */
    public function general()
    {
        $this->data['action'] = 'general';

        return $this->render('backend/account/generalView', $this->data);
    }

    /**
     * Gestisce la visualizzazione e l'aggiornamento asincrono dei dati personali e anagrafici del profilo amministratore.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito dell'aggiornamento o l'HTML della vista di modifica
     */
    public function edit(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            if (isset($posts['action']) && $posts['action'] === 'refresh'):
                return $this->jsonResponse(['result' => true, 'output' => view('backend/account/partials/edit/editPartial', $this->data)]);
            endif;

            /* Passiamo l'UUID sicuro ricavato dall'oggetto dell'admin loggato */
            $rules = $this->accountModel->editValidationRules($this->currentAdmin->uuid);

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/account.messages.validationErrors')]);
            endif;

            $json = $this->accountModel->edit($posts, $this->currentAdmin);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if ($json['result'] === true):
                $this->data['currentAdmin'] = $json['currentAdmin'];
                $json['output'] = view('backend/account/partials/edit/editPartial', $this->data);

                /* Inserisco anche la vista del menu in alto nel caso admin corrente abbia modificato il nome o il cognome */
                $json['navBarTop'] = view('backend/template/navbarTopView', $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'edit';

        return $this->render('backend/account/editView', $this->data);
    }

    /**
     * Gestisce la visualizzazione dei permessi e delle eccezioni assegnate all'account, consentendo il rinfresco asincrono delle autorizzazioni.
     *
     * @return string|ResponseInterface Risposta JSON per l'aggiornamento dinamico o l'HTML della vista dei permessi
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

            return $this->jsonResponse([
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
     * Renderizza l'interfaccia dedicata all'upload, alla rimozione e alla gestione dell'immagine di profilo (avatar).
     *
     * @return string HTML renderizzato della vista per le immagini
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
     * Gestisce la visualizzazione dei dispositivi attualmente connessi e delle sessioni (token) attive per l'account corrente.
     *
     * @return string|ResponseInterface Risposta JSON per il ricaricamento della lista o l'HTML della vista dei token
     */
    public function tokens(): string|ResponseInterface
    {
        $this->data['userAgent'] = new UserAgent();
        $this->data['tokens'] = $this->accountModel->getTokens($this->currentAdmin->uuid);
        
        /* Delega totale al Model per recuperare l'ID della sessione in uso */
        $this->data['currentTokenId'] = $this->accountModel->getCurrentTokenId();

        if ($this->request->isAJAX() && $this->request->is('post')):
            return $this->jsonResponse([
                'result' => true,
                'output' => view('backend/account/partials/tokens/tokensPartial', $this->data)
            ]);
        endif;

        $this->data['action'] = 'tokens';
        return $this->render('backend/account/tokensView', $this->data);
    }

    /**
     * Valida ed elimina asincronamente una specifica sessione attiva (token) per disconnettere forzatamente un dispositivo remoto.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'eliminazione e l'interfaccia della lista aggiornata
     */
    public function deleteToken(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->accountModel->deleteTokenValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/account.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Delega totale al Model per l'ID corrente, poi lo passa per lo sbarramento */
            $currentTokenId = $this->accountModel->getCurrentTokenId();
            $result = $this->accountModel->deleteToken($posts, $this->currentAdmin, $currentTokenId);

            if($result['result'] === true):
                $this->data['userAgent'] = new UserAgent();
                $this->data['tokens'] = $this->accountModel->getTokens($this->currentAdmin->uuid);
                $this->data['currentTokenId'] = $currentTokenId;

                $json = ['result' => true, 'message' => $result['message']];
                $json['output'] = view('backend/account/partials/tokens/tokensPartial', $this->data);
            else:
                $json = ['result' => false, 'message' => $result['message']];
            endif;

            return $this->jsonResponse($json);
        endif;
    }

    /**
     * Gestisce la procedura di modifica della password dell'account, validando i requisiti di robustezza e le scadenze temporali.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito dell'operazione o l'HTML della vista per il reset
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

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'resetPassword';

        $this->data['expiringDate'] = $this->accountModel->getExpiringDate($this->currentAdmin);

        return $this->render('backend/account/resetPasswordView', $this->data);
    }

    /**
     * Renderizza l'interfaccia principale per la scelta e la gestione dei metodi di autenticazione a due fattori (2FA).
     *
     * @return string HTML renderizzato della vista di sicurezza
     */
    public function security()
    {
        $this->data['action'] = 'security';

        $this->data['activeMethod'] = $this->accountModel->getActiveMethod($this->currentAdmin->uuid);
        
        return $this->render('backend/account/securityView', $this->data);
    }

    /**
     * Valida e salva in modo asincrono il metodo di autenticazione a due fattori di base selezionato (es. email o disattivato).
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'aggiornamento delle preferenze
     */
    public function saveBasicMethod()
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            $method = (string) $this->request->getPost('twoFactorMethod');
            $allowedMethods = setting('Backend\Auth')->twoFactorMethods;

            /* Validazione dell'input */
            if ( ! in_array($method, $allowedMethods, true) || $method === 'totp') :
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.methodNotValid')]);
            endif;

            /* Eseguo l'aggiornamento nel Model */
            $updated = $this->accountModel->setBasicMethod($this->currentAdmin, $method);

            if ( ! $updated) :
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.updateSecuritySettingsError')]);
            endif;

            return $this->jsonResponse(['result' => true, 'message' => lang('backend/account.messages.updateSecuritySettingsSuccess')]);

        endif;
    }

    /**
     * Inizializza la configurazione per l'app Authenticator (TOTP), generando un codice segreto temporaneo e il relativo QR Code visivo per il pairing.
     *
     * @return ResponseInterface Risposta JSON contenente il QR Code generato e l'interfaccia parziale per l'inserimento del codice
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
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.configurationInitializeError')]);
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

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    /**
     * Verifica il primo codice OTP inserito dall'utente per validare l'associazione e attivare definitivamente l'autenticazione tramite app.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'attivazione e il messaggio di conferma finale
     */
    public function confirmTotp()
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            /* Definisco i soli campi consentiti in questa richiesta */
            $allowedFields = ['otp'];
            
            /* Sanifico l'input filtrando l'array $_POST */
            $posts = array_intersect_key($this->request->getPost(), array_flip($allowedFields));

            if ( ! $this->validateData($posts, ['otp' => 'required|is_natural_no_zero|exact_length[6]'])) :
                return $this->jsonResponse(['result'  => false, 'message' => lang('backend/account.messages.missingCode')]);
            endif;

            /* Da qui in poi lavoriamo solo sul parametro sanificato e validato */
            $otpCode = (string) $posts['otp'];
            $adminUuid = $this->currentAdmin->uuid;

            /* Recupero il secret temporaneo */
            $secret = $this->accountModel->getTemporarySecret($adminUuid);

            if ( ! $secret) :
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.noConfigurationSession')]);
            endif;

            /* Istanzio il servizio e valido il codice */
            $otpService = new \App\Libraries\AppOtpService();
            if ( ! $otpService->verify($secret, $otpCode)) :
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.wrongCode')]);
            endif;

            /* Attivo definitivamente il TOTP nel DB */
            $activated = $this->accountModel->activateTotpMethod($adminUuid, $this->currentAdmin);

            if ( ! $activated) :
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/account.messages.totpActivationNotPossible')]);
            endif;

            return $this->jsonResponse(['result' => true, 'message' => lang('backend/account.messages.totpConfigurationSuccess')]);

        endif;
    }
}
