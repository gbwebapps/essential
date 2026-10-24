<!-- Form per la conferma finale -->
<?php if (empty($embedded)): ?>
<form id="importForm">
    
    <input type="hidden" name="importId" value="<?= esc($importId ?? ''); ?>">
    <input type="hidden" name="step" value="confirm">
<?php endif; ?>

    <?php if ($plan['insert'] === 0 && $plan['update'] === 0): ?>

        <!-- STATO: NESSUNA AZIONE NECESSARIA -->
        <div class="alert app-alert-success d-flex align-items-center mb-3">
            <i class="fa-solid fa-check-circle fa-2x me-3"></i>
            <div>
                <strong><?= lang('backend/components/import.labels.perfectSync'); ?></strong><br>
                <?= sprintf($syncNoChanges ?? lang('backend/components/import.labels.syncNoChanges'), $plan['skip']); ?>
            </div>
        </div>
    <?php else: ?>
        
        <!-- STATO: AZIONI TROVATE -->
        <div class="alert app-alert-warning mb-3">
            <i class="fa-solid fa-database me-2"></i>
            <?= sprintf(lang('backend/components/import.labels.executionPlan'), $plan['insert'], $plan['update'], $plan['skip']); ?>
        </div>
    <?php endif; ?>

    <!-- CONTENITORE CON SCROLL VERTICALE -->
    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-striped table-bordered align-middle mb-0">
            <thead class="app-table-inverse">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th class="sticky-top app-bg-inverse app-text-inverse"><?= esc($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty($rows)): ?>
                    <?php foreach ($rows as $rowItem): ?>
                        <?php 
                            $record = $rowItem['record'];
                            $action = $rowItem['action'];
                            $changed = $rowItem['changed'];
                            $rowClass = ($action === 'insert') ? 'app-table-success' : '';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <?php foreach ($headers as $header): ?>
                                <?php 
                                    $cellValue = $record[$header] ?? '';
                                    $isChanged = in_array($header, $changed, true);
                                    $cellClass = $isChanged ? 'app-table-warning fw-bold' : '';
                                ?>
                                <td class="<?= $cellClass ?>" <?= $isChanged ? 'title="Valore modificato"' : '' ?>>
                                    <?= esc((string) $cellValue); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($headers); ?>" class="text-center app-text-muted p-3">
                            <?= lang('backend/components/import.messages.noDataToPreview'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php 
        $totalToProcess = $plan['insert'] + $plan['update'];
        $previewCount = count($rows);
        
        if ($totalToProcess > $previewCount): 
    ?>
        <div class="text-center app-text-muted small mt-2">
            <i class="fa-solid fa-info-circle me-1"></i>
            <?= sprintf(lang('backend/components/import.labels.previewNotice'), $previewCount, $totalToProcess - $previewCount); ?>
        </div>
    <?php endif; ?>

<?php if (empty($embedded)): ?>
</form>
<?php endif; ?>
