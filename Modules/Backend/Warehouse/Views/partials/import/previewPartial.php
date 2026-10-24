<!-- La conferma segue lo stesso contratto del componente Import. -->
<form id="importForm">
    <input type="hidden" name="importId" value="<?= esc($importId ?? '', 'attr'); ?>">
    <input type="hidden" name="step" value="confirm">

    <?php if ($plan['insert'] === 0 && $plan['update'] === 0): ?>
        <div class="alert app-alert-success d-flex align-items-center mb-3">
            <i class="fa-solid fa-check-circle fa-2x me-3"></i>
            <div>
                <strong><?= lang('backend/components/import.labels.perfectSync'); ?></strong><br>
                <?= sprintf(lang('backend/warehouse.messages.syncNoChanges'), $plan['skip']); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert app-alert-warning mb-3">
            <i class="fa-solid fa-database me-2"></i>
            <?= sprintf(lang('backend/components/import.labels.executionPlan'), $plan['insert'], $plan['update'], $plan['skip']); ?>
        </div>
    <?php endif; ?>

    <?php foreach ($sheets as $tableName => $sheet): ?>
        <h6 class="fw-bold mt-4"><i class="fa-solid fa-table me-2"></i><?= esc($tableName); ?></h6>
        <?= view('backend/components/import/previewModalPartial', $sheet + ['embedded' => true, 'syncNoChanges' => lang('backend/warehouse.messages.syncNoChanges')]); ?>
    <?php endforeach; ?>

    <p class="small app-text-muted mt-3 mb-0"><?= esc(lang('backend/warehouse.descriptions.reExport')); ?></p>
</form>
