<!-- Form per la conferma finale -->
<form id="importForm">
    
    <input type="hidden" name="entity" value="<?= esc($entity); ?>">
    <input type="hidden" name="tempFile" value="<?= esc($tempFile); ?>">
    <input type="hidden" name="step" value="confirm">

    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info me-2"></i>
        <?= lang('backend/components/import.messages.previewInfo'); ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?= esc($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= esc($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($headers); ?>" class="text-center text-muted p-3">
                            <?= lang('backend/components/import.messages.noDataToPreview'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</form>