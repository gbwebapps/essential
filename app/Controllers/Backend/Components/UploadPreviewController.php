<?php declare(strict_types=1);

namespace App\Controllers\Backend\Components;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

use App\Models\Backend\Components\UploadPreviewModel;

class UploadPreviewController extends Controller
{
    protected UploadPreviewModel $uploadPreview;

    public function __construct()
    {
        $this->uploadPreview = model(UploadPreviewModel::class);
    }

    public function saveImages(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            /* Strutturato in array per consentire la validazione di validateData */
            $images = ['images' => $this->request->getFileMultiple('images') ?? []];

            if(empty($images['images'])):
                return $this->response->setJSON(['result' => false, 'message' => lang('backend/components/uploadPreviewImg.messages.imagesRequired')]);
            endif;

            $hidden = $this->request->getPost(['uuid', 'entity', 'context']);

            $hiddenRules = $this->uploadPreview->uploadPreviewHiddenRules();
            $imagesRules = $this->uploadPreview->uploadPreviewImagesRules();

            /* Validazione campi nascosti */
            if ( ! $this->validateData($hidden, $hiddenRules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->response->setJSON(['result'  => false, 'message' => sprintf(lang('backend/components/uploadPreviewImg.messages.validationToastErrors'), $errorMessage)]);
            endif;

            /* Validazione immagini */
            if ( ! $this->validateData($images, $imagesRules)) :
                return $this->response->setJSON(['imagesErrors' => $this->validator->getErrors(), 'message' => lang('backend/components/uploadPreviewImg.messages.validationErrors')]);
            endif;

            /* Unione dei dati per il salvataggio (sostituisce la variabile $posts mancante) */
            $posts = array_merge($hidden, $images);
            $json  = $this->uploadPreview->saveImages($posts);

            return $this->response->setJSON($json);

        endif;
    }
}