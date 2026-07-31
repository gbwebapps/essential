<!-- Sezione Generazione -->
<div class="card-body text-center py-4">
    <form id="backup-tools-form" autocomplete="off">
        <button type="button" class="btn btn-success btn-generate-backups" title="Crea e scarica un nuovo backup del database in formato ZIP">
            <i class="fa-solid fa-file-zipper me-2"></i><?= lang('backend/tools.buttons.generateBackups'); ?>
        </button>
    </form>
</div>

<!-- Sezione Lista Backup -->
<div class="card-body">
    <h5 class="mb-3 fw-bold"><i class="fa-solid fa-list me-2"></i><?= lang('backend/tools.labels.lastAvailableBackups'); ?></h5>
    
    <?php if (empty($backups)): ?>
        <div class="alert alert-danger mb-0 text-center">
            <?= lang('backend/tools.labels.noBackupsFound'); ?>
        </div>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach($backups as $backup): ?>
                
                <li class="list-group-item d-flex justify-content-between align-items-center py-3" data-filename="<?= $backup['filename']; ?>">
                    
                    <!-- Dettagli File -->
                    <div class="text-start">
                        <h6 class="mb-1 fw-bold"><?= $backup['filename']; ?></h6>
                        <small class="text-muted">
                            <span class="me-3"><i class="fa-regular fa-calendar me-1"></i><?= $backup['date']; ?></span>
                            <span><i class="fa-solid fa-hard-drive me-1"></i><?= sprintf(lang('backend/tools.labels.totalSpace'), $backup['size']); ?></span> 
                        </small>
                    </div>

                    <!-- Azioni: Scarica / Elimina -->
                    <div>
                        <button type="button" class="btn btn-sm btn-primary me-2 btn-download-backups" data-filename="<?= $backup['filename']; ?>">
                            <i class="fa-solid fa-download me-1"></i><?= lang('backend/tools.buttons.download'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger ms-2 btn-delete-backups" data-filename="<?= $backup['filename']; ?>" data-message="<?= lang('backend/tools.messages.areYouSureToDeleteBackups'); ?>">
                            <i class="fa-solid fa-trash me-1"></i><?= lang('backend/tools.buttons.delete'); ?>
                        </button>
                    </div>

                </li>

            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>