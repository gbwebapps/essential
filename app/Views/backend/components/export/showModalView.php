<!-- Modale di Esportazione Avanzata -->
<div class="modal fade" id="exportModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-export"></i> <?= sprintf(lang('backend/components/export.panels.main'), esc($entity)); ?>
                </h5>
            </div>

            <div class="modal-body">
                
                <div id="export-alert-container"></div>

                <!-- FASE 1: Selezione Colonne -->
                <div id="export-selection-area">
                    <p class="text-muted small mb-3">Seleziona le colonne da includere nell'esportazione. La chiave primaria verrà inclusa forzatamente dal sistema.</p>
                    
                    <form id="export-columns-form">
                        <div class="form-check mb-3 border-bottom pb-2">
                            <input class="form-check-input" type="checkbox" id="export-check-all" checked>
                            <label class="form-check-label fw-bold" for="export-check-all">
                                Seleziona / Deseleziona tutte
                            </label>
                        </div>
                        
                        <div class="row px-2" style="max-height: 40vh; overflow-y: auto;">
                            <?php foreach ($columns as $column): ?>
                                <div class="col-12 col-md-6 mb-1">
                                    <div class="form-check">
                                        <input class="form-check-input export-col-cb" type="checkbox" name="selected_columns[]" value="<?= esc($column) ?>" id="col_<?= esc($column) ?>" checked>
                                        <label class="form-check-label" for="col_<?= esc($column) ?>">
                                            <?= esc($column) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>

                <!-- FASE 2: Loader di Esportazione (Inizialmente Nascosto) -->
                <div id="export-spinner-area" class="d-none text-center py-4">
                    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h5 class="fw-bold text-dark">Esportazione in corso...</h5>
                    <p class="fw-bold text-success mb-0" id="export-progress-text">Preparazione dei dati...</p>
                </div>

            </div>

            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-sm btn-danger no-general-disabled" id="export-cancel-btn">
                    <i class="fa-solid fa-xmark"></i> Annulla operazione
                </button>
                <button type="button" class="btn btn-sm btn-success" id="export-start-btn">
                    <i class="fa-solid fa-play"></i> Avvia Esportazione
                </button>
            </div>

        </div>
    </div>
</div>