<?php declare(strict_types = 1); 

namespace Modules\Backend\Categories\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Backend\Categories\Config\Views;
use Modules\Backend\Categories\Models\CategoriesModel;
use Modules\Backend\Categories\Libraries\CategoriesClass;
use App\Controllers\Backend\BackendController; 

use App\Models\Backend\Components\GalleryOneModel;

/**
 * Gestisce la sezione amministrativa dedicata alle marche.
 */
class CategoriesController extends BackendController 
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
			return $this->jsonResponse(['result' => false, 'message' => lang('backend/categories.messages.cannotModifyDeleted')]);
		endif;
		
		return null;
	}

    /**
     * Prepara l'albero e il percorso della categoria corrente per le viste.
     *
     * @param string|null $selectedUuid Categoria da evidenziare
     * @param bool $includeDeleted Include anche le categorie presenti nel cestino
     * @return array Struttura gerarchica e mappe dei percorsi
     */
    private function loadCategoryHierarchy(?string $selectedUuid = null, bool $includeDeleted = false): array
    {
        $hierarchy = $this->categoriesModel->getCategoryHierarchy($includeDeleted);

        $this->data['categoryTree'] = $hierarchy['tree'];
        $this->data['selectedCategoryUuid'] = $selectedUuid;
        $this->data['selectedCategoryPath'] = ($selectedUuid !== null) ? ($hierarchy['pathsByUuid'][$selectedUuid] ?? null) : null;

        return $hierarchy;
    }

    /**
     * Istanza del modello dedicato ai category
     * 
     * @var CategoriesModel 
     */
    protected CategoriesModel $categoriesModel;

    /**
     * Istanza della libreria di presentazione del modulo
     * 
     * @var CategoriesClass 
     */
    protected CategoriesClass $categoriesClass;

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

        $this->data['controller'] = 'categories';
        $this->data['entity'] = 'categories';

        $this->categoriesModel = model(CategoriesModel::class);
        $this->categoriesClass = new CategoriesClass();

        $this->galleryOneModel = model(GalleryOneModel::class);
    }

    /**
     * Renderizza la vista iniziale della sezione category.
     *
     * @return string HTML renderizzato della vista index
     */
    public function index(): string
    {
        $this->data['action'] = 'index';
        
        $this->data['title'] = lang('backend/categories.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render(Views::INDEX_VIEW, $this->data);
    }

    /**
     * Gestisce il caricamento e il filtraggio asincrono dell'elenco dei category.
     *
     * @return string|ResponseInterface Risposta JSON con i dati filtrati o l'HTML della vista per l'elenco generale
     */
    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
        
            $rules = $this->categoriesModel->showAllValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates']  = $posts['searchDates']  ?? [];

            $rules = $this->categoriesModel->showAllSearchValidationRules();
            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);
                return $this->jsonResponse(['errors' => $formattedErrors, 'message' => lang('backend/categories.messages.validationErrors')]);
            endif;
            
            $this->data['data'] = $this->categoriesModel->getData($posts);

            $json = [];

            if($this->data['data']['result'] === true):

                $this->data['posts'] = $posts;
                $hierarchy = $this->loadCategoryHierarchy(null, true);

                foreach ($this->data['data']['records'] as $category):
                    $category->category_path = $hierarchy['pathsById'][(int) $category->id] ?? $category->category;
                endforeach;

                $json['output'] = view(Views::SHOW_ALL_PARTIAL, $this->data);
                $json['trashCounts'] = $this->categoriesModel->getTrashCounts();
                $json['menuCounts'] = model(\App\Models\Backend\MenuCountsModel::class)->getCounts($this->currentAdmin, 'categories');
                $json['result'] = true;

            elseif($this->data['data']['result'] === false):

                $json['result'] = false;
                $json['message'] = $this->data['data']['message'];

            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'showAll';
        
        $this->data['title'] = lang('backend/categories.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-folder-tree"></i>';
        $this->loadCategoryHierarchy(null, true);

        return $this->render(Views::SHOW_ALL_VIEW, $this->data);
    }

    /**
     * Gestisce la creazione di un category e l'eventuale caricamento delle immagini.
     *
     * @return string|ResponseInterface Risposta JSON con l'esito del salvataggio o l'HTML del modulo di inserimento
     */
    public function add(): string|ResponseInterface
    {
        $this->data['parentCategories'] = $this->categoriesModel->getParentOptions();
        $this->loadCategoryHierarchy();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->jsonResponse(['result' => true,'output' => view(Views::ADD_PARTIAL, $this->data)]);
            endif;

            $rules = $this->categoriesModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/categories.messages.validationErrors')]);
            endif;

            $json = $this->categoriesModel->add($posts);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if($json['result'] === true):
                $this->data['parentCategories'] = $this->categoriesModel->getParentOptions();
                $this->loadCategoryHierarchy();
                $json['output'] = view(Views::ADD_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'add';
        
        $this->data['title'] = lang('backend/categories.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-plus"></i>';

        return $this->render(Views::ADD_VIEW, $this->data);
    }

    /**
     * Gestisce la modifica di un category esistente e impedisce modifiche ai record cestinati.
     *
     * @param string|null $uuid Identificativo univoco del category da modificare
     * @return string|ResponseInterface Risposta JSON con l'esito dell'aggiornamento o l'HTML del modulo di modifica
     * @throws \CodeIgniter\Exceptions\PageNotFoundException Se l'identificativo non è valido o il category non esiste
     */
    public function edit(?string $uuid = null): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge($this->request->getPost(), ['images' => $this->getUploadedImages()]);

            if(( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/categories.errors.uuid')]);
            endif;

            $category = $this->categoriesModel->getByUUID($posts['uuid']);

			if($category['result'] === false):
				return $this->jsonResponse(['result' => false, 'message' => $category['message']]);
			endif;

			if (($category['row']->deleted_at ?? null) !== null):
				return $this->jsonResponse(['result' => false, 'message' => lang('backend/categories.messages.cannotModifyDeleted')]);
			endif;

            $this->data['parentCategories'] = $this->categoriesModel->getParentOptions($posts['uuid']);
            $this->loadCategoryHierarchy($posts['uuid']);

            /* Caso 1: Refresh della vista parziale */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):

                $this->data['category'] = $category['row'] ?? null;
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'categories', 'uuid' => $posts['uuid']]);

                return $this->jsonResponse(['result' => true,'output' => view(Views::EDIT_PARTIAL, $this->data)]);
            endif;

            $rules = $this->categoriesModel->editValidationRules($posts);
                        
            if ( ! $this->validateData($posts, $rules)):

                return $this->jsonResponse(['errors' => $this->validator->getErrors(), 'message' => lang('backend/categories.messages.validationErrors')]);
            endif;

            $result = $this->categoriesModel->edit($posts);

            $json = ['result' => $result['result'], 'message' => $result['message']];

            /* Caso 2: Salvataggio riuscito */
            if ($result['result'] === true):

                $this->data['category'] = $result['row'];
                $this->data['uuid'] = $posts['uuid'];
                $this->data['parentCategories'] = $this->categoriesModel->getParentOptions($posts['uuid']);
                $this->loadCategoryHierarchy($posts['uuid']);

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'categories', 'uuid' => $posts['uuid']]);
                
                $json['output'] = view(Views::EDIT_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        /* GET Request - Caricamento iniziale della pagina */
        if (($uuid === null) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', lang('backend/categories.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $category = $this->categoriesModel->getByUUID($uuid);

        if($category['result'] === false):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', $category['message'])->with('class', 'app-alert-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($category['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', lang('backend/categories.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'edit';
        
        $this->data['title'] = lang('backend/categories.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-pen"></i>';

        $this->data['category'] = $category['row'] ?? null;
        $this->data['uuid'] = $uuid;
        $this->data['parentCategories'] = $this->categoriesModel->getParentOptions($uuid);
        $this->loadCategoryHierarchy($uuid);

        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'categories', 'uuid' => $uuid]);

        return $this->render(Views::EDIT_VIEW, $this->data);
    }

    /**
     * Renderizza la vista di dettaglio di un category.
     *
     * @param string $uuid Identificativo univoco del category
     * @return RedirectResponse|string Redirect in caso di parametri non validi o l'HTML renderizzato della vista di dettaglio
     */
    public function show(string $uuid): RedirectResponse|string
    {
        if ( ! $this->regexp->validateUUID($uuid)):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', lang('backend/categories.errors.uuid'))->with('class', 'app-alert-danger fw-bold');
        endif;

        $category = $this->categoriesModel->getByUUID($uuid);

        if ($category['result'] === false):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', $category['message'])->with('class', 'app-alert-danger fw-bold');
        endif;

        /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
        if ($category['row']->deleted_at !== null):
            return redirect()->to(base_url('backend/categories/showAll'))->with('message', lang('backend/categories.messages.cannotModifyDeleted'))->with('class', 'app-alert-danger fw-bold')->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'show';

        $this->data['title'] = lang('backend/categories.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-tag"></i>';

        $this->data['category'] = $category['row'] ?? null;
        $this->data['uuid'] = $uuid;
        $this->loadCategoryHierarchy($uuid);

        $this->data['context'] = 'show';
        $this->data['images'] = $this->galleryOneModel->getImages(['entity' => 'categories', 'uuid' => $uuid]);

        return $this->render(Views::SHOW_VIEW, $this->data);
    }

    /**
     * Elimina definitivamente un category non associato a prodotti.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'eliminazione definitiva
     */
    public function hardDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $json = $this->categoriesModel->hardDelete($posts);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Sposta nel cestino un category non associato a prodotti.
     *
     * @return ResponseInterface Risposta JSON con l'esito della disattivazione
     */
    public function softDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $json = $this->categoriesModel->softDelete($posts);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Ripristina un category precedentemente inserito nel cestino.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione di ripristino
     */
	public function restoreDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $json = $this->categoriesModel->restoreDelete($posts);

            return $this->jsonResponse($json);

        endif;

		return service('response')->setStatusCode(400);
    }

    /**
     * Modifica asincronamente lo stato di un category e aggiorna la porzione di interfaccia interessata.
     *
     * @return ResponseInterface Risposta JSON con l'esito del cambio di stato e l'HTML aggiornato
     */
    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $json = $this->categoriesModel->changeStatus($posts);

            if($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if(isset($posts['context']) && $posts['context'] === 'show'):

                $this->data['category'] = $json['category'];

                $json['statusView'] = view(Views::CHANGE_STATUS_PARTIAL, $this->data);
                $json['metaView'] = view(Views::META_DATA_PARTIAL, $this->data); 

            endif;

            unset($json['category']);

			return $this->jsonResponse($json);

		endif;

		return service('response')->setStatusCode(400);
	}

    /**
     * Recupera e ricarica i dati generali del category.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei dati generali
     */
    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

			$record = $this->categoriesModel->getByUUID($posts['uuid']);

			if (($accessError = $this->targetAccessError($record)) !== null):
				return $accessError;
			endif;

			if($record['result'] === true):

                $json = ['result' => true];
                $this->data['category'] = $record['row'];

                if(isset($posts['context']) && $posts['context'] === 'edit'):
                    $this->data['parentCategories'] = $this->categoriesModel->getParentOptions($posts['uuid']);
                endif;

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
     * Recupera e ricarica l'albero delle categorie senza modificare gli altri pannelli.
     *
     * @return ResponseInterface Risposta JSON contenente il markup aggiornato dell'albero
     */
    public function getTree(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->treeValidationRules($posts);

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

            $selectedUuid = null;

            if (in_array($posts['context'], ['edit', 'show'], true)):
                $record = $this->categoriesModel->getByUUID($posts['uuid']);

                if (($accessError = $this->targetAccessError($record)) !== null):
                    return $accessError;
                endif;

                $selectedUuid = $posts['uuid'];
            endif;

            $this->loadCategoryHierarchy($selectedUuid);

            $output = view(Views::CATEGORY_TREE_PARTIAL, [
                'categoryTreeDisplay' => 'content',
                'categoryTree' => $this->data['categoryTree'],
                'selectedCategoryUuid' => $this->data['selectedCategoryUuid'],
                'selectedCategoryPath' => $this->data['selectedCategoryPath'],
            ]);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Ricarica i metadati temporali del category.
     *
     * @return ResponseInterface Risposta JSON contenente l'HTML aggiornato dei metadati
     */
    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->categoriesModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                
                return $this->jsonResponse(['result'  => false, 'message' => ui_message('backend/categories.messages.validationToastErrors', [$errorMessage], lang('backend/categories.messages.validationToastErrors'))]);
            endif;

			$record = $this->categoriesModel->getByUUID($posts['uuid']);

			if (($accessError = $this->targetAccessError($record)) !== null):
				return $accessError;
			endif;

			if($record['result'] === true):

                $json = ['result' => true];

                $this->data['category'] = $record['row'];

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
