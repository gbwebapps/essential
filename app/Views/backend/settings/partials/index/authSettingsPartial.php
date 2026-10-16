<div class="card-body">

    <!-- Indicatore della sorgente dati -->
    <div class="ps-2 py-1 app-bg-surface fw-bold small mb-3 border text-center rounded-2">
        <?php if (isset($isFromDatabase) && $isFromDatabase): ?>
            <span class="app-text-accent">
                <i class="fa-solid fa-database me-1"></i> <?= lang('backend/settings.messages.sourceDatabase'); ?>
            </span>
        <?php else: ?>
            <span class="app-text">
                <i class="fa-solid fa-file-code me-1"></i> <?= lang('backend/settings.messages.sourceConfigFile'); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <form id="auth-settings" autocomplete="off" novalidate>
        <div class="row">
            <div class="col-12">
            
                <!-- Sezione 1: Sicurezza e Tentativi di Accesso -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-shield-halved me-2"></i><?= lang('backend/settings.labels.security'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attempts" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attempts'); ?></label>
                        <select id="auth_attempts" name="attempts" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['attempts'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['attempts'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_attempts app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attemptsLimit" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attemptsLimit'); ?></label>
                        <input type="number" id="auth_attemptsLimit" name="attemptsLimit" class="form-control shadow-none" min="1" max="20" value="<?= esc($authSettings['attemptsLimit']); ?>" placeholder="<?= lang('backend/settings.placeholders.attemptsLimit'); ?>">
                        <div class="error_attemptsLimit app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_attemptsInterval" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.attemptsInterval'); ?></label>
                        <input type="number" id="auth_attemptsInterval" name="attemptsInterval" class="form-control shadow-none" min="10" value="<?= esc($authSettings['attemptsInterval']); ?>" placeholder="<?= lang('backend/settings.placeholders.attemptsInterval'); ?>">
                        <div class="error_attemptsInterval app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                </div>

                <!-- Sezione 2: Autenticazione a Due Fattori (2FA) -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-key me-2"></i><?= lang('backend/settings.labels.twoFactor'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactor" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactor'); ?></label>
                        <select id="auth_twoFactor" name="twoFactor" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['twoFactor'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['twoFactor'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_twoFactor app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorTime" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorTime'); ?></label>
                        <input type="number" id="auth_twoFactorTime" name="twoFactorTime" class="form-control shadow-none" min="10" value="<?= esc($authSettings['twoFactorTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorTime'); ?>">
                        <div class="error_twoFactorTime app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorIssuer" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorIssuer'); ?></label>
                        <input type="text" id="auth_twoFactorIssuer" name="twoFactorIssuer" class="form-control shadow-none" value="<?= esc($authSettings['twoFactorIssuer']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorIssuer'); ?>">
                        <div class="error_twoFactorIssuer app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorDigits" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorDigits'); ?></label>
                        <select id="auth_twoFactorDigits" name="twoFactorDigits" class="form-select shadow-none">
                            <option value="6" <?= $authSettings['twoFactorDigits'] == 6 ? 'selected' : ''; ?>>6</option>
                            <option value="8" <?= $authSettings['twoFactorDigits'] == 8 ? 'selected' : ''; ?>>8</option>
                        </select>
                        <div class="error_twoFactorDigits app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorWindow" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorWindow'); ?></label>
                        <input type="number" id="auth_twoFactorWindow" name="twoFactorWindow" class="form-control shadow-none" min="0" max="2" value="<?= esc($authSettings['twoFactorWindow']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorWindow'); ?>">
                        <div class="error_twoFactorWindow app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="auth_twoFactorEmailExpiry" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorEmailExpiry'); ?></label>
                        <input type="number" id="auth_twoFactorEmailExpiry" name="twoFactorEmailExpiry" class="form-control shadow-none" min="10" value="<?= esc($authSettings['twoFactorEmailExpiry']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorEmailExpiry'); ?>">
                        <div class="error_twoFactorEmailExpiry app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="auth_twoFactorEmailFrom" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorEmailFrom'); ?></label>
                        <input type="email" id="auth_twoFactorEmailFrom" name="twoFactorEmailFrom" class="form-control shadow-none" value="<?= esc($authSettings['twoFactorEmailFrom']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorEmailFrom'); ?>">
                        <div class="error_twoFactorEmailFrom app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-shield-halved me-2"></i><?= lang('backend/settings.labels.twoFactorAttemptsSecurity'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorAttempts" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorAttempts'); ?></label>
                        <select id="auth_twoFactorAttempts" name="twoFactorAttempts" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['twoFactorAttempts'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['twoFactorAttempts'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_twoFactorAttempts app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorLimit" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorLimit'); ?></label>
                        <input type="number" id="auth_twoFactorLimit" name="twoFactorLimit" class="form-control shadow-none" min="1" max="10" value="<?= esc($authSettings['twoFactorLimit']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorLimit'); ?>">
                        <div class="error_twoFactorLimit app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_twoFactorAttemptsInterval" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.twoFactorAttemptsInterval'); ?></label>
                        <input type="number" id="auth_twoFactorAttemptsInterval" name="twoFactorAttemptsInterval" class="form-control shadow-none" min="10" max="86400" value="<?= esc($authSettings['twoFactorAttemptsInterval']); ?>" placeholder="<?= lang('backend/settings.placeholders.twoFactorAttemptsInterval'); ?>">
                        <div class="error_twoFactorAttemptsInterval app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Sezione 3: Protezione Reset Password Pubblico -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-unlock-keyhole me-2"></i><?= lang('backend/settings.labels.resetPasswordSecurity'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_resetAttempts" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resetAttempts'); ?></label>
                        <select id="auth_resetAttempts" name="resetAttempts" class="form-select shadow-none">
                            <option value="0" <?= (int) $authSettings['resetAttempts'] === 0 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.disabled'); ?></option>
                            <option value="1" <?= (int) $authSettings['resetAttempts'] === 1 ? 'selected' : ''; ?>><?= lang('backend/settings.labels.enabled'); ?></option>
                        </select>
                        <div class="error_resetAttempts app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_resetAttemptsLimit" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resetAttemptsLimit'); ?></label>
                        <input type="number" id="auth_resetAttemptsLimit" name="resetAttemptsLimit" class="form-control shadow-none" min="1" max="20" value="<?= esc($authSettings['resetAttemptsLimit']); ?>" placeholder="<?= lang('backend/settings.placeholders.resetAttemptsLimit'); ?>">
                        <div class="error_resetAttemptsLimit app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_resetAttemptsInterval" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.resetAttemptsInterval'); ?></label>
                        <input type="number" id="auth_resetAttemptsInterval" name="resetAttemptsInterval" class="form-control shadow-none" min="60" max="86400" value="<?= esc($authSettings['resetAttemptsInterval']); ?>" placeholder="<?= lang('backend/settings.placeholders.resetAttemptsInterval'); ?>">
                        <div class="error_resetAttemptsInterval app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Sezione 4: Sessioni e Persistenza -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="row g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-2">
                            <div class="col-12 text-start">
                                <h5><i class="fa-solid fa-clock me-2"></i><?= lang('backend/settings.labels.sessions'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_sessionTime" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.sessionTime'); ?></label>
                        <input type="number" id="auth_sessionTime" name="sessionTime" class="form-control shadow-none" min="60" value="<?= esc($authSettings['sessionTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.sessionTime'); ?>">
                        <div class="error_sessionTime app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_rememberMeTime" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.rememberMeTime'); ?></label>
                        <input type="number" id="auth_rememberMeTime" name="rememberMeTime" class="form-control shadow-none" min="3600" value="<?= esc($authSettings['rememberMeTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.rememberMeTime'); ?>">
                        <div class="error_rememberMeTime app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="auth_activationTime" class="form-label  mb-1"><i class="fa-solid fa-circle-arrow-down"></i> <?= lang('backend/settings.labels.activationTime'); ?></label>
                        <input type="number" id="auth_activationTime" name="activationTime" class="form-control shadow-none" min="3600" value="<?= esc($authSettings['activationTime']); ?>" placeholder="<?= lang('backend/settings.placeholders.activationTime'); ?>">
                        <div class="error_activationTime app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
                    </div>
                </div>

                <!-- Pulsanti di Controllo -->
                <div class="row">
                    <div class="col-12">
                        <!-- Flexbox: In colonna su mobile, in riga da tablet in poi, centrato orizzontalmente -->
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            
                            <button type="button" class="btn app-btn-danger btn-sm btn-delete-auth" data-message="<?= lang('backend/settings.messages.areYouSureDeleteSettings'); ?>">
                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('backend/settings.buttons.restoreData'); ?>
                            </button>
                            
                            <button type="button" class="btn app-btn-warning btn-sm btn-refresh-auth" data-message="<?= lang('backend/settings.messages.areYouSureRefreshSettings'); ?>">
                                <i class="fa-solid fa-rotate me-1"></i><?= lang('backend/settings.buttons.refreshData'); ?>
                            </button>
                            
                            <button type="submit" class="btn app-btn-success btn-sm btn-save-auth">
                                <i class="fa-solid fa-floppy-disk me-1"></i><?= lang('backend/settings.buttons.sendData'); ?>
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
