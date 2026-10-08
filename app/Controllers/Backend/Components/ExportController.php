<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\ExportModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class ExportController extends BackendController
{
    private ExportModel $exportModel;

    /**
     * Inizializza il controller risolvendo il model dedicato all'esportazione.
     *
     * @return void
     */
    public function __construct()
    {
        $this->exportModel = model(ExportModel::class);
    }

    /**
     * Restituisce la modale di esportazione per il contesto CRUD.
     *
     * @return ResponseInterface Risposta JSON contenente la view oppure un errore di validazione.
     */
    public function showModal(): ResponseInterface
    {
        return $this->showModalForContext(ExportModel::CONTEXT_CRUD);
    }

    /**
     * Restituisce la modale di esportazione per Tools > Database.
     *
     * @return ResponseInterface Risposta JSON contenente la view oppure un errore di validazione.
     */
    public function showDatabaseModal(): ResponseInterface
    {
        return $this->showModalForContext(ExportModel::CONTEXT_DATABASE);
    }

    /**
     * Implementazione condivisa della modale export, vincolata al contesto deciso dall'endpoint server.
     *
     * @param string $context Contesto di esportazione da applicare.
     * @return ResponseInterface Risposta HTTP/JSON della richiesta.
     */
    private function showModalForContext(string $context): ResponseInterface
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $posts = $this->request->getPost();

        if (! $this->validateData($posts, ['entity' => 'required|alpha_dash'])):
            return $this->validationErrorResponse();
        endif;

        $entity = (string) $posts['entity'];
        $columns = $this->exportModel->getExportColumns($entity, $context);

        if ($columns === []):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')]);
        endif;

        $output = view('backend/components/export/showModalView', [
            'entity' => $entity,
            'columns' => $columns,
            'requiredColumns' => $this->exportModel->getRequiredExportColumns($entity, $context),
        ]);

        return $this->jsonResponse(['result' => true, 'output' => $output]);
    }

    /**
     * Avvia o continua una esportazione nel contesto CRUD.
     *
     * @return ResponseInterface Risposta JSON con stato, progresso o completamento.
     */
    public function generate(): ResponseInterface
    {
        return $this->generateForContext(ExportModel::CONTEXT_CRUD);
    }

    /**
     * Avvia o continua una esportazione nel contesto Tools > Database.
     *
     * @return ResponseInterface Risposta JSON con stato, progresso o completamento.
     */
    public function generateDatabase(): ResponseInterface
    {
        return $this->generateForContext(ExportModel::CONTEXT_DATABASE);
    }

    /**
     * Gestisce il protocollo HTTP stateful dell'export per il contesto specificato.
     *
     * La prima richiesta contiene entità, filtri e colonne; le continuazioni accettano soltanto exportId.
     *
     * @param string $context Contesto di esportazione determinato dall'endpoint.
     * @return ResponseInterface Risposta JSON del model o errore di validazione.
     */
    private function generateForContext(string $context): ResponseInterface
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $exportId = $this->request->getPost('exportId');

        if (is_string($exportId) && $exportId !== ''):
            if (! $this->validateData(['exportId' => $exportId], ['exportId' => 'required|regex_match[/^[a-f0-9]{64}$/]'])):
                return $this->validationErrorResponse();
            endif;

            return $this->jsonResponse($this->exportModel->generate([], $exportId, $context));
        endif;

        $posts = $this->request->getPost();

        if (! $this->validateData($posts, $this->exportModel->generateValidationRules())):
            return $this->validationErrorResponse();
        endif;

        return $this->jsonResponse($this->exportModel->generate($posts, null, $context));
    }

    /**
     * Cancella una sessione di export appartenente all'amministratore corrente.
     *
     * @return ResponseInterface Risposta JSON con l'esito della rimozione.
     */
    public function remove(): ResponseInterface
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $posts = $this->request->getPost();

        if (! $this->validateData($posts, ['exportId' => 'required|regex_match[/^[a-f0-9]{64}$/]'])):
            return $this->validationErrorResponse();
        endif;

        return $this->jsonResponse(['result' => $this->exportModel->deleteExport((string) $posts['exportId'])]);
    }

    /**
     * Scarica il CSV di una esportazione completata risolta tramite exportId.
     *
     * @param string|null $exportId Identificatore crittografico dell'export.
     * @return ResponseInterface Risposta di download.
     * @throws PageNotFoundException Se exportId è invalido, non appartiene alla sessione o il file non è disponibile.
     */
    public function download(?string $exportId = null): ResponseInterface
    {
        if ($exportId === null || preg_match('/^[a-f0-9]{64}$/', $exportId) !== 1):
            throw PageNotFoundException::forPageNotFound();
        endif;

        $download = $this->exportModel->getDownloadFile($exportId);

        if ($download === null):
            throw PageNotFoundException::forPageNotFound();
        endif;

        return $this->response->download($download['path'], null)->setFileName($download['fileName']);
    }

    /**
     * Converte gli errori del validator in una risposta JSON uniforme.
     *
     * @return ResponseInterface Risposta JSON contenente il messaggio di validazione.
     */
    private function validationErrorResponse(): ResponseInterface
    {
        $errorMessage = implode('<br>', $this->validator->getErrors());

        return $this->jsonResponse([
            'result' => false,
            'message' => sprintf(lang('backend/components/export.messages.validateToastErrors'), $errorMessage),
        ]);
    }
}
