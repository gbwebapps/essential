<?php declare(strict_types = 1); 

namespace App\Controllers\Backend\Components;

use App\Controllers\BaseController;
use App\Models\Backend\Components\ExportModel;
use CodeIgniter\HTTP\ResponseInterface;

class ExportController extends BaseController
{
    private ExportModel $exportModel;

    public function __construct()
    {
        $this->exportModel = model(ExportModel::class);
    }

    public function showModal(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = ['entity' => 'required|alpha_dash'];

            /* Validazione campi nascosti */
            if ( ! $this->validateData($posts, $rules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/export.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $output = view('backend/components/export/showModalView', ['entity' => $posts['entity']]);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function generate(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')): 

            $posts = $this->request->getPost();
            $rules = $this->exportModel->generateValidationRules();
            
            /* Aggiungiamo dinamicamente le regole per il chunking */
            $rules['offset'] = ['label' => 'Offset', 'rules' => 'permit_empty|is_natural'];
            $rules['fileName'] = ['label' => 'File Name', 'rules' => 'permit_empty|string'];

            /* Validazione dell'entità o dei filtri base */
            if ( ! $this->validateData($posts, $rules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/components/export.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Estrazione variabili di chunking */
            $offset = (int) $this->request->getPost('offset');
            $fileName = $this->request->getPost('fileName'); // Sarà null al primo giro

            /* Genera l'esportazione a blocchi */
            $exportResult = $this->exportModel->generate($posts, $offset, $fileName);

            if ($exportResult['result'] === false):
                return $this->jsonResponse($exportResult);
            endif;

            /* Se ha finito (isFinished = true), restituiamo il link al file */
            if ($exportResult['isFinished'] === true):
                return $this->jsonResponse($exportResult);
            endif;

            /* Se il blocco è parziale, restituiamo i dati per il prossimo giro e la vista del loader */
            return $this->jsonResponse([
                'result' => true,
                'isFinished' => false,
                'nextOffset' => $exportResult['nextOffset'],
                'fileName' => $exportResult['fileName'],
                'progressMessage' => sprintf(lang('backend/components/export.messages.processedRows'), $exportResult['nextOffset'])
            ]);

        endif;
    }

    public function download(?string $fileName = null): ResponseInterface
    {
        if (empty($fileName)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        $filePath = WRITEPATH . 'exports/' . $fileName;

        if ( ! is_file($filePath)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        return $this->response->download($filePath, null);
    }
}