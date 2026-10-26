<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="logs-list" class="logs crud-list">

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
                                <button class="btn app-btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#actionsOffcanvas" aria-controls="actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> <?= ui_text('backend/logs.labels.filtersAndOptions', [], false); ?>
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= ui_text('backend/logs.labels.filtersAndOptions', [], false); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">
                                        
                                        <!-- Azioni e Filtri -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#logs-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= ui_text('backend/logs.links.filters', [], false); ?>
                                        </a>

                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/logs.links.resetFilters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= ui_text('backend/logs.links.resetSorting', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= ui_text('backend/logs.links.reloadList', [], false); ?>
                                        </a>

                                        <a href="#" id="export-entity" data-export-entity="admins_logs" class="cmd-link">
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
                    <div id="logs-search-bar" class="collapse search-bar">
                        <div class="row card-body">

                            <!-- Ricerca avanzata per username -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="logs-username"><?= ui_text('backend/logs.labels.username', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/logs.placeholders.searchUsername', 'placeholder', [], true); ?> type="text" id="logs-username" class="form-control" placeholder="<?= esc(lang('backend/logs.placeholders.searchUsername'), 'attr'); ?>" data-search-field="username" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="username" class="error_username app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per username -->

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="logs-login-from"><?= ui_text('backend/logs.labels.dateFrom', [], false); ?></label>
                                    <div id="wrapper-logs-login-from" class="input-group">
                                        <input <?= ui_binding('backend/logs.placeholders.dateFrom', 'placeholder', [], true); ?> type="text" id="logs-login-from" data-input class="form-control" placeholder="<?= esc(lang('backend/logs.placeholders.dateFrom'), 'attr'); ?>" data-search-field="login-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="login-from" class="error_login-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="logs-login-to"><?= ui_text('backend/logs.labels.dateTo', [], false); ?></label>
                                    <div id="wrapper-logs-login-to" class="input-group">
                                        <input <?= ui_binding('backend/logs.placeholders.dateTo', 'placeholder', [], true); ?> type="text" id="logs-login-to" data-input class="form-control" placeholder="<?= esc(lang('backend/logs.placeholders.dateTo'), 'attr'); ?>" data-search-field="login-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="login-to" class="error_login-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                            <!-- Ricerca avanzata per logout_reason -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="logs-logout_reason"><?= ui_text('backend/logs.labels.logoutReason', [], false); ?></label>
                                    <div class="input-group">
                                        <select id="logs-logout_reason" class="form-select" data-search-field="logout_reason">
                                            <!-- Option che funge da placeholder -->
                                            <option <?= ui_binding('backend/logs.placeholders.searchLogoutReason', 'text', [], false); ?> value="" disabled selected hidden><?= lang('backend/logs.placeholders.searchLogoutReason'); ?></option>
                                            <option <?= ui_binding('backend/logs.labels.manual', 'text', [], false); ?> value="manual"><?= lang('backend/logs.labels.manual'); ?></option>
                                            <option <?= ui_binding('backend/logs.labels.timeout', 'text', [], false); ?> value="timeout"><?= lang('backend/logs.labels.timeout'); ?></option>
                                            <option <?= ui_binding('backend/logs.labels.banned', 'text', [], false); ?> value="banned"><?= lang('backend/logs.labels.banned'); ?></option>
                                            <option <?= ui_binding('backend/logs.labels.deleted', 'text', [], false); ?> value="deleted"><?= lang('backend/logs.labels.deleted'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="logout_reason" class="error_logout_reason app-text-danger fw-bold small pt-1">&nbsp;</div>
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
