<div class="card-body">
    <form id="cleanSpace-tools-form" autocomplete="off">
        <div class="row">
            <div class="col-12">
                
                <ul class="list-group">
                    <?php foreach($folders as $folder): ?>
                                            
                        <li class="list-group-item py-3" data-folder="<?= $folder['name']; ?>">
                            <div class="row align-items-md-center">
                                
                                <!-- Blocco Sinistra: Nome Cartella -->
                                <div class="col-12 col-md-4 mb-2 mb-md-0 text-start">
                                    <div class="fw-bold">
                                        <i class="fa-regular fa-folder-open me-2"></i><?= $folder['name']; ?>
                                    </div>
                                    <!-- Linea visibile solo su mobile -->
                                    <hr class="d-md-none my-2 text-secondary">
                                </div>
                                
                                <!-- Blocco Centrale: Conteggio File -->
                                <div class="col-12 col-md-4 mb-3 mb-md-0 text-start">
                                    <div class="text-muted"> 
                                        <span><i class="fa-solid fa-file-lines me-2"></i>
                                            <?php $def = ($folder['count'] === 1) ? 'file' : 'files'; ?>
                                            <?= $folder['count']; ?> <?= lang('backend/tools.labels.' . $def); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Blocco Destra: Pulsante Pulisci -->
                                <div class="col-12 col-md-4 text-start text-md-end d-grid d-md-block">
                                    <button type="button" class="btn btn-sm btn-danger btn-clean-folder" 
                                            data-folder="<?= $folder['name']; ?>"
                                            data-confirm="<?= sprintf(lang('backend/tools.messages.confirmCleanFolder'), $folder['name']); ?>"
                                            <?= $folder['count'] == 0 ? 'disabled' : ''; ?>>
                                        <i class="fa-solid fa-trash-can me-2"></i><?= lang('backend/tools.buttons.cleanFolder'); ?>
                                    </button>
                                </div>
                                
                            </div>
                        </li>
                        
                    <?php endforeach; ?>
                </ul>

            </div>
        </div>
    </form>
</div>