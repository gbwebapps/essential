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
    
    <form id="general-settings" autocomplete="off" novalidate>
        <div class="row">
            <div class="col-12">
            
                <!-- Sezione 1: Sicurezza e Tentativi di Accesso -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-globe me-2"></i><?= lang('backend/settings.labels.locale'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="general_timezone" class="form-label fw-semibold mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= lang('backend/settings.labels.timezone'); ?>
                        </label>
                        <select name="timezone" id="timezone" class="form-select">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($tz === esc($generalSettings['timezone']) ? 'selected' : ''); ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_timezone text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="general_language" class="form-label fw-semibold mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= lang('backend/settings.labels.language'); ?>
                        </label>
                        <select name="language" id="language" class="form-select">
                            <?php foreach ($languages as $code => $label): ?>
                                <option value="<?= $code ?>" <?= ($code === esc($generalSettings['language']) ? 'selected' : ''); ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_language text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div> 

                    <div class="col-md-4 mb-3">
                        <label for="dateFormat" class="form-label fw-semibold mb-1"> 
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= lang('backend/settings.labels.dateFormat') ?>
                        </label>
                        <select name="dateFormat" id="dateFormat" class="form-select">
                            <?php foreach ($dateFormats as $format => $label): ?>
                                <option value="<?= $format ?>" <?= ($format === esc($generalSettings['dateFormat'])) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_dateFormat text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button type="button" class="btn btn-danger btn-sm btn-delete-general" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/settings.buttons.restoreData'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-warning btn-sm text-dark btn-refresh-general" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= lang('backend/settings.buttons.refreshData'); ?>
                            </button>
                            
                            <button type="submit" class="btn btn-success btn-sm text-white btn-save-general">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/settings.buttons.sendData'); ?>
                            </button>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
