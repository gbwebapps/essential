<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="audits-list" class="audits crud-list">

        <div class="row">
            <div class="col-12">

                <div class="card">

                    <!-- Inizio testata lista -->
                    <div class="card-header">
                        <div class="row">

                            <!-- Select per il numero delle righe da mostrare -->
                            <div class="col-12 col-md-1 mb-3 mb-md-0">
                                <select class="form-select changeNumRows">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            <!-- End Select per il numero delle righe da mostrare -->

                            <!-- Bottone Mobile per aprire l'Offcanvas -->
                            <div class="col-12 d-md-none mb-3">
                                <button class="btn app-btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#audits-actionsOffcanvas" aria-controls="audits-actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> <?= ui_text('backend/audits.labels.filtersAndOptions', [], false); ?>
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="audits-actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= ui_text('backend/audits.labels.filtersAndOptions', [], false); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#audits-actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">

                                        <!-- Azioni e Filtri -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#audits-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= ui_text('backend/audits.links.filters', [], false); ?>
                                        </a>

                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/audits.links.resetFilters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= ui_text('backend/audits.links.resetSorting', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= ui_text('backend/audits.links.reloadList', [], false); ?>
                                        </a>

                                        <a href="#" id="export-entity" data-export-entity="admins_audits" class="cmd-link">
                                            <i class="fa-solid fa-file-export"></i> <?= ui_text('backend/components/export.links.export', [], false); ?>
                                        </a>
                                        <!-- End Azioni e Filtri -->

                                    </div>
                                </div>
                            </div>
                            <!-- End Contenitore Offcanvas -->

                        </div>
                    </div>
                    <!-- Fine testata lista -->

                    <!-- Inizio parte filtri -->
                    <div id="audits-search-bar" class="collapse search-bar">
                        <div class="row card-body">

                            <!-- Ricerca avanzata per username -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="audits-username"><?= ui_text('backend/audits.labels.username', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.searchUsername', 'placeholder', [], true); ?> type="text" id="audits-username" class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.searchUsername'), 'attr'); ?>" data-search-field="username" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="username" class="error_username app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per username -->

                            <!-- Ricerca avanzata per azione -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="audits-action"><?= ui_text('backend/audits.labels.action', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.searchAction', 'placeholder', [], true); ?> type="text" id="audits-action" class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.searchAction'), 'attr'); ?>" data-search-field="action" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="action" class="error_action app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per azione -->

                            <!-- Ricerca avanzata per sezione -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="audits-section"><?= ui_text('backend/audits.labels.section', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.searchSection', 'placeholder', [], true); ?> type="text" id="audits-section" class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.searchSection'), 'attr'); ?>" data-search-field="section" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="section" class="error_section app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per sezione -->

                            <!-- Ricerca avanzata per dettagli -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="audits-details"><?= ui_text('backend/audits.labels.details', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.searchDetails', 'placeholder', [], true); ?> type="text" id="audits-details" class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.searchDetails'), 'attr'); ?>" data-search-field="details" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="details" class="error_details app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per dettagli -->

                        </div>

                        <div class="row card-body py-0">

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="audits-created_at-from"><?= ui_text('backend/audits.labels.dateFrom', [], false); ?></label>
                                    <div id="wrapper-audits-created_at-from" class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.dateFrom', 'placeholder', [], true); ?> type="text" id="audits-created_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.dateFrom'), 'attr'); ?>" data-search-field="created_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-from" class="error_created_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="audits-created_at-to"><?= ui_text('backend/audits.labels.dateTo', [], false); ?></label>
                                    <div id="wrapper-audits-created_at-to" class="input-group">
                                        <input <?= ui_binding('backend/audits.placeholders.dateTo', 'placeholder', [], true); ?> type="text" id="audits-created_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/audits.placeholders.dateTo'), 'attr'); ?>" data-search-field="created_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-to" class="error_created_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                        </div>
                        
                    </div>
                    <!-- Fine parte filtri -->

                    <!-- Inizio contenuto -->
                    <div class="row">
                        <div class="col-12">
                            <div id="index-audits-container"></div>
                        </div>
                    </div>
                    <!-- Fine contenuto -->

                </div>
            </div>
        </div>
    </div>

    <div id="export-modal-container"></div>
    
<?= $this->endSection() ?>
