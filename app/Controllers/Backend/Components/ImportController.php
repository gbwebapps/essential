<?php declare(strict_types = 1);

namespace App\Controllers\Backend\Components;

use App\Controllers\Backend\BackendController;
use App\Models\Backend\Components\ImportModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gestisce le operazioni di importazione dei dati tramite file CSV, includendo il download dei template, la validazione strutturale, l'anteprima e l'elaborazione a blocchi.
 */
class ImportController extends BackendController
{
    /**
     * Istanza del modello responsabile della logica di validazione, del parsing e del salvataggio dei dati importati
     * @var ImportModel 
     */
    private ImportModel $importModel;

    /**
     * Inizializza il controller e carica il modello dedicato alle operazioni di importazione.
     */
    public function __construct()
    {
        $this->importModel = model(ImportModel::class);
    }

    /**
     * Valida la richiesta e renderizza l'interfaccia della finestra modale iniziale per il caricamento del file CSV.
     *
     * @return ResponseInterface Risposta JSON contenente l'esito della validazione e l'HTML generato per la modale
     */
    public function showModal(): ResponseInterface
    {
        return $this->showModalByMode(ImportModel::IMPORT_MODE_CRUD);
    }

    /**
     * Renderizza la modale per Tools > Database, esponendo lo schema fisico completo.
     */
    public function showDatabaseModal(): ResponseInterface
    {
        return $this->showModalByMode(ImportModel::IMPORT_MODE_DATABASE);
    }

