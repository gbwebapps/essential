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

                <div class="row justify-content-center">
                    
                    <!-- Ricerca avanzata per data da -->
                    <div class="col-md-4">
                        <div class="mb-2 mb-md-0">
                            <!-- Campo Data Da -->
                            <label for="audits-created_at-from"><?= lang('backend/audits.labels.dateFrom'); ?></label>
                            <div id="wrapper-audits-created_at-from" class="input-group">
                                <input type="text" id="audits-created_at-from" name="fromDate" data-input class="form-control" placeholder="<?= lang('backend/audits.placeholders.dateFrom'); ?>" autocomplete="off">
                                <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                            </div>
                            <div class="error_fromDate text-danger fw-bold small pt-1">&nbsp;</div>
                        </div>
                    </div>
                    <!-- End ricerca avanzata per data da -->

                    <!-- Ricerca avanzata per data a -->
                    <div class="col-md-4">
                        <div class="mb-2 mb-md-0">
                            <!-- Campo Data A -->
                            <label for="audits-created_at-to"><?= lang('backend/audits.labels.dateTo'); ?></label>
                            <div id="wrapper-audits-created_at-to" class="input-group">
                                <input type="text" id="audits-created_at-to" name="toDate" data-input class="form-control" placeholder="<?= lang('backend/audits.placeholders.dateTo'); ?>" autocomplete="off">
                                <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                            </div>
                            <div class="error_toDate text-danger fw-bold small pt-1">&nbsp;</div>
                        </div>
                    </div>
                    <!-- End ricerca avanzata per data a -->

                </div>

                <!-- Pulsanti -->
                <div class="row mt-lg-2">
                    <div class="col-12 d-flex align-middle justify-content-center">
                        <button type="button" class="btn btn-warning text-dark btn-sm me-2 btn-reset-tools" data-confirm="<?= lang('backend/tools.messages.areYouSureToResetData'); ?>">
                            <i class="fa-solid fa-refresh me-1"></i><?= lang('backend/tools.buttons.resetData'); ?>
                        </button>
                        <button type="button" class="btn btn-success btn-sm mx-2 btn-action-audits" data-action="delete">
                            <i class="fa-solid fa-trash me-1"></i><?= lang('backend/tools.buttons.deleteData'); ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>