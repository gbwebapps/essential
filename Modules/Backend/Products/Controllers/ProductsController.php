<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Controllers;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\GalleryOneModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Backend\Products\Config\Views;
use Modules\Backend\Products\Libraries\ProductsClass;
use Modules\Backend\Products\Models\ProductsModel;
use Psr\Log\LoggerInterface;

/**
 * Gestisce la sezione amministrativa dedicata ai prodotti.
 */
class ProductsController extends BackendController
{
    /**
     * Istanza del modello dedicato ai prodotti.
     *
     * @var ProductsModel
     */
    protected ProductsModel $productsModel;

    /**
     * Istanza della libreria di presentazione del modulo.
     *
     * @var ProductsClass
     */
    protected ProductsClass $productsClass;

    /**
     * Istanza del modello responsabile della galleria immagini.
     *
     * @var GalleryOneModel
     */
    protected GalleryOneModel $galleryOneModel;

    /**
     * Restituisce esclusivamente i file realmente inviati.
     *
     * Il campo file multiplo genera un placeholder anche quando non viene
     * selezionata alcuna immagine. Il placeholder deve essere escluso prima
     * della validazione e dell'invio al servizio di upload.
     *
     * @return array<array-key, \CodeIgniter\HTTP\Files\UploadedFile>
     */
    private function getUploadedImages(): array
    {
        $images = $this->request->getFileMultiple('images') ?? [];

        return array_filter($images, static function ($image): bool {
            return $image->getError() !== UPLOAD_ERR_NO_FILE;
        });
    }

    /**
     * Associa gli errori dei singoli UUID al messaggio della rispettiva select multipla.
     * Mantiene il primo errore per campo e lascia invariate le chiavi delle immagini.
     *
     * @return array<string, string> Errori compatibili con i contenitori del form
     */
    private function getFormValidationErrors(): array
    {
        $errors = $this->validator->getErrors();

        foreach ($errors as $field => $message):
            foreach (['brand_uuids', 'category_uuids'] as $relation):
                if (str_starts_with($field, $relation . '.')):
                    $errors[$relation] ??= $message;
                    unset($errors[$field]);
                    break;
                endif;
            endforeach;
        endforeach;

        return $errors;
    }

    /**
     * Verifica che il prodotto esista e non sia stato cestinato.
     *
     * @param array $record Risultato restituito dal modello
     * @return ResponseInterface|null Errore JSON oppure null quando l'accesso è consentito
     */
    private function targetAccessError(array $record): ?ResponseInterface
    {
        if ($record['result'] === false):
            return $this->jsonResponse(['result' => false, 'message' => $record['message']]);
        endif;

        if (($record['row']->deleted_at ?? null) !== null):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/products.messages.cannotModifyDeleted')]);
        endif;

        return null;
    }

    /**
     * Carica le marche e le categorie selezionabili nei form e nei filtri.
     */
    private function loadRelationOptions(): void
    {
        $this->data['brandOptions'] = $this->productsModel->getBrandOptions();
        $this->data['categoryOptions'] = $this->productsModel->getCategoryOptions();
    }

    /**
     * Inizializza il controller e le dipendenze del modulo.
     *
     * @param RequestInterface $request Richiesta HTTP corrente
     * @param ResponseInterface $response Risposta HTTP corrente
     * @param LoggerInterface $logger Servizio di log
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'products';
        $this->data['entity'] = 'products';

        $this->productsModel = model(ProductsModel::class);
        $this->productsClass = new ProductsClass();

        $this->galleryOneModel = model(GalleryOneModel::class);
    }

    /**
     * Renderizza la pagina iniziale del modulo.
     *
     * @return string HTML della vista index
     */
    public function index(): string
    {
        $this->data['action'] = 'index';

        $this->data['title'] = lang('backend/products.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-chart-simple"></i>';

        return $this->render(Views::INDEX_VIEW, $this->data);
    }

    /**
     * Gestisce la pagina elenco e il relativo aggiornamento asincrono.
     *
     * @return string|ResponseInterface Vista completa oppure risposta JSON
     */
    public function showAll(): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();

            $rules = $this->productsModel->showAllValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            $posts['searchFields'] = $posts['searchFields'] ?? [];
            $posts['searchDates'] = $posts['searchDates'] ?? [];

            $rules = $this->productsModel->showAllSearchValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $formattedErrors = removeDot('searchFields.', $this->validator->getErrors());
                $formattedErrors = removeDot('searchDates.', $formattedErrors);

                return $this->jsonResponse([
                    'errors' => $formattedErrors,
                    'message' => lang('backend/products.messages.validationErrors'),
                ]);
            endif;

            $this->data['data'] = $this->productsModel->getData($posts);

            if ($this->data['data']['result'] === false):
                return $this->jsonResponse([
                    'result' => false,
                    'message' => $this->data['data']['message'],
                ]);
            endif;

            $this->data['posts'] = $posts;

            return $this->jsonResponse([
                'result' => true,
                'output' => view(Views::SHOW_ALL_PARTIAL, $this->data),
                'trashCounts' => $this->productsModel->getTrashCounts(),
                'menuCounts' => model(\App\Models\Backend\MenuCountsModel::class)->getCounts($this->currentAdmin, 'products'),
            ]);

        endif;

        $this->data['action'] = 'showAll';

        $this->data['title'] = lang('backend/products.titles.showAll');
        $this->data['icon'] = '<i class="fa-solid fa-boxes-stacked"></i>';

        $this->loadRelationOptions();

        return $this->render(Views::SHOW_ALL_VIEW, $this->data);
    }

