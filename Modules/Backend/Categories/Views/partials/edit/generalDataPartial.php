<div class="card-body pb-0">

    <!-- Categoria, categoria padre e stato -->
    <div class="row">
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="category" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/categories.labels.category'); ?></label>
                <input type="text" id="category" name="category" value="<?= esc($category->category, 'attr'); ?>" class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.category'), 'attr'); ?>">
                <!-- Contenitore dinamico per l'errore di validazione -->
                <div class="error_category app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="parent_id" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/categories.labels.parentCategory'); ?></label>
                <select name="parent_id" class="form-select" id="parent_id">
                    <option value=""><?= lang('backend/categories.labels.rootCategory'); ?></option>
                    <?php foreach($parentCategories as $parentCategory): ?>
                        <option value="<?= esc($parentCategory->id, 'attr'); ?>"<?= ((int) $category->parent_id === (int) $parentCategory->id) ? ' selected' : ''; ?>><?= esc($parentCategory->path ?? $parentCategory->category); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="error_parent_id app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="mb-2">
                <label for="status" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/categories.labels.status'); ?></label>
                <select name="status" class="form-select" id="status">
                    <option value="1"<?= ((int) $category->status === 1) ? ' selected' : ''; ?>><?= lang('backend/categories.labels.active'); ?></option>
                    <option value="0"<?= ((int) $category->status === 0) ? ' selected' : ''; ?>><?= lang('backend/categories.labels.unactive'); ?></option>
                </select>
                <div class="error_status app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- End categoria, categoria padre e stato -->

    <!-- Note Aggiuntive -->
    <div class="row">
        <div class="col-12">
            <div class="mb-2">
                <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/categories.labels.note'); ?></label>
                <textarea name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.note'), 'attr'); ?>"><?= esc($category->note); ?></textarea>
                <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- End Note Aggiuntive -->

</div>
