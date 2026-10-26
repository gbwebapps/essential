<?php declare(strict_types=1);

namespace App\Controllers\Backend\Components;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\GalleryOneModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gestisce le operazioni asincrone del componente galleria immagini singola (Gallery One), inclusa la visualizzazione, l'eliminazione e la gestione delle copertine.
 */
class GalleryOneController extends BackendController
{
    private GalleryOneModel $galleryModel;

    public function __construct()
    {
        $this->galleryModel = model(GalleryOneModel::class);
    }

    private function getPosts(?string $scope = null): array
    {
        $posts = $this->request->getPost();

        if ($scope === 'account'):
            $posts['entity'] = 'admins';
            $posts['uuid'] = (string) ($this->currentAdmin->uuid ?? '');
        endif;

        return $posts;
    }

    private function canAccess(array $posts): bool
    {
        if (($posts['entity'] ?? '') === 'brands'):
            if ($this->currentAdmin === null):
                return false;
            endif;

            return (isset($this->currentAdmin->permissions->all) && $this->currentAdmin->permissions->all === true)
                || (isset($this->currentAdmin->permissions->brands_add) && $this->currentAdmin->permissions->brands_add === true)
                || (isset($this->currentAdmin->permissions->brands_edit) && $this->currentAdmin->permissions->brands_edit === true);
        endif;

        if (($posts['entity'] ?? '') === 'categories'):
            if ($this->currentAdmin === null):
                return false;
            endif;

            return (isset($this->currentAdmin->permissions->all) && $this->currentAdmin->permissions->all === true)
                || (isset($this->currentAdmin->permissions->categories_add) && $this->currentAdmin->permissions->categories_add === true)
                || (isset($this->currentAdmin->permissions->categories_edit) && $this->currentAdmin->permissions->categories_edit === true);
        endif;

        if (($posts['entity'] ?? '') === 'products'):
            if ($this->currentAdmin === null):
                return false;
            endif;

            return (isset($this->currentAdmin->permissions->all) && $this->currentAdmin->permissions->all === true)
                || (isset($this->currentAdmin->permissions->products_add) && $this->currentAdmin->permissions->products_add === true)
                || (isset($this->currentAdmin->permissions->products_edit) && $this->currentAdmin->permissions->products_edit === true);
        endif;

        if (($posts['entity'] ?? '') !== 'admins'):
            return true;
        endif;

        if ($this->currentAdmin === null):
            return false;
        endif;

        return (int) $this->currentAdmin->superadmin === 1
            || hash_equals((string) $this->currentAdmin->uuid, (string) ($posts['uuid'] ?? ''));
    }

    private function accessDenied(): ResponseInterface
    {
        return $this->jsonResponse(['result' => false, 'message' => lang('backend/global.errors.err403')], 403);
    }

    private function invalidRequest(): ResponseInterface
    {
        return $this->jsonResponse(['result' => false, 'message' => lang('backend/global.errors.err403')], 400);
    }

    public function showGallery(?string $scope = null): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->getPosts($scope);
            $rules = $this->galleryModel->getImagesValidateFields();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/components/galleryOne.messages.validationToastErrors', [$errorMessage], lang('backend/components/galleryOne.messages.validationToastErrors'))]);
            endif;

            if ( ! $this->canAccess($posts)):
                return $this->accessDenied();
            endif;

            $data = ['entity' => $posts['entity'], 'uuid' => $posts['uuid'], 'context' => $posts['context'], 'images' => $this->galleryModel->getImages($posts)];
            return $this->jsonResponse(['result' => true, 'output' => view('backend/components/galleryOne/galleryOneView', $data)]);
        endif;

        return $this->invalidRequest();
    }

    public function deleteImage(?string $scope = null): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->getPosts($scope);
            $rules = $this->galleryModel->deleteImageValidateFields();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/components/galleryOne.messages.validationToastErrors', [$errorMessage], lang('backend/components/galleryOne.messages.validationToastErrors'))]);
            endif;

            if ( ! $this->canAccess($posts)):
                return $this->accessDenied();
            endif;

            if ( ! $this->galleryModel->deleteImage($posts)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/galleryOne.messages.deleteError')]);
            endif;

            $data = ['entity' => $posts['entity'], 'uuid' => $posts['uuid'], 'context' => $posts['context'], 'filename' => $posts['filename'], 'images' => $this->galleryModel->getImages($posts)];
            return $this->jsonResponse(['result' => true, 'message' => lang('backend/components/galleryOne.messages.deleteSuccess'), 'output' => view('backend/components/galleryOne/galleryOneView', $data)]);
        endif;

        return $this->invalidRequest();
    }

    public function setCover(?string $scope = null): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->getPosts($scope);
            $rules = $this->galleryModel->coverValidateFields();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/components/galleryOne.messages.validationToastErrors', [$errorMessage], lang('backend/components/galleryOne.messages.validationToastErrors'))]);
            endif;

            if ( ! $this->canAccess($posts)):
                return $this->accessDenied();
            endif;

            if ( ! $this->galleryModel->setCover($posts)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/galleryOne.messages.setCoverError')]);
            endif;

            $data = ['entity' => $posts['entity'], 'uuid' => $posts['uuid'], 'context' => $posts['context'], 'images' => $this->galleryModel->getImages($posts)];
            return $this->jsonResponse(['result' => true, 'message' => lang('backend/components/galleryOne.messages.setCoverSuccess'), 'output' => view('backend/components/galleryOne/galleryOneView', $data)]);
        endif;

        return $this->invalidRequest();
    }

    public function removeCover(?string $scope = null): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $posts = $this->getPosts($scope);
            $rules = $this->galleryModel->coverValidateFields();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/components/galleryOne.messages.validationToastErrors', [$errorMessage], lang('backend/components/galleryOne.messages.validationToastErrors'))]);
            endif;

            if ( ! $this->canAccess($posts)):
                return $this->accessDenied();
            endif;

            if ( ! $this->galleryModel->removeCover($posts)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/galleryOne.messages.removeCoverError')]);
            endif;

            $data = ['entity' => $posts['entity'], 'uuid' => $posts['uuid'], 'context' => $posts['context'], 'images' => $this->galleryModel->getImages($posts)];
            return $this->jsonResponse(['result' => true, 'message' => lang('backend/components/galleryOne.messages.removeCoverSuccess'), 'output' => view('backend/components/galleryOne/galleryOneView', $data)]);
        endif;

        return $this->invalidRequest();
    }
}
