<div class="card bg-light border mt-3 animate__animated animate__fadeIn">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            
            <div class="col-12 col-md-6 text-center">
                <div class="bg-white p-3 d-inline-block rounded border shadow-sm">
                    <img src="<?= esc($qrCode); ?>" alt="QR Code TOTP" class="img-fluid" style="max-width: 220px;">
                </div>
            </div>

            <div class="col-12 col-md-6">
                <h5 class="fw-bold mb-2"><?= lang('backend/account.titles.totpSetupTitle'); ?></h5>
                <p class="text-muted small mb-3"><?= lang('backend/account.labels.totpSetupDesc'); ?></p>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold mb-1"><?= lang('backend/account.labels.totpSecretKey'); ?></label>
                    <div class="input-group input-group-sm" style="max-width: 350px;">
                        <button class="btn btn-secondary bg-white border" type="button" id="toggle-secret-visibility">
                            <i class="fa-solid fa-eye text-muted" id="toggle-secret-icon"></i>
                        </button>
                        <input type="password" 
                               id="totp-secret-field" 
                               class="form-control bg-white font-monospace text-uppercase" 
                               value="<?= esc($totpSecret); ?>" 
                               readonly>
                    </div>
                </div>

                <form id="confirm-totp-form" novalidate>
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label for="otp-code" class="form-label small fw-bold mb-1"><?= lang('backend/account.labels.enterOtpCode'); ?></label>
                            <input type="text" 
                                   id="otp-code" 
                                   name="otp" 
                                   class="form-control form-control-sm text-center font-monospace" 
                                   maxlength="6" 
                                   placeholder="000000" 
                                   style="width: 140px; letter-spacing: 4px; font-size: 1.1rem;">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-circle-check me-1"></i> <?= lang('backend/account.buttons.verifyAndActivate'); ?>
                            </button>
                        </div>
                        <!-- Nuovo pulsante Annulla -->
                        <div class="col-auto">
                            <button type="button" id="cancel-setup-btn" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-xmark me-1"></i> <?= lang('backend/account.buttons.undoAndClose'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="error_otp text-danger small mt-1">&nbsp;</div>
                </form>
            </div>

        </div>
    </div>
</div>