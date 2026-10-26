<div class="card-body">

    <div class="row mb-3 g-0 app-bg-surface security-section-title pt-2 ps-2 pe-2 border rounded-1">
        <div class="col-12 text-start">
            <h5><?= ui_text('backend/account.titles.twoFactor', [], false); ?></h5>
        </div>
    </div>

	<form id="security-password-form" data-unsaved-check>
		<div class="row mb-3">
			<div class="col-12 col-lg-4">
				<label for="securityCurrentPassword" class="form-label"><?= ui_text('backend/account.labels.currentPassword', [], false); ?></label>
				<div class="input-group">
					<button <?= ui_binding('backend/account.buttons.showPassword', 'aria-label', [], true); ?> class="btn app-btn-secondary app-bg-surface-elevated border toggle-password-visibility" type="button" aria-label="<?= esc(lang('backend/account.buttons.showPassword'), 'attr'); ?>" aria-pressed="false">
						<i class="fa-solid fa-eye app-text-muted"></i>
					</button>
					<input <?= ui_binding('backend/account.errors.currentPasswordRequired', 'data-required-message', [], true); ?> <?= ui_binding('backend/account.placeholders.currentPassword', 'placeholder', [], true); ?> type="password" id="securityCurrentPassword" name="currentPassword" class="form-control" autocomplete="current-password" data-required-message="<?= esc(lang('backend/account.errors.currentPasswordRequired'), 'attr'); ?>" placeholder="<?= esc(lang('backend/account.placeholders.currentPassword'), 'attr'); ?>">
				</div>
				<div class="error_currentPassword app-text-danger fw-bold small pt-1" aria-live="polite">&nbsp;</div>
			</div>
		</div>
	</form>

    <!-- Aggiunto gy-3 per gestire lo spazio verticale automatico solo quando vanno a capo -->
    <div class="row gy-3">

        <?php foreach (setting('Backend\Auth')->twoFactorMethods as $method) : ?>
            
            <!-- Rimossa la classe mb-3 fissa, ora gestita dalla row -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 border <?= ($activeMethod === $method) ? 'app-border-accent app-bg-surface' : ''; ?>">
                    <div class="card-body d-flex align-items-start p-3">
                        <div class="form-check w-100 m-0">
                            <input <?= ui_binding('backend/account.messages.areYouSureChangeMethod', 'data-message', [], false); ?> class="form-check-input twofactor-method-trigger" 
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
                                    <?= ui_text('backend/account.labels.method' . ucfirst($method) . 'Title', [], false); ?>
                                </span>
                                <small class="app-text-muted d-block"><?= ui_text('backend/account.labels.method' . ucfirst($method) . 'Desc', [], false); ?></small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>

    </div>

    <div id="totp-setup-wrapper"></div>

</div>