    /**
     * Implementazione condivisa della modale iniziale. Il mode è determinato dall'endpoint server-side.
     */
    private function showModalByMode(string $mode): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = ['entity' => 'required|alpha_dash'];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/components/import.messages.validateToastErrors'), $errorMessage)
                ]);
            endif;

            $entity = (string) $this->request->getPost('entity');
            $structure = $this->importModel->getImportTableStructure($entity, $mode);

            if ($structure === []):
                return $this->jsonResponse([
                    'result' => false,
                    'message' => lang('backend/components/import.messages.noStructure')
                ]);
            endif;

            $downloadUrl = $mode === ImportModel::IMPORT_MODE_DATABASE
                ? base_url('backend/import/database/download/' . rawurlencode($entity))
                : base_url('backend/import/download/' . rawurlencode($entity));

            $output = view('backend/components/import/showModalView', [
                'structure' => $structure,
                'entity' => $entity,
                'downloadUrl' => $downloadUrl,
            ]);

            return $this->jsonResponse(['result' => true, 'output' => $output]);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Genera in memoria e scarica un file CSV vuoto, configurato come template con le intestazioni di colonna corrette per l'entità specificata.
     *
     * @param string $entity Nome della tabella o entità per la quale generare il template
     * @return ResponseInterface Risposta HTTP per forzare il download del CSV oppure redirect in caso di parametri non validi
     */
    public function download(string $entity): ResponseInterface
    {
        return $this->downloadByMode($entity, ImportModel::IMPORT_MODE_CRUD);
    }

    /**
     * Genera il template completo destinato a Tools > Database.
     */
    public function downloadDatabase(string $entity): ResponseInterface
    {
        return $this->downloadByMode($entity, ImportModel::IMPORT_MODE_DATABASE);
    }

    /**
     * Genera il template CSV coerente con il contesto di importazione richiesto.
     */
    private function downloadByMode(string $entity, string $mode): ResponseInterface
    {
        if ( ! preg_match('/^[a-zA-Z0-9_-]+$/', $entity)):
            return redirect()->back()
                ->with('message', lang('backend/components/import.messages.invalidEntity'))
                ->with('class', 'light text-danger fw-bold');
        endif;

        $structure = $this->importModel->getImportTableStructure($entity, $mode);

        if ($structure === []):
            return redirect()->back()
                ->with('message', lang('backend/components/import.messages.noStructure'))
                ->with('class', 'light text-danger fw-bold');
        endif;

        $headers = array_column($structure, 'name');
        $output = fopen('php://memory', 'w');

        if ($output === false):
            return service('response')->setStatusCode(500);
        endif;

        if (fwrite($output, "\xEF\xBB\xBF") === false || fputcsv($output, $headers, ',') === false):
            fclose($output);
            return service('response')->setStatusCode(500);
        endif;

        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);

        if ($csvData === false):
            return service('response')->setStatusCode(500);
        endif;

        $filename = 'template_import_' . $entity . '.csv';

        return service('response')->download($filename, $csvData)->setContentType('text/csv');
    }

    /**
     * Processa un CSV proveniente da una sezione CRUD applicativa.
     * Le regole strutturali SQL vengono integrate, quando disponibili, dalle regole import-specifiche del model di dominio.
     */
    public function processCsv(): ResponseInterface
    {
        return $this->processCsvByMode(ImportModel::IMPORT_MODE_CRUD);
    }

    /**
     * Processa un CSV proveniente da Tools > Database.
     * In questa modalità vengono applicate esclusivamente le regole strutturali dedotte dal database.
     */
    public function processDatabaseCsv(): ResponseInterface
    {
        return $this->processCsvByMode(ImportModel::IMPORT_MODE_DATABASE);
    }

    /**
     * Implementazione condivisa del parsing/preview. Il contesto di importazione viene scelto dal metodo server-side
     * chiamante e viene poi persistito nel manifest; non viene accettato come parametro libero dal browser.
     */
    private function processCsvByMode(string $mode): ResponseInterface
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

            $entity = (string) $this->request->getPost('entity');
            $file = $this->request->getFile('csvFile');

            if ($file === null):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/import.errors.uploaded')]);
            endif;

            $previewData = $this->importModel->parseAndValidateCsv($file, $entity, $mode);

            if ($previewData['status'] === false):

                if (isset($previewData['validationErrors'])):
                    $errorOutput = view('backend/components/import/errorsModalPartial', [
                        'validationErrors' => $previewData['validationErrors']
                    ]);
                    return $this->jsonResponse(['result' => false, 'errorOutput' => $errorOutput]);
                endif;

                return $this->jsonResponse(['result' => false, 'message' => $previewData['message']]);
            endif;

            $output = view('backend/components/import/previewModalPartial', [
                'entity' => $entity,
                'headers' => $previewData['headers'],
                'rows' => $previewData['rows'],
                'importId' => $previewData['importId'],
                'plan' => $previewData['plan']
            ]);

            $hasProcessableData = ($previewData['plan']['insert'] > 0 || $previewData['plan']['update'] > 0);

            return $this->jsonResponse([
                'result' => true,
                'output' => $output,
                'importId' => $previewData['importId'],
                'hasProcessableData' => $hasProcessableData
            ]);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Esegue l'importazione progressiva (chunking) dei dati validati nel database, occupandosi di creare un backup della tabella interessata prima di iniziare.
     *
     * @return ResponseInterface Risposta JSON con stato di avanzamento e contatori server-side dell'importazione
     */
    public function executeImport(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = [
                'importId' => 'required|regex_match[/^[a-f0-9]{64}$/]',
                'step' => 'required|in_list[confirm]'
            ];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $importId = (string) $this->request->getPost('importId');

            /* Il metodo è idempotente: crea il backup solo al primo chunk e ne verifica l'integrità nei successivi. */
            if ( ! $this->importModel->backupImport($importId)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/import.messages.backupError')]);
            endif;

            $importResult = $this->importModel->executeImport($importId);

            if ($importResult['status'] === false):
                $recoveryRequired = (bool) ($importResult['recoveryRequired'] ?? false);
                $message = $recoveryRequired
                    ? sprintf(lang('backend/components/import.messages.recoveryRequired'), $importId)
                    : $importResult['message'];

                return $this->jsonResponse([
                    'result' => false,
                    'message' => $message,
                    'recoveryRequired' => $recoveryRequired,
                    'importId' => $recoveryRequired ? $importId : null,
                ]);
            endif;

            return $this->jsonResponse([
                'result' => true,
                'message' => $importResult['message'],
                'isFinished' => $importResult['isFinished'],
                'inserted' => $importResult['inserted'],
                'updated' => $importResult['updated'],
                'totalInserted' => $importResult['totalInserted'],
                'totalUpdated' => $importResult['totalUpdated'],
                'processed' => $importResult['processed'],
                'progressOutput' => view('backend/components/import/loadingModalPartial'),
                'progressMessage' => sprintf(lang('backend/components/import.messages.processedRows'), $importResult['processed']),
            ]);

        endif;

        return service('response')->setStatusCode(400);
    }

    /**
     * Rimuove fisicamente il file CSV temporaneo dalla directory di staging quando l'utente annulla l'operazione o chiude la finestra modale.
     *
     * @return ResponseInterface Risposta JSON di conferma dell'avvenuta eliminazione del file
     */
    public function deleteFile(): ResponseInterface
    {
        if ($this->request->isAJAX() && $this->request->is('post')):

            $posts = $this->request->getPost();
            $rules = ['importId' => 'required|regex_match[/^[a-f0-9]{64}$/]'];

            if ( ! $this->validateData($posts, $rules)):
                $errorMessage = implode('<br>', $this->validator->getErrors());
                return $this->jsonResponse(['result' => false, 'message' => $errorMessage]);
            endif;

            $importId = (string) $this->request->getPost('importId');

            if ( ! $this->importModel->deleteStagingImport($importId)):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/import.messages.stagingDeleteError')]);
            endif;

            return $this->jsonResponse(['result' => true]);

        endif;

        return service('response')->setStatusCode(400);
    }
}
