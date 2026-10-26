<div class="card-body pb-0">

    <!-- SKU e prodotto -->
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="sku" class="form-label">
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.sku', [], false); ?>
                </label>
                <input <?= ui_binding('backend/products.placeholders.sku', 'placeholder', [], true); ?> type="text" id="sku" name="sku" value="<?= esc($product->sku, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.sku'), 'attr'); ?>">
                <div class="error_sku app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="mb-2">
                <label for="product" class="form-label">
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.product', [], false); ?>
                </label>
                <input <?= ui_binding('backend/products.placeholders.product', 'placeholder', [], true); ?> type="text" id="product" name="product" value="<?= esc($product->product, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.product'), 'attr'); ?>">
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
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.quantity', [], false); ?>
                </label>
                <input type="number" id="quantity" name="quantity" value="<?= esc((string) $product->quantity, 'attr'); ?>" class="form-control" min="0" max="4294967295" step="1">
                <div class="error_quantity app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="price" class="form-label">
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.price', [], false); ?>
                </label>
                <input type="number" id="price" name="price" value="<?= esc((string) $product->price, 'attr'); ?>" class="form-control" min="0" max="9999999999.99" step="0.01">
                <div class="error_price app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="status" class="form-label">
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.status', [], false); ?>
                </label>
                <select name="status" class="form-select" id="status">
                    <option <?= ui_binding('backend/products.labels.active', 'text', [], false); ?> value="1"<?= ((int) $product->status === 1) ? ' selected' : ''; ?>><?= lang('backend/products.labels.active'); ?></option>
                    <option <?= ui_binding('backend/products.labels.unactive', 'text', [], false); ?> value="0"<?= ((int) $product->status === 0) ? ' selected' : ''; ?>><?= lang('backend/products.labels.unactive'); ?></option>
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
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.brands', [], false); ?>
                </label>
                <select name="brand_uuids[]" id="brand_uuids" class="form-select tom-select" multiple>
                    <?php foreach($brandOptions as $brand): ?>
                        <option <?php if((int) $brand->status === 0): ?><?= ui_binding('backend/products.labels.suspendedRelation', 'text', [], true, '', ($brand->brand) . ' — '); ?><?php endif; ?> value="<?= esc($brand->uuid, 'attr'); ?>"<?= in_array($brand->uuid, $product->brand_uuids, true) ? ' selected' : ''; ?><?= ((int) $brand->status === 0) ? ' disabled' : ''; ?>>
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
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.categories', [], false); ?>
                </label>
                <select name="category_uuids[]" id="category_uuids" class="form-select tom-select" multiple>
                    <?php foreach($categoryOptions as $category): ?>
                        <option <?php if((int) $category->status === 0): ?><?= ui_binding('backend/products.labels.suspendedRelation', 'text', [], true, '', ($category->path ?? $category->category) . ' — '); ?><?php endif; ?> value="<?= esc($category->uuid, 'attr'); ?>"<?= in_array($category->uuid, $product->category_uuids, true) ? ' selected' : ''; ?><?= ((int) $category->status === 0) ? ' disabled' : ''; ?>>
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
                    <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/products.labels.note', [], false); ?>
                </label>
                <textarea <?= ui_binding('backend/products.placeholders.note', 'placeholder', [], true); ?> name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.note'), 'attr'); ?>"><?= esc($product->note); ?></textarea>
                <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- End Note aggiuntive -->

</div>
