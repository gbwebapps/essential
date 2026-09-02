<div class="card-body">

    <!-- Indicatore della sorgente dati -->
    <div class="ps-2 py-1 bg-light fw-bold small mb-3 border text-center rounded-2">
        <?php if (isset($isFromDatabase) && $isFromDatabase): ?>
            <span class="text-primary">
                <i class="fa-solid fa-database me-1"></i> <?= lang('backend/settings.messages.sourceDatabase'); ?>
            </span>
        <?php else: ?>
            <span class="text-secondary">
                <i class="fa-solid fa-file-code me-1"></i> <?= lang('backend/settings.messages.sourceConfigFile'); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <form id="upload-settings" autocomplete="off" novalidate>
        <div class="row">
            <div class="col-12">
            
                <!-- Sezione 1: Regole di Caricamento Immagini -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-images me-2"></i><?= lang('backend/settings.labels.uploadImageRules'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="upload_renameImages" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.renameImages'); ?></label>
                        <select id="upload_renameImages" name="renameImages" class="form-select shadow-none">
                            <option value="0" <?= (int) $uploadSettings['renameImages'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $uploadSettings['renameImages'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_renameImages text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="upload_overwriteImages" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.overwriteImages'); ?></label>
                        <select id="upload_overwriteImages" name="overwriteImages" class="form-select shadow-none">
                            <option value="0" <?= (int) $uploadSettings['overwriteImages'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $uploadSettings['overwriteImages'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_overwriteImages text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Limiti di Peso File -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-weight-hanging me-2"></i><?= lang('backend/settings.labels.uploadWeightRules'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label for="upload_maxFileSize" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.maxFileSize'); ?></label>
                        <input type="number" id="upload_maxFileSize" name="maxFileSize" class="form-control shadow-none" min="1" value="<?= esc($uploadSettings['maxFileSize']); ?>" placeholder="<?= lang('backend/settings.placeholders.maxFileSize'); ?>">
                        <div class="error_maxFileSize text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label for="upload_allowedExtensions" class="form-label fw-semibold mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.allowedExtensions'); ?>
                        </label>
                        
                        <!-- Trasformiamo l'input in una select multipla. L'attributo name termina con [] per inviare un array a PHP -->
                        <select id="upload_allowedExtensions" name="allowedExtensions[]" class="tom-select shadow-none" multiple>
                            <?php
                            /* 
                               Prendiamo la stringa salvata a database (es. "png|jpg|webp") 
                               e la convertiamo in un array per verificare quali elementi selezionare di default 
                            */
                            $currentExtensions = ! empty($uploadSettings['allowedExtensions']) ? explode('|', $uploadSettings['allowedExtensions']) : [];
                                
                            /* Elenco delle estensioni comuni che vogliamo proporre nella selezione */
                            $availableExtensions = ['png', 'jpg', 'jpeg', 'webp', 'avif', 'gif', 'svg'];
                            ?>

                            <?php foreach ($availableExtensions as $ext) : ?>
                                <option value="<?= esc($ext); ?>" <?= in_array($ext, $currentExtensions, true) ? 'selected' : ''; ?>>
                                    <?= esc(strtoupper($ext)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="error_allowedExtensions text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Dimensioni larghezza e altezza massima -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-expand me-2"></i><?= lang('backend/settings.labels.dimensionsOriginal'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_maxImageX" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.maxImageX'); ?></label>
                        <input type="number" id="upload_maxImageX" name="maxImageX" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['maxImageX']); ?>" placeholder="<?= lang('backend/settings.placeholders.maxImageX'); ?>">
                        <div class="error_maxImageX text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_maxImageY" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.maxImageY'); ?></label>
                        <input type="number" id="upload_maxImageY" name="maxImageY" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['maxImageY']); ?>" placeholder="<?= lang('backend/settings.placeholders.maxImageY'); ?>">
                        <div class="error_maxImageY text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Sezione 2: Dimensioni Anteprime Medie -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-maximize me-2"></i><?= lang('backend/settings.labels.dimensionsMedium'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_resizeMediumX" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resizeMediumX'); ?></label>
                        <input type="number" id="upload_resizeMediumX" name="resizeMediumX" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['resizeMediumX']); ?>" placeholder="<?= lang('backend/settings.placeholders.resizeMediumX'); ?>">
                        <div class="error_resizeMediumX text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_resizeMediumY" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resizeMediumY'); ?></label>
                        <input type="number" id="upload_resizeMediumY" name="resizeMediumY" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['resizeMediumY']); ?>" placeholder="<?= lang('backend/settings.placeholders.resizeMediumY'); ?>">
                        <div class="error_resizeMediumY text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Sezione 3: Dimensioni Anteprime Piccole -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-minimize me-2"></i><?= lang('backend/settings.labels.dimensionsSmall'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_resizeSmallX" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resizeSmallX'); ?></label>
                        <input type="number" id="upload_resizeSmallX" name="resizeSmallX" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['resizeSmallX']); ?>" placeholder="<?= lang('backend/settings.placeholders.resizeSmallX'); ?>">
                        <div class="error_resizeSmallX text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="upload_resizeSmallY" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resizeSmallY'); ?></label>
                        <input type="number" id="upload_resizeSmallY" name="resizeSmallY" class="form-control shadow-none" min="0" value="<?= esc($uploadSettings['resizeSmallY']); ?>" placeholder="<?= lang('backend/settings.placeholders.resizeSmallY'); ?>">
                        <div class="error_resizeSmallY text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Pulsanti di Controllo -->
                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button type="button" class="btn btn-danger btn-sm btn-delete-upload" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/settings.buttons.restoreData'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-warning btn-sm text-dark btn-refresh-upload" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= lang('backend/settings.buttons.refreshData'); ?>
                            </button>
                            
                            <button type="submit" class="btn btn-success btn-sm text-white btn-save-upload">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/settings.buttons.sendData'); ?>
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>