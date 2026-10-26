<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="container-fluid container-lg px-0">
        <div class="row g-0">
            <div class="col-12">

                <div class="accordion" id="mainGroupsDashboard">

                    <div class="accordion-item mb-3 border shadow-sm">
                        <h2 class="accordion-header" id="main_heading_add">
                            <button class="accordion-button collapsed shadow-none app-bg-surface app-text-muted py-3 btn-trigger-add-group" type="button" aria-expanded="false" aria-controls="main_collapse_add">
                                <h2 class="card-title mb-0 fs-5">
                                    <i class="fa-solid fa-plus-circle me-2"></i><?= ui_text('backend/groups.panels.addGroup', [], false); ?>
                                </h2>
                            </button>
                        </h2>
                        <div id="main_collapse_add" class="accordion-collapse collapse" aria-labelledby="main_heading_add" data-bs-parent="#mainGroupsDashboard">
                            <div id="add-groups-container" class="accordion-body app-bg-surface-elevated border-top mb-0"></div>
                        </div>
                    </div>

                    <div class="accordion-item mb-3 border shadow-sm">
                        <h2 class="accordion-header" id="main_heading_list">
                            <button class="accordion-button collapsed shadow-none app-bg-surface app-text-muted py-3" type="button" data-bs-toggle="collapse" data-bs-target="#main_collapse_list" aria-expanded="false" aria-controls="main_collapse_list">
                                <h2 class="card-title mb-0 fs-5">
                                    <i class="fa-solid fa-list me-2"></i><?= ui_text('backend/groups.panels.listGroup', [], false); ?>
                                </h2>
                            </button>
                        </h2>
                        <div id="main_collapse_list" class="accordion-collapse collapse" aria-labelledby="main_heading_list" data-bs-parent="#mainGroupsDashboard">
                            <div id="showAll-groups-container" class="accordion-body app-bg-surface-elevated border-top"></div>
                        </div>
                    </div>

                    <div class="accordion-item border shadow-sm">
                        <h2 class="accordion-header" id="main_heading_exceptions">
                            <button class="accordion-button collapsed shadow-none app-bg-surface app-text-muted py-3 btn-trigger-exceptions-group" type="button" aria-expanded="false" aria-controls="main_collapse_exceptions">
                                <h2 class="card-title mb-0 fs-5">
                                    <i class="fa-solid fa-code-branch me-2"></i><?= ui_text('backend/groups.panels.exceptionsPerms', [], false); ?>
                                </h2>
                            </button>
                        </h2>
                        <div id="main_collapse_exceptions" class="accordion-collapse collapse" aria-labelledby="main_heading_exceptions" data-bs-parent="#mainGroupsDashboard">
                            <div id="exceptions-groups-container" class="accordion-body app-bg-surface-elevated border-top"></div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
    
<?= $this->endSection() ?>