<div class="card-body">

    <!-- Indicatore della sorgente dati -->
    <div class="ps-2 py-1 app-bg-surface fw-bold small mb-3 border text-center rounded-2">
        <?php if (isset($isFromDatabase) && $isFromDatabase): ?>
            <span class="app-text-accent">
                <i class="fa-solid fa-database me-1"></i> <?= ui_text('backend/settings.messages.sourceDatabase', [], false); ?>
            </span>
        <?php else: ?>
            <span class="app-text">
                <i class="fa-solid fa-file-code me-1"></i> <?= ui_text('backend/settings.messages.sourceConfigFile', [], false); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <form id="general-settings" autocomplete="off" novalidate data-unsaved-check>
        <div class="row">
            <div class="col-12">
            
                <!-- Sezione 1: Sicurezza e Tentativi di Accesso -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-globe me-2"></i><?= ui_text('backend/settings.labels.locale', [], false); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="general_timezone" class="form-label  mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= ui_text('backend/settings.labels.timezone', [], false); ?>
                        </label>
                        <select name="timezone" id="timezone" class="form-select">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($tz === esc($generalSettings['timezone']) ? 'selected' : ''); ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_timezone app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="language" class="form-label  mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= ui_text('backend/settings.labels.language', [], false); ?>
                        </label>
                        <select name="language" id="language" class="form-select">
                            <?php foreach ($languages as $code => $label): ?>
                                <option <?= ui_value_binding($label); ?> value="<?= $code ?>" <?= ($code === esc($generalSettings['language']) ? 'selected' : ''); ?>>
                                    <?= $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_language app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div> 

                    <div class="col-md-4 mb-3">
                        <label for="dateFormat" class="form-label  mb-1"> 
                            <i class="fa-solid fa-circle-arrow-down"></i> 
                            <?= ui_text('backend/settings.labels.dateFormat', [], false); ?>
                        </label>
                        <select name="dateFormat" id="dateFormat" class="form-select">
                            <?php foreach ($dateFormats as $format => $label): ?>
                                <option <?= ui_value_binding($label); ?> value="<?= $format ?>" <?= ($format === esc($generalSettings['dateFormat'])) ? 'selected' : '' ?>>
                                    <?= $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_dateFormat app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="theme" class="form-label mb-1">
                            <i class="fa-solid fa-circle-arrow-down"></i>
                            <?= ui_text('backend/settings.labels.theme', [], false); ?>
                        </label>
                        <select name="theme" id="theme" class="form-select">
                            <?php foreach ($themes as $code => $label): ?>
                                <option <?= ui_value_binding($label); ?> value="<?= esc($code); ?>" <?= ($code === esc($generalSettings['theme'])) ? 'selected' : ''; ?>>
                                    <?= ui_value($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error_theme app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button <?= ui_binding('backend/settings.messages.areYouSureDeleteSettings', 'data-message', [], false); ?> type="button" class="btn app-btn-danger btn-sm btn-delete-general" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= ui_text('backend/settings.buttons.restoreData', [], false); ?>
                            </button>
                            
                            <button <?= ui_binding('backend/settings.messages.areYouSureRefreshSettings', 'data-message', [], false); ?> type="button" class="btn app-btn-warning btn-sm btn-refresh-general" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= ui_text('backend/settings.buttons.refreshData', [], false); ?>
                            </button>
                            
                            <button type="submit" class="btn app-btn-success btn-sm btn-save-general">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= ui_text('backend/settings.buttons.sendData', [], false); ?>
                            </button>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
