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

    public function generate(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->exportModel->generateValidationRules();

            /* Validazione dell'entità o dei filtri base */
            if ( ! $this->validateData($posts, $rules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => sprintf(lang('backend/components/export.messages.validateToastErrors'), $errorMessage)]);
            endif;

            /* Genera l'esportazione con tutte le colonne e i filtri correnti */
            $json = $this->exportModel->generate($posts);

            return $this->jsonResponse($json);

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