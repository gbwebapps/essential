<div class="card-body pb-0">
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="sku" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.sku'); ?></label>
                <input type="text" id="sku" name="sku" value="<?= esc($product->sku, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.sku'), 'attr'); ?>">
                <div class="error_sku app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="mb-2">
                <label for="product" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.product'); ?></label>
                <input type="text" id="product" name="product" value="<?= esc($product->product, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.product'), 'attr'); ?>">
                <div class="error_product app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="quantity" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.quantity'); ?></label>
                <input type="number" id="quantity" name="quantity" value="<?= esc((string) $product->quantity, 'attr'); ?>" class="form-control" min="0" step="1">
                <div class="error_quantity app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="price" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.price'); ?></label>
                <input type="number" id="price" name="price" value="<?= esc((string) $product->price, 'attr'); ?>" class="form-control" min="0" step="0.01">
                <div class="error_price app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="status" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.status'); ?></label>
                <select name="status" class="form-select" id="status">
                    <option value="1"<?= ((int) $product->status === 1) ? ' selected' : ''; ?>><?= lang('backend/products.labels.active'); ?></option>
                    <option value="0"<?= ((int) $product->status === 0) ? ' selected' : ''; ?>><?= lang('backend/products.labels.unactive'); ?></option>
                </select>
                <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="mb-2">
                <label for="brand_uuids" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.brands'); ?></label>
                <select name="brand_uuids[]" id="brand_uuids" class="form-select tom-select" multiple>
                    <?php foreach($brandOptions as $brand): ?>
                        <option value="<?= esc($brand->uuid, 'attr'); ?>"<?= in_array($brand->uuid, $product->brand_uuids, true) ? ' selected' : ''; ?>><?= esc($brand->brand); ?><?= ((int) $brand->status === 0) ? ' — ' . esc(lang('backend/products.labels.unactive')) : ''; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="error_brand_uuids app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="mb-2">
                <label for="category_uuids" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.categories'); ?></label>
                <select name="category_uuids[]" id="category_uuids" class="form-select tom-select" multiple>
                    <?php foreach($categoryOptions as $category): ?>
                        <option value="<?= esc($category->uuid, 'attr'); ?>"<?= in_array($category->uuid, $product->category_uuids, true) ? ' selected' : ''; ?>><?= esc($category->path); ?><?= ((int) $category->status === 0) ? ' — ' . esc(lang('backend/products.labels.unactive')) : ''; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="error_category_uuids app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="mb-2">
                <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/products.labels.note'); ?></label>
                <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/products.placeholders.note'), 'attr'); ?>"><?= esc($product->note); ?></textarea>
                <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
</div>
