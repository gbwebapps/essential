<!-- Struttura principale del form di inserimento -->

<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="products-add" data-unsaved-check>

                <!-- Dati generali -->
                <div id="generalData">

                    <div class="card-header">
                        <h2 class="card-title text-center text-lg-start mb-0"><?= lang('backend/products.panels.generalData'); ?></h2>
                    </div>

                    <div class="card-body pb-0">

                        <!-- SKU e prodotto -->
                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="sku" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.sku'); ?>
                                    </label>
                                    <input type="text" id="sku" name="sku" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.sku'), 'attr'); ?>">
                                    <div class="error_sku app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-8">
                                <div class="mb-2">
                                    <label for="product" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.product'); ?>
                                    </label>
                                    <input type="text" id="product" name="product" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.product'), 'attr'); ?>">
                                    <div class="error_product app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End SKU e prodotto -->

                        <!-- Quantità, prezzo e stato -->
                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="quantity" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.quantity'); ?>
                                    </label>
                                    <input type="number" id="quantity" name="quantity" class="form-control" min="0" max="4294967295" step="1" value="0">
                                    <div class="error_quantity app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="price" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.price'); ?>
                                    </label>
                                    <input type="number" id="price" name="price" class="form-control" min="0" max="9999999999.99" step="0.01" value="0.00">
                                    <div class="error_price app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="status" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.status'); ?>
                                    </label>
                                    <select name="status" class="form-select" id="status">
                                        <option value="1"><?= lang('backend/products.labels.active'); ?></option>
                                        <option value="0"><?= lang('backend/products.labels.unactive'); ?></option>
                                    </select>
                                    <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Quantità, prezzo e stato -->

                        <!-- Marche e categorie -->
                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="brand_uuids" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.brands'); ?>
                                    </label>
                                    <select name="brand_uuids[]" id="brand_uuids" class="form-select tom-select" multiple>
                                        <?php foreach($brandOptions as $brand): ?>
                                            <option value="<?= esc($brand->uuid, 'attr'); ?>"<?= ((int) $brand->status === 0) ? ' disabled' : ''; ?>>
                                                <?= esc($brand->brand); ?><?= ((int) $brand->status === 0) ? ' — ' . esc(lang('backend/products.labels.suspendedRelation')) : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="error_brand_uuids app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="mb-2">
                                    <label for="category_uuids" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.categories'); ?>
                                    </label>
                                    <select name="category_uuids[]" id="category_uuids" class="form-select tom-select" multiple>
                                        <?php foreach($categoryOptions as $category): ?>
                                            <option value="<?= esc($category->uuid, 'attr'); ?>"<?= ((int) $category->status === 0) ? ' disabled' : ''; ?>>
                                                <?= esc($category->path); ?><?= ((int) $category->status === 0) ? ' — ' . esc(lang('backend/products.labels.suspendedRelation')) : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="error_category_uuids app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Marche e categorie -->

                        <!-- Note aggiuntive -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">
                                    <label for="note">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.note'); ?>
                                    </label>
                                    <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.note'), 'attr'); ?>"></textarea>
                                    <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Note aggiuntive -->

                    </div>

                </div>
                <!-- End Dati generali -->

                <!-- Upload e anteprima immagini -->
                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>
                <!-- End Upload e anteprima immagini -->

            </form>

        </div>

    </div>
</div>
