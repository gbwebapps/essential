<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="admins-list" class="admins crud-list">

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
                                <button class="btn app-btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#admins-actionsOffcanvas" aria-controls="actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> <?= ui_text('backend/admins.labels.filtersAndOptions', [], false); ?>
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="admins-actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= ui_text('backend/admins.labels.filtersAndOptions', [], false); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#admins-actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">
                                        
                                        <!-- Selezione stato record -->
                                        <div class="d-flex flex-column flex-md-row gap-0 gap-md-3 me-md-auto mb-3 mb-md-0 border-md-end pe-md-3">
                                            <a href="#" class="cmd-link active-filter" data-trash-filter="active">
                                                <i class="fa-solid fa-check"></i> <?= ui_text('backend/admins.links.activeRecords', [], false); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="active" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="suspended">
                                                <i class="fa-solid fa-pause"></i> <?= ui_text('backend/admins.links.suspendedRecords', [], false); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="suspended" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="trashed">
                                                <i class="fa-solid fa-trash-can"></i> <?= ui_text('backend/admins.links.trashedRecords', [], false); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="trashed" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="all">
                                                <i class="fa-solid fa-list"></i> <?= ui_text('backend/admins.links.allRecords', [], false); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="all" hidden></span>
                                            </a>
                                        </div>
                                        <!-- End Selezione stato record -->

                                        <!-- Azioni e Filtri -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#admins-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= ui_text('backend/admins.links.filters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/admins.links.resetFilters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= ui_text('backend/admins.links.resetSorting', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= ui_text('backend/admins.links.reloadList', [], false); ?>
                                        </a>
                                        
                                        <a href="#" id="export-entity" data-export-entity="admins" class="cmd-link">
                                            <i class="fa-solid fa-file-export"></i> <?= ui_text('backend/components/export.links.export', [], false); ?>
                                        </a>

                                        <a href="#" id="import-entity" data-import-entity="admins" class="cmd-link">
                                            <i class="fa-solid fa-file-import"></i> <?= ui_text('backend/components/import.links.import', [], false); ?>
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
                    <div id="admins-search-bar" class="collapse search-bar">
                        <div class="row card-body pb-0">

                            <!-- Ricerca avanzata per nome -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-firstname"><?= ui_text('backend/admins.labels.firstname', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.searchFirstname', 'placeholder', [], true); ?> type="text" id="admins-firstname" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.searchFirstname'), 'attr'); ?>" data-search-field="firstname" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="firstname" class="error_firstname app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per nome -->

                            <!-- Ricerca avanzata per cognome -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-lastname"><?= ui_text('backend/admins.labels.lastname', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.searchLastname', 'placeholder', [], true); ?> type="text" id="admins-lastname" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.searchLastname'), 'attr'); ?>" data-search-field="lastname" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="lastname" class="error_lastname app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per cognome -->

                            <!-- Ricerca avanzata per email -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-email"><?= ui_text('backend/admins.labels.email', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.searchEmail', 'placeholder', [], true); ?> type="text" id="admins-email" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.searchEmail'), 'attr'); ?>" data-search-field="email" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="email" class="error_email app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per email -->

                            <!-- Ricerca avanzata per phone -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-phone"><?= ui_text('backend/admins.labels.phone', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.searchPhone', 'placeholder', [], true); ?> type="text" id="admins-phone" class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.searchPhone'), 'attr'); ?>" data-search-field="phone" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="phone" class="error_phone app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per phone -->

                        </div>

                        <div class="row card-body py-0">

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="admins-created_at-from"><?= ui_text('backend/admins.labels.dateFromAdd', [], false); ?></label>
                                    <div id="wrapper-admins-created_at-from" class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.dateFromAdd', 'placeholder', [], true); ?> type="text" id="admins-created_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.dateFromAdd'), 'attr'); ?>" data-search-field="created_at-from" autocomplete="off">
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
                                    <label for="admins-created_at-to"><?= ui_text('backend/admins.labels.dateToAdd', [], false); ?></label>
                                    <div id="wrapper-admins-created_at-to" class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.dateToAdd', 'placeholder', [], true); ?> type="text" id="admins-created_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.dateToAdd'), 'attr'); ?>" data-search-field="created_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-to" class="error_created_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="admins-updated_at-from"><?= ui_text('backend/admins.labels.dateFromEdit', [], false); ?></label>
                                    <div id="wrapper-admins-updated_at-from" class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.dateFromEdit', 'placeholder', [], true); ?> type="text" id="admins-updated_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.dateFromEdit'), 'attr'); ?>" data-search-field="updated_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-from" class="error_updated_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="admins-updated_at-to"><?= ui_text('backend/admins.labels.dateToEdit', [], false); ?></label>
                                    <div id="wrapper-admins-updated_at-to" class="input-group">
                                        <input <?= ui_binding('backend/admins.placeholders.dateToEdit', 'placeholder', [], true); ?> type="text" id="admins-updated_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/admins.placeholders.dateToEdit'), 'attr'); ?>" data-search-field="updated_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-to" class="error_updated_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                        </div>

                    </div>
                    <!-- Fine parte filtri -->

                    <!-- Inizio contenuto -->
                    <div class="row">
                        <div class="col-12">
                            <div id="showAll-admins-container"></div>
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
