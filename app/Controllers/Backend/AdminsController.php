<?php declare(strict_types = 1); 

namespace App\Controllers\Backend;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

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
        if($this->request->isAJAX()):

            $posts = $this->request->getPost();

            $rules = $this->adminsModel->showAllValidationRules();
            if (! $this->validateData($posts, $rules)):
                return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $rules = $this->adminsModel->showAllSearchValidationRules();
            if (! $this->validateData($posts, $rules)):

                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());

                return $this->response->setStatusCode(422)->setJSON(['errors' => $formattedErrors, 'message' => lang('backend/admins.messages.validationErrors')]);
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
        if($this->request->isAJAX()):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->response->setJSON(['result' => true,'output' => view('backend/admins/partials/add/addPartial', $this->data)]);
            endif;

            $rules = $this->adminsModel->addValidationRules();

            if (! $this->validateData($posts, $rules)):
                return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors(), 'message' => lang('backend/admins.messages.validationErrors')]);
            endif;

            $json = $this->adminsModel->add($posts);

            if (($json['result'] === 'createAdminFailed') || ($json['result'] === 'emailFailed')):
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
        if($this->request->isAJAX()):

            $posts = array_merge($this->request->getPost(), ['images' => $this->request->getFileMultiple('images') ?? []], ['documents' => $this->request->getFileMultiple('documents') ?? []]);

            // ...

        endif;

        $this->data['action'] = 'edit';
        
        $this->data['title'] = lang('backend/admins.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/editView', $this->data);
    }

    public function show(string $uuid): string|ResponseInterface
    {
        $posts = $this->request->getPost();
        
        $this->data['action'] = 'show';
        
        $this->data['title'] = lang('backend/admins.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-gauge"></i>';

        return $this->render('backend/admins/showView', $this->data);
    }

    public function delete(): ResponseInterface
    {
        if($this->request->isAJAX()):

            $posts = $this->request->getPost();

            // ...

        endif;
    }

    public function changeStatus(): ResponseInterface
    {
        if($this->request->isAJAX()):

            $posts = $this->request->getPost();

            // ...

        endif;
    }

    public function getGeneralData(): ResponseInterface
    {
        if($this->request->isAJAX()):

            $posts = $this->request->getPost();

            // ...

        endif;
    }

    public function getMetaData(): ResponseInterface
    {
        if($this->request->isAJAX()):

            $posts = $this->request->getPost();

            // ...

        endif;
    }
}
