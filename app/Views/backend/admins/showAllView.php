<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div class="admins">

        <div class="row">
            <div class="col-12">

                <div class="card">

                    <!-- Inizio testata lista -->
                    <div class="card-header">
                        <div class="row">
                            <div class="col-12 col-md-2">

                                <!-- Select per il numero delle righe da mostrare -->
                                <select id="changeNumRows" class="form-select">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>

                            </div>

                            <div class="col-12 col-md-10">
                                <div class="pt-2 d-flex flex-column flex-md-row align-items-center justify-content-md-end">

                                    <!-- Apertura/chiusura parte filtri -->
                                    <a href="#" id="link-search"
                                       data-bs-toggle="collapse"
                                       data-bs-target="#search-bar"
                                       aria-expanded="false"
                                       aria-controls="search-bar"
                                        class="mb-2 mb-md-0 me-0 me-md-2 bar-link">
                                        <b><i class="fa-solid fa-filter"></i> <?= lang('backend/admins.links.filters'); ?></b>
                                    </a>

                                    <!-- Reset filtri e ordinamento -->
                                    <a href="#" id="link-reset-search" class="mb-2 mb-md-0 mx-0 mx-md-2 bar-link">
                                        <b><i class="fa-solid fa-filter-circle-xmark"></i> <?= lang('backend/admins.links.resetFilters'); ?></b>
                                    </a>

                                    <!-- Reset solo ordinamento -->
                                    <a href="#" id="reset-sorting-link" class="mb-2 mb-md-0 mx-0 mx-md-2 bar-link">
                                        <b><i class="fa-solid fa-sort"></i> <?= lang('backend/admins.links.resetSorting'); ?></b>
                                    </a>

                                    <!-- Semplice ricarica lista -->
                                    <a href="#" id="refresh-list" class="mb-md-0 ms-0 ms-md-2 bar-link">
                                        <b><i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/admins.links.reloadList'); ?></b>
                                    </a>

                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Inizio parte filtri -->
                    <div id="search-bar" class="collapse">
                        <div class="row card-body">

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
    
<?= $this->endSection() ?>