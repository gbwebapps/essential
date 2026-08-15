<div class="card-body">
    <form id="cleanSpace-tools-form" autocomplete="off">
        <div class="row">
            <div class="col-12">
                
                <ul class="list-group">
                    <?php foreach($folders as $folder): ?>
                        
                        <li class="list-group-item py-3" data-folder="<?= $folder['name']; ?>">
                            <div class="row align-items-center">
                                
                                <!-- Blocco Sinistra: Nome Cartella -->
                                <div class="col-4 text-start">
                                    <h6 class="fw-bold mb-0">
                                        <i class="fa-regular fa-folder-open me-2"></i><?= $folder['name']; ?>
                                    </h6>
                                </div>
                                
                                <!-- Blocco Centrale: Conteggio File -->
                                <div class="col-4 text-start">
                                    <div class="text-muted"> 
                                        <!-- Assicurati di avere le traduzioni o sostituisci con testo fisso -->
                                        <span class="me-3"><i class="fa-solid fa-file-lines me-2"></i>
                                        	<?php $def = ($folder['count'] === 1) ? 'file' : 'files'; ?>
                                        	<?= $folder['count']; ?> <?= lang('backend/tools.labels.' . $def); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Blocco Destra: Pulsante Pulisci -->
                                <div class="col-4 text-end">
                                    <!-- Disabilitiamo il pulsante se la cartella è già vuota -->
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