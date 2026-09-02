<!-- Modale nascosto per esportazione da csv (Solo Loader) -->
<div class="modal fade" id="exportModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-export"></i> <?= sprintf(lang('backend/components/export.panels.main'), esc($entity)); ?>
                </h5>
            </div>

            <div class="modal-body pt-0 pb-4">
                
                <!-- Contenitore per eventuali errori critici -->
                <div id="export-alert-container"></div>

                <!-- Area dinamica con il loader pre-caricato -->
                <div id="export-content-area">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h5 class="fw-bold text-dark">Esportazione in corso...</h5>
                        <p class="fw-bold text-success mb-0" id="export-progress-text">Preparazione dei dati...</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>