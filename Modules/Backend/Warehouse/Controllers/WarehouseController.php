<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use Modules\Backend\Warehouse\Config\Views;
use Modules\Backend\Warehouse\Config\Package;
use Modules\Backend\Warehouse\Libraries\WarehouseClass;
use Modules\Backend\Warehouse\Libraries\WarehouseXlsx;
use Modules\Backend\Warehouse\Libraries\WarehouseXlsxImport;
use Modules\Backend\Warehouse\Libraries\WarehouseXlsxExport;
use Modules\Backend\Warehouse\Libraries\WarehouseRecoveryException;
use Modules\Backend\Warehouse\Models\WarehouseModel;
use Modules\Backend\Warehouse\Models\WarehouseImportModel;
use App\Controllers\Backend\BackendController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Gestisce la sezione dedicata all'importazione e all'esportazione del pacchetto magazzino.
 */
class WarehouseController extends BackendController
{
    /**
     * Istanza del modello del pacchetto magazzino.
     *
     * @var WarehouseModel
     */
    protected WarehouseModel $warehouseModel;

    /** Modello che applica schema e regole Import alle cinque tabelle. */
    protected WarehouseImportModel $warehouseImportModel;

    /**
     * Istanza della libreria di presentazione del modulo.
     *
     * @var WarehouseClass
     */
    protected WarehouseClass $warehouseClass;

    /**
     * Componente XLSX specifico del pacchetto magazzino.
     *
     * @var WarehouseXlsx
     */
    protected WarehouseXlsx $warehouseXlsx;

    /** Gestisce i job di esportazione progressiva delle cinque tabelle. */
    protected WarehouseXlsxExport $warehouseXlsxExport;

    /**
     * Componente responsabile dello staging e dell'importazione XLSX.
     *
     * @var WarehouseXlsxImport
     */
    protected WarehouseXlsxImport $warehouseXlsxImport;

    /**
     * Inizializza le dipendenze e i dati condivisi dal modulo Warehouse.
     *
     * @param RequestInterface $request Richiesta HTTP corrente
     * @param ResponseInterface $response Risposta HTTP corrente
     * @param LoggerInterface $logger Sistema di log
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->data['controller'] = 'warehouse';
        $this->data['entity'] = 'warehouse';

        $this->warehouseModel = model(WarehouseModel::class);
        $this->warehouseImportModel = model(WarehouseImportModel::class);
        $this->warehouseClass = new WarehouseClass();
        $this->warehouseXlsx = new WarehouseXlsx($this->warehouseModel);
        $this->warehouseXlsxExport = new WarehouseXlsxExport($this->warehouseModel, $this->warehouseXlsx);
        $this->warehouseXlsxImport = new WarehouseXlsxImport($this->warehouseImportModel);
    }

    /**
     * Renderizza la schermata principale del pacchetto magazzino.
     *
     * @return string HTML renderizzato della vista
     */
    public function index(): string
    {
        $this->data['action'] = 'index';

        $this->data['title'] = lang('backend/warehouse.titles.index');
        $this->data['icon'] = '<i class="fa-solid fa-warehouse"></i>';
        $this->data['packageTables'] = $this->warehouseModel->getPackageTables();
        $lastImportId = $this->session->get('warehouseImportId');
        $this->data['warehouseImport'] = is_string($lastImportId)
            ? $this->warehouseXlsxImport->getImportStatus($lastImportId)
            : null;

        return $this->render(Views::INDEX_VIEW, $this->data);
    }

