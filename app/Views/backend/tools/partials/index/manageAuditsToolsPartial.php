<?php 
    $minYear = $minAuditYear ?? (int) date('Y'); 
    $maxYear = (int) date('Y');
?>

<div class="card-body">
    <form id="manageAudits-tools-form" autocomplete="off">
        <div class="row">
            <div class="col-12">
                <div class="ps-2 py-1 bg-light fw-bold small mb-3 border text-center rounded-2">
                    <?php if ($stats['total'] === 0): ?>
                        <?= lang('backend/tools.labels.noAuditsStats'); ?>
                    <?php else: ?>
                        <?= sprintf(lang('backend/tools.labels.auditsStats'), $stats['total'], convertDate($stats['min_date']), convertDate($stats['max_date'])); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">

                <div class="row">
                    
                    <!-- Blocco Data Da -->
                    <div class="col-12 col-md-6 mb-3 mb-md-0 px-md-4">
                        <label class="form-label fw-bold">
                            <i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/tools.labels.dateFrom'); ?>
                        </label>
                        <div class="row g-1">
                            <div class="col-7">
                                <input type="date" name="fromDate" id="fromDate" step="1" class="form-control form-control-sm" min="<?= $minYear; ?>-01-01" max="<?= $maxYear; ?>-12-31">
                                <div class="error_fromDate text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                            </div>
                            <div class="col-5">
                                <input type="time" name="fromTime" id="fromTime" step="1" class="form-control form-control-sm">
                                <div class="error_fromTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                            </div>
                        </div>
                    </div>

                    <!-- Blocco Data A -->
                    <div class="col-12 col-md-6 mb-3 mb-md-0 px-md-4">
                        <label class="form-label fw-bold">
                            <i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/tools.labels.dateTo'); ?>      
                        </label>
                        <div class="row g-1">
                            <div class="col-7">
                                <input type="date" name="toDate" id="toDate" step="1" class="form-control form-control-sm" min="<?= $minYear; ?>-01-01" max="<?= $maxYear; ?>-12-31">
                                <div class="error_toDate text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                            </div>
                            <div class="col-5">
                                <input type="time" name="toTime" id="toTime" step="1" class="form-control form-control-sm">
                                <div class="error_toTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Pulsanti -->
                <div class="row mt-4">
                    <div class="col-12 d-flex align-middle justify-content-center">
                        <button type="button" class="btn btn-danger btn-sm me-1 btn-reset-tools" data-confirm="<?= lang('backend/tools.messages.areYouSureToResetData'); ?>">
                            <i class="fa-solid fa-refresh me-1"></i><?= lang('backend/tools.buttons.resetData'); ?>
                        </button>
                        <button type="button" class="btn btn-success btn-sm mx-1 btn-action-audits" data-action="delete" data-warning="<?= lang('backend/tools.messages.areYouSureToDeleteData'); ?>">
                            <i class="fa-solid fa-trash me-1"></i><?= lang('backend/tools.buttons.deleteData'); ?>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm ms-1 btn-action-audits" data-action="export">
                            <i class="fa-solid fa-file-export me-1"></i><?= lang('backend/tools.buttons.exportData'); ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- Modale nascosto per la selezione delle colonne di esportazione -->
<div class="modal fade" id="exportColumnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title">Seleziona le colonne da esportare</h5>
            </div>
            <div class="modal-body">
                <form id="exportColumnsForm">
                    <?php if ( ! empty($columns)): ?>
                        <?php foreach ($columns as $column): ?>
                            <div class="form-check">
                                <input class="form-check-input export-column-checkbox" type="checkbox" value="<?= esc($column); ?>" id="export_col_<?= esc($column); ?>" checked>
                                <label class="form-check-label" for="export_col_<?= esc($column); ?>">
                                    <?= esc($column); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-danger" id="btnCancelExport" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="btnConfirmExport">Esporta</button>
            </div>
        </div>
    </div>
</div>