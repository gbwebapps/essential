<!-- Struttura principale del form di inserimento (Partial) -->

<!-- Form per aggiornamento albero categorie -->
<form id="getTree"></form>

<div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">

        <div class="card">

            <!-- Form identificato per la gestione AJAX -->
            <form id="categories-add" data-unsaved-check>

                <!-- General Data -->
                <div id="generalData">

                    <div class="card-header">
                        <h2 class="card-title text-center text-lg-start mb-0"><?= ui_text('backend/categories.panels.generalData', [], false); ?></h2>
                    </div>

                    <div class="card-body">

                        <!-- Categoria, categoria padre e stato -->
                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="category" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/categories.labels.category', [], false); ?></label>
                                    <input <?= ui_binding('backend/categories.placeholders.category', 'placeholder', [], true); ?> type="text" id="category" name="category" class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.category'), 'attr'); ?>">
                                    <!-- Contenitore dinamico per l'errore di validazione -->
                                    <div class="error_category app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="parent_id" class="form-label"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/categories.labels.parentCategory', [], false); ?></label>
                                    <select name="parent_id" class="form-select" id="parent_id">
                                        <option <?= ui_binding('backend/categories.labels.rootCategory', 'text', [], false); ?> value=""><?= lang('backend/categories.labels.rootCategory'); ?></option>
                                        <?php foreach($parentCategories as $parentCategory): ?>
                                            <option <?php if((int) $parentCategory->status === 0): ?><?= ui_binding('backend/categories.labels.suspendedRelation', 'text', [], true, '', ($parentCategory->path ?? $parentCategory->category) . ' — '); ?><?php endif; ?> value="<?= esc($parentCategory->id, 'attr'); ?>"<?= ((int) $parentCategory->status === 0) ? ' disabled' : ''; ?>><?= esc($parentCategory->path ?? $parentCategory->category); ?><?= ((int) $parentCategory->status === 0) ? ' — ' . esc(lang('backend/categories.labels.suspendedRelation')) : ''; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="error_parent_id app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <div class="mb-2">
                                    <label for="status" class="form-label">
                                        <i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/categories.labels.status', [], false); ?>
                                    </label>
                                    <select name="status" class="form-select" id="status">
                                        <option <?= ui_binding('backend/categories.labels.active', 'text', [], false); ?> value="1"><?= lang('backend/categories.labels.active'); ?></option>
                                        <option <?= ui_binding('backend/categories.labels.unactive', 'text', [], false); ?> value="0"><?= lang('backend/categories.labels.unactive'); ?></option>
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
                                    <label for="note"><i class="fa-solid fa-circle-arrow-down"></i><?= ui_text('backend/categories.labels.note', [], false); ?></label>
                                    <textarea <?= ui_binding('backend/categories.placeholders.note', 'placeholder', [], true); ?> name="note" id="note" rows="7" class="form-control" placeholder="<?= esc(lang('backend/categories.placeholders.note'), 'attr'); ?>"></textarea>
                                    <div class="error_note app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                        <!-- End Note Aggiuntive -->

                    </div>

                </div>
                <!-- End General Data -->

                <!-- Albero Categorie -->
                <?= view(\Modules\Backend\Categories\Config\Views::CATEGORY_TREE_PARTIAL, [
                    'categoryTreeDisplay' => 'section',
                    'categoryTree' => $categoryTree ?? [],
                    'selectedCategoryUuid' => $selectedCategoryUuid ?? null,
                    'selectedCategoryPath' => $selectedCategoryPath ?? null,
                    'categoryTreeContext' => 'add',
                ]); ?>
                <!-- End Albero Categorie -->

                <!-- Sezione: Upload e Preview Immagini Profilo -->
                <div id="uploadPreview">
                    <?= $this->include('backend/components/uploadPreview/uploadPreviewView'); ?>
                </div>
                <!-- End Sezione: Upload e Preview Immagini Profilo -->

            </form>

        </div>

    </div>
</div>

<?= view(\Modules\Backend\Categories\Config\Views::CATEGORY_TREE_PARTIAL, [
    'categoryTreeDisplay' => 'modal',
    'categoryTree' => $categoryTree ?? [],
    'selectedCategoryUuid' => $selectedCategoryUuid ?? null,
    'selectedCategoryPath' => $selectedCategoryPath ?? null,
]); ?>
