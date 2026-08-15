<!-- Modale nascosto per importazione da csv -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title"><?= lang('backend/components/import.panels.main'); ?></h5>
            </div>

            <div class="modal-body">
                
                <form id="importForm" enctype="multipart/form-data">
                    
                    <!-- Campo nascosto per mantenere il riferimento all'entità -->
                    <input type="hidden" name="entity" id="importEntity" value="<?= esc($entity); ?>">

                    <!-- Griglia struttura tabella -->
                    <div class="table-responsive mb-4">

                        <!-- Link per il download del template CSV -->
                        <div class="d-flex justify-content-center mb-3 lead">
                            <a href="<?= base_url('backend/import/download/' . esc($entity)); ?>">
                                <i class="fa-solid fa-download"></i> <?= lang('backend/components/import.links.downloadTemplate'); ?>
                            </a>
                        </div>

                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= lang('backend/components/import.labels.columnName'); ?></th>
                                    <th><?= lang('backend/components/import.labels.dataType'); ?></th>
                                    <th><?= lang('backend/components/import.labels.maxLength'); ?></th>
                                    <th class="text-center"><?= lang('backend/components/import.labels.keysAndIndexes'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty($structure)): ?>
                                    <?php foreach ($structure as $field): ?>
                                        <tr>
                                            <td class="fw-bold"><?= esc($field['name']); ?></td>
                                            <td><span><?= esc(strtoupper($field['type'])); ?></span></td>
                                            <td><?= esc($field['max_length'] ?? '-'); ?></td>
                                            <td class="text-center">
                                                <?php if ($field['primary_key'] === 1): ?>
                                                    <i class="fa-solid fa-key text-warning" title="Primary Key"></i>
                                                <?php elseif ($field['is_index']): ?>
                                                    <i class="fa-solid fa-key text-secondary" title="Index"></i>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted"><?= lang('backend/components/import.messages.noStructure'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Input per caricamento file CSV -->
                    <div class="mb-3">
                        <label for="csvFile" class="form-label fw-bold"><?= lang('backend/components/import.labels.uploadCsv'); ?></label>
                        <input class="form-control form-control-sm" type="file" id="csvFile" name="csvFile">
                    </div>

                </form>

            </div>

            <div class="modal-footer border-0">
                <button type="button" id="btnCancelImport" class="btn btn-danger" data-bs-dismiss="modal"><?= lang('backend/components/import.buttons.undo'); ?></button>
                <button type="submit" class="btn btn-success" form="importForm"><?= lang('backend/components/import.buttons.import'); ?></button>
            </div>

        </div>
    </div>
</div>