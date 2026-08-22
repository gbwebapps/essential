<?php declare(strict_types=1);

namespace App\Models\Backend\Components;

use App\Models\Backend\BackendModel;

class UploadPreviewModel extends BackendModel 
{
	private array $uploadPreviewAllowedFields = ['uuid', 'entity', 'context', 'images'];

	public function uploadPreviewHiddenRules()
	{
		return [
			'entity' => [
			    'label' => lang('backend/components/uploadPreviewImg.labels.entity'),
			    'rules' => ['required', 'alpha'],
			],
			'uuid' => [
			    'label' => lang('backend/components/uploadPreviewImg.labels.uuid'),
			    'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
			],
			'context' => [
			    'label' => lang('backend/components/uploadPreviewImg.labels.context'),
			    'rules' => ['required', 'in_list[edit,show]'],
			],
		];
	}

	public function uploadPreviewImagesRules()
	{
		return [
			'images' => [
			    'rules' => ['checkImages[size:2048,ext:png|jpg|jpeg|webp]']
			]
		];
	}

	public function saveImages(array $posts): array
	{
		$posts = $this->checkAllowedFields($posts, $this->uploadPreviewAllowedFields);

		try {

		    if ( ! empty($posts['images'])):

		        $uploadService = new \App\Libraries\Backend\UploadClass();
		        $filenames = $uploadService->doUpload($posts['images'], $posts['entity'], $posts['uuid']);

		        if ($filenames):

		            /* Eseguiamo la query secca. Se fallisce, restituisce false o lancia un'eccezione */
		            $inserted = $this->insertImages($filenames, $posts['uuid'], $posts['entity'], $posts['context']);
		            
		            if ($inserted === false):
		                log_message('error', lang('backend/components/uploadPreviewImg.messages.addError'));
		                return ['result' => false, 'message' => lang('backend/components/uploadPreviewImg.messages.saveImagesError')];
		            endif;
		        endif;

		        log_admin_activity('SAVE_IMAGES', 'upload preview', 'Salvataggio immagini.');

		        return ['result' => true, 'message' => lang('backend/components/uploadPreviewImg.messages.saveImagesSuccess')];

		    endif;

		} catch(\Throwable $e) {

		    /* Qualsiasi errore strutturale finisce qui dentro in sicurezza */
		    log_message('error', lang('backend/components/uploadPreviewImg.messages.saveImagesError') . ' - ' . $e);
		    return ['result' => false, 'message' => lang('backend/components/uploadPreviewImg.messages.saveImagesError')];
		}
	}
}