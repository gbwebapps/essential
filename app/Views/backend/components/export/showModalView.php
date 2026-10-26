<!-- Modale di Esportazione Avanzata -->
<div class="modal fade" id="exportModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-export"></i> <?= isset($modalTitle) ? ui_value($modalTitle) : ui_text('backend/components/export.panels.main', [esc($entity)]); ?>
                </h5>
            </div>

            <div class="modal-body">

                <div id="export-alert-container"></div>

                <!-- FASE 1: Contenuto del pacchetto oppure selezione colonne CSV -->
                <div id="export-selection-area">
                    <?php if (isset($fixedPackage)): ?>
                        <p class="app-text-muted small mb-3"><?= ui_value($packageDescription); ?></p>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($fixedPackage as $table): ?>
                                <li class="mb-2"><i class="fa-solid fa-table me-2"></i><?= esc($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                    <p class="app-text-muted small mb-3"><?= ui_text('backend/components/export.labels.selectColumns', [], false); ?></p>

                    <form id="export-columns-form">
                        <div class="form-check mb-3 border-bottom pb-2">
                            <input class="form-check-input" type="checkbox" id="export-check-all" checked>
                            <label class="form-check-label fw-bold" for="export-check-all">
                                <?= ui_text('backend/components/export.labels.selectDeselectAll', [], false); ?>
                            </label>
                        </div>

                        <div class="row px-2" style="max-height: 40vh; overflow-y: auto;">
                            <?php foreach ($columns as $column): ?>
                                <?php $isRequired = in_array($column, $requiredColumns ?? [], true); ?>
                                <div class="col-12 col-md-6 mb-1">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input export-col-cb"
                                            type="checkbox"
                                            name="selected_columns[]"
                                            value="<?= esc($column, 'attr') ?>"
                                            id="col_<?= esc($column, 'attr') ?>"
                                            <?= $isRequired ? 'data-required="1" disabled' : '' ?>
                                            checked
                                        >
                                        <label class="form-check-label<?= $isRequired ? ' fw-bold' : '' ?>" for="col_<?= esc($column, 'attr') ?>">
                                            <?= esc($column) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- FASE 2: Loader di Esportazione (Inizialmente Nascosto) -->
                <div id="export-spinner-area" class="d-none text-center py-4">
                    <div class="spinner-border app-text-success mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h5 class="fw-bold app-text"><?= ui_text('backend/components/export.labels.runningExportation', [], false); ?></h5>
                    <p class="fw-bold app-text-success mb-0" id="export-progress-text"><?= ui_text('backend/components/export.labels.dataElaboration', [], false); ?></p>
                </div>

            </div>

            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-sm app-btn-danger no-general-disabled" id="<?= esc($controlsPrefix ?? 'export', 'attr'); ?>-cancel-btn">
                    <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/components/export.buttons.operationUndo', [], false); ?>
                </button>
                <button type="button" class="btn btn-sm app-btn-success" id="<?= esc($controlsPrefix ?? 'export', 'attr'); ?>-start-btn">
                    <i class="fa-solid fa-play"></i> <?= ui_text('backend/components/export.buttons.exportationStart', [], false); ?>
                </button>
            </div>

        </div>
    </div>
</div>
