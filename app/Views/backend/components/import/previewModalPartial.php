<!-- Form per la conferma finale -->
<form id="importForm">
    
    <input type="hidden" name="entity" value="<?= esc($entity); ?>">
    <input type="hidden" name="tempFile" value="<?= esc($tempFile); ?>">
    <input type="hidden" name="step" value="confirm">

    <?php if ($plan['insert'] === 0 && $plan['update'] === 0): ?>

        <!-- STATO: NESSUNA AZIONE NECESSARIA -->
        <div class="alert alert-success d-flex align-items-center mb-3">
            <i class="fa-solid fa-check-circle fa-2x me-3"></i>
            <div>
                <strong><?= lang('backend/components/import.labels.perfectSync'); ?></strong><br>
                <?= sprintf(lang('backend/components/import.labels.syncNoChanges'), $plan['skip']); ?>
            </div>
        </div>
    <?php else: ?>
        
        <!-- STATO: AZIONI TROVATE -->
        <div class="alert alert-warning mb-3">
            <i class="fa-solid fa-database me-2"></i>
            <?= sprintf(lang('backend/components/import.labels.executionPlan'), $plan['insert'], $plan['update'], $plan['skip']); ?>
        </div>
    <?php endif; ?>

    <!-- CONTENITORE CON SCROLL VERTICALE -->
    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-sm table-striped table-bordered align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th class="sticky-top bg-dark text-white"><?= esc($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty($rows)): ?>
                    <?php foreach ($rows as $rowItem): ?>
                        <?php 
                            /* Estrazione dei dati dal nuovo array strutturato */
                            $record = $rowItem['record'];
                            $action = $rowItem['action'];
                            $changed = $rowItem['changed'];
                            
                            /* Evidenzia l'intera riga se è un inserimento nuovo */
                            $rowClass = ($action === 'insert') ? 'table-success' : '';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <?php foreach ($headers as $header): ?>
                                <?php 
                                    $cellValue = $record[$header] ?? '';
                                    
                                    /* Verifica se la colonna corrente rientra tra quelle modificate */
                                    $isChanged = in_array($header, $changed, true);
                                    
                                    /* Evidenzia la singola cella se è stata modificata */
                                    $cellClass = $isChanged ? 'table-warning fw-bold' : '';
                                ?>
                                <td class="<?= $cellClass ?>" <?= $isChanged ? 'title="Valore modificato"' : '' ?>>
                                    <?= esc((string)$cellValue); ?>
                                </td>
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

    <?php 
        /* Calcolo per visualizzare l'avviso di record eccedenti l'anteprima */
        $totalToProcess = $plan['insert'] + $plan['update'];
        $previewCount = count($rows);
        
        if ($totalToProcess > $previewCount): 
    ?>
        <div class="text-center text-muted small mt-2">
            <i class="fa-solid fa-info-circle me-1"></i>
            <?= lkang('backend/components/import.messages.previewNotice'); ?>
        </div>
    <?php endif; ?>

</form>