<?php declare(strict_types=1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Controller;
use App\Models\Backend\Components\GalleryOneImgModel;
use CodeIgniter\HTTP\ResponseInterface;

class GalleryOneController extends Controller
{
    private GalleryOneImgModel $galleryModel;

    public function __construct()
    {
        $this->galleryModel = model(GalleryOneImgModel::class);
    }

    /**
     * Mostra la galleria di immagini filtrata per entità e uuid.
     */
    public function showGallery(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->getImagesValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result' => true, 'output' => $output]);

		endif;
    }

    /**
     * Elimina un'immagine dal database e dal disco.
     */
    public function deleteImage(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->deleteImageValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->deleteImage($posts)):
	            return $this->response->setJSON(['result' => false, 'message' => lang('backend/components/galleryOneImg.messages.deleteError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'filename' => $posts['filename'], 
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result' => true, 'message' => lang('backend/components/galleryOneImg.messages.deleteSuccess'), 'output'  => $output]);

		endif;
    }

    /**
     * Imposta un'immagine come copertina principale dell'entità.
     */
    public function setCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->coverValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->setCover($posts)):
	            return $this->response->setJSON(['result' => false, 'message' => lang('backend/components/galleryOneImg.messages.setCoverError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result'  => true, 'message' => lang('backend/components/galleryOneImg.messages.setCoverSuccess'), 'output'  => $output ]);

	    endif;
    }

    /**
     * Rimuove lo stato di copertina da un'immagine.
     */
    public function removeCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->coverValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->removeCover($posts)):
	            return $this->response->setJSON(['result'  => false, 'message' => lang('backend/components/galleryOneImg.messages.removeCoverError') ]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result'  => true, 'message' => lang('backend/components/galleryOneImg.messages.removeCoverSuccess'), 'output'  => $output ]);

	    endif;
    }
}