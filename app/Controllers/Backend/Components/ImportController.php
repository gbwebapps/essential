<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Controllers\BaseController;
use App\Models\Backend\Components\ImportModel;
use CodeIgniter\HTTP\ResponseInterface;

class ImportController extends BaseController
{
    private ImportModel $importModel;

    public function __construct()
    {
        $this->importModel = model(ImportModel::class);
    }

    public function showModal(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = $this->importModel->showModalValidationRules();

            /* Validazione campi nascosti */
            if ( ! $this->validateData($posts, $rules)) :
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result'  => false, 'message' => sprintf(lang('backend/components/import.messages.validateToastErrors'), $errorMessage)]);
            endif;

            $data = ['structure' => $this->importModel->getTableStructure($posts['entity']), 'entity' => $posts['entity']]; 
            $output = view('backend/components/import/showModalView', $data);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function download(string $entity): ResponseInterface
    {
        /* Validazione basilare per prevenire input malevoli */
        if ( ! preg_match('/^[a-zA-Z_-]+$/', $entity)):
            return redirect()->back()->with('message', lang('backend/components/import.messages.invalidEntity'))->with('class', 'light text-danger fw-bold');
        endif;

        /* Recupero la struttura dal model per avere solo i nomi delle colonne */
        $structure = $this->importModel->getTableStructure($entity);
        
        if (empty($structure)):
            return redirect()->back()->with('message', lang('backend/components/import.messages.noStructure'))->with('class', 'light text-danger fw-bold');
        endif;

        /* Estraggo solo l'array dei nomi colonna */
        $headers = array_column($structure, 'name');

        /* Apre un buffer in memoria per scrivere il CSV senza creare file su disco */
        $output = fopen('php://memory', 'w');
        
        /* Scrive l'intestazione come unica riga del CSV (usando il punto e virgola come separatore) */
        fputcsv($output, $headers, ';');
        
        /* Riporta il puntatore all'inizio del buffer per poterlo leggere */
        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);

        /* Genera il download diretto del file */
        $filename = 'template_import_' . $entity . '.csv';

        return $this->response->download($filename, $csvData)->setContentType('text/csv');
    }

    public function processCsv(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            /* Validazione dell'entità e del file caricato (obbligatorio, estensione csv, max 2MB) */
            $rules = [
                'entity' => 'required|alpha_dash',
                'csvFile' => [
                    'rules'  => 'uploaded[csvFile]|ext_in[csvFile,csv,txt]|max_size[csvFile,2048]',
                    'errors' => [
                        'uploaded' => 'Devi selezionare un file da importare.', /* Il messaggio per file mancante */
                        'ext_in'   => 'Il file caricato non è in un formato valido.' /* Il messaggio per estensione errata */
                    ]
                ]
            ];

            if ( ! $this->validateData($this->request->getPost(), $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $entity = $this->request->getPost('entity');
            $file = $this->request->getFile('csvFile');

            /* Deleghiamo al Model il parsing e la validazione strutturale del CSV */
            $previewData = $this->importModel->parseAndValidateCsv($file, $entity);

            /* Se la validazione fallisce (es. colonne mancanti o errate), blocchiamo tutto */
            if ($previewData['status'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $previewData['message']]);
            endif;

            /* Prepara la vista con la tabella di anteprima dei dati */
            $output = view('backend/components/import/previewModalView', [
                'entity' => $entity,
                'headers' => $previewData['headers'],
                'rows' => $previewData['rows'], 
                'tempFile' => $previewData['tempFile'] 
            ]);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;
    }

    public function executeImport(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $rules = [
                'entity' => 'required|alpha_dash',
                'tempFile' => 'required|regex_match[/^[a-zA-Z0-9_\-\.]+$/]',
                'step' => 'required|in_list[confirm]'
            ];

            if ( ! $this->validateData($this->request->getPost(), $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $entity = $this->request->getPost('entity');
            $tempFile = $this->request->getPost('tempFile');

            /* Avvio scrittura massiva */
            $importResult = $this->importModel->executeImport($entity, $tempFile);

            if ($importResult['status'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $importResult['message']]);
            endif;

            $successMessage = sprintf(lang('backend/components/import.messages.importSuccess'), $importResult['inserted'], $importResult['updated']);

            return $this->jsonResponse(['result' => true, 'message' => $successMessage]);

        endif;
    }
}