<div class="card-body">
    <form id="auth-settings" autocomplete="off" novalidate>
        <div class="row">
            <div class="col-12">
            
                <!-- Sezione 1: Sicurezza e Tentativi di Accesso -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-shield-halved me-2"></i><?= lang('backend/settings.labels.security'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attempts" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attempts'); ?></label>
                        <select id="auth_attempts" name="attempts" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['attempts'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['attempts'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_attempts text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attemptsLimit" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attemptsLimit'); ?></label>
                        <input type="number" id="auth_attemptsLimit" name="attemptsLimit" class="form-control shadow-none" min="1" max="20" value="<?= esc($authSettings['attemptsLimit']); ?>" placeholder="<?= lang('backend/settings.placeholders.attemptsLimit'); ?>">
                        <div class="error_attemptsLimit text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attemptsInterval" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attemptsInterval'); ?></label>
                        <input type="number" id="auth_attemptsInterval" name="attemptsInterval" class="form-control shadow-none" min="10" value="<?= esc($authSettings['attemptsInterval']); ?>" placeholder="<?= lang('backend/settings.placeholders.attemptsInterval'); ?>">
                        <div class="error_attemptsInterval text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>  
                </div>

                <!-- Sezione 2: Autenticazione a Due Fattori (2FA) -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-key me-2"></i><?= lang('backend/settings.labels.twoFactor'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactor" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactor'); ?></label>
                        <select id="auth_twoFactor" name="twoFactor" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['twoFactor'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['twoFactor'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_twoFactor text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorLimit" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorLimit'); ?></label>
                        <input type="number" id="auth_twoFactorLimit" name="twoFactorLimit" class="form-control shadow-none" min="1" max="10" value="<?= esc($authSettings['twoFactorLimit']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorLimit'); ?>">
                        <div class="error_twoFactorLimit text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorTime" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorTime'); ?></label>
                        <input type="number" id="auth_twoFactorTime" name="twoFactorTime" class="form-control shadow-none" min="10" value="<?= esc($authSettings['twoFactorTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorTime'); ?>">
                        <div class="error_twoFactorTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorIssuer" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorIssuer'); ?></label>
                        <input type="text" id="auth_twoFactorIssuer" name="twoFactorIssuer" class="form-control shadow-none" value="<?= esc($authSettings['twoFactorIssuer']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorIssuer'); ?>">
                        <div class="error_twoFactorIssuer text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorDigits" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorDigits'); ?></label>
                        <select id="auth_twoFactorDigits" name="twoFactorDigits" class="form-select shadow-none">
                            <option value="6" <?= $authSettings['twoFactorDigits'] == 6 ? 'selected' : ''; ?>>6</option>
                            <option value="8" <?= $authSettings['twoFactorDigits'] == 8 ? 'selected' : ''; ?>>8</option>
                        </select>
                        <div class="error_twoFactorDigits text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorWindow" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorWindow'); ?></label>
                        <input type="number" id="auth_twoFactorWindow" name="twoFactorWindow" class="form-control shadow-none" min="0" max="2" value="<?= esc($authSettings['twoFactorWindow']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorWindow'); ?>">
                        <div class="error_twoFactorWindow text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="auth_twoFactorEmailExpiry" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorEmailExpiry'); ?></label>
                        <input type="number" id="auth_twoFactorEmailExpiry" name="twoFactorEmailExpiry" class="form-control shadow-none" min="10" value="<?= esc($authSettings['twoFactorEmailExpiry']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorEmailExpiry'); ?>">
                        <div class="error_twoFactorEmailExpiry text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="auth_twoFactorEmailFrom" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorEmailFrom'); ?></label>
                        <input type="email" id="auth_twoFactorEmailFrom" name="twoFactorEmailFrom" class="form-control shadow-none" value="<?= esc($authSettings['twoFactorEmailFrom']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorEmailFrom'); ?>">
                        <div class="error_twoFactorEmailFrom text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Sezione 3: Sessioni e Persistenza -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 bg-light pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-clock me-2"></i><?= lang('backend/settings.labels.sessions'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_sessionTime" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.sessionTime'); ?></label>
                        <input type="number" id="auth_sessionTime" name="sessionTime" class="form-control shadow-none" min="60" value="<?= esc($authSettings['sessionTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.sessionTime'); ?>">
                        <div class="error_sessionTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_rememberMeTime" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.rememberMeTime'); ?></label>
                        <input type="number" id="auth_rememberMeTime" name="rememberMeTime" class="form-control shadow-none" min="3600" value="<?= esc($authSettings['rememberMeTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.rememberMeTime'); ?>">
                        <div class="error_rememberMeTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_activationTime" class="form-label fw-semibold mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.activationTime'); ?></label>
                        <input type="number" id="auth_activationTime" name="activationTime" class="form-control shadow-none" min="3600" value="<?= esc($authSettings['activationTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.activationTime'); ?>">
                        <div class="error_activationTime text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Pulsanti di Controllo -->
                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button type="button" class="btn btn-danger btn-sm btn-delete-auth" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/settings.buttons.restoreData'); ?>
                            </button>
                            
                            <button type="button" class="btn btn-warning btn-sm text-dark btn-refresh-auth" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= lang('backend/settings.buttons.refreshData'); ?>
                            </button>
                            
                            <button type="submit" class="btn btn-success btn-sm text-white btn-save-auth">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/settings.buttons.sendData'); ?>
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>