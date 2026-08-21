<div class="card bg-light border mt-3 animate__animated animate__fadeIn">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            
            <!-- Colonna QR Code (Centrato ovunque) -->
            <div class="col-12 col-lg-6 text-center">
                <div class="bg-white p-3 d-inline-block rounded border shadow-sm">
                    <img src="<?= esc($qrCode); ?>" alt="QR Code TOTP" class="img-fluid" style="max-width: 220px;">
                </div>
            </div>

            <!-- Colonna Contenuti e Form -->
            <div class="col-12 col-lg-6">
                <h5 class="fw-bold mb-2"><?= lang('backend/account.titles.totpSetupTitle'); ?></h5>
                <p class="text-muted small mb-3"><?= lang('backend/account.labels.totpSetupDesc'); ?></p>

                <form id="confirm-totp-form" novalidate>
                    <div class="row g-3 align-items-end">
                        
                        <!-- Chiave Segreta: Piena larghezza in portrait, affiancata in landscape -->
                        <div class="col-12 col-lg-7">
                            <label class="form-label text-muted small fw-bold mb-1"><?= lang('backend/account.labels.totpSecretKey'); ?></label>
                            <div class="input-group input-group-sm">
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

                        <!-- Input Codice OTP: Piena larghezza in portrait, affiancato in landscape -->
                        <div class="col-12 col-lg-5">
                            <label for="otp-code" class="form-label small fw-bold mb-1"><?= lang('backend/account.labels.enterOtpCode'); ?></label>
                            <input type="text" 
                                   id="otp-code" 
                                   name="otp" 
                                   class="form-control form-control-sm text-center font-monospace w-100" 
                                   maxlength="6" 
                                   placeholder="000000" 
                                   style="letter-spacing: 4px; font-size: 1.1rem;">
                        </div>

                        <!-- Pulsanti di azione: Uno sopra l'altro a piena larghezza in portrait, affiancati sotto in landscape -->
                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-success btn-sm flex-fill">
                                    <i class="fa-solid fa-circle-check me-1"></i> <?= lang('backend/account.buttons.verifyAndActivate'); ?>
                                </button>
                                <button type="button" id="cancel-setup-btn" class="btn btn-danger btn-sm flex-fill">
                                    <i class="fa-solid fa-xmark me-1"></i> <?= lang('backend/account.buttons.undoClose'); ?>
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>