    /**
     * Gestisce l'inserimento di un prodotto, delle relazioni e delle immagini.
     *
     * @return string|ResponseInterface Vista completa oppure risposta JSON
     */
    public function add(): string|ResponseInterface
    {
        $this->loadRelationOptions();

        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge(
                $this->request->getPost(),
                ['images' => $this->getUploadedImages()]
            );

            if (isset($posts['action']) && $posts['action'] === 'reset'):
                return $this->jsonResponse([
                    'result' => true,
                    'output' => view(Views::ADD_PARTIAL, $this->data),
                ]);
            endif;

            $rules = $this->productsModel->addValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse([
                    'errors' => $this->getFormValidationErrors(),
                    'message' => lang('backend/products.messages.validationErrors'),
                ]);
            endif;

            $json = $this->productsModel->add($posts);

            if ($json['result'] === true):
                $this->loadRelationOptions();
                $json['output'] = view(Views::ADD_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        $this->data['action'] = 'add';

        $this->data['title'] = lang('backend/products.titles.add');
        $this->data['icon'] = '<i class="fa-solid fa-plus"></i>';

        return $this->render(Views::ADD_VIEW, $this->data);
    }

    /**
     * Gestisce la modifica di un prodotto e delle relative associazioni.
     *
     * @param string|null $uuid Identificativo del prodotto da modificare
     * @return string|ResponseInterface Vista completa, redirect oppure risposta JSON
     */
    public function edit(?string $uuid = null): string|ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = array_merge(
                $this->request->getPost(),
                ['images' => $this->getUploadedImages()]
            );

            if (( ! isset($posts['uuid'])) || ( ! $this->regexp->validateUUID($posts['uuid']))):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/products.errors.uuid')]);
            endif;

            $product = $this->productsModel->getProduct($posts['uuid']);

            if (($accessError = $this->targetAccessError($product)) !== null):
                return $accessError;
            endif;

            $this->loadRelationOptions();

            /* Caso 1: ricaricamento della vista parziale senza salvataggio. */
            if (isset($posts['action']) && $posts['action'] === 'refresh'):
                $this->data['product'] = $product['row'];
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages([
                    'entity' => 'products',
                    'uuid' => $posts['uuid'],
                ]);

                return $this->jsonResponse([
                    'result' => true,
                    'output' => view(Views::EDIT_PARTIAL, $this->data),
                ]);
            endif;

            $rules = $this->productsModel->editValidationRules($posts);

            if ( ! $this->validateData($posts, $rules)):
                return $this->jsonResponse([
                    'errors' => $this->getFormValidationErrors(),
                    'message' => lang('backend/products.messages.validationErrors'),
                ]);
            endif;

            $result = $this->productsModel->edit($posts);
            $json = ['result' => $result['result'], 'message' => $result['message']];

            /* Caso 2: salvataggio completato con successo. */
            if ($result['result'] === true):
                $this->data['product'] = $result['row'];
                $this->data['uuid'] = $posts['uuid'];

                $this->data['context'] = 'edit';
                $this->data['images'] = $this->galleryOneModel->getImages([
                    'entity' => 'products',
                    'uuid' => $posts['uuid'],
                ]);

                $this->loadRelationOptions();

                $json['output'] = view(Views::EDIT_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse($json);

        endif;

        /* Richiesta GET: caricamento iniziale della pagina. */
        if (($uuid === null) || ( ! $this->regexp->validateUUID($uuid))):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', lang('backend/products.errors.uuid'))
                ->with('class', 'app-alert-danger fw-bold');
        endif;

        $product = $this->productsModel->getProduct($uuid);

        if ($product['result'] === false):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', $product['message'])
                ->with('class', 'app-alert-danger fw-bold');
        endif;

        if ($product['row']->deleted_at !== null):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', lang('backend/products.messages.cannotModifyDeleted'))
                ->with('class', 'app-alert-danger fw-bold')
                ->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'edit';

        $this->data['title'] = lang('backend/products.titles.edit');
        $this->data['icon'] = '<i class="fa-solid fa-pen"></i>';

        $this->data['product'] = $product['row'];
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'edit';
        $this->data['images'] = $this->galleryOneModel->getImages([
            'entity' => 'products',
            'uuid' => $uuid,
        ]);

        $this->loadRelationOptions();

        return $this->render(Views::EDIT_VIEW, $this->data);
    }

    /**
     * Renderizza la scheda di dettaglio di un prodotto.
     *
     * @param string $uuid Identificativo del prodotto
     * @return RedirectResponse|string Redirect oppure HTML della scheda
     */
    public function show(string $uuid): RedirectResponse|string
    {
        if ( ! $this->regexp->validateUUID($uuid)):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', lang('backend/products.errors.uuid'))
                ->with('class', 'app-alert-danger fw-bold');
        endif;

        $product = $this->productsModel->getProduct($uuid);

        if ($product['result'] === false):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', $product['message'])
                ->with('class', 'app-alert-danger fw-bold');
        endif;

        if ($product['row']->deleted_at !== null):
            return redirect()
                ->to(base_url('backend/products/showAll'))
                ->with('message', lang('backend/products.messages.cannotModifyDeleted'))
                ->with('class', 'app-alert-danger fw-bold')
                ->with('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
        endif;

        $this->data['action'] = 'show';

        $this->data['title'] = lang('backend/products.titles.show');
        $this->data['icon'] = '<i class="fa-solid fa-box"></i>';

        $this->data['product'] = $product['row'];
        $this->data['uuid'] = $uuid;

        $this->data['context'] = 'show';
        $this->data['images'] = $this->galleryOneModel->getImages([
            'entity' => 'products',
            'uuid' => $uuid,
        ]);

        return $this->render(Views::SHOW_VIEW, $this->data);
    }

    /**
     * Elimina definitivamente un prodotto già presente nel cestino.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione
     */
    public function hardDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            return $this->jsonResponse($this->productsModel->hardDelete($posts));

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Sposta un prodotto nel cestino mantenendo le associazioni pivot.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione
     */
    public function softDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            return $this->jsonResponse($this->productsModel->softDelete($posts));

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Ripristina un prodotto precedentemente spostato nel cestino.
     *
     * @return ResponseInterface Risposta JSON con l'esito dell'operazione
     */
    public function restoreDelete(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->delValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            return $this->jsonResponse($this->productsModel->restoreDelete($posts));

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Alterna lo stato del prodotto e aggiorna il pannello di dettaglio.
     *
     * @return ResponseInterface Risposta JSON con l'esito e gli eventuali partial aggiornati
     */
    public function changeStatus(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->changeStatusValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            $json = $this->productsModel->changeStatus($posts);

            if ($json['result'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $json['message']]);
            endif;

            if (isset($posts['context']) && $posts['context'] === 'show'):
                $this->data['product'] = $json['product'];

                $json['statusView'] = view(Views::CHANGE_STATUS_PARTIAL, $this->data);
                $json['metaView'] = view(Views::META_DATA_PARTIAL, $this->data);
            endif;

            unset($json['product']);

            return $this->jsonResponse($json);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Ricarica il pannello dei dati generali del prodotto.
     *
     * @return ResponseInterface Risposta JSON contenente il partial aggiornato
     */
    public function getGeneralData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->generalDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            $record = $this->productsModel->getProduct($posts['uuid']);

            if (($accessError = $this->targetAccessError($record)) !== null):
                return $accessError;
            endif;

            $this->data['product'] = $record['row'];

            if ($posts['context'] === 'edit'):
                $this->loadRelationOptions();
                $output = view(Views::EDIT_GENERAL_DATA_PARTIAL, $this->data);
            else:
                $output = view(Views::SHOW_GENERAL_DATA_PARTIAL, $this->data);
            endif;

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Ricarica il pannello dei metadati temporali del prodotto.
     *
     * @return ResponseInterface Risposta JSON contenente il partial aggiornato
     */
    public function getMetaData(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->productsModel->metaDataValidationRules();

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());

                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/products.messages.validationToastErrors'), $errorMessage),
                ]);
            endif;

            $record = $this->productsModel->getProduct($posts['uuid']);

            if (($accessError = $this->targetAccessError($record)) !== null):
                return $accessError;
            endif;

            $this->data['product'] = $record['row'];

            return $this->jsonResponse([
                'result' => true,
                'output' => view(Views::META_DATA_PARTIAL, $this->data),
            ]);

        endif;

        return service('response')->setStatusCode(400);
    }
}
