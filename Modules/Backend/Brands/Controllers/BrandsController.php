<?php declare(strict_types = 1); 

namespace Modules\Backend\Brands\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Backend\Brands\Config\Views;
use Modules\Backend\Brands\Models\BrandsModel;
use Modules\Backend\Brands\Libraries\BrandsClass;
use App\Controllers\Backend\BackendController; 

use App\Models\Backend\Components\GalleryOneModel;

/**
 * Gestisce la sezione amministrativa dedicata alle marche.
 */
class BrandsController extends BackendController 
{
	/**
	 * Restituisce esclusivamente i file realmente inviati, escludendo il placeholder generato dal campo file vuoto.
	 *
	 * @return array<array-key, \CodeIgniter\HTTP\Files\UploadedFile> File ricevuti dalla richiesta
	 */
	private function getUploadedImages(): array
	{
		$images = $this->request->getFileMultiple('images') ?? [];

		return array_filter($images, static function ($image): bool {
			return $image->getError() !== UPLOAD_ERR_NO_FILE;
		});
	}

	private function targetAccessError(array $record): ?ResponseInterface
	{
		if ($record['result'] === false):
			return $this->jsonResponse(['result' => false, 'message' => $record['message']]);
		endif;

		if (($record['row']->deleted_at ?? null) !== null):
			return $this->jsonResponse(['result' => false, 'message' => lang('backend/brands.messages.cannotModifyDeleted')]);
		endif;
		
		return null;
	}

    /**
     * Istanza del modello dedicato ai brand
     * 
     * @var BrandsModel 
     */
    protected BrandsModel $brandsModel;

    /**
     * Istanza della libreria di presentazione del modulo
     * 
     * @var BrandsClass 
     */
    protected BrandsClass $brandsClass;

    /**
     * Istanza del modello responsabile della galleria immagini
     * 
     * @var GalleryOneModel 
     */
    protected GalleryOneModel $galleryOneModel;

