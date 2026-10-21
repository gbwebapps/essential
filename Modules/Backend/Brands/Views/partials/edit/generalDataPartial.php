<div class="card-body pb-0">

    <!-- Brand e Status -->
    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="mb-2">
                <label for="brand" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.brand'); ?></label>
                <input type="text" id="brand" name="brand" value="<?= esc($brand->brand, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/brands.placeholders.brand'), 'attr'); ?>">
                <!-- Contenitore dinamico per l'errore di validazione -->
                <div class="error_brand app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="mb-2">
                <label for="status" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.status'); ?></label>
                <select name="status" class="form-select" id="status">
                    <option value="1"<?= ((int) $brand->status === 1) ? ' selected' : ''; ?>><?= lang('backend/brands.labels.active'); ?></option>
                    <option value="0"<?= ((int) $brand->status === 0) ? ' selected' : ''; ?>><?= lang('backend/brands.labels.unactive'); ?></option>
                </select>
                <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- End Brand e Status -->

    <!-- Note Aggiuntive -->
    <div class="row">
        <div class="col-12">
            <div class="mb-2">
                <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/brands.labels.note'); ?></label>
                <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/brands.placeholders.note'), 'attr'); ?>"><?= esc($brand->note); ?></textarea>
                <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- End Note Aggiuntive -->

</div>
