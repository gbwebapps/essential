<div class="card-body">

    <div class="row mb-3 g-0 app-bg-surface pt-2 ps-2 pe-2 border rounded-1">
        <div class="col-12 text-start">
            <h5><?= lang('backend/account.titles.twoFactor'); ?></h5>
        </div>
    </div>

	<div class="row mb-3">
		<div class="col-12 col-lg-4">
			<label for="securityCurrentPassword" class="form-label"><?= lang('backend/account.labels.currentPassword'); ?></label>
			<div class="input-group">
				<button class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
					<i class="fa-solid fa-eye app-text-muted"></i>
				</button>
				<input type="password" id="securityCurrentPassword" class="form-control" autocomplete="current-password" data-required-message="<?= esc(lang('backend/account.errors.currentPasswordRequired'), 'attr'); ?>" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
			</div>
			<div class="error_currentPassword app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
		</div>
	</div>

    <!-- Aggiunto gy-3 per gestire lo spazio verticale automatico solo quando vanno a capo -->
    <div class="row gy-3">

        <?php foreach (setting('Backend\Auth')->twoFactorMethods as $method) : ?>
            
            <!-- Rimossa la classe mb-3 fissa, ora gestita dalla row -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 border <?= ($activeMethod === $method) ? 'app-border-accent app-bg-surface' : ''; ?>">
                    <div class="card-body d-flex align-items-start p-3">
                        <div class="form-check w-100 m-0">
                            <input class="form-check-input twofactor-method-trigger" 
                                   type="radio" 
                                   name="twoFactorMethod" 
                                   id="method-<?= esc($method); ?>" 
                                   value="<?= esc($method); ?>" 
                                   data-message="<?= lang('backend/account.messages.areYouSureChangeMethod'); ?>" 
                                   data-requires-setup="<?= ($method === 'totp') ? 'true' : 'false'; ?>"
                                   <?= ($activeMethod === $method) ? 'checked' : ''; ?>>
                            <label class="form-check-label d-block ms-2 cursor-pointer" for="method-<?= esc($method); ?>">
                                <span class="d-block fw-bold mb-1">
                                    <?php if ($method === 'email') : ?>
                                        <i class="fa-solid fa-envelope me-1"></i>
                                    <?php elseif ($method === 'totp') : ?>
                                        <i class="fa-solid fa-mobile-screen me-1"></i>
                                    <?php endif; ?>
                                    <?= lang('backend/account.labels.method' . ucfirst($method) . 'Title'); ?>
                                </span>
                                <small class="app-text-muted d-block"><?= lang('backend/account.labels.method' . ucfirst($method) . 'Desc'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>

    </div>

    <div id="totp-setup-wrapper"></div>

</div>
