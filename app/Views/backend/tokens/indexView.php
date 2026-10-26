<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="tokens-list" class="tokens crud-list">

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
                                    <i class="fa-solid fa-bars"></i> <?= ui_text('backend/tokens.labels.filtersAndOptions', [], false); ?>
                                </button>
                            </div>
                            <!-- End Bottone Mobile -->

                            <!-- Contenitore Offcanvas -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= ui_text('backend/tokens.labels.filtersAndOptions', [], false); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#actionsOffcanvas" aria-label="Close"></button>
                                </div>
                                
                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">

                                        <!-- Azioni e Filtri -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#tokens-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= ui_text('backend/tokens.links.filters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= ui_text('backend/tokens.links.resetFilters', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= ui_text('backend/tokens.links.resetSorting', [], false); ?>
                                        </a>
                                        
                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= ui_text('backend/tokens.links.reloadList', [], false); ?>
                                        </a>

                                        <a href="#" id="export-entity" data-export-entity="admins_tokens" class="cmd-link">
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
                    <div id="tokens-search-bar" class="collapse search-bar">
                        <div class="row card-body">

                            <!-- Ricerca avanzata per email -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="tokens-email"><?= ui_text('backend/tokens.labels.username', [], false); ?></label>
                                    <div class="input-group">
                                        <input <?= ui_binding('backend/tokens.placeholders.searchUsername', 'placeholder', [], true); ?> type="text" id="tokens-email" class="form-control" placeholder="<?= esc(lang('backend/tokens.placeholders.searchUsername'), 'attr'); ?>" data-search-field="email" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="email" class="error_email app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per email -->

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="tokens-token_create-from"><?= ui_text('backend/tokens.labels.dateFrom', [], false); ?></label>
                                    <div id="wrapper-tokens-token_create-from" class="input-group">
                                        <input <?= ui_binding('backend/tokens.placeholders.dateFrom', 'placeholder', [], true); ?> type="text" id="tokens-token_create-from" data-input class="form-control" placeholder="<?= esc(lang('backend/tokens.placeholders.dateFrom'), 'attr'); ?>" data-search-field="token_create-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="token_create-from" class="error_token_create-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="tokens-token_create-to"><?= ui_text('backend/tokens.labels.dateTo', [], false); ?></label>
                                    <div id="wrapper-tokens-token_create-to" class="input-group">
                                        <input <?= ui_binding('backend/tokens.placeholders.dateTo', 'placeholder', [], true); ?> type="text" id="tokens-token_create-to" data-input class="form-control" placeholder="<?= esc(lang('backend/tokens.placeholders.dateTo'), 'attr'); ?>" data-search-field="token_create-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="token_create-to" class="error_token_create-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                            <!-- Ricerca avanzata per azione -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="tokens-token_type"><?= ui_text('backend/tokens.labels.tokenType', [], false); ?></label>
                                    <div class="input-group">
                                        <select id="tokens-token_type" class="form-select" data-search-field="token_type">
                                            <!-- Option che funge da placeholder -->
                                            <option <?= ui_binding('backend/tokens.placeholders.searchType', 'text', [], false); ?> value="" disabled selected hidden><?= lang('backend/tokens.placeholders.searchType'); ?></option>
                                            <option <?= ui_binding('backend/tokens.labels.activation', 'text', [], false); ?> value="activation"><?= lang('backend/tokens.labels.activation'); ?></option>
                                            <option <?= ui_binding('backend/tokens.labels.session', 'text', [], false); ?> value="session"><?= lang('backend/tokens.labels.session'); ?></option>
                                            <option <?= ui_binding('backend/tokens.labels.cookie', 'text', [], false); ?> value="cookie"><?= lang('backend/tokens.labels.cookie'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="token_type" class="error_token_type app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per azione -->

                        </div>

                    </div>
                    <!-- Fine parte filtri -->

                    <!-- Inizio contenuto -->
                    <div class="row">
                        <div class="col-12">
                            <div id="index-tokens-container"></div>
                        </div>
                    </div>
                    <!-- Fine contenuto -->

                </div>
            </div>
        </div>
    </div>

    <div id="export-modal-container"></div>
    
<?= $this->endSection() ?>
