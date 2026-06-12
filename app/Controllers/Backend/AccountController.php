<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AccountModel;
use App\Libraries\Backend\AccountClass;
use App\Controllers\Backend\BackendController; 

/**
 * Class AccountController
 *
 * Controller dedicato alla gestione completa del profilo, delle impostazioni personali,
 * dei permessi, della sicurezza e dei token dell'operatore correntemente autenticato nel Backend.
 */
class AccountController extends BackendController 
{
    /**
     * @var AccountModel Istanza del modello dedicato alla persistenza dei dati del profilo utente.
     */
    protected AccountModel $accountModel;

    /**
     * @var AccountClass Istanza della libreria logica per l'elaborazione delle funzionalità dell'account.
     */
    protected AccountClass $accountClass;

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

        $this->accountModel = model(AccountModel::class);
        $this->accountClass = new AccountClass($this->accountModel);

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
                'icon' => '<i class="fa-solid fa-shield"></i>',
                'icon_3x' => '<i class="fa-solid fa-shield fa-3x"></i>',
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
        
        $this->data['title'] = lang('backend/account.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

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

        $this->data['title'] = lang('backend/account.titles.general');
        $this->data['icon'] = '<i class="fa-solid fa-id-card"></i>';

        return $this->render('backend/account/generalView', $this->data);
    }

    /**
     * Mostra la maschera di configurazione e modifica dei dati del profilo dell'operatore.
     *
     * @return string La vista HTML del form di modifica.
     */
    public function edit()
    {
        $this->data['action'] = 'edit';

        $this->data['title'] = lang('backend/account.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-user-edit"></i>';

        return $this->render('backend/account/editView', $this->data);
    }

    /**
     * Mostra l'elenco e il riepilogo dei permessi RBAC associati e attivi per l'operatore corrente.
     *
     * @return string La vista HTML della schermata dei permessi.
     */
    public function permissions()
    {
        $this->data['action'] = 'permissions';

        $this->data['title'] = lang('backend/account.titles.permissions');
        $this->data['icon'] = '<i class="fa-solid fa-check-circle"></i>';

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

        $this->data['title'] = lang('backend/account.titles.images');
        $this->data['icon'] = '<i class="fa-solid fa-images"></i>';

        return $this->render('backend/account/imagesView', $this->data);
    }

    /**
     * Mostra l'elenco, lo stato di validità e la cronologia dei token di sicurezza legati all'account.
     *
     * @return string La vista HTML della sezione token.
     */
    public function tokens()
    {
        $this->data['action'] = 'tokens';

        $this->data['title'] = lang('backend/account.titles.tokens');
        $this->data['icon'] = '<i class="fa-solid fa-chain"></i>';

        return $this->render('backend/account/tokensView', $this->data);
    }

    /**
     * Mostra la maschera per l'aggiornamento guidato delle credenziali d'accesso (Password) dell'utente.
     *
     * @return string La vista HTML del form di cambio password.
     */
    public function resetPassword()
    {
        $this->data['action'] = 'resetPassword';

        $this->data['title'] = lang('backend/account.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-unlock"></i>';

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
        
        $this->data['title'] = lang('backend/account.titles.security');
        $this->data['icon'] = '<i class="fa-solid fa-shield"></i>';

        return $this->render('backend/account/securityView', $this->data);
    }
}
