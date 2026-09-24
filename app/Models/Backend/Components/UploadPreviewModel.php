<?php declare(strict_types=1);

namespace App\Models\Backend\Components;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato al Componente Globale di caricamento e anteprima immagini (UploadPreview).
 * 
 * Estende il BackendModel e incapsula la logica asincrona per l'upload di file multimediali 
 * slegato dal salvataggio del modulo principale. Riceve i file via AJAX, applica le regole 
 * di validazione rigorose e delega il salvataggio fisico su disco e la persistenza 
 * a database tramite i servizi condivisi.
 */
class UploadPreviewModel extends BackendModel 
{
	/**
	 * Whitelist dei campi HTTP POST consentiti per il salvataggio delle immagini.
	 * Previene l'iniezione di parametri malevoli o non previsti durante la chiamata asincrona.
	 * 
	 * @var array 
	 */
	private array $uploadPreviewAllowedFields = ['uuid', 'entity', 'context', 'images'];

	/**
	 * Genera le regole di validazione per i parametri di sistema nascosti (Hidden Inputs).
	 * 
	 * Assicura che la richiesta asincrona di upload sia legittima e correttamente associata 
	 * a un'entità di destinazione. Valida l'integrità crittografica del formato UUID, impone 
	 * che l'entità sia una stringa alfabetica pulita e restringe il contesto operativo 
	 * esclusivamente a visualizzazione ('show') o modifica ('edit').
	 *
	 * @return array Regole native di CodeIgniter per i campi nascosti
	 */
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

	/**
	 * Genera le regole di validazione specifiche per il payload dei file caricati.
	 * 
	 * Sfrutta la regola personalizzata `checkImages`. L'applicazione dei limiti opera con 
	 * una logica di sovrascrittura granulare (fallback): il sistema carica prima le impostazioni 
	 * globali di sicurezza (dal Database o dai file di Configurazione). I parametri inline 
	 * definiti in questo metodo (es. `size:2048` o `ext:png|jpg|jpeg|webp`) hanno la priorità assoluta: 
	 * ogni argomento dichiarato sovrascrive unicamente la propria controparte globale, 
	 * lasciando intatti ed ereditati i restanti vincoli di sistema.
	 *
	 * @return array Regole di validazione per l'array di file
	 */
	public function uploadPreviewImagesRules()
	{
		return [
			'images' => [
			    'rules' => ['checkImages[size:2048,ext:png|jpg|jpeg|webp]']
			]
		];
	}

	/**
	 * Processa l'upload dei file e salva le relative informazioni a database.
	 * 
	 * Il metodo filtra i parametri di base tramite la whitelist e, se rileva file in arrivo, 
	 * istanzia il servizio `UploadClass` per eseguire la scrittura fisica sicura sul server. 
	 * Se il caricamento fisico va a buon fine, richiama il metodo ereditato `insertImages` 
	 * per mappare i file sul database collegandoli all'UUID dell'entità proprietaria. 
	 * Include un blocco try-catch per intercettare crolli strutturali del file system o di MySQL, 
	 * restituendo sempre al frontend una risposta JSON sicura e pulita.
	 *
	 * @param array $posts Il payload combinato (dati POST sanificati e array dei file 'images')
	 * @return array Esito strutturato dell'operazione (result) e messaggio di feedback per l'utente
	 */
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