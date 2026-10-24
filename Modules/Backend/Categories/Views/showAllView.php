<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="categories-list" class="categories crud-list">

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
                                <button class="btn app-btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#categories-actionsOffcanvas" aria-controls="categories-actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> <?= lang('backend/categories.labels.filtersAndOptions'); ?>
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="categories-actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= lang('backend/categories.labels.filtersAndOptions'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#categories-actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">
                                        
                                        <!-- Selezione stato record -->
                                        <div class="d-flex flex-column flex-md-row gap-0 gap-md-3 me-md-auto mb-3 mb-md-0 border-md-end pe-md-3">
                                            <a href="#" class="cmd-link active-filter" data-trash-filter="active">
                                                <i class="fa-solid fa-check"></i> <?= lang('backend/categories.links.activeRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="active" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="suspended">
                                                <i class="fa-solid fa-pause"></i> <?= lang('backend/categories.links.suspendedRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="suspended" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="trashed">
                                                <i class="fa-solid fa-trash-can"></i> <?= lang('backend/categories.links.trashedRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="trashed" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="all">
                                                <i class="fa-solid fa-list"></i> <?= lang('backend/categories.links.allRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="all" hidden></span>
                                            </a>
                                        </div>
                                        <!-- End Selezione stato record -->

                                        <!-- Azioni e Filtri -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#categories-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= lang('backend/categories.links.filters'); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= lang('backend/categories.links.resetFilters'); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= lang('backend/categories.links.resetSorting'); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/categories.links.reloadList'); ?>
                                        </a>
                                        
                                        <a href="#" id="export-entity" data-export-entity="categories" class="cmd-link">
                                            <i class="fa-solid fa-file-export"></i> <?= lang('backend/components/export.links.export'); ?>
                                        </a>

                                        <a href="#" id="import-entity" data-import-entity="categories" class="cmd-link">
                                            <i class="fa-solid fa-file-import"></i> <?= lang('backend/components/import.links.import'); ?>
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
                    <div id="categories-search-bar" class="collapse search-bar">
                        <div class="row card-body pb-0">

                            <!-- Ricerca avanzata per category -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-category"><?= lang('backend/categories.labels.category'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="categories-category" class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.searchCategory'), 'attr'); ?>" data-search-field="category" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="category" class="error_category app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per category -->

                            <!-- Ricerca avanzata per stato -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-status"><?= lang('backend/categories.labels.status'); ?></label>
                                    <div class="input-group">
                                        <select id="categories-status" class="form-select" data-search-field="status">
                                            <option value=""><?= lang('backend/categories.links.allRecords'); ?></option>
                                            <option value="1"><?= lang('backend/categories.labels.active'); ?></option>
                                            <option value="0"><?= lang('backend/categories.labels.unactive'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="status" class="error_status app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per stato -->

                        </div>

                        <div class="row card-body py-0">

                            <!-- Ricerca avanzata per data creazione da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-created_at-from"><?= lang('backend/categories.labels.dateFromAdd'); ?></label>
                                    <div id="wrapper-categories-created_at-from" class="input-group">
                                        <input type="text" id="categories-created_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.dateFromAdd'), 'attr'); ?>" data-search-field="created_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-from" class="error_created_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per data creazione da -->

                            <!-- Ricerca avanzata per data creazione a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-created_at-to"><?= lang('backend/categories.labels.dateToAdd'); ?></label>
                                    <div id="wrapper-categories-created_at-to" class="input-group">
                                        <input type="text" id="categories-created_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.dateToAdd'), 'attr'); ?>" data-search-field="created_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-to" class="error_created_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per data creazione a -->

                            <!-- Ricerca avanzata per data modifica da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-updated_at-from"><?= lang('backend/categories.labels.dateFromEdit'); ?></label>
                                    <div id="wrapper-categories-updated_at-from" class="input-group">
                                        <input type="text" id="categories-updated_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.dateFromEdit'), 'attr'); ?>" data-search-field="updated_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-from" class="error_updated_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per data modifica da -->

                            <!-- Ricerca avanzata per data modifica a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="categories-updated_at-to"><?= lang('backend/categories.labels.dateToEdit'); ?></label>
                                    <div id="wrapper-categories-updated_at-to" class="input-group">
                                        <input type="text" id="categories-updated_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.dateToEdit'), 'attr'); ?>" data-search-field="updated_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-to" class="error_updated_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per data modifica a -->

                        </div>

                    </div>
                    <!-- Fine parte filtri -->

                    <!-- Inizio contenuto -->
                    <div class="row">
                        <div class="col-12">
                            <div id="showAll-categories-container"></div>
                        </div>
                    </div>
                    <!-- Fine contenuto -->

                </div>
            </div>
        </div>
    </div>

    <div id="import-modal-container"></div>
    <div id="export-modal-container"></div>
    
<?= $this->endSection() ?>
