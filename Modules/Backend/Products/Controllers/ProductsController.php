<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Controllers;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\GalleryOneModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Backend\Products\Config\Views;
use Modules\Backend\Products\Libraries\ProductsClass;
use Modules\Backend\Products\Models\ProductsModel;
use Psr\Log\LoggerInterface;

/**
 * Gestisce la sezione amministrativa dedicata ai prodotti.
 */
class ProductsController extends BackendController
{
    protected ProductsModel $productsModel;
    protected ProductsClass $productsClass;
    protected GalleryOneModel $galleryOneModel;

    private function getUploadedImages(): array
    {
        $images = $this->request->getFileMultiple('images') ?? [];

        return array_filter($images, static function($image): bool {
            return $image->getError() !== UPLOAD_ERR_NO_FILE;
        });
    }

    private function targetAccessError(array $record): ?ResponseInterface
    {
        if ($record['result'] === false):
            return $this->jsonResponse(['result' => false, 'message' => $record['message']]);
        endif;

        if (($record['row']->deleted_at ?? null) !== null):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')]);
        endif;

        return null;
    }

    private function loadOptions(): void
    {
        $this->data['brandOptions'] = $this->productsModel->getBrandOptions();
        $this->data['categoryOptions'] = $this->productsModel->getCategoryOptions();
    }

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'products';
        $this->data['entity'] = 'products';
        $this->productsModel = model(ProductsModel::class);
        $this->productsClass = new ProductsClass();
        $this->galleryOneModel = model(GalleryOneModel::class);
    }

    public function index(): string
    {
        $this->data['action'] = 'index';
        $this->data['title'] = lang('backend/products.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render(Views::INDEX_VIEW, $this->data);
    }

    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->request->getPost();

            if ( ! $this->validateData($posts, $this->productsModel->showAllValidationRules())):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates'] = $posts['searchDates'] ?? [];

            if ( ! $this->validateData($posts, $this->productsModel->showAllSearchValidationRules())):
                $errors = removeDot('searchFields.', $this->validator->getErrors());
                $errors = removeDot('searchDates.', $errors);
                return $this->jsonResponse(['errors' => $errors, 'message' => lang('backend/products.messages.validationErrors')]);
            endif;

            $this->data['data'] = $this->productsModel->getData($posts);

            if ($this->data['data']['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $this->data['data']['message']]);
            endif;

            $this->data['posts'] = $posts;
            return $this->jsonResponse(['result' => true, 'output' => view(Views::SHOW_ALL_PARTIAL, $this->data)]);
        endif;

        $this->data['action'] = 'showAll';
        $this->data['title'] = lang('backend/products.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-boxes-stacked"></i>';
        $this->loadOptions();

        return $this->render(Views::SHOW_ALL_VIEW, $this->data);
    }

    public function add(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                $this->loadOptions();
                return $this->jsonResponse(['result' => true, 'output' => view(Views::ADD_PARTIAL, $this->data)]);
            endif;

            if ( ! $this->validateData($posts, $this->productsModel->addValidationRules())):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/products.messages.validationErrors')]);
            endif;

            $json = $this->productsModel->add($posts);
            if ($json['result'] === true):
                $this->loadOptions();
                $json['output'] = view(Views::ADD_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);
        endif;

        $this->data['action'] = 'add';
        $this->data['title'] = lang('backend/products.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-plus"></i>';
        $this->loadOptions();

        return $this->render(Views::ADD_VIEW, $this->data);
    }

    public function edit(?string $uuid = null): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if (( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/products.errors.uuid')]);
            endif;

            $product = $this->productsModel->getProduct($posts['uuid']);
            if (($accessError = $this->targetAccessError($product)) !== null):
                return $accessError;
            endif;

            if (isset($posts['action']) && $posts['action'] === 'refresh'):
                $this->data['product'] = $product['row'];
                $this->data['uuid'] = $posts['uuid'];
                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'products', 'uuid' => $posts['uuid']]);
                $this->loadOptions();
                return $this->jsonResponse(['result' => true, 'output' => view(Views::EDIT_PARTIAL, $this->data)]);
            endif;

            if ( ! $this->validateData($posts, $this->productsModel->editValidationRules($posts))):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/products.messages.validationErrors')]);
            endif;

            $result = $this->productsModel->edit($posts);
            $json = ['result' => $result['result'], 'message' => $result['message']];

            if ($result['result'] === true):
                $this->data['product'] = $result['row'];
                $this->data['uuid'] = $posts['uuid'];
                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'products', 'uuid' => $posts['uuid']]);
                $this->loadOptions();
                $json['output'] = view(Views::EDIT_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);
        endif;

        if ($uuid === null || ! $this->regexp->validateUUID($uuid)):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', lang('backend/products.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $product = $this->productsModel->getProduct($uuid);
        if ($product['result'] === false):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', $product['message'])->with('class', 'app-alert-danger fw-bold');
        endif;
        if ($product['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', lang('backend/products.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $this->data['action'] = 'edit';
        $this->data['title'] = lang('backend/products.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-pen"></i>';
        $this->data['product'] = $product['row'];
        $this->data['uuid'] = $uuid;
        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'products', 'uuid' => $uuid]);
        $this->loadOptions();

        return $this->render(Views::EDIT_VIEW, $this->data);
    }

    public function show(string $uuid): RedirectResponse|string
    {
        if ( ! $this->regexp->validateUUID($uuid)):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', lang('backend/products.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $product = $this->productsModel->getProduct($uuid);
        if ($product['result'] === false):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', $product['message'])->with('class', 'app-alert-danger fw-bold');
        endif;
        if ($product['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/products/showAll'))->with('message', lang('backend/products.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $this->data['action'] = 'show';
        $this->data['title'] = lang('backend/products.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-box"></i>';
        $this->data['product'] = $product['row'];
        $this->data['uuid'] = $uuid;
        $this->data['context'] = 'show';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'products', 'uuid' => $uuid]);

        return $this->render(Views::SHOW_VIEW, $this->data);
    }

    public function hardDelete(): ResponseInterface
    {
        return $this->deleteResponse('hardDelete');
    }

    public function softDelete(): ResponseInterface
    {
        return $this->deleteResponse('softDelete');
    }

    public function restoreDelete(): ResponseInterface
    {
        return $this->deleteResponse('restoreDelete');
    }

    private function deleteResponse(string $method): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->request->getPost();
            if ( ! $this->validateData($posts, $this->productsModel->delValidationRules())):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage)]);
            endif;

            return $this->jsonResponse($this->productsModel->{$method}($posts));
        endif;

        return service('response')->setStatusCode(400);
    }

    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->request->getPost();
            if ( ! $this->validateData($posts, $this->productsModel->changeStatusValidationRules())):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->productsModel->changeStatus($posts);
            if ($json['result'] === true && ($posts['context'] ?? null) === 'show'):
                $this->data['product'] = $json['product'];
                $json['statusView'] = view(Views::CHANGE_STATUS_PARTIAL, $this->data);
                $json['metaView'] = view(Views::META_DATA_PARTIAL, $this->data);
            endif;
            unset($json['product']);

            return $this->jsonResponse($json);
        endif;

        return service('response')->setStatusCode(400);
    }

    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->request->getPost();
            if ( ! $this->validateData($posts, $this->productsModel->generalDataValidationRules())):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $record = $this->productsModel->getProduct($posts['uuid']);
            if (($accessError = $this->targetAccessError($record)) !== null):
                return $accessError;
            endif;

            $this->data['product'] = $record['row'];
            if ($posts['context'] === 'edit'):
                $this->loadOptions();
                $output = view(Views::EDIT_GENERAL_DATA_PARTIAL, $this->data);
            else:
                $output = view(Views::SHOW_GENERAL_DATA_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse(['result' => true, 'output' => $output]);
        endif;

        return service('response')->setStatusCode(400);
    }

    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->request->getPost();
            if ( ! $this->validateData($posts, $this->productsModel->metaDataValidationRules())):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $record = $this->productsModel->getProduct($posts['uuid']);
            if (($accessError = $this->targetAccessError($record)) !== null):
                return $accessError;
            endif;

            $this->data['product'] = $record['row'];
            return $this->jsonResponse(['result' => true, 'output' => view(Views::META_DATA_PARTIAL, $this->data)]);
        endif;

        return service('response')->setStatusCode(400);
    }
}
