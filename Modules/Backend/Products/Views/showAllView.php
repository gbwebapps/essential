<?= $this->extend('backend/template/mainView') ?>

<?= $this->section('content') ?>

    <div id="products-list" class="products crud-list">

        <div class="row">
            <div class="col-12">

                <div class="card">

                    <!-- Testata lista -->
                    <div class="card-header">
                        <div class="row">

                            <!-- Numero di record per pagina -->
                            <div class="col-12 col-md-1 mb-3 mb-md-0">
                                <select class="form-select changeNumRows">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            <!-- End Numero di record per pagina -->

                            <!-- Apertura offcanvas su dispositivi mobili -->
                            <div class="col-12 d-md-none mb-3">
                                <button class="btn app-btn-secondary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#products-actionsOffcanvas" aria-controls="products-actionsOffcanvas">
                                    <i class="fa-solid fa-bars"></i> <?= lang('backend/products.labels.filtersAndOptions'); ?>
                                </button>
                            </div>
                            <!-- End Apertura offcanvas su dispositivi mobili -->

                            <!-- Azioni della lista -->
                            <div class="col-12 col-md-11 offcanvas-md offcanvas-bottom" tabindex="-1" id="products-actionsOffcanvas">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title">
                                        <i class="fa-solid fa-bars"></i> <?= lang('backend/products.labels.filtersAndOptions'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#products-actionsOffcanvas" aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body pt-md-2">
                                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-end w-100 gap-0 gap-md-3">

                                        <!-- Selezione stato del cestino -->
                                        <div class="d-flex flex-column flex-md-row gap-0 gap-md-3 me-md-auto mb-3 mb-md-0 border-md-end pe-md-3">
                                            <a href="#" class="cmd-link active-filter" data-trash-filter="active">
                                                <i class="fa-solid fa-check"></i> <?= lang('backend/products.links.activeRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="active" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="suspended">
                                                <i class="fa-solid fa-pause"></i> <?= lang('backend/products.links.suspendedRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="suspended" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="trashed">
                                                <i class="fa-solid fa-trash-can"></i> <?= lang('backend/products.links.trashedRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="trashed" hidden></span>
                                            </a>
                                            <a href="#" class="cmd-link" data-trash-filter="all">
                                                <i class="fa-solid fa-list"></i> <?= lang('backend/products.links.allRecords'); ?>
                                                <span class="badge app-bg-surface app-text-muted border rounded-1 ms-1" data-trash-count="all" hidden></span>
                                            </a>
                                        </div>
                                        <!-- End Selezione stato del cestino -->

                                        <!-- Filtri e comandi -->
                                        <a href="#" data-bs-toggle="collapse" data-bs-target="#products-search-bar" class="cmd-link link-search">
                                            <i class="fa-solid fa-magnifying-glass"></i> <?= lang('backend/products.links.filters'); ?>
                                        </a>

                                        <a href="#" class="cmd-link link-reset-search">
                                            <i class="fa-solid fa-xmark"></i> <?= lang('backend/products.links.resetFilters'); ?>
                                        </a>

                                        <a href="#" class="cmd-link reset-sorting-link">
                                            <i class="fa-solid fa-sort"></i> <?= lang('backend/products.links.resetSorting'); ?>
                                        </a>

                                        <a href="#" class="cmd-link refresh-list">
                                            <i class="fa-solid fa-arrows-rotate"></i> <?= lang('backend/products.links.reloadList'); ?>
                                        </a>

                                        <a href="#" id="export-entity" data-export-entity="products" class="cmd-link">
                                            <i class="fa-solid fa-file-export"></i> <?= lang('backend/components/export.links.export'); ?>
                                        </a>

                                        <a href="#" id="import-entity" data-import-entity="products" class="cmd-link">
                                            <i class="fa-solid fa-file-import"></i> <?= lang('backend/components/import.links.import'); ?>
                                        </a>
                                        <!-- End Filtri e comandi -->

                                    </div>
                                </div>
                            </div>
                            <!-- End Azioni della lista -->

                        </div>
                    </div>
                    <!-- End Testata lista -->

                    <!-- Filtri di ricerca -->
                    <div id="products-search-bar" class="collapse search-bar">

                        <!-- Identificazione e stato -->
                        <div class="row card-body pb-0">
                            <div class="col-12 col-md-4">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-sku"><?= lang('backend/products.labels.sku'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="products-sku" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.searchSku'), 'attr'); ?>" data-search-field="sku" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="sku" class="error_sku app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-product"><?= lang('backend/products.labels.product'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="products-product" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.searchProduct'), 'attr'); ?>" data-search-field="product" autocomplete="off">
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="product" class="error_product app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-status"><?= lang('backend/products.labels.status'); ?></label>
                                    <div class="input-group">
                                        <select id="products-status" class="form-select" data-search-field="status">
                                            <option value=""><?= lang('backend/products.links.allRecords'); ?></option>
                                            <option value="1"><?= lang('backend/products.labels.active'); ?></option>
                                            <option value="0"><?= lang('backend/products.labels.unactive'); ?></option>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="status" class="error_status app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Identificazione e stato -->

                        <!-- Relazioni -->
                        <div class="row card-body py-0">
                            <div class="col-12 col-md-6">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-brand_uuid"><?= lang('backend/products.labels.brands'); ?></label>
                                    <div class="input-group">
                                        <select id="products-brand_uuid" class="form-select" data-search-field="brand_uuid">
                                            <option value=""><?= lang('backend/products.links.allRecords'); ?></option>
                                            <?php foreach($brandOptions as $brand): ?>
                                                <option value="<?= esc($brand->uuid, 'attr'); ?>"><?= esc($brand->brand); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="brand_uuid" class="error_brand_uuid app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-category_uuid"><?= lang('backend/products.labels.categories'); ?></label>
                                    <div class="input-group">
                                        <select id="products-category_uuid" class="form-select" data-search-field="category_uuid">
                                            <option value=""><?= lang('backend/products.links.allRecords'); ?></option>
                                            <?php foreach($categoryOptions as $category): ?>
                                                <option value="<?= esc($category->uuid, 'attr'); ?>"><?= esc($category->path); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                    </div>
                                    <div data-search-error="category_uuid" class="error_category_uuid app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Relazioni -->

                        <!-- Intervalli numerici -->
                        <div class="row card-body py-0">
                            <?php foreach(['quantity-from' => 'quantityFrom', 'quantity-to' => 'quantityTo', 'price-from' => 'priceFrom', 'price-to' => 'priceTo'] as $field => $label): ?>
                                <div class="col-12 col-md-3">
                                    <div class="mb-2 mb-md-0">
                                        <label for="products-<?= esc($field, 'attr'); ?>"><?= lang('backend/products.labels.' . $label); ?></label>
                                        <div class="input-group">
                                            <input type="number" id="products-<?= esc($field, 'attr'); ?>" class="form-control" min="0"<?= str_starts_with($field, 'price') ? ' max="9999999999.99" step="0.01"' : ' max="4294967295" step="1"'; ?> placeholder="<?= esc(lang('backend/products.placeholders.' . $label), 'attr'); ?>" data-search-field="<?= esc($field, 'attr'); ?>" autocomplete="off">
                                            <span class="input-group-text reset-search-field"><i class="fa-solid fa-times"></i></span>
                                        </div>
                                        <div data-search-error="<?= esc($field, 'attr'); ?>" class="error_<?= esc($field, 'attr'); ?> app-text-danger fw-bold small pt-1">&nbsp;</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- End Intervalli numerici -->

                        <!-- Intervalli temporali -->
                        <div class="row card-body py-0">
                            <div class="col-12 col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-created_at-from"><?= lang('backend/products.labels.dateFromAdd'); ?></label>
                                    <div id="wrapper-products-created_at-from" class="input-group">
                                        <input type="text" id="products-created_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.dateFromAdd'), 'attr'); ?>" data-search-field="created_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-from" class="error_created_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-created_at-to"><?= lang('backend/products.labels.dateToAdd'); ?></label>
                                    <div id="wrapper-products-created_at-to" class="input-group">
                                        <input type="text" id="products-created_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.dateToAdd'), 'attr'); ?>" data-search-field="created_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="created_at-to" class="error_created_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-updated_at-from"><?= lang('backend/products.labels.dateFromEdit'); ?></label>
                                    <div id="wrapper-products-updated_at-from" class="input-group">
                                        <input type="text" id="products-updated_at-from" data-input class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.dateFromEdit'), 'attr'); ?>" data-search-field="updated_at-from" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-from" class="error_updated_at-from app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-3">
                                <div class="mb-2 mb-md-0">
                                    <label for="products-updated_at-to"><?= lang('backend/products.labels.dateToEdit'); ?></label>
                                    <div id="wrapper-products-updated_at-to" class="input-group">
                                        <input type="text" id="products-updated_at-to" data-input class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.dateToEdit'), 'attr'); ?>" data-search-field="updated_at-to" autocomplete="off">
                                        <span class="input-group-text reset-search-field" data-clear><i class="fa-solid fa-times"></i></span>
                                        <span class="input-group-text" data-open><i class="fa-solid fa-calendar-days"></i></span>
                                    </div>
                                    <div data-search-error="updated_at-to" class="error_updated_at-to app-text-danger fw-bold small pt-1">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Intervalli temporali -->

                    </div>
                    <!-- End Filtri di ricerca -->

                    <!-- Contenuto aggiornato via AJAX -->
                    <div class="row">
                        <div class="col-12">
                            <div id="showAll-products-container"></div>
                        </div>
                    </div>
                    <!-- End Contenuto aggiornato via AJAX -->

                </div>

            </div>
        </div>
    </div>

    <div id="import-modal-container"></div>
    <div id="export-modal-container"></div>

<?= $this->endSection() ?>

