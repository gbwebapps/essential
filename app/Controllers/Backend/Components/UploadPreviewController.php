<?php declare(strict_types=1);

namespace App\Controllers\Backend\Components;

use App\Controllers\Backend\BackendController;
use CodeIgniter\HTTP\ResponseInterface;

use App\Models\Backend\Components\UploadPreviewModel;

/**
 * Gestisce il caricamento e il salvataggio asincrono delle immagini di anteprima (Upload Preview) per i componenti del backend.
 */
class UploadPreviewController extends BackendController 
{
    /**
     * Istanza del modello responsabile della validazione, elaborazione e salvataggio fisico dei file immagine
     * @var UploadPreviewModel 
     */
    protected UploadPreviewModel $uploadPreview;

    /**
     * Inizializza il controller e carica il modello dedicato alle operazioni di upload delle anteprime.
     */
    public function __construct()
    {
        $this->uploadPreview = model(UploadPreviewModel::class);
    }

	/**
	 * Verifica che l'amministratore corrente possa modificare le immagini dell'entità richiesta.
	 *
	 * @param array $posts Parametri validati contenenti entity e uuid
	 * @return bool True se l'accesso è consentito, false altrimenti
	 */
	private function canAccess(array $posts): bool
	{
		if (($posts['entity'] ?? '') !== 'admins' || $this->currentAdmin === null):
			return false;
		endif;

		return (int) $this->currentAdmin->superadmin === 1
			|| hash_equals((string) $this->currentAdmin->uuid, (string) ($posts['uuid'] ?? ''));
	}

	/**
	 * Restituisce la risposta JSON standard per un tentativo di accesso non autorizzato.
	 *
	 * @return ResponseInterface Risposta HTTP 403
	 */
	private function accessDenied(): ResponseInterface
	{
		return $this->jsonResponse(['result' => false, 'message' => lang('backend/global.errors.err403')], 403);
	}

    /**
     * Elabora la richiesta di caricamento multiplo, validando i parametri di contesto e i file inviati prima di delegarne il salvataggio al modello.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito dell'operazione, i dati processati o gli eventuali errori di validazione
     */
    public function saveImages(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')) :

            /* Strutturato in array per consentire la validazione di validateData */
            $images = ['images' => $this->request->getFileMultiple('images') ?? []];

            if(empty($images['images'])):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/uploadPreview.messages.imagesRequired')]);
            endif;

            $hidden = $this->request->getPost(['uuid', 'entity', 'context']);

            $hiddenRules = $this->uploadPreview->uploadPreviewHiddenRules();
            $imagesRules = $this->uploadPreview->uploadPreviewImagesRules();

            /* Validazione campi nascosti */
            if ( ! $this->validateData($hidden, $hiddenRules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/uploadPreview.messages.validationToastErrors'), $errorMessage)]);
            endif;

			if ( ! $this->canAccess($hidden)):
				return $this->accessDenied();
			endif;

            /* Validazione immagini */
            if ( ! $this->validateData($images, $imagesRules)) :
                return $this->jsonResponse(['imagesErrors' => $this->validator->getErrors(), 'message' => lang('backend/components/uploadPreview.messages.validationErrors')]);
            endif;

            /* Unione dei dati per il salvataggio (sostituisce la variabile $posts mancante) */
            $posts = array_merge($hidden, $images);
            $json  = $this->uploadPreview->saveImages($posts);

            return $this->jsonResponse($json);

        endif;

        return service('response')->setStatusCode(400);
    }
}
