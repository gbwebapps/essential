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
                <strong>Sincronizzazione perfetta!</strong><br>
                I dati presenti nel CSV sono già identici a quelli del database (<?= $plan['skip'] ?> record analizzati). Nessuna importazione necessaria.
            </div>
        </div>
    <?php else: ?>
        
        <!-- STATO: AZIONI TROVATE -->
        <div class="alert alert-warning mb-3">
            <i class="fa-solid fa-database me-2"></i>
            <strong>Piano di Esecuzione:</strong> Trovati <strong><?= $plan['insert'] ?></strong> nuovi record da inserire e <strong><?= $plan['update'] ?></strong> record da aggiornare (<?= $plan['skip'] ?> record verranno ignorati perché già identici).
        </div>
    <?php endif; ?>

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