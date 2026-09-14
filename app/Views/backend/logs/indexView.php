<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="logs">

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
                                        
                                        <!-- Azioni e Filtri -->
                                        <a href="#" id="link-search" data-bs-toggle="collapse" data-bs-target="#search-bar" class="cmd-link">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= lang('backend/logs.links.filters'); ?>
                                        </a>
                                        
                                        <a href="#" id="link-reset-search" class="cmd-link">
                                            <i class="fa-solid fa-xmark"></i> <?= lang('backend/logs.links.resetFilters'); ?>
                                        </a>
                                        
                                        <a href="#" id="reset-sorting-link" class="cmd-link">
                                            <i class="fa-solid fa-sort"></i> <?= lang('backend/logs.links.resetSorting'); ?>
                                        </a>
                                        
                                        <a href="#" id="refresh-list" class="cmd-link">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/logs.links.reloadList'); ?>
                                        </a>

                                        <a href="#" id="export-entity" class="cmd-link"  data-export-entity="admins_logs">
                                            <i class="fa-solid fa-file-export"></i> <?= lang('backend/components/export.links.export'); ?>
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
                        <div class="row card-body">

                            <!-- Ricerca avanzata per username -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="logs-username"><?= lang('backend/logs.labels.username'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="logs-username" class="form-control" placeholder="<?= lang('backend/logs.placeholders.searchUsername'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_username text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per username -->

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="logs-login-from"><?= lang('backend/logs.labels.dateFrom'); ?></label>
                                    <div id="wrapper-logs-login-from" class="input-group">
                                        <input type="text" id="logs-login-from" data-input class="form-control" placeholder="<?= lang('backend/logs.placeholders.dateFrom'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div class="error_login-from text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="logs-login-to"><?= lang('backend/logs.labels.dateTo'); ?></label>
                                    <div id="wrapper-logs-login-to" class="input-group">
                                        <input type="text" id="logs-login-to" data-input class="form-control" placeholder="<?= lang('backend/logs.placeholders.dateTo'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div class="error_login-to text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                            <!-- Ricerca avanzata per logout_reason -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="logs-logout_reason"><?= lang('backend/logs.labels.logoutReason'); ?></label>
                                    <div class="input-group">
                                        <select id="logs-logout_reason" class="form-select">
                                            <!-- Option che funge da placeholder -->
                                            <option value="" disabled selected hidden><?= lang('backend/logs.placeholders.searchLogoutReason'); ?></option>
                                            <option value="manual"><?= lang('backend/logs.labels.manual'); ?></option>
                                            <option value="timeout"><?= lang('backend/logs.labels.timeout'); ?></option>
                                            <option value="banned"><?= lang('backend/logs.labels.banned'); ?></option>
                                            <option value="deleted"><?= lang('backend/logs.labels.deleted'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_logout_reason text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per logout_reason -->

                        </div>
                    </div>
                    <!-- Fine parte filtri -->

                    <!-- Inizio contenuto -->
                    <div class="row">
                        <div class="col-12">
                            <div id="index-logs-container"></div>
                        </div>
                    </div>
                    <!-- Fine contenuto -->

                </div>
            </div>
        </div>
    </div>

    <div id="export-modal-container"></div>
    
<?= $this->endSection() ?>