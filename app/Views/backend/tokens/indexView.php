<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="tokens">

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
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= lang('backend/tokens.links.filters'); ?>
                                        </a>
                                        
                                        <a href="#" id="link-reset-search" class="cmd-link">
                                            <i class="fa-solid fa-xmark"></i> <?= lang('backend/tokens.links.resetFilters'); ?>
                                        </a>
                                        
                                        <a href="#" id="reset-sorting-link" class="cmd-link">
                                            <i class="fa-solid fa-sort"></i> <?= lang('backend/tokens.links.resetSorting'); ?>
                                        </a>
                                        
                                        <a href="#" id="refresh-list" class="cmd-link">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/tokens.links.reloadList'); ?>
                                        </a>

                                        <a href="#" id="export-entity" class="cmd-link"  data-export-entity="admins_tokens">
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

                            <!-- Ricerca avanzata per email -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="tokens-email"><?= lang('backend/tokens.labels.username'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="tokens-email" class="form-control" placeholder="<?= lang('backend/tokens.placeholders.searchUsername'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_email text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End Ricerca avanzata per email -->

                            <!-- Ricerca avanzata per data da -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data Da -->
                                    <label for="tokens-token_create-from"><?= lang('backend/tokens.labels.dateFrom'); ?></label>
                                    <div id="wrapper-tokens-token_create-from" class="input-group">
                                        <input type="text" id="tokens-token_create-from" data-input class="form-control" placeholder="<?= lang('backend/tokens.placeholders.dateFrom'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div class="error_token_create-from text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data da -->

                            <!-- Ricerca avanzata per data a -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <!-- Campo Data A -->
                                    <label for="tokens-token_create-to"><?= lang('backend/tokens.labels.dateTo'); ?></label>
                                    <div id="wrapper-tokens-token_create-to" class="input-group">
                                        <input type="text" id="tokens-token_create-to" data-input class="form-control" placeholder="<?= lang('backend/tokens.placeholders.dateTo'); ?>" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div class="error_created_at-to text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                            <!-- End ricerca avanzata per data a -->

                            <!-- Ricerca avanzata per azione -->
                            <div class="col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="tokens-token_type"><?= lang('backend/tokens.labels.tokenType'); ?></label>
                                    <div class="input-group">
                                        <select id="tokens-token_type" class="form-select">
                                            <!-- Option che funge da placeholder -->
                                            <option value="" disabled selected hidden><?= lang('backend/tokens.placeholders.searchType'); ?></option>
                                            <option value="activation"><?= lang('backend/tokens.labels.activation'); ?></option>
                                            <option value="session"><?= lang('backend/tokens.labels.session'); ?></option>
                                            <option value="cookie"><?= lang('backend/tokens.labels.cookie'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div class="error_token_type text-danger fw-bold small pt-1">&nbsp;</div>
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