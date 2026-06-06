<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;

use App\Models\Backend\AdminsModel;
use App\Libraries\Backend\AdminsClass;
use App\Controllers\Backend\BackendController; 

class AdminsController extends BackendController 
{
    protected AdminsModel $adminsModel;
    protected AdminsClass $adminsClass;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'admins';

        $this->adminsModel = model(AdminsModel::class);
        $this->adminsClass = (new AdminsClass())->withModel($this->adminsModel);
    }

    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/admins.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/indexView', $this->data);
    }

    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            $rules = $this->adminsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
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
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/showAllView', $this->data);
    }

    public function add(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->response->setJSON(['result' => true,'output' => view('backend/admins/partials/add/addPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):

                $formattedErrors = removeDotPermissions('permissions', $this->validator->getErrors());

                return $this->response->setJSON(['errors' => $formattedErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
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
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/addView', $this->data);
    }

    public function edit(string $uuid = null): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            if(( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/admins.messages.wrongUUIDFormat')]);
            endif;

            $admin = $this->adminsModel->getByUUID($posts['uuid']);

            if($admin['result'] === false):
                return $this->response->setJSON(['result' => false, 'message' => $admin['message']]);
            endif;

            if (isset($posts['action']) && $posts['action'] === 'refresh'):
                $this->data['admin'] = $admin['row'];
                return $this->response->setJSON(['result' => true,'output' => view('backend/admins/partials/edit/editPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->editValidationRules($posts);
            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $result = $this->adminsModel->edit($posts);

            $json = ['result'  => $result['result'], 'message' => $result['message']];

            if ($result['result'] === true):

                $this->data['admin'] = $result['row'];

                $rawPermissions = $this->adminsModel->getPermissions($uuid);

                $this->data['perms'] = array_map(function($perm) {
                    return $perm->permission;
                }, $rawPermissions);

                $json['output'] = view('backend/admins/partials/edit/editPartial', $this->data);
            endif;

            return $this->response->setJSON($json);

        endif;

        if(( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', lang('backend/admins.messages.wrongUUIDFormat'))->with('class', 'danger');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'danger');
        endif;
        
        $this->data['action'] = 'edit';
        
        $this->data['title'] = lang('backend/admins.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        $this->data['admin'] = $admin['row'] ?? null;
        $this->data['uuid'] = $uuid;

        $rawPermissions = $this->adminsModel->getPermissions($uuid);

        $this->data['perms'] = array_map(function($perm) {
            return $perm->permission;
        }, $rawPermissions);

        return $this->render('backend/admins/editView', $this->data);
    }

    public function show(string $uuid): RedirectResponse|string
    {
        if(( ! isset($uuid)) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', 'Formato identificativo non valido.')->with('class', 'danger');
        endif;

        $admin = $this->adminsModel->getByUUID($uuid);

        if($admin['result'] === false):
            return redirect()->to(base_url('backend/admins/showAll'))->with('message', $admin['message'])->with('class', 'danger');
        endif;
        
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/admins.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        $this->data['admin'] = $admin['row'] ?? null;
        $this->data['uuid'] = $uuid;

        return $this->render('backend/admins/showView', $this->data);
    }

    public function delete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->del($posts);

            return $this->response->setJSON($json);

        endif;
    }

    public function resetPassword(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->resetPasswordValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->resetPassword($posts, $this->request);

            return $this->response->setJSON($json);

        endif;
    }

    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->changeStatus($posts);

            if(isset($posts['context']) && $posts['context'] === 'show'):

                $this->data['admin'] = $json['admin'];

                $json['statusView'] = view('backend/admins/partials/show/statusDataPartial', $this->data);
                $json['metaView'] = view('backend/admins/partials/common/metaDataPartial', $this->data); 

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $record = $this->adminsModel->getByUUID($posts['uuid']);

            if($record['result'] === true):

                $json = ['result' => true];

                $this->data['admin'] = $record['row'];

                if(isset($posts['context']) && $posts['context'] === 'show'):
                    $json['output'] = view('backend/admins/partials/show/generalDataPartial', $this->data);
                endif;

                if(isset($posts['context']) && $posts['context'] === 'edit'):
                    $json['output'] = view('backend/admins/partials/edit/generalDataPartial', $this->data);
                endif;

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

            return $this->response->setJSON($json);

        endif;
    }

    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->adminsModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->response->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
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
}