    /**
     * Genera il pacchetto XLSX completo del magazzino.
     *
     * @return ResponseInterface Risposta JSON con URL protetto di download
     */
    public function export(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        try
        {
            $posts = $this->request->getPost();
            $hasExportId = array_key_exists('exportId', $posts);
            $rules = $hasExportId
                ? ['exportId' => 'required|regex_match[/^[a-f0-9]{64}$/]']
                : ['entity' => 'required|in_list[warehouse]'];

            if ( ! $this->validateData($posts, $rules) || array_diff(array_keys($posts), array_keys($rules)) !== []):
                return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')]);
            endif;

            $export = $this->warehouseXlsxExport->generate($hasExportId ? (string) $posts['exportId'] : null);

            if ($export['result'] && $export['isFinished'] && ! $export['alreadyCompleted']):
                try
                {
                    log_admin_activity(
                        'EXPORT_WAREHOUSE', 'warehouse',
                        sprintf(lang('backend/warehouse.audits.export'), $export['processedCount']),
                        $this->currentAdmin
                    );
                }
                catch (\Throwable $e)
                {
                    log_message('error', 'Audit export Warehouse non riuscito: ' . $e->getMessage());
                }
            endif;

            return $this->jsonResponse($export);
        }
        catch (\DomainException $e)
        {
            return $this->jsonResponse(['result' => false, 'message' => esc($e->getMessage())]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Esportazione Warehouse XLSX non riuscita: ' . $e->getMessage());

            return $this->jsonResponse([
                'result' => false,
                'message' => lang('backend/warehouse.messages.exportError'),
            ]);
        }
    }

    /**
     * Scarica un pacchetto XLSX appartenente alla sessione corrente.
     *
     * @param string $exportId Identificatore crittografico dell'esportazione
     * @return ResponseInterface Risposta di download
     * @throws PageNotFoundException Se il file non esiste o appartiene a un'altra sessione
     */
    public function download(string $exportId): ResponseInterface
    {
        $download = $this->warehouseXlsxExport->getDownloadFile($exportId) ?? $this->warehouseXlsx->getDownloadFile($exportId);

        if ($download === null):
            throw PageNotFoundException::forPageNotFound();
        endif;

        return $this->response->download($download['path'], null)->setFileName($download['fileName']);
    }

    /** Apre il modale Export condiviso con colonne fisse e senza selezioni per tabella. */
    public function showExportModal(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        if ( ! $this->validateData($this->request->getPost(), ['entity' => 'required|in_list[warehouse]'])):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')]);
        endif;

        return $this->jsonResponse([
            'result' => true,
            'output' => view(Views::EXPORT_MODAL, [
                'entity' => 'warehouse',
                'controlsPrefix' => 'warehouse-export',
                'modalTitle' => lang('backend/warehouse.panels.export'),
                'fixedPackage' => Package::TABLES,
                'packageDescription' => lang('backend/warehouse.descriptions.export'),
            ]),
        ]);
    }

    /** Annulla e rimuove esclusivamente il job export della sessione corrente. */
    public function removeExport(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $posts = $this->request->getPost();

        if ( ! $this->validateData($posts, ['exportId' => 'required|regex_match[/^[a-f0-9]{64}$/]'])):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/components/export.messages.invalidEntity')]);
        endif;

        try
        {
            return $this->jsonResponse(['result' => $this->warehouseXlsxExport->delete($posts['exportId'])]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Annullamento export Warehouse non riuscito: ' . $e->getMessage());
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/warehouse.messages.exportError')]);
        }
    }

    /** Scarica un workbook vuoto con le colonne effettive delle cinque tabelle. */
    public function downloadTemplate(): ResponseInterface
    {
        $export = $this->warehouseXlsx->export(true);

        return $this->download($export['exportId']);
    }

    /** Apre lo stesso modale del componente Import, con gli schemi del pacchetto. */
    public function showImportModal(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        if ( ! $this->validateData($this->request->getPost(), ['entity' => 'required|in_list[warehouse]'])):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/warehouse.messages.invalidImportId')]);
        endif;

        try
        {
            $data = [
                'entity' => 'warehouse',
                'modalTitle' => lang('backend/warehouse.panels.import'),
                'templateLabel' => lang('backend/warehouse.buttons.downloadTemplate'),
                'structure' => [],
                'structures' => $this->warehouseImportModel->getPackageImportStructures(),
                'compactStructures' => true,
                'fullWidthStructures' => ['products'],
                'downloadUrl' => base_url('backend/warehouse/downloadTemplate'),
                'uploadName' => 'warehouseFile',
                'uploadAccept' => '.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadLabel' => lang('backend/warehouse.labels.selectFile'),
                'uploadHelp' => lang('backend/warehouse.messages.mergeWarning') . ' ' . lang('backend/warehouse.descriptions.importHelp') . ' ' . lang('backend/warehouse.descriptions.reExport'),
            ];
            $status = null;

            if ($this->request->getGet('resume') === '1'):
                $importId = $this->session->get('warehouseImportId');
                $status = is_string($importId) ? $this->warehouseXlsxImport->getImportStatus($importId) : null;

                if ($status === null):
                    throw new \DomainException(lang('backend/warehouse.messages.importNotFound'));
                endif;

                $data['initialOutput'] = $status['status'] === 'staged'
                    ? view(Views::IMPORT_PREVIEW_PARTIAL, $status)
                    : '<div class="alert alert-app-alert-' . ($status['status'] === 'completed' ? 'success' : 'danger') . '">'
                        . esc(lang('backend/warehouse.messages.' . ($status['status'] === 'completed' ? 'importCompleted' : 'importNeedsReview')))
                        . '<br>' . esc(lang('backend/warehouse.labels.importId')) . ': ' . esc($status['importId']) . '</div>';
            endif;

            return $this->jsonResponse([
                'result' => true,
                'output' => view(Views::IMPORT_MODAL, $data),
                'loadingOutput' => view(Views::IMPORT_LOADING_PARTIAL),
                'importId' => $status['importId'] ?? null,
                'hasProcessableData' => $status === null || $status['status'] === 'staged',
                'recoveryRequired' => $status !== null && in_array($status['status'], ['processing', 'failed'], true),
            ]);
        }
        catch (\DomainException $e)
        {
            return $this->jsonResponse(['result' => false, 'message' => esc($e->getMessage())]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Apertura modale Warehouse non riuscita: ' . $e->getMessage());
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/warehouse.messages.importPrepareError')]);
        }
    }

    /**
     * Carica e valida il pacchetto XLSX senza modificare il database.
     *
     * @return ResponseInterface Risposta JSON con riepilogo e importId
     */
    public function prepareImport(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $rules = [
            'warehouseFile' => [
                'rules' => 'uploaded[warehouseFile]|ext_in[warehouseFile,xlsx]|max_size[warehouseFile,10240]',
                'errors' => [
                    'uploaded' => lang('backend/warehouse.messages.fileRequired'),
                    'ext_in' => lang('backend/warehouse.messages.invalidXlsx'),
                    'max_size' => lang('backend/warehouse.messages.fileTooLarge'),
                ],
            ],
        ];

        if ( ! $this->validateData($this->request->getPost(), $rules)):
            return $this->jsonResponse([
                'result' => false,
                'message' => implode('<br>', $this->validator->getErrors()),
            ]);
        endif;

        $file = $this->request->getFile('warehouseFile');

        if ($file === null || ! $file->isValid()):
            return $this->jsonResponse([
                'result' => false,
                'message' => lang('backend/warehouse.messages.fileRequired'),
            ]);
        endif;

        try
        {
            $prepared = $this->warehouseXlsxImport->prepare($file);

            if ( ! $prepared['result']):
                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/components/import.messages.validationErrors'), count($prepared['validationErrors'])),
                    'errorOutput' => view(Views::IMPORT_ERRORS_PARTIAL, ['validationErrors' => $prepared['validationErrors']]),
                ]);
            endif;

            if ($prepared['importId'] !== null):
                $this->session->set('warehouseImportId', $prepared['importId']);
            else:
                $this->session->remove('warehouseImportId');
            endif;

            return $this->jsonResponse([
                'result' => true,
                'importId' => $prepared['importId'],
                'hasProcessableData' => $prepared['plan']['insert'] + $prepared['plan']['update'] > 0,
                'output' => view(Views::IMPORT_PREVIEW_PARTIAL, $prepared),
            ]);
        }
        catch (\DomainException $e)
        {
            return $this->jsonResponse(['result' => false, 'message' => esc($e->getMessage())]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Preparazione import Warehouse non riuscita: ' . $e->getMessage());

            return $this->jsonResponse([
                'result' => false,
                'message' => lang('backend/warehouse.messages.importPrepareError'),
            ]);
        }
    }

    /**
     * Conferma ed esegue la fusione atomica del pacchetto Warehouse.
     *
     * @return ResponseInterface Risposta JSON con i conteggi importati
     */
    public function executeImport(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $posts = $this->request->getPost();
        $rules = [
            'importId' => 'required|regex_match[/^[a-f0-9]{64}$/]',
            'step' => 'required|in_list[confirm]',
        ];

        if ( ! $this->validateData($posts, $rules)):
            return $this->jsonResponse([
                'result' => false,
                'message' => implode('<br>', $this->validator->getErrors()),
            ]);
        endif;

        try
        {
            $result = $this->warehouseXlsxImport->execute((string) $posts['importId']);

            if (($result['result'] ?? true) === false):
                return $this->jsonResponse([
                    'result' => false,
                    'message' => sprintf(lang('backend/components/import.messages.validationErrors'), count($result['validationErrors'])),
                    'errorOutput' => view(Views::IMPORT_ERRORS_PARTIAL, ['validationErrors' => $result['validationErrors']]),
                ]);
            endif;

            if ($result['isFinished'] === false):
                return $this->jsonResponse($result + ['progressOutput' => view(Views::IMPORT_LOADING_PARTIAL, ['progressMessage' => $result['progressMessage']])]);
            endif;

            if ( ! $result['alreadyCompleted']):
                /* Il fallimento dell'audit non cambia l'esito di un commit già riuscito. */
                try
                {
                    log_admin_activity(
                        'IMPORT_WAREHOUSE',
                        'warehouse',
                        sprintf(lang('backend/warehouse.audits.import'), $result['inserted'] + $result['updated']),
                        $this->currentAdmin
                    );
                }
                catch (\Throwable $e)
                {
                    log_message('error', 'Audit import Warehouse non riuscito: ' . $e->getMessage());
                }
            endif;

            return $this->jsonResponse([
                'result' => true,
                'isFinished' => true,
                'totalInserted' => $result['inserted'],
                'totalUpdated' => $result['updated'],
                'message' => sprintf(lang('backend/warehouse.messages.importSuccess'), $result['inserted'], $result['updated']),
            ]);
        }
        catch (WarehouseRecoveryException $e)
        {
            return $this->jsonResponse([
                'result' => false,
                'recoveryRequired' => true,
                'importId' => $posts['importId'],
                'message' => lang('backend/warehouse.messages.importNeedsReview') . ' ' . esc($posts['importId']),
            ]);
        }
        catch (\DomainException $e)
        {
            return $this->jsonResponse(['result' => false, 'message' => esc($e->getMessage())]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Import Warehouse non riuscito: ' . $e->getMessage());

            return $this->jsonResponse([
                'result' => false,
                'message' => lang('backend/warehouse.messages.importError'),
            ]);
        }
    }

    /** Elimina soltanto lo staging annullabile appartenente alla sessione corrente. */
    public function deleteImport(): ResponseInterface
    {
        if ( ! $this->request->isAJAX() || ! $this->request->is('post')):
            return service('response')->setStatusCode(400);
        endif;

        $posts = $this->request->getPost();

        if ( ! $this->validateData($posts, ['importId' => 'required|regex_match[/^[a-f0-9]{64}$/]'])):
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/warehouse.messages.invalidImportId')]);
        endif;

        try
        {
            if ( ! $this->warehouseXlsxImport->deleteImport($posts['importId'])):
                throw new \DomainException(lang('backend/warehouse.messages.importNeedsReview'));
            endif;

            if ($this->session->get('warehouseImportId') === $posts['importId']):
                $this->session->remove('warehouseImportId');
            endif;

            return $this->jsonResponse(['result' => true]);
        }
        catch (\Throwable $e)
        {
            log_message('error', 'Annullamento staging Warehouse non riuscito: ' . $e->getMessage());
            return $this->jsonResponse(['result' => false, 'message' => lang('backend/warehouse.messages.importNotReady')]);
        }
    }
}
