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
            $rules = ['entity' => 'required|alpha_dash'];

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
        
        /* Aggiunge il BOM UTF-8 per la compatibilità con Excel */
        fputs($output, "\xEF\xBB\xBF");
        
        /* Scrive l'intestazione come unica riga del CSV (usando la virgola come separatore) */
        fputcsv($output, $headers, ',');
        
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

            $posts = $this->request->getPost();
            $rules = [
                'entity' => 'required|alpha_dash',
                'csvFile' => [
                    'rules'  => 'uploaded[csvFile]|ext_in[csvFile,csv,txt]|max_size[csvFile,2048]',
                    'errors' => [
                        'uploaded' => lang('backend/components/import.errors.uploaded'),  
                        'ext_in'   => lang('backend/components/import.errors.ext_in'),  
                    ]
                ]
            ];

            if ( ! $this->validateData($posts, $rules)):
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

            $posts = $this->request->getPost();
            $rules = [
                'entity' => 'required|alpha_dash',
                'tempFile' => 'required|regex_match[/^[a-zA-Z0-9_\-\.]+$/]',
                'step' => 'required|in_list[confirm]',
                /* --- INIZIO MODIFICA CHUNKING: Validazione parametro offset --- */
                'offset' => 'permit_empty|is_natural'
                /* --- FINE MODIFICA CHUNKING --- */
            ];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $entity = $this->request->getPost('entity');
            $tempFile = $this->request->getPost('tempFile');
            
            /* --- INIZIO MODIFICA CHUNKING: Inizializzazione offset --- */
            $offset = (int) $this->request->getPost('offset'); // Se null/vuoto, diventa 0
            /* --- FINE MODIFICA CHUNKING --- */

            /* --- INIZIO MODIFICA CHUNKING: Backup SOLO al primo blocco --- */
            /* Esecuzione del backup preventivo della tabella (solo al giro iniziale) */
            if ($offset === 0):
                if ($this->importModel->backupTableBeforeImport($entity) === false):
                    return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/import.messages.backupError')]);
                endif;
            endif;
            /* --- FINE MODIFICA CHUNKING --- */

            /* --- INIZIO MODIFICA CHUNKING: Passaggio offset al model --- */
            /* Avvio scrittura massiva a blocchi */
            $importResult = $this->importModel->executeImport($entity, $tempFile, $offset);
            /* --- FINE MODIFICA CHUNKING --- */

            if ($importResult['status'] === false):
                return $this->jsonResponse(['result' => false, 'message' => $importResult['message']]);
            endif;

            /* --- INIZIO MODIFICA CHUNKING: Restituzione dati di stato al JS --- */
            return $this->jsonResponse([
                'result' => true, 
                'message' => $importResult['message'],
                'nextOffset' => $importResult['nextOffset'],
                'isFinished' => $importResult['isFinished'],
                'inserted' => $importResult['inserted'],
                'updated' => $importResult['updated']
            ]);
            /* --- FINE MODIFICA CHUNKING --- */

        endif;
    }

    public function deleteFile(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = ['file' => 'required|regex_match[/^[a-zA-Z0-9_\-\.]+$/]'];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $file = $this->request->getPost('file');
            $filePath = WRITEPATH . 'uploads/csv/' . $file;

            /* Elimina il file se esiste fisicamente sul server */
            if (file_exists($filePath)):
                unlink($filePath);
            endif;

            return $this->jsonResponse(['result' => true]);

        endif;
    }
}