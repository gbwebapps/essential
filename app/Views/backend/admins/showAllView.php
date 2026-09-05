<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="admins">

        <div class="row">
            <div class="col-12">

                <div class="card">

                    <!-- Inizio testata lista -->
                    <div class="card-header">
                        <div class="row">

                            <!-- Select per il numero delle righe da mostrare -->
                            <div class="col-12 col-md-1 mb-3 mb-md-0">
                                <select id="changeNumRows" class="form-select">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            <!-- End Select per il numero delle righe da mostrare -->

                            <!-- Bottone Mobile per aprire l'Offcanvas -->
                            <div class="col-12 d-md-none mb-3">
                                <button class="btn btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#actionsOffcanvas" aria-controls="actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> Opzioni e Filtri
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> Opzioni e Filtri
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">
                                        
                                        <!-- Selezione stato record -->
                                        <div class="d-flex flex-column flex-md-row gap-0 gap-md-3 me-md-auto mb-3 mb-md-0 border-md-end pe-md-3">
                                            <a href="#" class="cmd-link active-filter" data-trash-filter="active">
                                                <i class="fa-solid fa-check"></i> <?= lang('backend/admins.links.activeRecords'); ?>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="trashed">
                                                <i class="fa-solid fa-trash-can"></i> <?= lang('backend/admins.links.trashedRecords'); ?>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="all">
                                                <i class="fa-solid fa-list"></i> <?= lang('backend/admins.links.allRecords'); ?>
                                            </a>
                                        </div>
                                        <!-- End Selezione stato record -->

                                        <!-- Azioni e Filtri -->
                                        <a href="#" id="link-search" data-bs-toggle="collapse" data-bs-target="#search-bar" class="cmd-link">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= lang('backend/admins.links.filters'); ?>
                                        </a>
                                        
                                        <a href="#" id="link-reset-search" class="cmd-link">
                                            <i class="fa-solid fa-xmark"></i> <?= lang('backend/admins.links.resetFilters'); ?>
                                        </a>
                                        
                                        <a href="#" id="reset-sorting-link" class="cmd-link">
                                            <i class="fa-solid fa-sort"></i> <?= lang('backend/admins.links.resetSorting'); ?>
                                        </a>
                                        
                                        <a href="#" id="refresh-list" class="cmd-link">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/admins.links.reloadList'); ?>
                                        </a>
                                        
                                        <a href="#" id="export-entity" data-export-entity="admins" class="cmd-link">
                                            <i class="fa-solid fa-file-export"></i> <?= lang('backend/components/export.links.export'); ?>
                                        </a>
                                        
                                        <a href="#" id="import-entity" data-import-entity="admins" class="cmd-link">
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
                    <div id="search-bar" class="collapse">
                        <div class="row card-body pb-0">

                            <!-- Ricerca avanzata per nome -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-firstname"><?= lang('backend/admins.labels.firstname'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="admins-firstname" class="form-control" placeholder="<?= lang('backend/admins.placeholders.searchFirstname'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_firstname text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per nome -->

                            <!-- Ricerca avanzata per cognome -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-lastname"><?= lang('backend/admins.labels.lastname'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="admins-lastname" class="form-control" placeholder="<?= lang('backend/admins.placeholders.searchLastname'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_lastname text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per cognome -->

                            <!-- Ricerca avanzata per email -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-email"><?= lang('backend/admins.labels.email'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="admins-email" class="form-control" placeholder="<?= lang('backend/admins.placeholders.searchEmail'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per email -->

                            <!-- Ricerca avanzata per phone -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="admins-phone"><?= lang('backend/admins.labels.phone'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="admins-phone" class="form-control" placeholder="<?= lang('backend/admins.placeholders.searchPhone'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_phone text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per phone -->

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