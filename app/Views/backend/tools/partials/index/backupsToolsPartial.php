<!-- Sezione Generazione -->
<div class="card-body text-center pb-3">
    <form id="backup-tools-form" autocomplete="off">
        <button type="button" class="btn btn-success btn-generate-backups btn-sm" title="Crea e scarica un nuovo backup del database in formato ZIP">
            <i class="fa-solid fa-file-zipper me-2"></i><?= lang('backend/tools.buttons.generateBackups'); ?>
        </button>
    </form>
</div>

<!-- Sezione Lista Backup -->
<div class="card-body">
    <h5 class="mb-3"><i class="fa-solid fa-circle-arrow-down me-2"></i><?= lang('backend/tools.labels.lastAvailableBackups'); ?></h5>
    
    <?php if (empty($backups)): ?>
        <div class="text-center text-dark py-3 fw-bold">
            <?= lang('backend/tools.labels.noBackupsFound'); ?>
        </div>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach($backups as $backup): ?>
                
                <!-- Rimossa la classe d-flex globale, gestiamo tutto con la Row interna -->
                <li class="list-group-item py-3" data-filename="<?= $backup['filename']; ?>">
                    
                    <div class="row align-items-center text-start">
                        
                        <!-- Dettagli File (Tutta l'ampiezza su mobile, 8/12 su tablet, 9/12 su desktop) -->
                        <div class="col-12 col-md-8 col-lg-9 mb-3 mb-md-0">
                            <h6 class="mb-1 fw-bold"><?= $backup['filename']; ?></h6>
                            <small class="text-muted">
                                <span class="me-3"><i class="fa-regular fa-calendar me-1"></i><?= $backup['date']; ?></span>
                                <span><i class="fa-solid fa-hard-drive me-1"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), $backup['size']); ?></span> 
                            </small>
                        </div>

                        <!-- Azioni: Scarica / Elimina (Rinchiuse nella colonna rimanente) -->
                        <div class="col-12 col-md-4 col-lg-3">
                            <div class="d-flex justify-content-center justify-content-md-end gap-2">
                                <!-- flex-fill allarga al 50% su mobile, flex-md-grow-0 annulla l'espansione da tablet in poi -->
                                <button type="button" class="btn btn-sm btn-primary flex-fill flex-md-grow-0 btn-download-backups" data-filename="<?= $backup['filename']; ?>">
                                    <i class="fa-solid fa-download me-1"></i><?= lang('backend/tools.buttons.download'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger flex-fill flex-md-grow-0 btn-delete-backups" data-filename="<?= $backup['filename']; ?>" data-message="<?= lang('backend/tools.messages.areYouSureToDeleteBackups'); ?>">
                                    <i class="fa-solid fa-trash me-1"></i><?= lang('backend/tools.buttons.delete'); ?>
                                </button>
                            </div>
                        </div>

                    </div>
                </li>

            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>