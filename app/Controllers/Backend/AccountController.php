<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\Backend\AccountModel;
use App\Libraries\Backend\AccountClass;
use App\Controllers\Backend\BackendController; 

class AccountController extends BackendController 
{
    protected AccountModel $accountModel;
    protected AccountClass $accountClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'account';

        $this->accountModel = model(AccountModel::class);
        $this->accountClass = (new AccountClass())->withModel($this->accountModel);

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
                'title' => lang('backend/account.leftMenu.securityView'),
                'class' => 'col-4',
                'icon' => '<i class="fa-solid fa-shield"></i>',
                'icon_3x' => '<i class="fa-solid fa-shield fa-3x"></i>',
                'route' => 'backend/account/security',
            ],
        ];
    }

    public function index()
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/account.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/account/indexView', $this->data);
    }

    public function general()
    {
        $this->data['action'] = 'general';

        $this->data['title'] = lang('backend/account.titles.general');
        $this->data['icon'] = '<i class="fa-solid fa-id-card"></i>';

        return $this->render('backend/account/generalView', $this->data);
    }

    public function edit()
    {
        $this->data['action'] = 'edit';

        $this->data['title'] = lang('backend/account.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-user-edit"></i>';

        return $this->render('backend/account/editView', $this->data);
    }

    public function permissions()
    {
        $this->data['action'] = 'permissions';

        $this->data['title'] = lang('backend/account.titles.permissions');
        $this->data['icon'] = '<i class="fa-solid fa-check-circle"></i>';

        return $this->render('backend/account/permissionsView', $this->data);
    }

    public function images()
    {
        $this->data['action'] = 'images';

        $this->data['title'] = lang('backend/account.titles.images');
        $this->data['icon'] = '<i class="fa-solid fa-images"></i>';

        return $this->render('backend/account/imagesView', $this->data);
    }

    public function tokens()
    {
        $this->data['action'] = 'tokens';

        $this->data['title'] = lang('backend/account.titles.tokens');
        $this->data['icon'] = '<i class="fa-solid fa-chain"></i>';

        return $this->render('backend/account/tokensView', $this->data);
    }

    public function resetPassword()
    {
        $this->data['action'] = 'resetPassword';

        $this->data['title'] = lang('backend/account.titles.resetPassword');
        $this->data['icon'] = '<i class="fa-solid fa-unlock"></i>';

        return $this->render('backend/account/resetPasswordView', $this->data);
    }

    public function security()
    {
        $this->data['action'] = 'security';
        
        $this->data['title'] = lang('backend/account.titles.security');
        $this->data['icon'] = '<i class="fa-solid fa-shield"></i>';

        return $this->render('backend/account/securityView', $this->data);
    }
}