    /**
     * Inizializza il controller, configura i metadati della pagina e istanzia i modelli e le classi necessarie.
     *
     * @param RequestInterface $request Istanza della richiesta HTTP corrente
     * @param ResponseInterface $response Istanza della risposta HTTP per il client
     * @param LoggerInterface $logger Istanza del sistema di log per la registrazione degli eventi
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'brands';
        $this->data['entity'] = 'brands';

        $this->brandsModel = model(BrandsModel::class);
        $this->brandsClass = new BrandsClass();

        $this->galleryOneModel = model(GalleryOneModel::class);
    }

    /**
     * Renderizza la vista iniziale della sezione brand.
     *
     * @return string HTML renderizzato della vista index
     */
    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/brands.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render(Views::INDEX_VIEW, $this->data);
    }

    /**
     * Gestisce il caricamento e il filtraggio asincrono dell'elenco dei brand.
     *
     * @return string|ResponseInterface Risposta JSON con i dati filtrati o l'HTML della vista per l'elenco generale
     */
    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
        
            $rules = $this->brandsModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->brandsModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/brands.messages.validationErrors')]);
            endif;
            
            $this->data['data'] = $this->brandsModel->getData($posts);

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;

                $json['output'] = view(Views::SHOW_ALL_PARTIAL, $this->data);
                $json['trashCounts'] = $this->brandsModel->getTrashCounts();
                $json['menuCounts'] = model(\App\Models\Backend\MenuCountsModel::class)->getCounts($this->currentAdmin, 'brands');
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/brands.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-tags"></i>';

        return $this->render(Views::SHOW_ALL_VIEW, $this->data);
    }

    /**
     * Gestisce la creazione di un brand e l'eventuale caricamento delle immagini.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito del salvataggio o l'HTML del modulo di inserimento
     */
    public function add(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->jsonResponse(['result' => true,'output' => view(Views::ADD_PARTIAL, $this->data)]);
            endif;

            $rules = $this->brandsModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/brands.messages.validationErrors')]);
            endif;

            $json = $this->brandsModel->add($posts);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if($json['result'] === true):
                $json['output'] = view(Views::ADD_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'add';
        
        $this->data['title'] = lang('backend/brands.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-plus"></i>';

        return $this->render(Views::ADD_VIEW, $this->data);
    }

    /**
     * Gestisce la modifica di un brand esistente e impedisce modifiche ai record cestinati.
     *
     * @param string|null $uuid Identificativo univoco del brand da modificare
     * @return string|ResponseInterface Risposta JSON con l'esito dell'aggiornamento o l'HTML del modulo di modifica
     * @throws \CodeIgniter\Exceptions\PageNotFoundException Se l'identificativo non è valido o il brand non esiste
     */
    public function edit(?string $uuid = null): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if(( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/brands.errors.uuid')]);
            endif;

            $brand = $this->brandsModel->getByUUID($posts['uuid']);

			if($brand['result'] === false):
				return $this->jsonResponse(['result' => false, 'message' => $brand['message']]);
			endif;

			if (($brand['row']->deleted_at ?? null) !== null):
				return $this->jsonResponse(['result' => false, 'message' => lang('backend/brands.messages.cannotModifyDeleted')]);
			endif;

            /* Caso 1: Refresh della vista parziale */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                $this->data['brand'] = $brand['row'] ?? null;
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'brands', 'uuid' => $posts['uuid']]);

                return $this->jsonResponse(['result' => true,'output' => view(Views::EDIT_PARTIAL, $this->data)]);
            endif;

            $rules = $this->brandsModel->editValidationRules($posts);
                        
            if ( ! $this->validateData($posts, $rules)):

                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/brands.messages.validationErrors')]);
            endif;

            $result = $this->brandsModel->edit($posts);

            $json = ['result' => $result['result'], 'message' => $result['message']];

            /* Caso 2: Salvataggio riuscito */
            if ($result['result'] === true):

                $this->data['brand'] = $result['row'];
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'brands', 'uuid' => $posts['uuid']]);
                
                $json['output'] = view(Views::EDIT_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        /* GET Request - Caricamento iniziale della pagina */
        if (($uuid === null) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', lang('backend/brands.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $brand = $this->brandsModel->getByUUID($uuid);

        if($brand['result'] === false):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', $brand['message'])->with('class', 'app-alert-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($brand['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', lang('backend/brands.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'edit';
        
        $this->data['title'] = lang('backend/brands.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-pen"></i>';

        $this->data['brand'] = $brand['row'] ?? null;
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'brands', 'uuid' => $uuid]);

        return $this->render(Views::EDIT_VIEW, $this->data);
    }

    /**
     * Renderizza la vista di dettaglio di un brand.
     *
     * @param string $uuid Identificativo univoco del brand
     * @return RedirectResponse|string Redirect in caso di parametri non validi o l'HTML renderizzato della vista di dettaglio
     */
    public function show(string $uuid): RedirectResponse|string
    {
        if ( ! $this->regexp->validateUUID($uuid)):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', lang('backend/brands.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $brand = $this->brandsModel->getByUUID($uuid);

        if ($brand['result'] === false):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', $brand['message'])->with('class', 'app-alert-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($brand['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/brands/showAll'))->with('message', lang('backend/brands.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'show';

        $this->data['title'] = lang('backend/brands.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-tag"></i>';

        $this->data['brand'] = $brand['row'] ?? null;
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'show';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'brands', 'uuid' => $uuid]);

        return $this->render(Views::SHOW_VIEW, $this->data);
    }

    /**
     * Elimina definitivamente un brand non associato a prodotti.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'eliminazione definitiva
     */
    public function hardDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->brandsModel->hardDelete($posts);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Sposta nel cestino un brand non associato a prodotti.
     *
     * @return ResponseInterface Risposta JSON con l'esito della disattivazione
     */
    public function softDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->brandsModel->softDelete($posts);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Ripristina un brand precedentemente inserito nel cestino.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione di ripristino
     */
	public function restoreDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->brandsModel->restoreDelete($posts);

            return $this->jsonResponse($json);

        endif;

		return service('response')->setStatusCode(400);
    }

    /**
     * Modifica asincronamente lo stato di un brand e aggiorna la porzione di interfaccia interessata.
     *
     * @return ResponseInterface Risposta JSON con l'esito del cambio di stato e l'HTML aggiornato
     */
    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

            $json = $this->brandsModel->changeStatus($posts);

            if($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if(isset($posts['context']) && $posts['context'] === 'show'):

                $this->data['brand'] = $json['brand'];

                $json['statusView'] = view(Views::CHANGE_STATUS_PARTIAL, $this->data);
                $json['metaView'] = view(Views::META_DATA_PARTIAL, $this->data); 

            endif;

            unset($json['brand']);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Recupera e ricarica i dati generali del brand.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei dati generali
     */
    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

			$record = $this->brandsModel->getByUUID($posts['uuid']);

			if (($accessError = $this->targetAccessError($record)) !== null):
				return $accessError;
			endif;

			if($record['result'] === true):

                $json = ['result' => true];
                $this->data['brand'] = $record['row'];

				if(isset($posts['context']) && $posts['context'] === 'show'):
					$json['output'] = view(Views::SHOW_GENERAL_DATA_PARTIAL, $this->data);
				endif;

                if(isset($posts['context']) && $posts['context'] === 'edit'):
                    $json['output'] = view(Views::EDIT_GENERAL_DATA_PARTIAL, $this->data);
                endif;

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

            endif;

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
    }

    /**
     * Ricarica i metadati temporali del brand.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei metadati
     */
    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->brandsModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/brands.messages.validationToastErrors'), $errorMessage)]);
            endif;

			$record = $this->brandsModel->getByUUID($posts['uuid']);

			if (($accessError = $this->targetAccessError($record)) !== null):
				return $accessError;
			endif;

			if($record['result'] === true):

                $json = ['result' => true];

                $this->data['brand'] = $record['row'];

                $json['output'] = view(Views::META_DATA_PARTIAL, $this->data);

            else:

                $json = ['result' => false];
                $json['message'] = $record['message'];

			endif;

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
    }
}
