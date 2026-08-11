<!-- Modale nascosto per la selezione delle colonne di esportazione -->
<div class="modal fade" id="exportColumnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">

            <?php if ( ! empty($columns)): ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title"><?= lang('backend/components/export.panels.main'); ?></h5>
                </div>
            <?php endif; ?>

            <div class="modal-body">

                <?php if ( ! empty($columns)): ?>
                    <form id="exportColumnsForm">

                        <?php foreach ($columns as $column): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="columns[]" value="<?= esc($column); ?>" id="export_col_<?= esc($column); ?>" checked>
                                <label class="form-check-label" for="export_col_<?= esc($column); ?>">
                                    <?= esc($column); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <input type="hidden" name="entity" value="<?= $entity; ?>">
                    </form>

                    <div class="form-check form-switch my-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="selectAllExportColumns" checked>
                        <label class="form-check-label fw-bold" for="selectAllExportColumns">
                            <?= lang('backend/components/export.labels.selectAll'); ?>
                        </label>
                    </div>

                <?php else: ?>

                    <div class="text-center text-danger lead">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= lang('backend/components/export.labels.noColumns'); ?>
                    </div>

                <?php endif; ?>

            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?= lang('backend/components/export.buttons.undo'); ?></button>

                <?php if ( ! empty($columns)): ?>
                    <button type="submit" class="btn btn-success" form="exportColumnsForm"><?= lang('backend/components/export.buttons.export'); ?></button>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>