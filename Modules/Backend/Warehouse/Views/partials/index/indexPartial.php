<div class="container-fluid container-lg px-0">
    <div class="row g-3">

        <div class="col-12 col-lg-4">
            <div class="card h-100 border shadow-sm app-bg-surface">
                <div class="card-header app-bg-surface-elevated border-bottom py-3">
                    <h2 class="card-title mb-0 fs-5">
                        <i class="fa-solid fa-file-export me-2"></i><?= esc(lang('backend/warehouse.panels.export')); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <span class="badge app-bg-primary mb-3">XLSX</span>
                    <p class="app-text-muted mb-4"><?= esc(lang('backend/warehouse.descriptions.export')); ?></p>
                    <button id="warehouse-export-button" type="button" class="btn app-btn-primary" data-export-entity="warehouse">
                        <i class="fa-solid fa-file-export me-2"></i><?= esc(lang('backend/warehouse.buttons.export')); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 border shadow-sm app-bg-surface">
                <div class="card-header app-bg-surface-elevated border-bottom py-3">
                    <h2 class="card-title mb-0 fs-5">
                        <i class="fa-solid fa-file-import me-2"></i><?= esc(lang('backend/warehouse.panels.import')); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <span class="badge app-bg-primary mb-3">XLSX</span>
                    <p class="app-text-muted mb-4"><?= esc(lang('backend/warehouse.descriptions.import')); ?></p>

                    <a href="#" id="warehouse-import-link" class="cmd-link" data-import-entity="warehouse">
                        <i class="fa-solid fa-file-import me-2"></i><?= esc(lang('backend/warehouse.buttons.import')); ?>
                    </a>
                    <?php if ($warehouseImport !== null): ?>
                        <a href="#" id="warehouse-resume-import-link" class="btn cmd-link d-block mt-3">
                            <i class="fa-solid fa-clock-rotate-left me-2"></i><?= esc(lang('backend/warehouse.buttons.resume')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border shadow-sm app-bg-surface">
                <div class="card-header app-bg-surface-elevated border-bottom py-3">
                    <h2 class="card-title mb-0 fs-5">
                        <i class="fa-solid fa-boxes-stacked me-2"></i><?= esc(lang('backend/warehouse.panels.package')); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <p class="app-text-muted"><?= esc(lang('backend/warehouse.descriptions.package')); ?></p>
                    <ul class="mb-0">
                        <?php foreach ($packageTables as $table): ?>
                            <li><code><?= esc($table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Il modale è fornito dalle stesse viste del componente Import. -->
<div id="import-modal-container"></div>
<div id="export-modal-container" data-export-error="<?= esc(lang('backend/warehouse.messages.exportError'), 'attr'); ?>"></div>
