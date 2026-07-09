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

	    	// sbarramento

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts['entity'], $posts['uuid']) ?? []
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
	        $id = (int) $posts['id'];

	        if ( ! $this->galleryModel->deleteImage($id, $posts['entity'], $posts['uuid'], $posts['filename'])):
	            return $this->response->setJSON(['result'  => false, 'message' => lang('backend/components/galleryOneImg.deleteError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'filename' => $posts['filename'], 
	            'images'  => $this->galleryModel->getImages($posts['entity'], $posts['uuid']) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result'  => true, 'message' => lang('backend/components/galleryOneImg.deleteSuccess'), 'output'  => $output]);

		endif;
    }

    /**
     * Imposta un'immagine come copertina principale dell'entità.
     */
    public function setCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	        $id = (int) $posts['id'];

	        if ( ! $this->galleryModel->setCover($id, $posts['entity'], $posts['uuid'])):
	            return $this->response->setJSON(['result' => false, 'message' => lang('backend/components/galleryOneImg.setCoverError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts['entity'], $posts['uuid']) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result'  => true, 'message' => lang('backend/components/galleryOneImg.setCoverSuccess'), 'output'  => $output ]);

	    endif;
    }

    /**
     * Rimuove lo stato di copertina da un'immagine.
     */
    public function removeCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	        $id    = (int) $posts['id'];

	        if ( ! $this->galleryModel->removeCover($id, $posts['entity'], $posts['uuid'])):
	            return $this->response->setJSON(['result'  => false, 'message' => lang('backend/components/galleryOneImg.removeCoverError') ]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts['entity'], $posts['uuid']) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->response->setJSON(['result'  => true, 'message' => lang('backend/components/galleryOneImg.removeCoverSuccess'), 'output'  => $output ]);

	    endif;
    }
}