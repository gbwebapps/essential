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

            $entity = $posts['entity'];
            
            /* Otteniamo le colonne esportabili (esclusa la PK) */
            $exportColumns = $this->exportModel->getExportColumns($entity);

            /* Passiamo i dati alla view che creerà i checkbox */
            $output = view('backend/components/export/showModalView', [
                'entity'  => $entity,
                'columns' => $exportColumns
            ]);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function generate(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')): 

            $posts = $this->request->getPost();
            $rules = $this->exportModel->generateValidationRules();
            
            /* Regole per il cursore numerico ID */
            $rules['lastId'] = ['label' => 'Last ID', 'rules' => 'permit_empty|is_natural'];
            $rules['processedCount'] = ['label' => 'Processed Count', 'rules' => 'permit_empty|is_natural'];
            $rules['fileName'] = ['label' => 'File Name', 'rules' => 'permit_empty|string'];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/components/export.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $lastId = $this->request->getPost('lastId') !== '' ? (int) $this->request->getPost('lastId') : null;
            $fileName = $this->request->getPost('fileName');
            $processedCount = (int) $this->request->getPost('processedCount');

            $exportResult = $this->exportModel->generate($posts, $lastId, $fileName);

            if ($exportResult['result'] === false || $exportResult['isFinished'] === true):
                return $this->jsonResponse($exportResult);
            endif;

            $currentTotal = $processedCount + $exportResult['chunkSize'];

            return $this->jsonResponse([
                'result' => true,
                'isFinished' => false,
                'lastId' => $exportResult['lastId'],
                'fileName' => $exportResult['fileName'],
                'processedCount' => $currentTotal,
                'progressMessage' => sprintf(lang('backend/components/export.messages.processedRows'), $currentTotal)
            ]);

        endif;
    }

    public function remove(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):
            $fileName = $this->request->getPost('fileName');
            
            if ($fileName):
                $filePath = WRITEPATH . 'exports/' . basename($fileName);
                if (is_file($filePath)):
                    unlink($filePath);
                endif;
            endif;

            return $this->jsonResponse(['result' => true]);
        endif;
    }

    public function download(?string $fileName = null): ResponseInterface
    {
        if (empty($fileName)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        $filePath = WRITEPATH . 'exports/' . basename($fileName);

        if ( ! is_file($filePath)):
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        endif;

        return $this->response->download($filePath, null);
    }
}