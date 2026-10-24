<!-- Modale nascosto per importazione da csv -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow">

            <div class="modal-header border-0 flex-wrap gap-2">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-import"></i> <?= isset($modalTitle) ? esc($modalTitle) : sprintf(lang('backend/components/import.panels.main'), esc($entity)); ?>
                </h5>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" id="btnCancelImportTop" class="btn app-btn-danger btn-sm" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> <span data-import-close-label data-close="<?= esc(lang('backend/components/import.buttons.close'), 'attr'); ?>" data-cancel="<?= esc(lang('backend/components/import.buttons.cancel'), 'attr'); ?>"><?= lang('backend/components/import.buttons.close'); ?></span>
                    </button>
                    <button type="submit" class="btn app-btn-success btn-sm" form="importForm">
                        <i class="fa-solid fa-file-import"></i> <?= lang('backend/components/import.buttons.import'); ?>
                    </button>
                </div>
            </div>

            <div class="modal-body pt-0">

                <div id="import-alert-container"></div>

                <div id="import-content-area">
                
                    <?php if (isset($initialOutput)): ?>
                        <?= $initialOutput; ?>
                    <?php else: ?>
                    <form id="importForm" enctype="multipart/form-data">
                        
                        <!-- Campo nascosto per mantenere il riferimento all'entità nella sola fase di upload -->
                        <input type="hidden" name="entity" id="importEntity" value="<?= esc($entity); ?>">

                        <!-- Griglia struttura tabella -->
                        <div class="<?= ! empty($compactStructures) ? 'mb-3' : 'table-responsive mb-4'; ?>">

                            <!-- Link per il download del template CSV coerente con il contesto di importazione -->
                            <div class="d-flex justify-content-center mb-3 lead">
                                <a href="<?= esc($downloadUrl, 'attr'); ?>">
                                    <i class="fa-solid fa-download"></i> <?= isset($templateLabel) ? esc($templateLabel) : sprintf(lang('backend/components/import.links.downloadTemplate'), esc($entity)); ?>
                                </a>
                            </div>

                            <?php if ( ! empty($compactStructures)): ?>
                            <div class="row g-3">
                            <?php endif; ?>
                            <?php foreach (($structures ?? [$entity => $structure]) as $tableName => $tableStructure): ?>
                            <?php if ( ! empty($compactStructures)): ?>
                            <div class="col-12<?= in_array($tableName, $fullWidthStructures ?? [], true) ? '' : ' col-lg-6'; ?>">
                            <?php endif; ?>
                            <?php if (isset($structures)): ?>
                                <h6 class="fw-bold <?= ! empty($compactStructures) ? 'mb-2' : 'mt-3'; ?>"><i class="fa-solid fa-table me-2"></i><?= esc($tableName); ?></h6>
                            <?php endif; ?>
                            <?php if ( ! empty($compactStructures)): ?>
                            <div class="table-responsive">
                            <?php endif; ?>
                            <table class="table table-sm table-bordered align-middle<?= ! empty($compactStructures) ? ' small mb-0' : ''; ?>">
                                <thead class="app-table-header">
                                    <tr>
                                        <th><?= lang('backend/components/import.labels.columnName'); ?></th>
                                        <th><?= lang('backend/components/import.labels.dataType'); ?></th>
                                        <th><?= lang('backend/components/import.labels.maxLength'); ?></th>
                                        <th class="text-center"><?= lang('backend/components/import.labels.keysAndIndexes'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ( ! empty($tableStructure)): ?>
                                        <?php foreach ($tableStructure as $field): ?>
                                            <tr>
                                                <td class="fw-bold"><?= esc($field['name']); ?></td>
                                                <td><span><?= esc(strtoupper($field['type'])); ?></span></td>
                                                <td><?= esc($field['max_length'] ?? '-'); ?></td>
                                                <td class="text-center">
                                                    <?php if ($field['primary_key'] === 1): ?>
                                                        <i class="fa-solid fa-key app-text-warning" title="Primary Key"></i>
                                                    <?php elseif ($field['is_index']): ?>
                                                        <i class="fa-solid fa-key app-text-muted" title="Index"></i>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center app-text-muted"><?= lang('backend/components/import.messages.noStructure'); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if ( ! empty($compactStructures)): ?>
                            </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ( ! empty($compactStructures)): ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Input per caricamento file CSV -->
                        <div class="mb-3">
                            <?php if (isset($uploadHelp)): ?>
                                <p class="small app-text-muted"><?= esc($uploadHelp); ?></p>
                            <?php endif; ?>
                            <label for="csvFile" class="form-label fw-bold"><?= esc($uploadLabel ?? lang('backend/components/import.labels.uploadCsv')); ?></label>
                            <input class="form-control form-control-sm" type="file" id="csvFile" name="<?= esc($uploadName ?? 'csvFile', 'attr'); ?>" accept="<?= esc($uploadAccept ?? '.csv,.txt,text/csv,text/plain', 'attr'); ?>">
                        </div>

                    </form>
                    <?php endif; ?>

                </div>

            </div>

            <div class="modal-footer border-0">
                <button type="button" id="btnCancelImport" class="btn app-btn-danger btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i> <span data-import-close-label data-close="<?= esc(lang('backend/components/import.buttons.close'), 'attr'); ?>" data-cancel="<?= esc(lang('backend/components/import.buttons.cancel'), 'attr'); ?>"><?= lang('backend/components/import.buttons.close'); ?></span>
                </button>
                <button type="submit" class="btn app-btn-success btn-sm" form="importForm">
                    <i class="fa-solid fa-file-import"></i> <?= lang('backend/components/import.buttons.import'); ?>
                </button>
            </div>

        </div>
    </div>
</div>
