<?php declare(strict_types=1);

namespace App\Controllers\Backend\Components;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\GalleryOneImgModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gestisce le operazioni asincrone del componente galleria immagini singola (Gallery One), inclusa la visualizzazione, l'eliminazione e la gestione delle copertine.
 */
class GalleryOneController extends BackendController
{
	/**
	 * @var GalleryOneImgModel Istanza del modello dedicato alla gestione dei file e dei record della galleria
	 */
    private GalleryOneImgModel $galleryModel;

    /**
     * Inizializza il controller e carica il modello di riferimento per le operazioni sulla galleria.
     */
    public function __construct()
    {
        $this->galleryModel = model(GalleryOneImgModel::class);
    }

    /**
     * Valida la richiesta e restituisce l'interfaccia renderizzata della galleria con le immagini associate all'entità.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito della validazione e l'HTML generato per la galleria
     */
    public function showGallery(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->getImagesValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->jsonResponse(['result' => true, 'output' => $output]);

		endif;
    }

    /**
     * Elimina fisicamente e logicamente un'immagine dalla galleria e restituisce la vista aggiornata.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'eliminazione, il messaggio di notifica e l'HTML aggiornato
     */
    public function deleteImage(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->deleteImageValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->deleteImage($posts)):
	            return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/galleryOneImg.messages.deleteError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'filename' => $posts['filename'], 
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->jsonResponse(['result' => true, 'message' => lang('backend/components/galleryOneImg.messages.deleteSuccess'), 'output'  => $output]);

		endif;
    }

    /**
     * Imposta un'immagine specifica come copertina principale della galleria e aggiorna l'interfaccia.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'assegnazione, la notifica e l'HTML aggiornato
     */
    public function setCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->coverValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->setCover($posts)):
	            return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/galleryOneImg.messages.setCoverError')]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->jsonResponse(['result'  => true, 'message' => lang('backend/components/galleryOneImg.messages.setCoverSuccess'), 'output'  => $output ]);

	    endif;
    }

    /**
     * Rimuove lo stato di copertina dall'immagine selezionata e aggiorna l'interfaccia della galleria.
     *
     * @return ResponseInterface Risposta JSON con l'esito della rimozione, la notifica e l'HTML aggiornato
     */
    public function removeCover(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

	        $posts = $this->request->getPost();
	    	$rules = $this->galleryModel->coverValidateFields();

	    	/* Validazione campi nascosti */
	    	if ( ! $this->validateData($posts, $rules)) :
	    	    $errorMessage = implode('<br>', $this->validator->getErrors());
	    	    return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/galleryOneImg.messages.validationToastErrors'), $errorMessage)]);
	    	endif;

	        if ( ! $this->galleryModel->removeCover($posts)):
	            return $this->jsonResponse(['result'  => false, 'message' => lang('backend/components/galleryOneImg.messages.removeCoverError') ]);
	        endif;

	        $data = [
	            'entity'  => $posts['entity'],
	            'uuid'    => $posts['uuid'],
	            'context' => $posts['context'],
	            'images'  => $this->galleryModel->getImages($posts) ?? []
	        ];

	        $output = view('backend/components/galleryOneImg/galleryOneImgView', $data);

	        return $this->jsonResponse(['result'  => true, 'message' => lang('backend/components/galleryOneImg.messages.removeCoverSuccess'), 'output'  => $output ]);

	    endif;
    }
}